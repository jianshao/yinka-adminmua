<?php

namespace app\admin\script;

use app\admin\common\ParseUserState;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiUserStateDayModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

//统计每天的日常数据量 日活 新增 充值人数 充值总金额
class AsaChannelAndSourceCommand extends Command
{
    //苹果asa归因
    const UPDATE_TABLE_NAME = 'bi_appstore_daily_day_new';
    const LIMIT = 1000;
    //统计asa的数据推广
    const COMMAND_NAME = "AsaChannelAndSourceCommand";
    const PROMOTE_CHANNEL = 'appStore';

    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME)
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d'))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d', strtotime("+1 days")));
    }

    protected function dealTodayData($start)
    {
        $res = Db::table('bi_user_stats_1day us')
            ->field('us.register_channel, us.source,ios.iad_adgroup_id,ios.iad_campaign_id,ios.iad_keyword_id,ios.iad_adgroup_name,ios.iad_campaign_name,ios.iad_keyword')
            ->join("bi_channel_appstore ios", "us.uid = ios.user_id")
            ->where('date', $start)
            ->where('promote_channel', 0)
            ->where('register_channel', SELF::PROMOTE_CHANNEL)
            ->group('register_channel,source,iad_adgroup_id,iad_campaign_id,iad_keyword_id')
            ->select()
            ->toArray();
        return $res;
    }

    protected function execute(Input $input, Output $output)
    {
        $start_time = $input->getArgument('start_time');
        $end_time = $input->getArgument('end_time');

        //因为间隔1个小时执行 要确保不要遗漏数据在0点过后要执行前一天的数据
        if (date('H') >= "00" and date('H') <= "03") {
            $start_time = date("Y-m-d", strtotime("-1days"));
        }
        $dates = ParseUserStateDataCommmon::getInstance()->getTimeNode($start_time, $end_time); //start_time=2021-07-10 end_time = 2021-07-11  [2021-07-10]
        $redis = RedisCommon::getInstance()->getRedis(['select' => 8]);
        $commandLockName = SELF::COMMAND_NAME . ":Lock";
        //防止定时任务重复调用
        if (!$redis->set($commandLockName, 1, ["nx", "ex" => 3600 * 24])) {
            Log::info(SELF::COMMAND_NAME . ":lock exists");
            return;
        }

        try {
            foreach ($dates as $date) {
                //因为分包源等
                $lists = $this->dealTodayData($date);
                foreach ($lists as $item) {
                    $this->baseExecute($item, $date);
                }
            }
            //更新留存数据
            $this->userKeepData();
        } catch (\Throwable $e) {
            Log::info(SELF::COMMAND_NAME . ':error:' . $e->getMessage());
        } finally {
            $redis->del($commandLockName);
        }

    }

    //执行的基本的数据统计 不包括次留等数据
    //item 是以channel source hw_taskid hw_channel
    public function baseExecute($item, $start_time)
    {
        $end_time = date('Y-m-d H:i:s', strtotime($start_time . "+1days"));
        $condition[] = ['date', '=', $start_time];
        $condition[] = ['promote_channel', '=', 0];
        $condition[] = ['source', '=', $item['source']];
        $condition[] = ['register_channel', '=', $item['register_channel']];
        $condition[] = ['iad_adgroup_id', '=', $item['iad_adgroup_id']];
        $condition[] = ['iad_campaign_id', '=', $item['iad_campaign_id']];
        $condition[] = ['iad_keyword_id', '=', $item['iad_keyword_id']];
        $where = $condition;
        $condition[] = ['register_time', '>=', $start_time . " 00:00:00"];
        $condition[] = ['register_time', '<', $end_time . " 00:00:00"];
        //获取要处理的数据源 今日新注册的
        $database = BiUserStateDayModel::getInstance()->getModel()->alias("d")
            ->join('bi_channel_appstore p', 'p.user_id = d.uid')
            ->where($condition);
        $registeruserRes = ParseUserState::getInstance()->getParseUserData($database);
        //今日所有的用户,
        $database = BiUserStateDayModel::getInstance()->getModel()->alias("d")
            ->join('bi_channel_appstore p', 'p.user_id = d.uid')
            ->where($where);
        $userRes = ParseUserState::getInstance()->getParseUserData($database);
        $data = [];
        $data['date'] = $start_time;
        $data['source'] = $item['source'];

        $data['iad_adgroup_id'] = $item['iad_adgroup_id'];
        $data['iad_campaign_id'] = $item['iad_campaign_id'];
        $data['iad_keyword_id'] = $item['iad_keyword_id'];
        $data['iad_adgroup_name'] = $item['iad_adgroup_name'] ?? '';
        $data['iad_campaign_name'] = $item['iad_campaign_name'] ?? '';
        $data['iad_keyword'] = $item['iad_keyword'] ?? '';

        //新增充值用户人数
        $data['register_user_charge_num'] = count($registeruserRes['charge']);

        //新增充值总额
        $data['register_user_charge_amount'] = $this->getUserChargeByUid($registeruserRes['charge'], $start_time);

        //充值总金额
        $data['charge_money_sum'] = $this->getUserChargeByUid($userRes['active'], $start_time);

        //充值总人数
        $data['charge_people_sum'] = count($userRes['charge']);

        //代充总金额
        $data['agentcharge_amount'] = $this->getUserChargeByUid($userRes['active'], $start_time, 2);

        //代充总人数
        //$insertData['agentcharge_people_num'] = 0 ;

        //直充总金额
        $data['directcharge_money_sum'] = $this->getUserChargeByUid($userRes['active'], $start_time, 1);

        //直充总人数
        //$$insertDatadata['directcharge_people_num'] = 0;

        //今日注册总人数
        $data['register_people_num'] = count($registeruserRes['active']);

        //今日用户注册uids
        $data['today_register_user'] = join(",", $registeruserRes['active']);

        //今日新增充值用户uids
        $data['today_charge_user'] = join(",", $registeruserRes['charge']);

        //日活
        $data['daily_life'] = count($userRes['active']);

        ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul([$data], self::UPDATE_TABLE_NAME, ["date", "source", "register_channel", "iad_adgroup_id", "iad_campaign_id", "iad_keyword_id", "id"]);
    }

    /**
     * 用户留存数据
     */
    public function userKeepData()
    {
        try {
            $page = 1;
            //更新原目标数据的最大日期 往前推30天
            $end_date = Db::name(SELF::UPDATE_TABLE_NAME)->max('date', false);
            $begin_date = date('Y-m-d', strtotime($end_date . "-30days"));
            $res = Db::name(SELF::UPDATE_TABLE_NAME)
                ->where("date", ">=", $begin_date)
                ->where("date", "<=", $end_date)
                ->page($page, SELF::LIMIT)
                ->select()->toArray();

            while (true) {
                if (empty($res)) {
                    break;
                }
                foreach ($res as $item) {
                    $date = $item['date'];
                    if ($date == date('Y-m-d')) {
                        continue; //当前日期的留存数据不需要更新
                    }
                    $primaryid = $item['id'];
                    $update = [];
                    //获取留存数据的基本数据
                    $keepdata = Db::name("bi_user_keep_day")->where("date", "=", $date)
                        ->where("type", "=", 'active')->find();

                    if (empty($item['keep_login_1'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'], $keepdata['keep_2']);
                        $update['keep_login_1'] = count($res);
                    }

                    if (empty($item['keep_login_3'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'], $keepdata['keep_3']);
                        $update['keep_login_3'] = count($res);
                    }

                    if (empty($item['keep_login_7'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'], $keepdata['keep_7']);
                        $update['keep_login_7'] = count($res);
                    }

                    if (empty($item['keep_login_15'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'], $keepdata['keep_15']);
                        $update['keep_login_15'] = count($res);
                    }

                    if (empty($item['keep_login_30'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'], $keepdata['keep_30']);
                        $update['keep_login_30'] = count($res);
                    }

                    if (empty($item['keep_charge_1'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'], $keepdata['keep_2']);
                        $update['keep_charge_1'] = count($res);
                    }

                    if (empty($item['keep_charge_3'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'], $keepdata['keep_3']);
                        $update['keep_charge_3'] = count($res);
                    }

                    if (empty($item['keep_charge_7'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'], $keepdata['keep_7']);
                        $update['keep_charge_7'] = count($res);
                    }

                    if (empty($item['keep_charge_15'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'], $keepdata['keep_15']);
                        $update['keep_charge_15'] = count($res);
                    }

                    if (empty($item['keep_charge_30'])) {
                        $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'], $keepdata['keep_30']);
                        $update['keep_charge_30'] = count($res);
                    }
                    Db::name(SELF::UPDATE_TABLE_NAME)->where("id", $primaryid)->update($update);
                }

                $page++;
                $res = Db::name(SELF::UPDATE_TABLE_NAME)
                    ->where("date", ">=", $begin_date)
                    ->where("date", "<=", $end_date)
                    ->page($page, SELF::LIMIT)->select()->toArray();
            }

        } catch (\Throwable $e) {
            throw $e;
        }

    }

    /**
     * @param $uids
     * @param $date
     * @param int $type
     * @return false|float
     */
    private function getUserChargeByUid($uids, $date, $type = 0)
    {
        $where[] = ["uid", "in", $uids];
        $where[] = ["date", "=", $date];
        if (!empty($type)) {
            $where[] = ["type", "=", $type];
        }
        $sumamount = BiDaysUserChargeModel::getInstance()->getModel()->where($where)->sum("amount");
        return $this->divedFunc($sumamount, 10, 2);
    }

    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }

}

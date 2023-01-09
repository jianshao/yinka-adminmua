<?php

namespace app\admin\script;

use app\admin\common\ParseUserState;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiUserKeepDayModel;
use app\admin\model\BiUserStateDayModel;
use app\admin\model\BiUserStats1DayModel;
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
class OppoChannelAndSourceCommand extends Command
{
    //OPPO归因
    const  UPDATE_TABLE_NAME = 'bi_oppo_daily_day';
    const  LIMIT = 1000;
    //统计Oppo的数据推广 归因
    const  COMMAND_NAME = "OppoChannelAndSourceCommand";
    const  PROMOTE_CHANNEL = 'Oppo';


    protected function configure()
    {
        // 指令配置
        $this->setName('OppoChannelAndSourceCommand')
            ->setDescription('OppoChannelAndSourceCommand')
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d'))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d', strtotime("+1 days")));
    }




    protected function execute(Input $input, Output $output)
    {

        /*
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `date` varchar(20) NOT NULL DEFAULT '' COMMENT '日期',
      `daily_life` int(11) NOT NULL DEFAULT '0' COMMENT '日活',
      `register_people_num` int(11) NOT NULL DEFAULT '0' COMMENT '注册人数',
      `charge_money_sum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '充值总金额',
      `charge_people_sum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '充值总人数',
      `register_user_charge_amount` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '新增充值额度',
      `register_user_charge_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '新增充值人数',
      `keep_charge_1` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存充值用户再次登录1日',
      `keep_charge_3` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存充值用户再次登录3日',
      `keep_charge_7` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存充值用户再次登录7日',
      `keep_charge_15` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存充值用户再次登录15日',
      `keep_charge_30` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存充值用户再次登录30日',
      `keep_login_1` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存新用户再次登录1日',
      `keep_login_7` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存新用户再次登录7日',
      `keep_login_15` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存新用户再次登录15日',
      `keep_login_30` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '留存新用户再次登录30日',
      `today_register_user` text COMMENT '今日注册用户用户列表',
      `source` varchar(30) NOT NULL DEFAULT '' COMMENT '包源',
      `taskid` varchar(30) NOT NULL DEFAULT '' COMMENT '包任务id',
      `pay_amount_up_now` int(11) NOT NULL DEFAULT '0' COMMENT '当日注册的用户到目前的充值总金额',
      `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
      `today_charge_user` text COMMENT '今日注册用户中的充值用户',
       PRIMARY KEY (`id`) USING BTREE,
       UNIQUE KEY `date_chann` (`date`,`source`,`taskid`) USING BTREE,
      KEY `date` (`date`) USING BTREE
*/

        //end_time 应该是当前的时间节点 也就是凌晨过后的日期节点
        //如果支持每天不定时多次调用 可以调整 getTimeNode函数以及keepRecodeinit的时间节点
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
                //因为分包源
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


    /**
     *处理每日数据
     */
    protected function dealTodayData($start)
    {
        return BiUserStats1DayModel::getInstance()->getModel()->alias('u')
            ->field('u.register_channel,u.source,p.factory_type')
            ->join("zb_promote_callback p", "u.uid = p.user_id")
            ->where('u.date', $start)
            ->where("u.register_channel", SELF::PROMOTE_CHANNEL)
            ->where('p.factory_type', SELF::PROMOTE_CHANNEL)
            ->where('u.promote_channel', 0)
            ->group('u.source')
            ->select()
            ->toArray();
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
        $where = $condition;
        $condition[] = ['register_time', '>=', $start_time . " 00:00:00"];
        $condition[] = ['register_time', '<', $end_time . " 00:00:00"];
        //获取要处理的数据源 今日新注册的
        $database = BiUserStateDayModel::getInstance()->getModel()->alias("d")
            ->join('zb_promote_callback p', 'p.user_id = d.uid')
            ->where($condition);
        $registeruserRes = ParseUserState::getInstance()->getParseUserData($database);
        //今日所有的用户,
        $database = BiUserStateDayModel::getInstance()->getModel()->alias("d")
            ->join('zb_promote_callback p', 'p.user_id = d.uid')
            ->where($where);
        $userRes = ParseUserState::getInstance()->getParseUserData($database);
        $data = [];
        $data['date'] = $start_time;
        $data['source'] = $item['source'];
        $data['taskid'] = $item['taskid'];
        //`register_people_num` '注册人数',
        // `charge_money_sum`   '充值总金额',
        // `charge_people_sum`  '充值总人数',
        // `register_user_charge_amount` '新增充值额度',
        // `register_user_charge_num`  '新增充值人数',

        $data['register_user_charge_num'] = count($registeruserRes['charge']); //今日新增充值人数
        $data['register_user_charge_amount'] = $this->getUserChargeByid($registeruserRes['charge'], $start_time);
        $data['register_people_num'] = count($registeruserRes['active']); //注册人数
        $data['charge_money_sum'] = $this->getUserChargeByid($userRes['active'], $start_time); //充值总金额
        $data['charge_people_sum'] = count($userRes['charge']);
        $data['today_register_user'] = join(",",$registeruserRes['active']); //今日注册用户有哪些
        $data['today_charge_user'] = join(",",$registeruserRes['charge']); //今日注册用户中充值用户有哪些
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul([$data], self::UPDATE_TABLE_NAME, ["date,source,taskid"]);
    }


    /**
     * 用户留存数据
     */
    public function userKeepData()
    {
        $page = 1;
        //更新原目标数据的最大日期 往前推30天
        $end_date = Db::name(SELF::UPDATE_TABLE_NAME)->max('date');
        $begin_date = date('Y-m-d',strtotime($end_date."-30days"));
        $res = Db::name(SELF::UPDATE_TABLE_NAME)
            ->where("date", ">=", $begin_date)
            ->where("date", "<=", $end_date)
            ->page($page,SELF::LIMIT)
            ->select()->toArray();

        while (true) {
            if (empty($res)) {
                break;
            }

            foreach ($res as $item) {
                $date = $item['date'];
                if($date == date('Y-m-d')){
                    continue;//当前日期的留存数据不需要更新
                }
                $primaryid = $item['id'];
                $update =[];
                //获取留存数据的基本数据
                $keepdata = BiUserKeepDayModel::getInstance()->getModel()->where("date","=",$date)
                    ->where("type","=",'active')->find();

                if(empty($item['keep_login_1'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'],$keepdata['keep_2']);
                    $update['keep_login_1'] = count($res);
                }

                if(empty($item['keep_login_3'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'],$keepdata['keep_3']);
                    $update['keep_login_3'] = count($res);
                }

                if(empty($item['keep_login_7'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'],$keepdata['keep_7']);
                    $update['keep_login_7'] = count($res);
                }

                if(empty($item['keep_login_15'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'],$keepdata['keep_15']);
                    $update['keep_login_15'] = count($res);
                }

                if(empty($item['keep_login_30'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_register_user'],$keepdata['keep_30']);
                    $update['keep_login_30'] = count($res);
                }

                if(empty($item['keep_charge_1'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'],$keepdata['keep_2']);
                    $update['keep_charge_1'] = count($res);
                }

                if(empty($item['keep_charge_3'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'],$keepdata['keep_3']);
                    $update['keep_charge_3'] = count($res);
                }

                if(empty($item['keep_charge_7'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'],$keepdata['keep_7']);
                    $update['keep_charge_7'] = count($res);
                }

                if(empty($item['keep_charge_15'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'],$keepdata['keep_15']);
                    $update['keep_charge_15'] = count($res);
                }

                if(empty($item['keep_charge_30'])){
                    $res = ParseUserState::getInstance()->strIntersect($item['today_charge_user'],$keepdata['keep_30']);
                    $update['keep_charge_30'] = count($res);
                }
                Db::name(SELF::UPDATE_TABLE_NAME)->where("id", $primaryid)->update($update);
            }

            $page++;
            $res = Db::name(SELF::UPDATE_TABLE_NAME)
                ->where("date", ">=", $begin_date)
                ->where("date", "<=", $end_date)
                ->page($page,SELF::LIMIT)->select()->toArray();
        }

    }


    /**
     * 根据用户来获取充值
     * @param $uids
     * @return false|float
     */
    private function getUserChargeByid($uids, $date)
    {
        $sumamount = BiDaysUserChargeModel::getInstance()->getModel()->where("uid", "in", $uids)->where(['date' => $date])->sum("amount");
        return $this->divedFunc($sumamount, 10, 2);
    }

    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }

}

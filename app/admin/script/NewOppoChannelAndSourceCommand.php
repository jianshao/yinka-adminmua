<?php

namespace app\admin\script;

use app\admin\common\ParseUserState;
use app\admin\model\BiChannelOppoModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiOppoDailyDayModel;
use app\admin\model\BiUserKeepDayModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\model\PromoteCallbackModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;


//统计每天的日常数据量 日活 新增 充值人数 充值总金额
class NewOppoChannelAndSourceCommand extends Command
{
    //可以自定义修改配置 保证数据库建立好对应的字段即可
    protected $config =
        [
            //新增用户留存  对应数据库字段 keep_login_xx
            'keepLoginNewUser' =>
                [
                    2 => "keep_login_1",
                    7 => 'keep_login_7',
                    15 => 'keep_login_15',
                    30 => 'keep_login_30',
                    60 => 'keep_login_60'
                ],


            //新增用户付费总额 对应数据库字段 fee_register_xx
            'sumLoginNewChargeUser' => [
                7 => 'fee_register_7',
                30 => 'fee_register_30',
                60 => 'fee_register_60',
                90 => 'fee_register_90',
            ]
        ];


    const  UPDATE_TABLE_NAME = 'bi_oppo_daily_day';
    const  UPDATE_LIMIT = 1000;
    const COMMAND_NAME = "NewOppoChannelAndSourceCommand";


    protected function configure()
    {
        // 指令配置
        $this->setName('NewOppoChannelAndSourceCommand')
            ->setDescription('NewOppoChannelAndSourceCommand')
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d'))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d', strtotime("+1 days")))
            ->addArgument('history', Argument::OPTIONAL, "history", '');
    }

    /**
     *处理每日数据
     */
    protected function dealTodayData($start)
    {
        $res = BiUserStats1DayModel::getInstance()->getModel()->alias('us')
            ->field('us.register_channel,us.source,p.taskid')
            ->join("bi_channel_oppo p", "us.uid = p.user_id")
            ->where('us.date', $start)
            ->where('us.promote_channel', 0)
            ->group('us.register_channel,us.source,p.taskid')
            ->select()
            ->toArray();
        return $res;
    }


    protected function execute(Input $input, Output $output)
    {
        //日期	日活 	新增	新增充值人数 	新增用户次留 	七留 	十五留 	三十留	六十留	次留（新充值用户）	三留	七留	十五留	三十留	六十留
        //日期	日活	新增	新增充值人	新增充值金额	新增充值率	新增ARPU(当日新增充值金额/当日新增)	   新增ARPPU(当日新增充值金额/新增充值人数)
        //总充值人数	总充值金额
        //代冲(额)	代冲(人)	vip	充值率	ARPU(总充值金额/日活)	ARPPU(总充值金额/总充值人数)	七日付费金额	三十日付费金额
        //六十日付费  90日付费	120日付费	150日
        //end_time 应该是当前的时间节点 也就是凌晨过后的日期节点
        //如果支持每天不定时多次调用 可以调整 getTimeNode函数以及keepRecodeinit的时间节点


        $start_time = $input->getArgument('start_time');
        $end_time = $input->getArgument('end_time');
        $history = $input->getArgument('history');
        //因为间隔1个小时执行 要确保不要遗漏数据在0点过后要执行前一天的数据
        $currenthours = date('H');
        if ($currenthours >= "00" and $currenthours <= "02") {
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
                //基础数据的更新
                $this->baseOppoData($date);
            }


            foreach ($dates as $date) {
                $lists = $this->dealTodayData($date);
                foreach ($lists as $item) {
                    $this->baseExecute($item, $date, date('Y-m-d', strtotime("+1days", strtotime($date))));
                }
            }

            $this->keepRecodeinit($history);

        } catch (\Throwable $e) {
            //dump(SELF::COMMAND_NAME . ":exception" . $e->getMessage() . "getLine:" . $e->getLine() . $e->getFile());
            Log::error(SELF::COMMAND_NAME . ":exception" . $e->getMessage() . "getLine:" . $e->getLine());
        } finally {
            $redis->del($commandLockName);
        }

    }


    //执行的基本的数据统计 不包括次留等数据
    //item 是以channel source hw_taskid hw_channel
    public function baseExecute($item, $start_time = '', $end_time = '')
    {
        $condition = [];
        $condition[] = ['us.date', '=', $start_time];
        $condition[] = ['us.promote_channel', '=', 0];
        $condition[] = ['us.source', '=', $item['source']];
        $condition[] = ['us.register_channel', '=', $item['register_channel']];
        $condition[] = ['p.taskid', '=', $item['taskid']];
        $currentTotalRes = ParseUserStateByUniqkey::getInstance()->parseOppoData($condition, $start_time . "-oppall"); //所有的数据
        $condition[] = ['us.register_time', '>=', $start_time . " 00:00:00"];
        $condition[] = ['us.register_time', '<', $end_time . " 00:00:00"];
        $currentRegisterRes = ParseUserStateByUniqkey::getInstance()->parseOppoData($condition, $start_time . "-oppregister");//新增的数据
        $data = [];
        $data['date'] = $start_time;
        $data['source'] = $item['source'];
        $data['register_channel'] = $item['register_channel'];
        $data['taskid'] = $item['taskid'];
        $data['register_user_charge_num'] = 0;
        $regchargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($currentRegisterRes, 'charge');
        $regagentchargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($currentRegisterRes, 'agentcharge');
        $data['register_user_charge_num'] = count(array_unique(array_merge($regchargeUser, $regagentchargeUser)));//新增充值用户个数
        $data['register_user_charge_amount'] = 0;
        $regchargeamount = ParseUserStateByUniqkey::getInstance()->getChargeSum($currentRegisterRes, 'charge');
        $regagentchargeamount = ParseUserStateByUniqkey::getInstance()->getAgentChargeSum($currentRegisterRes, 'agentcharge');
        $data['register_user_charge_amount'] = $regchargeamount + $regagentchargeamount; //新增充值总额度
        $data['charge_money_sum'] = 0;
        $chargeamount = ParseUserStateByUniqkey::getInstance()->getChargeSum($currentTotalRes, 'charge');
        $agentchargeamount = ParseUserStateByUniqkey::getInstance()->getAgentChargeSum($currentTotalRes, 'agentcharge');
        $data['charge_money_sum'] = $chargeamount + $agentchargeamount; //充值总金额
        $data['charge_people_sum'] = 0;
        $chargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($currentTotalRes, 'charge');
        $agentchargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($currentTotalRes, 'agentcharge');
        $data['charge_people_sum'] = count(array_unique(array_merge($chargeUser, $agentchargeUser))); //充值总人数
        $data['agentcharge_amount'] = ParseUserStateByUniqkey::getInstance()->getAgentChargeSum($currentTotalRes, 'agentcharge'); //代充总金额
        $data['agentcharge_people_num'] = ParseUserStateByUniqkey::getInstance()->getChargeCount($currentTotalRes, 'agentcharge'); //代充总人数
        $data['directcharge_money_sum'] = ParseUserStateByUniqkey::getInstance()->getChargeSum($currentTotalRes, 'charge'); //直冲总金额
        $data['vip_money_sum'] = ParseUserStateByUniqkey::getInstance()->getChargeVipSum($currentTotalRes, 'charge'); //vip总金额
        $data['directcharge_people_num'] = ParseUserStateByUniqkey::getInstance()->getChargeCount($currentTotalRes, 'charge'); //直充总人数
        $today_register_user = ParseUserStateByUniqkey::getInstance()->getArrayKeyValue($currentRegisterRes, 'active_users');
        $data['register_people_num'] = count($today_register_user); //今日注册总人数
        $data['today_register_user'] = implode(",", $today_register_user); //注册用户id
        $data['daily_life'] = count(ParseUserStateByUniqkey::getInstance()->getArrayKeyValue($currentTotalRes, 'active_users')); //日活
        $data['recharge_rate'] = $this->divedFunc($data['daily_life'], $data['register_user_charge_num']); //充值人数/日活
        $data['arpu'] = $this->divedFunc($data['register_user_charge_amount'], $data['register_people_num']);
        //当日新增充值金额/新增充值人数
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiOppoDailyDayModel::getInstance()->getModel(), [$data], ["date", "source", "register_channel", "taskid","id"]);
    }

    public function keepRecodeinit($history = 0)
    {
        $page = 1;
        while (true) {
            $historyRes = BiOppoDailyDayModel::getInstance()->getModel()->page($page, self::UPDATE_LIMIT)->select()->toArray();
            if (empty($historyRes)) {
                break;
            }
            foreach ($historyRes as $item) {
                $item_date = $item['date'];
                //keepLoginNewUser  keepLoginNewChargeUser  sumLoginNewChargeUser
                //新增用户留存  对应数据库字段 keep_login_xx
                $updates = [];
                $today_register_user = $item['today_register_user'];
                if (empty($today_register_user)) {
                    continue;
                }

                $today_register_user_arr = explode(",", $today_register_user);

                //需要更新所有的节点
                //register:注册 active:日活 charge:充值 charge_add:新增充值
                $keepdata = BiUserKeepDayModel::getInstance()->where("date", $item_date)->where("type", "active")->find();
                foreach ($this->config['keepLoginNewUser'] as $keepLoginKey => $keeploginItem) {
                    //如果时间节点已到期 AND 历史数据不需要强更
                    if (strtotime($item_date . "+{$keepLoginKey} days +1days") < time() && $history == '') {
                        continue;
                    }
                    $res = ParseUserState::getInstance()->strIntersect($today_register_user, $keepdata['keep_' . $keepLoginKey]);
                    $updates[$keeploginItem] = count($res);

                }

                //新增用户的付费总额
                foreach ($this->config['sumLoginNewChargeUser'] as $sumchargeKey => $sumchargeItem) {
                    $start_date = $item_date;
                    //如果时间节点已到期 AND 历史数据不需要强更
                    if (strtotime($item_date . "+{$sumchargeKey} days +1days") < time() && $history == '') {
                        continue;
                    }
                    $end_date = date('Y-m-d', strtotime($item_date . "+{$sumchargeKey}days"));
                    $money = $this->getChargeMoney($start_date, $end_date, $today_register_user_arr);
                    $updates[$sumchargeItem] = $money;
                }

                $pay_amount_up_now = BiDaysUserChargeModel::getInstance()->getModel()->where("uid", "in", $today_register_user_arr)->sum("amount");
                $updates['pay_amount_up_now'] = $this->divedFunc($pay_amount_up_now, 10);

                if ($updates) {
                    BiOppoDailyDayModel::getInstance()->getModel()->where("id", $item['id'])->update($updates);
                }
            }
            $page++;
        }
    }

    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }

    //获取充值额度
    public function getChargeMoney($start, $end, $uids)
    {
        $amount = BiDaysUserChargeModel::getInstance()
            ->where("date", ">=", $start)
            ->where("date", "<", $end)
            ->where("uid", "in", $uids)
            ->sum('amount');

        return $this->divedFunc($amount, 10);
    }


    public function baseOppoData($param)
    {
        /*
         * select  c.user_id,c.oaid,r.aid from zb_promote_callback c  Left join zb_promote_report r on r.oaid = c.oaid where c.factory_type = "Oppo"  and c.str_date = '20220801' and c.event_type = 2
         * */
        $res = PromoteCallbackModel::getInstance()->getModel()->alias("c")->LEFTJOIN("zb_promote_report r", "r.oaid = c.oaid")
            ->field("c.user_id,r.aid as taskid,DATE_FORMAT(c.str_date,'%Y-%m-%d') as date")
            ->where("c.factory_type", "Oppo")
            ->where("c.str_date", date("Ymd", strtotime($param)))
            ->where("c.event_type", 2)
            ->select()->toArray();

        if ($res) {
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiChannelOppoModel::getInstance()->getModel(), $res, ['user_id', 'id']);
        }
    }


}

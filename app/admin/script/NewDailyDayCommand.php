<?php

namespace app\admin\script;

use app\admin\model\BiNewDailyDayModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use function GuzzleHttp\Psr7\str;


//统计每天的日常数据量 日活 新增 充值人数 充值总金额
class NewDailyDayCommand extends Command
{
    //可以自定义修改配置 保证数据库建立好对应的字段即可
    protected $config =
        [
            //新增用户留存  对应数据库字段 keep_login_xx
            'keepLoginNewUser' =>
                [
                    2 => "keep_login_1",
                    3 => "keep_login_3",
                    7 => 'keep_login_7',
                    15 => 'keep_login_15',
                    30 => 'keep_login_30',
                    //60 => 'keep_login_60'
                ],

            //新增充值用户留存  对应数据库字段 keep_charge_xx
            'keepLoginNewChargeUser' => [
                2 => "keep_charge_1",
                3 => 'keep_charge_3',
                7 => 'keep_charge_7',
                15 => 'keep_charge_15',
                30 => 'keep_charge_30',
                //60 => 'keep_charge_60',
            ],

            //新增用户付费总额 对应数据库字段 fee_register_xx
            'sumLoginNewChargeUser' => [
                3 => 'fee_register_3',
                7 => 'fee_register_7',
                15 => 'fee_register_15',
                30 => 'fee_register_30',
                //60 => 'fee_register_60',
                //90 => 'fee_register_90',
                //120 => 'fee_register_120',
                //150 => 'fee_register_150',
            ]
        ];


    const  UPDATE_TABLE_NAME = 'bi_new_daily_day';
    const  UPDATE_LIMIT = 100;
    const  MAXDAY = 35; //最大计算为150天的


    protected function configure()
    {
        // 指令配置
        $this->setName('NewDailyDayCommand')
            ->setDescription('NewDailyDayCommand')
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d', strtotime("-1 days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d'));
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
        $dates = ParseUserStateDataCommmon::getInstance()->getTimeNode($start_time, $end_time); //start_time=2021-07-10 end_time = 2021-07-11  [2021-07-10]
        try {
            foreach ($dates as $date) {
                 $this->baseExecute($date, date('Y-m-d', strtotime("+1days", strtotime($date))));
            }
            $this->keepRecodeLeaseinit();
        } catch (\Throwable $e) {
            Log::error("NewDailyDayCommand:exception:" . $e->getMessage() . $e->getLine());
        }

    }


    //执行的基本的数据统计 不包括次留等数据
    //$start_time = '2021-10-01';
    public function baseExecute($start_time = '')
    {
        $condition = [];
        $condition[] = ['date', '=', $start_time];
        //$condition[]=['promote_channel','=',0];
        $currentTotalRes = ParseUserStateByUniqkey::getInstance()->parseData($condition, $start_time . "-all"); //所有的数据
        $condition[] = ['register_time', '>=', $start_time . " 00:00:00"];
        $condition[] = ['register_time', '<', date('Y-m-d', strtotime("+1 days", strtotime($start_time))) . " 00:00:00"];
        $currentRegisterRes = ParseUserStateByUniqkey::getInstance()->parseData($condition, $start_time . "-register");//新增的数据
        $data = [];
        $data['date'] = $start_time;
        $data['register_user_charge_num'] = 0;
        $regchargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($currentRegisterRes, 'charge');
        $regagentchargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($currentRegisterRes, 'agentcharge');
        $data['register_user_charge_num'] = count(array_unique(array_merge($regchargeUser, $regagentchargeUser)));//新增充值用户个数
        //$data['register_user_charge_num'] += ParseUserStateByUniqkey::getInstance()->getChargeSum($currentRegisterRes, 'charge');
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
        $data['svip_money_sum'] = ParseUserStateByUniqkey::getInstance()->getChargeSvipSum($currentTotalRes, 'charge'); //vip总金额
        $data['directcharge_people_num'] = ParseUserStateByUniqkey::getInstance()->getChargeCount($currentTotalRes, 'charge'); //直充总人数
        $today_register_user = ParseUserStateByUniqkey::getInstance()->getArrayKeyValue($currentRegisterRes, 'active_users');
        $data['register_people_num'] = count($today_register_user); //今日注册总人数
        $data['today_register_user'] = implode(",", $today_register_user); //注册用户id
        $data['daily_life'] = count(ParseUserStateByUniqkey::getInstance()->getArrayKeyValue($currentTotalRes, 'active_users')); //日活
        $data['recharge_rate'] = ParseUserStateDataCommmon::getInstance()->divedFunc($data['daily_life'], $data['register_user_charge_num']); //充值人数/日活
        $data['arpu'] =  ParseUserStateDataCommmon::getInstance()->divedFunc($data['register_user_charge_amount'], $data['register_people_num']);
        //当日新增充值金额/新增充值人数
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiNewDailyDayModel::getInstance()->getModel(),[$data], ["id","date"]);
    }


    //初始化所有的数据
    public function keepRecodeinit()
    {
        $page = 1;

        $historyRes = BiNewDailyDayModel::getInstance()->getModel()
            ->where('date', '<=', date('Y-m-d'))
            ->where('date', '>=', date('Y-m-d', strtotime("-" . SELF::MAXDAY . "days")))
            ->page($page, self::UPDATE_LIMIT)
            ->select();

        while (!$historyRes->isEmpty()) {
            foreach ($historyRes as $item) {
                $item_date = $item['date'];
                dump("runing....." . $item_date);
                $updates = [];
                //===================新增用户留存问题============================
                $bs_action = false;
                $active_user_count = 0;
                foreach (array_keys($this->config['keepLoginNewUser']) as $keepLoginKey) {
                    if ($bs_action == true) {
                        $updates[$this->config['keepLoginNewUser'][$keepLoginKey]] = $active_user_count;
                        continue;
                    }
                    $condition = [];
                    $closeDate = date('Y-m-d', strtotime("+$keepLoginKey days -1days", strtotime($item_date)));
                    $condition[] = ['date', '=', $closeDate];
                    $condition[] = ['uid', 'in', $item['today_register_user']];
                    $parseRegisterUserRes = ParseUserStateByUniqkey::getInstance()->parseData($condition, $item_date . "-login");
                    //新增用户留存的数量
                    $active_user_count = count(ParseUserStateByUniqkey::getInstance()->getArrayKeyValue($parseRegisterUserRes, 'active_users'));
                    $updates[$this->config['keepLoginNewUser'][$keepLoginKey]] = $active_user_count;
                    if ($closeDate > date('Y-m-d')) {
                        $bs_action = true;
                    }
                }


                //=============新增充值用户的再次登录的留存========================
                $where = [];
                $where[] = ['date', '=', $item['date']];
                $where[] = ['register_time', '>=', $item['date'] . " 00:00:00"];
                $where[] = ['register_time', '<', $item['date'] . " 23:59:59"];
                $parseRegisterUserChargeRes = ParseUserStateByUniqkey::getInstance()->parseData($where, $item_date . "-charge");
                $item_agentchargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($parseRegisterUserChargeRes, 'agentcharge');
                $item_chargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($parseRegisterUserChargeRes, 'charge');
                //获取新增用户中充值的用户
                $item_charge_user_arr = array_unique(array_merge($item_chargeUser, $item_agentchargeUser));
                $bs_action = false;
                foreach (array_keys($this->config['keepLoginNewChargeUser']) as $keepLoginchargeKey) {
                    if ($bs_action == true) {
                        $updates[$this->config['keepLoginNewChargeUser'][$keepLoginchargeKey]] = $active_user_count;
                        continue;
                    }
                    $condition = [];
                    $closeDate = date('Y-m-d', strtotime("+$keepLoginchargeKey days -1days", strtotime($item_date)));
                    $condition[] = ['date', '=', $closeDate];
                    $condition[] = ['uid', 'in', implode(",", $item_charge_user_arr)];
                    $parseRegisterUserRes = ParseUserStateByUniqkey::getInstance()->parseData($condition, $item_date . "-login");
                    $active_user_count = count(ParseUserStateByUniqkey::getInstance()->getArrayKeyValue($parseRegisterUserRes, 'active_users'));
                    $updates[$this->config['keepLoginNewChargeUser'][$keepLoginchargeKey]] = $active_user_count;
                    if ($closeDate > date('Y-m-d')) {
                        $bs_action = true;
                    }
                }


                //=====================用户累计充值=================================
                $bs_action = false;
                $amount = 0;
                foreach (array_keys($this->config['sumLoginNewChargeUser']) as $sumkey) {
                    if ($bs_action == true) {
                        $updates[$this->config['sumLoginNewChargeUser'][$sumkey]] = $amount;
                        continue;
                    }
                    $condition = [];
                    $closeDate = date('Y-m-d', strtotime("+$sumkey days -1days", strtotime($item_date)));
                    $condition[] = ['date', '<=', $closeDate];
                    $condition[] = ['date', '>=', $item['date']];
                    $condition[] = ['uid', 'in', $item['today_register_user']];
                    $parseRegisterUserRes = ParseUserStateByUniqkey::getInstance()->parseData($condition, $item_date . "-newregister");
                    $charge_amount = ParseUserStateByUniqkey::getInstance()->getChargeSum($parseRegisterUserRes, 'charge');
                    $agent_charge_amount = ParseUserStateByUniqkey::getInstance()->getAgentChargeSum($parseRegisterUserRes, 'agentcharge');
                    $amount = $charge_amount + $agent_charge_amount;
                    if ($amount > 0) {
                        $updates[$this->config['sumLoginNewChargeUser'][$sumkey]] = $amount;
                    }
                    if ($closeDate > date('Y-m-d')) {
                        $bs_action = true;
                    }
                }


                //充值总今额 因为不知道数据起点 所以现在先不统计
                /*    if (in_array($diff_day, array_keys($this->config['sumLoginNewChargeUser']))) {
                        $pay_amount_where=[];
                        $pay_amount_where[]= ['date','>=',$item['date']];
                        $pay_amount_where[]= ['date','<',$end_time];
                        $parseRegisterUserRes = ParseUserStateByUniqkey::getInstance()->parseData($pay_amount_where, $item_date . "-payment");
                        $charge_payment_up_now_amount = ParseUserStateByUniqkey::getInstance()->getChargeSum($parseRegisterUserRes, 'charge');
                        $agentcharge_payment_up_now_amount = ParseUserStateByUniqkey::getInstance()->getAgentChargeSum($parseRegisterUserRes, 'agentcharge');
                        $pay_amount_up_now = $charge_payment_up_now_amount + $agentcharge_payment_up_now_amount;
                        $updates['pay_amount_up_now'] = $pay_amount_up_now;
                    }*/


                if ($updates) {
                    BiNewDailyDayModel::getInstance()->getModel()->where("id", $item['id'])->update($updates);
                }

            }
            $page++;
            $historyRes = BiNewDailyDayModel::getInstance()->getModel()
                ->where('date', '<=', date('Y-m-d'))
                ->where('date', '>=', date('Y-m-d', strtotime("-" . SELF::MAXDAY . "days")))
                ->page($page, self::UPDATE_LIMIT)
                ->select();
        }
    }




    //最优执行 在历史的数据中有些字段的数据已经固定 所以不需要再次修改
    public function keepRecodeLeaseinit()
    {
        $page = 1;
        $historyRes = BiNewDailyDayModel::getInstance()->getModel()
            ->where('date', '<=', date('Y-m-d'))
            ->where('date', '>=', date('Y-m-d', strtotime("-" . SELF::MAXDAY . "days")))
            ->page($page, self::UPDATE_LIMIT)
            ->select();

        while (!$historyRes->isEmpty()) {
            foreach ($historyRes as $item) {
                $item_date = $item['date'];
                dump("runing....." . $item_date);
                $updates = [];
                //===================新增用户留存问题============================
                foreach (array_keys($this->config['keepLoginNewUser']) as $keepLoginKey) {
                    $closeDate = date('Y-m-d', strtotime("+$keepLoginKey days -1days", strtotime($item_date)));
                    //因为值已经固定所以不需要计算的数值
                    if($item[$this->config['keepLoginNewUser'][$keepLoginKey]] > 0 && floor((strtotime(date('Y-m-d'))-strtotime($closeDate))/86400) > 1 ){
                        continue;
                    }
                    $condition = [];
                    $condition[] = ['date', '=', $closeDate];
                    $condition[] = ['uid', 'in', $item['today_register_user']];
                    $parseRegisterUserRes = ParseUserStateByUniqkey::getInstance()->parseData($condition, $item_date . "-login");
                    //新增用户留存的数量
                    $active_user_count = count(ParseUserStateByUniqkey::getInstance()->getArrayKeyValue($parseRegisterUserRes, 'active_users'));
                    $updates[$this->config['keepLoginNewUser'][$keepLoginKey]] = $active_user_count;
                }


                //=============新增充值用户的再次登录的留存========================
                $where = [];
                $where[] = ['date', '=', $item['date']];
                $where[] = ['register_time', '>=', $item['date'] . " 00:00:00"];
                $where[] = ['register_time', '<', $item['date'] . " 23:59:59"];
                $parseRegisterUserChargeRes = ParseUserStateByUniqkey::getInstance()->parseData($where, $item_date . "-charge");
                $item_agentchargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($parseRegisterUserChargeRes, 'agentcharge');
                $item_chargeUser = ParseUserStateByUniqkey::getInstance()->getChargeUsers($parseRegisterUserChargeRes, 'charge');
                //获取新增用户中充值的用户
                $item_charge_user_arr = array_unique(array_merge($item_chargeUser, $item_agentchargeUser));
                foreach (array_keys($this->config['keepLoginNewChargeUser']) as $keepLoginchargeKey) {
                    $condition = [];
                    $closeDate = date('Y-m-d', strtotime("+$keepLoginchargeKey days -1days", strtotime($item_date)));
                    //因为值已经固定所以不需要计算的数值
                    if($item[$this->config['keepLoginNewChargeUser'][$keepLoginchargeKey]] > 0 && floor((strtotime(date('Y-m-d'))-strtotime($closeDate))/86400) > 1 ){
                        continue;
                    }
                    $condition[] = ['date', '=', $closeDate];
                    $condition[] = ['uid', 'in', implode(",", $item_charge_user_arr)];
                    $parseRegisterUserRes = ParseUserStateByUniqkey::getInstance()->parseData($condition, $item_date . "-login");
                    $active_user_count = count(ParseUserStateByUniqkey::getInstance()->getArrayKeyValue($parseRegisterUserRes, 'active_users'));
                    $updates[$this->config['keepLoginNewChargeUser'][$keepLoginchargeKey]] = $active_user_count;
                }


                //=====================用户累计充值=================================
                foreach (array_keys($this->config['sumLoginNewChargeUser']) as $sumkey) {
                    $condition = [];
                    $closeDate = date('Y-m-d', strtotime("+$sumkey days -1days", strtotime($item_date)));
                    //因为值已经固定所以不需要计算的数值
                    if($item[$this->config['sumLoginNewChargeUser'][$sumkey]] > 0 && floor((strtotime(date('Y-m-d'))-strtotime($closeDate))/86400) > 1 ){
                        continue;
                    }
                    $condition[] = ['date', '<=', $closeDate];
                    $condition[] = ['date', '>=', $item['date']];
                    $condition[] = ['uid', 'in', $item['today_register_user']];
                    $parseRegisterUserRes = ParseUserStateByUniqkey::getInstance()->parseData($condition, $item_date . "-newregister");
                    $charge_amount = ParseUserStateByUniqkey::getInstance()->getChargeSum($parseRegisterUserRes, 'charge');
                    $agent_charge_amount = ParseUserStateByUniqkey::getInstance()->getAgentChargeSum($parseRegisterUserRes, 'agentcharge');
                    $amount = $charge_amount + $agent_charge_amount;
                    if ($amount > 0) {
                        $updates[$this->config['sumLoginNewChargeUser'][$sumkey]] = $amount;
                    }
                }


                if ($updates) {
                    BiNewDailyDayModel::getInstance()->getModel()->where("id", $item['id'])->update($updates);
                }

            }
            $page++;
            $historyRes = BiNewDailyDayModel::getInstance()->getModel()
                ->where('date', '<=', date('Y-m-d'))
                ->where('date', '>=', date('Y-m-d', strtotime("-" . SELF::MAXDAY . "days")))
                ->page($page, self::UPDATE_LIMIT)
                ->select();
        }
    }



}

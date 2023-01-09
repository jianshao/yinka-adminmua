<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\BiAsaUserModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiDaysUserChargeNewModel;
use app\admin\model\BiNewDailyDayModel;
use app\admin\model\BiRegisterChannelModel;
use app\admin\model\BiRegisterUserProvinceModel;
use app\admin\model\BiRoomEveryroomConsume;
use app\admin\model\BiUserKeepDayModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\MemberModel;
use app\admin\script\analysis\UserBehavior;
use app\common\ParseUserStateDataCommmon;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class ChartUserController extends AdminBaseController
{

    public function show()
    {
        //获取15日内充值
        $start_date_before15 = date("Y-m-d", strtotime("-15days"));
        $start_date_before7 = date("Y-m-d", strtotime("-7days"));
        $res = BiNewDailyDayModel::getInstance()->getModel()->field("daily_life,register_people_num,charge_money_sum,charge_people_sum,date")
            ->where("date", ">=", $start_date_before15)
            ->select()->toarray();
        $format_res = [];

        foreach ($res as $key => $item) {
            $format_res[$key] = $item;
            $format_res[$key]['month_day'] = date('m-d', strtotime($item['date']));
            $format_res[$key]['charge_sum'] = $item['charge_money_sum'] / 10000;
        }
        //一周的日活
        $weekRes = array_slice($format_res, -7);

        $dateList = array_column($format_res, "month_day");
        $chargeList = array_column($format_res, "charge_sum");
        $deilyList = array_column($format_res, "daily_life");
        $registerList = array_column($format_res, "register_people_num");
        $chargeUsersList = array_column($format_res, "charge_people_sum");

        //dimensions: ['product', '1101', '1102', '1103','1104','1105','1106','1107'],
        //{ product: '注册量', 1101: 43.3, 1102: 85.8, 1103: 93.7,1104: 93.7 ,1105: 93.7 ,1106: 93.7 ,1107: 93.7  },
        $weekList = ["product"];
        $week_register_user = ['product' => '注册量']; //一周的注册用户
        $week_daily_user = ['product' => '日活量']; //一周的日活用户
        $week_charge_user = ['product' => '充值量']; //一周的充值用户
        foreach ($weekRes as $week_key => $week_item) {
            $weekList[] = $week_item['month_day'];
            $week_register_user[$week_item['month_day']] = $week_item['register_people_num'];
            $week_daily_user[$week_item['month_day']] = $week_item['daily_life'];
            $week_charge_user[$week_item['month_day']] = $week_item['charge_people_sum'];
        }
        $week_source = [$week_daily_user, $week_register_user, $week_charge_user];

        //开始==================用户留存=====================================

        $field = "";
        $field .= "DATE_FORMAT(date,'%m-%d') days,";
        $field .= "(keep_login_1/register_people_num)  as avg_1,";
        $field .= "(keep_login_3/register_people_num)  as avg_3,";
        $field .= "(keep_login_7/register_people_num)  as avg_7,";
        $field .= "(keep_login_15/register_people_num) as avg_15,";
        $field .= "(keep_login_30/register_people_num) as avg_30";

        $number = strtotime("-1months");
        //$timestamp = mktime(0, 0, 0, date('m', $number), 1, date('Y', $number));
        $keep_res = BiNewDailyDayModel::getInstance()->getModel()->field($field)
            ->where('date', ">=", date('Y-m-d', $number))
            ->select()
            ->toArray();

        $keepMonthsList = array_column($keep_res, "days");
        foreach ($keepMonthsList as $key_month => $item_month) {
            $keepMonthsList[$key_month] = $item_month . "日";
        }

        $keep1VagList = array_column($keep_res, "avg_1");
        $keep3VagList = array_column($keep_res, "avg_3");
        $keep7VagList = array_column($keep_res, "avg_7");
        $keep15VagList = array_column($keep_res, "avg_15");
        $keep30VagList = array_column($keep_res, "avg_30");
        //结束==================用户留存=====================================

        //开始==================渠道的充值情况=====================================

        //注册渠道

        /*      select c.uid,c.amount,u.register_channel,sum(amount)/10 from  bi_days_user_charge
        c left join bi_user_stats_1day u on u.uid = c.uid and u.date=c.date where c.date > '2021-11-15'  and u.promote_channel=0 GROUP BY u.register_channel

        select sum(amount)/10  from  bi_days_user_charge
        c left join bi_user_stats_1day u on u.uid = c.uid and u.date=c.date where c.date = '2021-11-15'  and u.promote_channel>80000

        select sum(amount)/10  from  bi_days_user_charge
        c left join bi_user_stats_1day u on u.uid = c.uid and u.date=c.date where c.date > '2021-11-15'  and u.promote_channel>0 and  u.promote_channel < 80001

         */

        $userchargeModel = BiDaysUserChargeModel::getInstance()->getModel();

        $channel_charge_res = $userchargeModel->field("uid,amount,register_channel,sum(amount)/10 amounts")
            ->where("promote_channel", "=", 0)
            ->group("register_channel")
            ->where('date', ">=", $start_date_before15)
            ->order("amounts desc")
            ->select()->toArray();
        $before_number = 7;

        $channel_other = array_slice($channel_charge_res, $before_number);
        $amount_other = 0;

        if ($channel_other) {
            $amount_other = array_sum(array_column($channel_other, "amounts"));
        }

        $amount_koc = $userchargeModel->field("uid,amount,register_channel,sum(amount)/10 amounts")
            ->where("promote_channel", ">", 800000)
            ->where('date', ">=", $start_date_before15)
            ->sum("amount");

        $amount_koc = ceil($amount_koc / 10);

        $amount_1v1 = $userchargeModel->field("uid,amount,register_channel,sum(amount)/10 amounts")
            ->where("promote_channel", "<=", 800000)
            ->where("promote_channel", ">", 0)
            ->where('date', ">=", $start_date_before15)
            ->sum("amount");

        $amount_1v1 = ceil($amount_1v1 / 10);
        $channel_charge_data = [];

        if ($amount_koc) {
            $channel_charge_data[] = ["value" => $amount_koc, "name" => "KOC"];
        }

        if ($amount_1v1) {
            $channel_charge_data[] = ["value" => $amount_1v1, "name" => "1V1"];
        }

        if ($amount_other > 0) {
            $channel_charge_data[] = ["value" => $amount_other, "name" => "其他"];
        }

        $channel_charge_res_new = array_slice($channel_charge_res, 0, $before_number);
        foreach ($channel_charge_res_new as $key => $item) {
            $channel_charge_data[] = ["value" => ceil($item['amounts']), "name" => $item['register_channel']];
        }

        //结束==================渠道的充值情况=====================================

        //开始==================渠道的注册情况=====================================
        $register7dayList = BiUserStats1DayModel::getInstance()->getModel()->where([
            ["register_time", ">=", $start_date_before7 . " 00:00:00"],
            ["register_time", "<", date('Y-m-d') . " 00:00:00"],
            ["date", ">=", $start_date_before7],
            ["date", "<", date('Y-m-d')],
        ])->field("uid,register_channel,promote_channel")->group("uid")->select()->toArray();
        $register7DayMap = [];
        foreach ($register7dayList as $item7day) {
            if (isset($register7DayMap[$item7day['register_channel']])) {
                $register7DayMap[$item7day['register_channel']] += 1;
            } else {
                $register7DayMap[$item7day['register_channel']] = 1;
            }
        }

        krsort($register7DayMap);
        $channel_register_res = array_slice($register7DayMap, 0, 10);
        $channel_register_data = [];
        foreach ($channel_register_res as $key => $item) {
            $channel_register_data[] = ["value" => ceil($item), "name" => $key];
        }
        //结束==================渠道的注册情况=====================================

        View::assign('dateList', $dateList);
        View::assign('chargeList', $chargeList);
        View::assign('deilyList', $deilyList);
        View::assign('registerList', $registerList);
        View::assign('chargeUsersList', $chargeUsersList);
        View::assign('channelChargeList', $channel_charge_data); //
        View::assign('channelRegisterList', $channel_register_data);

        //一周的数据
        View::assign('weekList', $weekList);
        View::assign('week_register_user', $week_register_user);
        View::assign('week_daily_user', $week_daily_user);
        View::assign('week_charge_user', $week_charge_user);
        View::assign('week_source', $week_source);

        //留存数据
        View::assign('keepMonthsList', $keepMonthsList);
        View::assign('keep1VagList', $keep1VagList);
        View::assign('keep3VagList', $keep3VagList);
        View::assign('keep7VagList', $keep7VagList);
        View::assign('keep15VagList', $keep15VagList);
        View::assign('keep30VagList', $keep30VagList);

        //今日情况
        $userbehavior = new UserBehavior();
        $currentDate = date('Y-m-d');
        $tomorrowDate = date('Y-m-d', strtotime("+1days", strtotime($currentDate)));
        /*
        $res = Db::table("bi_user_stats_1day")->where('date', '=', $currentDate)->select()->toArray();
        $parseUserRes = [];
        $parseUserList = [];
        foreach ($res as $item) {
        ParseUserActionCommon::getInstance()->parseDataNew($item, $userbehavior, $parseUserList);
        }
        $parseUserRes = $userbehavior->toJson();

        $charge_amount = ParseUserActionCommon::getInstance()->getChargeSum($parseUserRes);
        $agent_amount = ParseUserActionCommon::getInstance()->getAgentChargeSum($parseUserRes);
        $today_charge = $charge_amount + $agent_amount;
        $today_daily_num = count($parseUserList['user']['user_all'] ?? []);
         */

        $today_status = [
            "charge" => ['total' => 0, 'market' => 0, 'koc' => 0, '1v1' => 0],
            "active" => ['total' => 0, 'market' => 0, 'koc' => 0, '1v1' => 0],
            "register" => ['total' => 0, 'market' => 0, 'koc' => 0, '1v1' => 0],
        ];

        $currentUserStatsList = BiUserStats1DayModel::getInstance()->getModel()->withoutField("json_data")
            ->where('date', '=', $currentDate)
            ->select()
            ->toArray();

        foreach ($currentUserStatsList as $statusitem) {

            if ($statusitem['register_time'] >= $currentDate . " 00:00:00" && $statusitem['register_time'] < $tomorrowDate . " 00:00:00") {
                $today_status['register']['total'] += 1;
            }

            if ($statusitem['promote_channel'] > 0 && $statusitem['promote_channel'] <= 800000) {
                if ($statusitem['register_time'] >= $currentDate . " 00:00:00" && $statusitem['register_time'] < $tomorrowDate . " 00:00:00") {
                    $today_status['register']['1v1'] += 1;
                }
                $today_status['active']['1v1'] += 1;
            }
            if ($statusitem['promote_channel'] > 800000) {
                if ($statusitem['register_time'] >= $currentDate . " 00:00:00" && $statusitem['register_time'] < $tomorrowDate . " 00:00:00") {
                    $today_status['register']['koc'] += 1;
                }
                $today_status['active']['koc'] += 1;
            }
            if ($statusitem['promote_channel'] == 0) {
                if ($statusitem['register_time'] >= $currentDate . " 00:00:00" && $statusitem['register_time'] < $tomorrowDate . " 00:00:00") {
                    $today_status['register']['market'] += 1;
                }
                $today_status['active']['market'] += 1;
            }
            $today_status['active']['total'] += 1;
        }

        $currentChargeStatusList = BiDaysUserChargeModel::getInstance()->getModel()->where("date", "=", $currentDate)->select()->toArray();
        $haveChargeList = [];
        foreach ($currentChargeStatusList as $chargeItems) {
            if (!in_array($chargeItems['uid'], $haveChargeList)) {
                array_push($haveChargeList, $chargeItems['uid']);
            }

            if ($chargeItems['promote_channel'] == 0) {
                $today_status['charge']['market'] += ceil($chargeItems['amount'] / 10);
            } elseif ($chargeItems['promote_channel'] <= 800000 && $chargeItems['promote_channel'] > 0) {
                $today_status['charge']['1v1'] += ceil($chargeItems['amount'] / 10);
            } elseif ($chargeItems['promote_channel'] > 800000) {
                $today_status['charge']['koc'] += ceil($chargeItems['amount'] / 10);
            }
            $today_status['charge']['total'] += ceil($chargeItems['amount'] / 10);
        }

        View::assign('current_user_status', $today_status);
        View::assign('token', $this->request->param('token'));

        return View::fetch('chart/user');
    }

    /**
     * bi新增用户付费情况
     */
    public function userDailyByChannel()
    {
        $date_b = $this->request->param("date_b") ?? date('Y-m-d', strtotime("-1days"));
        $date_e = $this->request->param("date_e") ?? date('Y-m-d');
        $data_type = $this->request->param("data_type");

        if (strtotime($date_b) == strtotime($date_e)) {
            $date_e = date('Y-m-d', strtotime("+1days", strtotime($date_b)));
        }
        $compRes = [];

        if ($data_type == 'daily') {
            $mark = '日活';
            $list = BiUserStats1DayModel::getInstance()->getModel()
                ->field("register_channel,count(1) as channel_daily_num,date")
                ->group("register_channel")
                ->where("date", ">=", $date_b)
                ->where("date", "<", $date_e)
                ->where("promote_channel", "=", 0)
                ->order("channel_daily_num desc")
                ->select()
                ->toArray();

            $register_channel_list = array_column($list, "register_channel");
            $channel_daily_num = array_column($list, "channel_daily_num");

            $compRes[] = ["name" => "market", "num" => array_sum(array_column($list, "channel_daily_num"))];
            $kocList = BiUserStats1DayModel::getInstance()->getModel()->alias("u")
                ->field("u.promote_channel,pro.channel_name,count(*) as nums,u.date")
                ->join("zb_promote_room_conf pc", "pc.id = u.promote_channel", "left")
                ->join("zb_promote pro", "pro.id = pc.promote_id", "left")
                ->where("u.date", ">=", $date_b)
                ->where("u.date", "<", $date_e)
                ->where("u.promote_channel", ">", 800000)
                ->group("pro.id")
                ->select()->toArray();
            $channel_koc = [];

            $compRes[] = ["name" => "koc", "num" => array_sum(array_column($kocList, "nums"))];

            foreach ($kocList as $key => $item) {
                $channel_koc[] = ["value" => ceil($item['nums']), "name" => $item['promote_channel']];
            }

            $koc_nums = array_column($kocList, "nums");
            $koc_channel = array_column($kocList, "channel_name");
            //1v1

            $marketList = MarketChannelModel::getInstance()->getModel()->where("pid", "=", 0)->select()->toArray();
            $marketListRes = array_column($marketList, null, "id");

            $v1List = BiUserStats1DayModel::getInstance()->getModel()->alias("u")
                ->field("m.id,one_level,count(1) as nums,u.date")
                ->join("zb_market_channel m", "m.id = u.promote_channel", "left")
                ->where("u.date", ">=", $date_b)
                ->where("u.date", "<", $date_e)
                ->where("u.promote_channel", ">", 0)
                ->where("u.promote_channel", "<", 800000)
                ->group("m.one_level")
                ->select()->toArray();

            $compRes[] = ["name" => "1v1", "num" => array_sum(array_column($v1List, "nums"))];

            foreach ($v1List as $key => $item) {
                $v1List[$key]["channel_name"] = $marketListRes[$item['one_level']]['channel_name'] ?? '';
            }

            $sourceList = BiUserStats1DayModel::getInstance()->getModel()->alias("u")
                ->field("source,count(1) as nums,u.date")
                ->where("u.date", ">=", $date_b)
                ->where("u.date", "<", $date_e)
                ->where("u.source", "<>", '')
                ->group("u.source")
                ->select()->toArray();
        } elseif ($data_type == "register") {
            $mark = "注册";
            $list = BiUserStats1DayModel::getInstance()->getModel()
                ->field("count(distinct uid) as numbers,register_channel,count(distinct uid) as channel_daily_num")
                ->group("register_channel")
                ->where("date", ">=", $date_b)
                ->where("date", "<", $date_e)
                ->where("register_time", ">=", $date_b . " 00:00:00")
                ->where("register_time", "<", $date_e . " 00:00:00")
                ->where("promote_channel", "=", 0)
                ->order("numbers desc")
                ->select()
                ->toArray();

            $register_channel_list = array_column($list, "register_channel");
            $channel_daily_num = array_column($list, "numbers");

            $compRes[] = ["name" => "market", "num" => array_sum(array_column($list, "numbers"))];
            $kocList = BiUserStats1DayModel::getInstance()->getModel()->alias("u")
                ->field("count(distinct uid) as nums,u.promote_channel,pro.channel_name")
                ->join("zb_promote_room_conf pc", "pc.id = u.promote_channel", "left")
                ->join("zb_promote pro", "pro.id = pc.promote_id", "left")
                ->where("u.date", ">=", $date_b)
                ->where("u.date", "<", $date_e)
                ->where("u.register_time", ">=", $date_b . " 00:00:00")
                ->where("u.register_time", "<", $date_e . " 00:00:00")
                ->where("u.promote_channel", ">", 800000)
                ->group("pro.id")
                ->select()->toArray();
            $channel_koc = [];

            $compRes[] = ["name" => "koc", "num" => array_sum(array_column($kocList, "nums"))];

            foreach ($kocList as $key => $item) {
                $channel_koc[] = ["value" => ceil($item['nums']), "name" => $item['promote_channel']];
            }

            $koc_nums = array_column($kocList, "nums");
            $koc_channel = array_column($kocList, "channel_name");
            //1v1

            $marketList = MarketChannelModel::getInstance()->getModel()->where("pid", "=", 0)->select()->toArray();
            $marketListRes = array_column($marketList, null, "id");

            $v1List = BiUserStats1DayModel::getInstance()->getModel()->alias("u")
                ->field("count(distinct u.uid) as nums,m.id,one_level")
                ->join("zb_market_channel m", "m.id = u.promote_channel", "left")
                ->where("u.date", ">=", $date_b)
                ->where("u.date", "<", $date_e)
                ->where("u.register_time", ">=", $date_b . " 00:00:00")
                ->where("u.register_time", "<", $date_e . " 00:00:00")
                ->where("u.promote_channel", ">", 0)
                ->where("u.promote_channel", "<", 800000)
                ->group("m.one_level")
                ->select()->toArray();

            $compRes[] = ["name" => "1v1", "num" => array_sum(array_column($v1List, "nums"))];

            foreach ($v1List as $key => $item) {
                $v1List[$key]["channel_name"] = $marketListRes[$item['one_level']]['channel_name'] ?? '';
            }

            $sourceList = BiUserStats1DayModel::getInstance()->getModel()->alias("u")
                ->field("count(distinct u.uid) as nums,source")
                ->where("u.date", ">=", $date_b)
                ->where("u.date", "<", $date_e)
                ->where("u.register_time", ">=", $date_b . " 00:00:00")
                ->where("u.register_time", "<", $date_e . " 00:00:00")
                ->where("u.source", "<>", '')
                ->group("u.source")
                ->select()->toArray();
        }

        View::assign('list', $list);
        View::assign('mark', $mark);
        View::assign('channel_list', $register_channel_list);
        View::assign('num_list', $channel_daily_num);

        View::assign('koc_nums', $koc_nums);
        View::assign('koc_channel', $koc_channel);
        View::assign('token', $this->request->param('token'));
        View::assign('koclist', $kocList);

        View::assign('v1_nums', array_column($v1List, "nums"));
        View::assign('v1_channel', array_column($v1List, "channel_name"));
        View::assign('v1list', $v1List);
        View::assign('comp_nums', array_column($compRes, "num"));
        View::assign('comp_channel', array_column($compRes, "name"));

        View::assign('source_nums', array_column($sourceList, "nums"));
        View::assign('source_name', array_column($sourceList, "source"));
        View::assign('sourceList', $sourceList);
        View::assign('daytime', $date_e);
        View::assign('date_b', $date_b);
        View::assign('date_e', $date_e);
        View::assign('data_type', $data_type);
        View::assign('mark', $mark);
        return View::fetch('chart/userdailychannel');
    }

    public function dailyListBySearch()
    {
        $uid = $this->request->param("uid");
        $page = $this->request->param("page") ?? 1;
        $channel = $this->request->param("channel") ?? "";
        $limit = $this->request->param("limit") ?? 10;
        $source = $this->request->param("source") ?? '';
        $date_b = $this->request->param("date_b") ?? date('Y-m-d');
        $date_e = $this->request->param("date_e") ?? date('Y-m-d');
        $data_type = $this->request->param("data_type", "daily");

        if (strtotime($date_b) == strtotime($date_e)) {
            $date_e = date('Y-m-d', strtotime("+1days", strtotime($date_b)));
        }

        $register_channel_list = BiRegisterChannelModel::getInstance()->getModel()->select()->toArray();

        if ($this->request->param("isRequest") == 1) {
            $where = [];

            if ($uid > 0) {
                $where[] = ["uid", "=", $uid];
            }

            if ($source) {
                $where[] = ["source", "=", $source];
            }

            if ($date_b && $date_e) {
                $where[] = ["date", ">=", $date_b];
                $where[] = ["date", "<", $date_e];
            }

            if ($channel) {
                if ($channel == 'koc') {
                    $where[] = ["promote_channel", ">", 800000];
                } elseif ($channel == '1v1') {
                    $where[] = ["promote_channel", "<=", 800000];
                    $where[] = ["promote_channel", ">", 0];
                } else {
                    $where[] = ["promote_channel", "=", 0];
                    $where[] = ["register_channel", "=", $channel];
                }
            }

            $res = [];
            $mark = "";

            if ($where) {
                $userstats1dayModel = BiUserStats1DayModel::getInstance()->getModel();
                if ($data_type == 'daily') {
                    $count = $userstats1dayModel->field("id")->where($where)->count();
                    $res = $userstats1dayModel->field('uid,date,register_channel,promote_channel,register_time,source')
                        ->where($where)
                        ->page($page, $limit)
                        ->select()->toArray();
                } elseif ($data_type == 'register') {
                    $where[] = ["register_time", ">=", $date_b . " 00:00:00"];
                    $where[] = ["register_time", "<", $date_e . " 00:00:00"];
                    $count = $userstats1dayModel->where($where)->count("distinct uid");
                    $res = $userstats1dayModel->field('distinct uid,register_channel,promote_channel,register_time,source')
                        ->where($where)
                        ->page($page, $limit)
                        ->select()->toArray();
                }

            }

            $uids = [];

            if ($res) {
                $uids = array_column($res, "uid");
            }

            $userList = MemberModel::getInstance()->getWhereAllData([['id', 'in', $uids]], 'nickname,login_time,id');
            $userInfo = array_column($userList, null, 'id');
            foreach ($res as $res_k => $res_v) {
                if ($res_v['promote_channel'] == 0) {
                    $res[$res_k]['channel_name'] = $res_v['register_channel'];
                } elseif ($res_v['promote_channel'] > 0 && $res_v['promote_channel'] <= 800000) {
                    $res[$res_k]['channel_name'] = "1v1";
                } elseif ($res_v['promote_channel'] > 800000) {
                    $res[$res_k]['channel_name'] = "koc";
                }
                $res[$res_k]['nickname'] = $userInfo[$res_v['uid']]['nickname'] ?? '';
                $res[$res_k]['login_time'] = $userInfo[$res_v['uid']]['login_time'] ?? '';
            }

            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            if ($data_type == "daily") {
                $mark = '日活图表';
            } elseif ($data_type == "register") {
                $mark = '注册图表';
            }

            View::assign('token', $this->request->param('token'));
            View::assign('date_b', $date_b);
            View::assign('date_e', $date_e);
            View::assign('data_type', $data_type);
            View::assign('mark', $mark);
            View::assign('channel', $channel);
            View::assign('register_channel_list', $register_channel_list);
            return View::fetch('chart/dailylistsearch');
        }
    }

    public function chargeListBySearch()
    {
        $uid = $this->request->param("uid");
        $page = $this->request->param("page") ?? 1;
        $channel = $this->request->param("channel") ?? "";
        $limit = $this->request->param("limit") ?? 10;
        $source = $this->request->param("source") ?? '';
        $date_b = $this->request->param("date_b") ?? date('Y-m-d');
        $date_e = $this->request->param("date_e") ?? date('Y-m-d');
        $data_type = $this->request->param("data_type", "charge");
        $charge_type = $this->request->param("charge_type", 0);
        $charge_range = $this->request->param("charge_range", "");

        if ($data_type == "chargenew") {
            $mark = "新增用户充值";
            $userchargeModelName = BiDaysUserChargeNewModel::getInstance()->getModel();
        } elseif ($data_type == "charge") {
            $mark = "用户充值";
            $userchargeModelName = BiDaysUserChargeModel::getInstance()->getModel();
        }

        if (strtotime($date_b) == strtotime($date_e)) {
            $date_e = date('Y-m-d', strtotime("+1days", strtotime($date_b)));
        }

        $register_channel_list = BiRegisterChannelModel::getInstance()->getModel()->select()->toArray();

        if ($this->request->param("isRequest") == 1) {
            $where = [];
            if ($uid > 0) {
                $where[] = ["uid", "=", $uid];
            }

            if ($source) {
                $where[] = ["source", "=", $source];
            }

            if ($date_b && $date_e) {
                $where[] = ["date", ">=", $date_b];
                $where[] = ["date", "<", $date_e];
            }

            if ($channel) {
                if ($channel == 'koc') {
                    $where[] = ["promote_channel", ">", 800000];
                } elseif ($channel == '1v1') {
                    $where[] = ["promote_channel", "<=", 800000];
                    $where[] = ["promote_channel", ">", 0];
                } else {
                    $where[] = ["promote_channel", "=", 0];
                    $where[] = ["register_channel", "=", $channel];
                }
            }

            if ($charge_type > 0) {
                $where[] = ["type", "=", $charge_type];
            }

            if ($charge_range) {
                $range = explode("-", $charge_range);
                if (count($range) == 2) {
                    //因为库里面存的豆 所以乘以10
                    $where[] = ['amount', '>=', $range[0] * 10];
                    $where[] = ['amount', '<', $range[1] * 10];
                }
            }

            $res = [];

            if ($where) {
                $count = $userchargeModelName->field("id")->where($where)->count();
                $res = $userchargeModelName->field("uid,date,register_channel,promote_channel,register_time,source,amount,case when type=1 then '直充' else '代充' end as charge_type")
                    ->where($where)
                    ->page($page, $limit)
                    ->select()->toArray();
                $totalRowAmount = $userchargeModelName->where($where)->sum('amount');

            }

            $uids = [];

            if ($res) {
                $uids = array_column($res, "uid");
            }

            $user_list = MemberModel::getInstance()->getWhereAllData([['id', 'in', $uids]], 'id,nickname,login_time');
            $userInfo = array_column($user_list, null, 'id');
            foreach ($res as $res_k => $res_v) {
                if ($res_v['promote_channel'] == 0) {
                    $res[$res_k]['channel_name'] = $res_v['register_channel'];
                } elseif ($res_v['promote_channel'] > 0 && $res_v['promote_channel'] <= 800000) {
                    $res[$res_k]['channel_name'] = "1v1";
                } elseif ($res_v['promote_channel'] > 800000) {
                    $res[$res_k]['channel_name'] = "koc";
                }
                $res[$res_k]['nickname'] = $userInfo[$res_v['uid']]['nickname'] ?? '';
                $res[$res_k]['login_time'] = $userInfo[$res_v['uid']]['login_time'] ?? '';
                $res[$res_k]['money'] = sprintf("%.2f", $res_v['amount'] / 10);
            }

            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res, 'totalRow' => ["money" => $totalRowAmount / 10]];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('date_b', $date_b);
            View::assign('date_e', $date_e);
            View::assign('data_type', $data_type);
            View::assign('mark', $mark);
            View::assign('channel', $channel);
            View::assign('register_channel_list', $register_channel_list);
            return View::fetch('chart/chargelistsearch');
        }
    }

    /**
     *用户充值图表
     */
    public function userchargechart()
    {
        $date_b = $this->request->param("date_b") ?? date('Y-m-d', strtotime("-1days"));
        $date_e = $this->request->param("date_e") ?? date('Y-m-d');
        $data_type = $this->request->param("data_type");

        if (strtotime($date_b) == strtotime($date_e)) {
            $date_e = date('Y-m-d', strtotime("+1days", strtotime($date_b)));
        }
        $compRes = [];

        if ($data_type == "chargenew") {
            $mark = "新增用户充值";
            //$tableName = "bi_days_user_charge_new";
            $userchargeModelName = BiDaysUserChargeNewModel::getInstance()->getModel();
        } elseif ($data_type == "charge") {
            $mark = "用户充值";
            //$tableName = "bi_days_user_charge";
            $userchargeModelName = BiDaysUserChargeModel::getInstance()->getModel();
        }

        $groupList = $userchargeModelName->field("round(sum(amount/10),2) as nums,uid")
            ->group("register_channel")
            ->where("date", ">=", $date_b)
            ->where("date", "<", $date_e)
            ->where("promote_channel", "=", 0)
            ->group("uid")
            ->select()
            ->toArray();

        $groupMarketChargeRes = $this->moneyPartition($groupList);

        $list = $userchargeModelName->field("round(sum(amount/10),2) as nums,register_channel")
            ->group("register_channel")
            ->where("date", ">=", $date_b)
            ->where("date", "<", $date_e)
            ->where("promote_channel", "=", 0)
            ->order("nums desc")
            ->select()
            ->toArray();

        $register_channel_list = array_column($list, "register_channel");
        $channel_daily_num = array_column($list, "nums");

        $compRes[] = ["name" => "market", "value" => array_sum(array_column($list, "nums")), "num" => array_sum(array_column($list, "nums")), "partition" => $groupMarketChargeRes];
        $groupkocList = $userchargeModelName->alias("u")
            ->field("round(sum(amount/10),2) as nums")
            ->where("u.date", ">=", $date_b)
            ->where("u.date", "<", $date_e)
            ->where("u.promote_channel", ">", 800000)
            ->group("u.uid")
            ->select()->toArray();

        $groupKocChargeRes = $this->moneyPartition($groupkocList);

        $kocList = $userchargeModelName->alias("u")
            ->field("round(sum(amount/10),2) as nums,u.promote_channel,pro.channel_name")
            ->join("zb_promote_room_conf pc", "pc.id = u.promote_channel", "left")
            ->join("zb_promote pro", "pro.id = pc.promote_id", "left")
            ->where("u.date", ">=", $date_b)
            ->where("u.date", "<", $date_e)
            ->where("u.promote_channel", ">", 800000)
            ->group("pro.id")
            ->select()->toArray();

        $channel_koc = [];
        $compRes[] = ["name" => "koc", "value" => array_sum(array_column($kocList, "nums")), "num" => array_sum(array_column($kocList, "nums")), "partition" => $groupKocChargeRes];

        foreach ($kocList as $key => $item) {
            $channel_koc[] = ["value" => ceil($item['nums']), "name" => $item['promote_channel']];
        }

        $koc_nums = array_column($kocList, "nums");
        $koc_channel = array_column($kocList, "channel_name");
        //1v1

        $marketList = MarketChannelModel::getInstance()->getModel()->where("pid", "=", 0)->select()->toArray();
        $marketListRes = array_column($marketList, null, "id");

        $groupv1List = $userchargeModelName->alias("u")
            ->field("round(sum(amount/10),2) as nums")
            ->where("u.date", ">=", $date_b)
            ->where("u.date", "<", $date_e)
            ->where("u.promote_channel", ">", 0)
            ->where("u.promote_channel", "<=", 800000)
            ->group("u.uid")
            ->select()->toArray();

        $group1v1ChargeRes = $this->moneyPartition($groupv1List);

        $v1List = $userchargeModelName->alias("u")
            ->field("round(sum(amount/10),2) as nums,m.id,one_level")
            ->join("zb_market_channel m", "m.id = u.promote_channel", "left")
            ->where("u.date", ">=", $date_b)
            ->where("u.date", "<", $date_e)
            ->where("u.promote_channel", ">", 0)
            ->where("u.promote_channel", "<=", 800000)
            ->group("m.one_level")
            ->select()->toArray();

        $compRes[] = ["name" => "1v1", "value" => array_sum(array_column($v1List, "nums")), "num" => array_sum(array_column($v1List, "nums")), "partition" => $group1v1ChargeRes];

        foreach ($v1List as $key => $item) {
            $v1List[$key]["channel_name"] = $marketListRes[$item['one_level']]['channel_name'] ?? '';
        }

        $sourceList = $userchargeModelName->alias("u")
            ->field("round(sum(amount/10),2) as nums,source")
            ->where("u.date", ">=", $date_b)
            ->where("u.date", "<", $date_e)
            ->where("u.source", "<>", '')
            ->group("u.source")
            ->select()->toArray();

        View::assign('list', $list);
        View::assign('mark', $mark);
        View::assign('channel_list', $register_channel_list);
        View::assign('num_list', $channel_daily_num);

        View::assign('koc_nums', $koc_nums);
        View::assign('koc_channel', $koc_channel);
        View::assign('token', $this->request->param('token'));
        View::assign('koclist', $kocList);

        View::assign('v1_nums', array_column($v1List, "nums"));
        View::assign('v1_channel', array_column($v1List, "channel_name"));
        View::assign('v1list', $v1List);
        //View::assign('comp_nums', array_column($compRes, "num"));
        View::assign('comp_nums', $compRes);
        View::assign('comp_channel', array_column($compRes, "name"));

        View::assign('source_nums', array_column($sourceList, "nums"));
        View::assign('source_name', array_column($sourceList, "source"));
        View::assign('sourceList', $sourceList);
        View::assign('daytime', $date_e);
        View::assign('date_b', $date_b);
        View::assign('date_e', $date_e);
        View::assign('data_type', $data_type);
        View::assign('mark', $mark);
        return View::fetch('chart/userchargechannel');
    }

    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }

    public function userchargeDetail()
    {
        /*[
        ['product', '代充', '直充', '直+代'],
        ['appstore', 43.3, 85.8, 93.7],
        ['Milk Tea', 83.1, 73.4, 55.1],
        ['Cheese Cocoa', 86.4, 65.2, 82.5],
        ['Walnut Brownie', 72.4, 53.9, 39.1]
        ]
         */
        $date_b = $this->request->param("date_b") ?? date('Y-m-d', strtotime("-1days"));
        $date_e = $this->request->param("date_e") ?? date('Y-m-d');
        $current_date = $this->request->param("current_date") ?? ''; //兼容图表12-14只有月和日
        if ($current_date) {
            $date_b = date('Y') . "-" . $current_date;
            $date_e = date('Y-m-d', strtotime("+1 days", strtotime($date_b)));
        }
        $charge_total_amount = 0;
        $add_charge_total_amount = 0;

        $chargeRes = BiDaysUserChargeModel::getInstance()->getModel()->field("date,amount/10 as nums,register_channel,promote_channel,type")
            ->where("date", ">=", $date_b)
            ->where("date", "<", $date_e)
            ->select()
            ->toArray();

        $addChargeRes = BiDaysUserChargeNewModel::getInstance()->getModel()->field("date,amount/10 as nums,register_channel,promote_channel,type")
            ->where("date", ">=", $date_b)
            ->where("date", "<", $date_e)
            ->select()
            ->toArray();

        $chargeChannelType = [];
        $addChargeChannelType = []; //新增用户充值
        foreach ($chargeRes as $item) {
            if ($item['promote_channel'] > 0 and $item['promote_channel'] <= 800000) {
                $mark = "1v1";
            } elseif ($item['promote_channel'] > 800000) {
                $mark = "Koc";
            } else {
                $mark = $item['register_channel'];
            }

            $charge_total_amount += $item['nums'];

            if (isset($chargeChannelType[$mark])) {
                $chargeChannelType[$mark]['total'] += ceil($item['nums']);
                if ($item['type'] == 1) {
                    $chargeChannelType[$mark]['direct'] += ceil($item['nums']);
                } elseif ($item['type'] == 2) {
                    $chargeChannelType[$mark]['agent'] += ceil($item['nums']);
                }
            } else {
                $chargeChannelType[$mark]['total'] = 0;
                $chargeChannelType[$mark]['direct'] = 0;
                $chargeChannelType[$mark]['agent'] = 0;
                $chargeChannelType[$mark]['total'] = ceil($item['nums']);
                if ($item['type'] == 1) {
                    $chargeChannelType[$mark]['direct'] = ceil($item['nums']);
                } elseif ($item['type'] == 2) {
                    $chargeChannelType[$mark]['agent'] = ceil($item['nums']);
                }
            }

        }

        foreach ($addChargeRes as $item) {
            if ($item['promote_channel'] > 0 and $item['promote_channel'] <= 800000) {
                $mark = "1v1";
            } elseif ($item['promote_channel'] > 800000) {
                $mark = "Koc";
            } else {
                $mark = $item['register_channel'];
            }

            $add_charge_total_amount += $item['nums'];

            if (isset($addChargeChannelType[$mark])) {
                $addChargeChannelType[$mark]['total'] += ceil($item['nums']);
                if ($item['type'] == 1) {
                    $addChargeChannelType[$mark]['direct'] += ceil($item['nums']);
                } elseif ($item['type'] == 2) {
                    $addChargeChannelType[$mark]['agent'] += ceil($item['nums']);
                }
            } else {
                $addChargeChannelType[$mark]['total'] = 0;
                $addChargeChannelType[$mark]['direct'] = 0;
                $addChargeChannelType[$mark]['agent'] = 0;
                $addChargeChannelType[$mark]['total'] = ceil($item['nums']);
                if ($item['type'] == 1) {
                    $addChargeChannelType[$mark]['direct'] = ceil($item['nums']);
                } elseif ($item['type'] == 2) {
                    $addChargeChannelType[$mark]['agent'] = ceil($item['nums']);
                }
            }
        }

        $chargeChartByType = [];
        $addchargeChartByType = [];
        foreach ($chargeChannelType as $key => $item) {
            $chargeChartByType[] = array_merge([$key], array_values($item));
        }

        foreach ($addChargeChannelType as $key => $item) {
            $addchargeChartByType[] = array_merge([$key], array_values($item));
        }

        array_unshift($chargeChartByType, ['product', '直+代', '直充', '代充']);
        array_unshift($addchargeChartByType, ['product', '直+代', '直充', '代充']);
        $format_charge_type = ["source" => $chargeChartByType];
        $format_add_charge_type = ["source" => $addchargeChartByType];

        $chargepartionList = $this->moneyPartitionType($chargeRes, "nums");
        $addchargepartionList = $this->moneyPartitionType($addChargeRes, "nums");

        $chargepartionListChart = [];
        $addchargepartionListChart = [];
        foreach ($chargepartionList as $part_key => $part_value) {
            $chargepartionListChart[] = array_merge(["name" => $part_key, "value" => $part_value['total']], $part_value);
        }
        foreach ($addchargepartionList as $add_part_key => $add_part_value) {
            $addchargepartionListChart[] = array_merge(["name" => $add_part_key, "value" => $add_part_value['total']], $add_part_value);
        }
        //柱形图 name=[]  value=[]
        //圆形图 [["name"=>"","value"=>""],["name"=>"","value"]]

        View::assign('chargepartionListChart', $chargepartionListChart);
        View::assign('addchargepartionListChart', $addchargepartionListChart);
        View::assign('token', $this->request->param('token'));
        View::assign('date_b', $date_b);
        View::assign('date_e', $date_e);
        View::assign('format_charge_type', $format_charge_type);
        View::assign('format_add_charge_type', $format_add_charge_type);
        View::assign('charge_total_amount', $charge_total_amount); //充值总值
        View::assign('add_charge_total_amount', $add_charge_total_amount); //新增充值总值
        return View::fetch('chart/userchargedetail');
    }

    /**
     * 区分代充或者直充
     * @param $data
     * @param string $field
     * @return array
     */
    public function moneyPartitionType($data, $field = 'nums')
    {
        $chargePartition = [
            '0-100' => ['total' => 0, "direct" => 0, "agent" => 0],
            '100-500' => ['total' => 0, "direct" => 0, "agent" => 0],
            '500-1000' => ['total' => 0, "direct" => 0, "agent" => 0],
            '1000-5000' => ['total' => 0, "direct" => 0, "agent" => 0],
            '5000-10000' => ['total' => 0, "direct" => 0, "agent" => 0],
            '10000-50000' => ['total' => 0, "direct" => 0, "agent" => 0],
            '50000-100000' => ['total' => 0, "direct" => 0, "agent" => 0],
            '100000-500000' => ['total' => 0, "direct" => 0, "agent" => 0],
            '500000-1000000' => ['total' => 0, "direct" => 0, "agent" => 0],
            '1000000-2000000' => ['total' => 0, "direct" => 0, "agent" => 0],
            '2000000-∞' => ['total' => 0, "direct" => 0, "agent" => 0],
        ];

        foreach ($data as $info) {
            $nums = $info[$field];
            $type = $info['type'];
            foreach ($chargePartition as $key => $item) {
                $parseParition = explode("-", $key);
                $min = $parseParition[0];
                $max = $parseParition[1];
                if (($nums >= $min) && ($nums < $max)) {
                    $chargePartition[$key]['total'] += 1;
                    if ($type == 1) {
                        $chargePartition[$key]['direct'] += 1;
                    }

                    if ($type == 2) {
                        $chargePartition[$key]['agent'] += 1;
                    }
                    break;
                }
            }

        }

        return array_filter($chargePartition, function ($v) {
            if ($v['total'] > 0) {
                return true;
            }
            return false;
        });

    }

    /**
     * 不分代充或者直充
     * @param $data
     * @param string $field
     * @return array
     */
    public function moneyPartition($data, $field = 'nums')
    {
        $chargePartition = [
            '0-100' => 0,
            '100-500' => 0,
            '500-1000' => 0,
            '1000-5000' => 0,
            '5000-10000' => 0,
            '10000-50000' => 0,
            '50000-100000' => 0,
            '100000-500000' => 0,
            '500000-1000000' => 0,
            '1000000-2000000' => 0,
            '2000000-∞' => 0,
        ];

        foreach ($data as $info) {
            $nums = $info[$field];
            foreach ($chargePartition as $key => $item) {
                $parseParition = explode("-", $key);
                $min = $parseParition[0];
                $max = $parseParition[1];
                if (($nums >= $min) && ($nums < $max)) {
                    $chargePartition[$key] += 1;
                    break;
                }
            }

        }

        return array_filter($chargePartition);

    }

    public function userKeepList()
    {
        /*[
        ['product', '代充', '直充', '直+代'],
        ['appstore', 43.3, 85.8, 93.7],
        ['Milk Tea', 83.1, 73.4, 55.1],
        ['Cheese Cocoa', 86.4, 65.2, 82.5],
        ['Walnut Brownie', 72.4, 53.9, 39.1]
        ]
         */
        //数据类型 register:注册 active:日活 charge:充值 charge_add:新增充值
        $date_b = $this->request->param("date_b", date('Y-m-d', strtotime("-10days")));
        $date_e = $this->request->param("date_e", date('Y-m-d'));
        $keep_type = $this->request->param("keep_type", "register");
        $daochu = $this->request->param("daochu", 0);

        $keepTypeList = [
            "register" => "注册用户留存",
            "charge" => "充值用户留存",
            "charge_add" => "新增充值用户留存",
            "active" => "日活用户留存",
        ];

        $field = "  date, ";
        $field .= " LENGTH(source) - LENGTH(REPLACE(source,',','')) + 1  as total_number,";
        $field .= " LENGTH(keep_2) - LENGTH(REPLACE(keep_2,',','')) + 1  as keep_2_number,";
        $field .= " LENGTH(keep_3) - LENGTH(REPLACE(keep_3,',','')) + 1  as keep_3_number, ";
        //$field .= " LENGTH(keep_4) - LENGTH(REPLACE(keep_4,',','')) + 1  as keep_4_number,";
        $field .= " LENGTH(keep_5) - LENGTH(REPLACE(keep_5,',','')) + 1  as keep_5_number,";
        //$field .= " LENGTH(keep_6) - LENGTH(REPLACE(keep_6,',','')) + 1  as keep_6_number,";
        $field .= " LENGTH(keep_7) - LENGTH(REPLACE(keep_7,',','')) + 1  as keep_7_number,";
        //$field .= " LENGTH(keep_8) - LENGTH(REPLACE(keep_8,',','')) + 1  as keep_8_number,";
        //$field .= " LENGTH(keep_9) - LENGTH(REPLACE(keep_9,',','')) + 1  as keep_9_number,";
        $field .= " LENGTH(keep_10) - LENGTH(REPLACE(keep_10,',','')) + 1  as keep_10_number,";
        $field .= " LENGTH(keep_15) - LENGTH(REPLACE(keep_15,',','')) + 1  as keep_15_number,";
        $field .= " LENGTH(keep_30) - LENGTH(REPLACE(keep_30,',','')) + 1  as keep_30_number";

        /*       {
        name: 'Email',
        type: 'line',
        stack: 'Total',
        data: [120, 132, 101, 134, 90, 230, 210]
        },
         */

        $res = BiUserKeepDayModel::getInstance()->getModel()->field($field)
            ->where("date", ">=", $date_b)
            ->where("date", "<", $date_e)
            ->where("type", "=", $keep_type)
            ->select()->toArray();

        foreach ($res as $key => $items) {
            $res[$key]['keep_2_number_rate'] = $this->divedFunc($items['keep_2_number'], $items['total_number'], 4);
            $res[$key]['keep_3_number_rate'] = $this->divedFunc($items['keep_3_number'], $items['total_number'], 4);
            $res[$key]['keep_5_number_rate'] = $this->divedFunc($items['keep_5_number'], $items['total_number'], 4);
            $res[$key]['keep_7_number_rate'] = $this->divedFunc($items['keep_7_number'], $items['total_number'], 4);
            $res[$key]['keep_10_number_rate'] = $this->divedFunc($items['keep_10_number'], $items['total_number'], 4);
            $res[$key]['keep_15_number_rate'] = $this->divedFunc($items['keep_15_number'], $items['total_number'], 4);
            $res[$key]['keep_30_number_rate'] = $this->divedFunc($items['keep_30_number'], $items['total_number'], 4);
        }

        $dateList = [];
        $nameList = [];
        $charFormatList = []; //图表格式数据

        if ($res) {
            $dateList = array_column($res, "date");
            $charFormatList[] = ["name" => "2日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_2_number_rate")];
            $charFormatList[] = ["name" => "3日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_3_number_rate")];
            //$charFormatList[] = ["name" => "4日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_4_number")];
            $charFormatList[] = ["name" => "5日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_5_number_rate")];
            //$charFormatList[] = ["name" => "6日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_6_number")];
            $charFormatList[] = ["name" => "7日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_7_number_rate")];
            //$charFormatList[] = ["name" => "8日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_8_number")];
            //$charFormatList[] = ["name" => "9日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_9_number")];
            $charFormatList[] = ["name" => "10日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_10_number_rate")];
            $charFormatList[] = ["name" => "15日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_15_number_rate")];
            $charFormatList[] = ["name" => "30日留存", "type" => "line", "stach" => "", "data" => array_column($res, "keep_30_number_rate")];
            $nameList = array_column($charFormatList, "name");
        }

        if ($daochu == 1) {
            $extension_keep_type = $keepTypeList[$keep_type] ?? '';
            $markinfo = "($extension_keep_type)";
            $this->exportcsv($res, [
                    "date" => '日期',
                    "keep_2_number_rate" => '次日' . $markinfo,
                    "keep_3_number_rate" => '3日' . $markinfo,
                    "keep_5_number_rate" => '5日' . $markinfo,
                    "keep_7_number_rate" => '7日' . $markinfo,
                    "keep_10_number_rate" => '10日' . $markinfo,
                    "keep_15_number_rate" => '15日' . $markinfo,
                    "keep_30_number_rate" => '30日' . $markinfo,
                ]
            );
        }

        View::assign('token', $this->request->param('token'));
        View::assign('date_b', $date_b);
        View::assign('date_e', $date_e);
        View::assign('keep_type', $keep_type);
        View::assign('dateList', $dateList);
        View::assign('nameList', $nameList);
        View::assign('charFormatList', $charFormatList);
        View::assign('resList', $res);
        View::assign('keepTypeMark', ($keepTypeList[$keep_type] ?? ''));
        return View::fetch('chart/userkeep');
    }

    public function registerUserProvince()
    {
        $date_b = $this->request->param("date_b", date('Y-m-d', strtotime("-1days")));
        $date_e = $this->request->param("date_e", date('Y-m-d'));
        $type = $this->request->param("type", 0);

        $mark = "";

        if ($type == 0) {
            $mark = "注册";
        }

        if ($type == 1) {
            $mark = "登录";
        }

        $fields = "sum(people_number) as people_numbers,sum(man_number) as man_numbers,sum(woman_number) as woman_numbers,province";

        $res = BiRegisterUserProvinceModel::getInstance()->getModel()->field($fields)
            ->where("date", ">=", $date_b)
            ->where("date", "<", $date_e)
            ->where("type", "=", $type)
            ->group("province")
            ->order("people_numbers", "desc")
            ->select()->toArray();

        $peopleList = array_column($res, "people_numbers");

        $collectList = [];
        foreach ($res as $key => $item) {
            $collectList[] = ["name" => str_replace("省", "", $item['province']), "value" => $item['people_numbers']];
        }

        $max_people = 0;
        $min_people = 0;

        $colorNode = [];
        if ($peopleList) {
            $max_people = max($peopleList);
            $min_people = min($peopleList);
        }

        $av_people = ceil(($max_people - $min_people) / 6);
        $end = $min_people;
        for ($i = 0; $i < 6; $i++) {
            $start = $end;
            $end = $av_people + $start;
            $colorNode[] = ["start" => intval($start), "end" => intval($end)];
        }

        foreach ($res as $n_index => $n_item) {
            $res[$n_index]['man_rate'] = round($n_item['man_numbers'] / $n_item['people_numbers'] * 100, 2) . "%";
            $res[$n_index]['woman_rate'] = round($n_item['woman_numbers'] / $n_item['people_numbers'] * 100, 2) . "%";
        }

        $total_numbers = array_sum(array_column($res, "people_numbers"));
        $man_numbers = array_sum(array_column($res, "man_numbers"));
        $woman_numbers = array_sum(array_column($res, "woman_numbers"));

        View::assign('token', $this->request->param('token'));
        View::assign('date_b', $date_b);
        View::assign('type', $type);
        View::assign('date_e', $date_e);
        View::assign('resList', $res);
        View::assign('collectList', $collectList);
        View::assign('colorNode', $colorNode);
        View::assign('total_numbers', $total_numbers);
        View::assign('man_numbers', $man_numbers);
        View::assign('woman_numbers', $woman_numbers);
        View::assign('mark', $mark);
        return View::fetch('chart/registeruserprovince');
    }

    //工会房间消费明细
    public function roomConsumeList()
    {
        $date_b = $this->request->param("date_b", date('Y-m-d', strtotime("-1days")));
        $date_e = $this->request->param("date_e", date('Y-m-d'));
        $guild_id = $this->request->param("guild_id", 0);
        $room_id = $this->request->param("room_id", 0);

        $guildInfo = MemberGuildModel::getInstance()->getWhereAllData([['status', '=', 1]], 'id,nickname');

        $roomInfo = LanguageroomModel::getInstance()->getWhereAllData([['guild_id', '>', 0]], 'id,guild_id,room_name');

        $roomInfoById = array_column($roomInfo, null, "id");
        $guildInfoById = array_column($guildInfo, null, "id");

        $roomids = [];
        if ($guild_id > 0 && empty($room_id)) {
            //工会下所有的房间
            $room_list = LanguageroomModel::getInstance()->getWhereAllData([['guild_id', '=', $guild_id]], 'id');
            $roomids = array_column($room_list, 'id');
        }

        if (empty($guild_id) && empty($room_id)) {
            $roomids = array_column($roomInfo, 'id');
        }

        if ($room_id > 0) {
            $roomids[] = $room_id;
        }

        $field = "";
        $field .= "room_id,";
        $field .= "sum(case when send_type=1 || send_type =3 then  reward_amount else 0 end)  as 'direct',";
        $field .= "sum(case when send_type=2 then  reward_amount else 0 end)  as 'pack',";
        $field .= "sum(case when send_type=1 || send_type=2 || send_type=3  then  reward_amount else 0 end)  as 'total'";

        $res = BiRoomEveryroomConsume::getInstance()->getModel()->field($field)
            ->where("date", ">=", $date_b)
            ->where("date", "<", $date_e)
            ->where("room_id", "in", $roomids)
            ->group("room_id")
            ->order("total asc")
            ->select()
            ->toArray();

        $format_data = [];
        foreach ($res as $key => $item) {
            $guildid = $roomInfoById[$item['room_id']]['guild_id'] ?? 0;
            $res[$key]['room_name'] = $roomInfoById[$item['room_id']]['room_name'] ?? '';
            $res[$key]['total_money'] = round($item['total'] / 10, 2);
            $res[$key]['direct_money'] = round($item['direct'] / 10, 2);
            $res[$key]['pack_money'] = round($item['pack'] / 10, 2);
            $res[$key]['guild_name'] = $guildInfoById[$guildid]['nickname'] ?? '';
            $res[$key]['show_name'] = $res[$key]['guild_name'] . "(" . $res[$key]['room_name'] . ")";
            $format_data[] = ["name" => $item['room_id'], "value" => $res[$key]['total_money'], "direct_money" => $res[$key]['direct_money'], "pack_money" => $res[$key]['pack_money']];
        }

        $totalList = array_column($res, "total_money");
        $showNameList = array_column($res, "show_name");

        View::assign('token', $this->request->param('token'));
        View::assign('date_b', $date_b);
        View::assign('date_e', $date_e);
        View::assign('guildList', $guildInfo);
        View::assign('room_id', $room_id);
        View::assign('guild_id', $guild_id);
        View::assign('totalList', $totalList);
        View::assign('roomNameList', $showNameList);
        View::assign('formatdata', $format_data);
        View::assign('count', count($res) * 70);
        return View::fetch('chart/roomconsumelist');
    }

    public function asaSummary()
    {

        $date_r = $this->request->param("date_r", '');
        $asatype = $this->request->param("asatype", 'appstore');
        if (empty($date_r)) {
            $begin_date = $this->request->param("date_b", date('Y-m-d', strtotime("-2days")));
            $end_date = $this->request->param("date_e", date('Y-m-d', strtotime("-1days")));
            $date_r = $begin_date . " - " . $end_date;
        } else {
            $params = explode(" - ", $date_r);
            $begin_date = $params[0];
            $end_date = $params[1];
        }
        try {
            $returnDataFormatter = $this->_asacommon($begin_date, $end_date, 0, $asatype);
        } catch (Throwable $e) {
            Log::ERROR("asasummary:error" . $e->getMessage());
        }

        $reskeyword = Db::name('bi_asa_by_keyword')
            ->where('date', '>=', $begin_date)
            ->where('date', '<', $end_date)
            ->where('asatype', '=', $asatype)
            ->select()->toArray();

        $calculRes = [];

        foreach ($reskeyword as $keywordItem) {
            $keywordid = $keywordItem['keyword_id'] ?? 0;
            if (isset($calculRes[$keywordid])) {
                $register_params = $this->array_merge_method($calculRes[$keywordid]['register_add'], explode(",", $keywordItem['register_uids']));
                $calculRes[$keywordid]['register_add'] = $register_params;

                $register_keep_params = $this->array_merge_method($calculRes[$keywordid]['register_keep2'], explode(",", $keywordItem['register_keep2_uids']));
                $calculRes[$keywordid]['register_keep2'] = $register_keep_params;

                $register_params = $this->array_merge_method($calculRes[$keywordid]['charge_keep2'], explode(",", $keywordItem['charge_keep2_uids']));
                $calculRes[$keywordid]['charge_keep2'] = $register_params;

            } else {
                $calculRes[$keywordid] = [
                    "register_add" => 0,
                    "register_keep2" => 0,
                    "charge_keep2" => 0,
                ];
                $calculRes[$keywordid]['register_add'] = explode(",", $keywordItem['register_uids']);
                $calculRes[$keywordid]['register_keep2'] = explode(",", $keywordItem['register_keep2_uids']);
                $calculRes[$keywordid]['charge_keep2'] = explode(",", $keywordItem['charge_keep2_uids']);
            }
        }

        $keyDataFormatter = [];

        foreach ($calculRes as $keyword => $calculItem) {
            $register_add_number = count(array_filter($calculItem['register_add']));
            $keyDataFormatter[$keyword]['register_add'] = $register_add_number;
            $register_keep2_number = count(array_filter($calculItem['register_keep2']));
            $keyDataFormatter[$keyword]['register_keep2'] = $register_keep2_number;
            $charge_keep2_number = count(array_filter($calculItem['charge_keep2']));
            $keyDataFormatter[$keyword]['charge_keep2'] = $charge_keep2_number;

            $keyDataFormatter[$keyword]['iad_keyword'] = $keyword;
            if ($asatype == 'appstore') {
                $keyDataFormatter[$keyword]['iad_keyword_name'] = (config("asakeyword.iadkeyword"))[$keyword] ?? '';
            } elseif ($asatype == 'huawei') {
                $keyDataFormatter[$keyword]['iad_keyword_name'] = (config("asakeyword.hwkeyword"))[$keyword] ?? '';
            }

            //充值总人数
            $uids = $calculItem['register_add'];
            $sum_charge_user_number = BiDaysUserChargeModel::getInstance()->getModel()
                ->where("uid", "in", $uids)->count('distinct uid');
            $keyDataFormatter[$keyword]['sum_charge_user_number'] = $sum_charge_user_number;

            //累计充值总金额
            $sum_charge_amount = BiDaysUserChargeModel::getInstance()->getModel()
                ->where("uid", "in", $uids)->sum('amount');
            $keyDataFormatter[$keyword]['sum_charge_amount'] = $this->divedFunc($sum_charge_amount, 10);

            //新增充值率
            $keyDataFormatter[$keyword]['register_charge_rate'] = $this->divedFunc($sum_charge_user_number, $register_add_number, 2);
            //新增次留率
            $keyDataFormatter[$keyword]['register_keep2_rate'] = $this->divedFunc($register_keep2_number, $register_add_number, 2);
            //付费次留率
            $keyDataFormatter[$keyword]['charge_keep2_rate'] = $this->divedFunc($charge_keep2_number, $register_add_number, 2);
        }

        $register_number_sort = $keyDataFormatter;
        $charge_number_sort = $keyDataFormatter;
        $sum_amount_sort = $keyDataFormatter;

        array_multisort(array_column($register_number_sort, "register_add"), SORT_DESC, $register_number_sort);
        array_multisort(array_column($charge_number_sort, "sum_charge_user_number"), SORT_DESC, $charge_number_sort);
        array_multisort(array_column($sum_amount_sort, "sum_charge_amount"), SORT_DESC, $sum_amount_sort);

        $register_number_sort_top = array_slice($register_number_sort, 0, 10);
        $charge_number_sort_top = array_slice($charge_number_sort, 0, 10);
        $sum_amount_sort_top = array_slice($sum_amount_sort, 0, 10);

        View::assign('token', $this->request->param('token'));
        View::assign('date_b', $begin_date);
        View::assign('date_e', $end_date);
        View::assign('summary', $returnDataFormatter);
        View::assign('top_by_register', $register_number_sort_top);
        View::assign('top_by_charge', $charge_number_sort_top);
        View::assign('top_by_amount', $sum_amount_sort_top);
        View::assign('date_r', $date_r);
        View::assign('asatype', $asatype);
        return View::fetch('chart/asasummary');
    }

    private function _asacommon($begin_date, $end_date, $keywordid = 0, $asatype = 'appstore')
    {

        $register_keep_count = 0; //新增次留总人数
        $charge_keep_count = 0; //新增充值次留总人数
        $register_total = 0; //注册总人数
        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($begin_date, $end_date);

        $register_keep_uids_temp = Db::name('bi_user_keep_day')->where('date', '>=', $begin_date)->where('date', '<', $end_date)->where('type', '=', "register")
            ->column('keep_2', 'date');
        $charge_keep_uids_temp = Db::name('bi_user_keep_day')->where('date', '>=', $begin_date)->where('date', '<', $end_date)->where('type', 'in', "charge")
            ->column('keep_2', 'date');

        foreach ($dateList as $node) {
            if ($keywordid > 0) {
                $uids = BiAsaUserModel::getInstance()->getModel()->where('date', '=', $node)
                    ->where('source', '=', $asatype)
                    ->where('iad_keyword_id', '=', $keywordid)
                    ->select()->toArray();
            } else {
                $uids = BiAsaUserModel::getInstance()->getModel()->where('date', '=', $node)
                    ->where('source', '=', $asatype)
                    ->select()->toArray();
            }

            $register_user = array_column($uids, "uid");
            $register_total += count($register_user);

            if (isset($register_keep_uids_temp[$node]) && $register_keep_uids_temp[$node]) {
                $register_keep_uids_arr = explode(",", $register_keep_uids_temp[$node]);
                $register_keep_count += count(array_intersect($register_user, $register_keep_uids_arr));
            }

            if (isset($charge_keep_uids_temp[$node]) && $charge_keep_uids_temp[$node]) {
                $charge_keep_uids_arr = explode(",", $charge_keep_uids_temp[$node]);
                $charge_keep_count += count(array_intersect($register_user, $charge_keep_uids_arr));
            }

        }

        $condition = [];
        $condition[] = ["date", ">=", $begin_date];
        $condition[] = ["date", "<", $end_date];
        $condition[] = ["source", "=", $asatype];
        if ($keywordid > 0) {
            $condition[] = ["iad_keyword_id", "=", $keywordid];
        }

        $buildSql = BiAsaUserModel::getInstance()->getModel()->field('uid')
            ->where($condition)
            ->buildSql(true);

        //累计新增充值用户(这段时间注册并且充值到当前的用户)
        $sumchargeRegisterUserNumber = BiDaysUserChargeModel::getInstance()->getModel()->where("date", ">=", $begin_date)
            ->where("uid", "exp", "in" . $buildSql)
            ->count('distinct uid');
        //累计充值金额（这段时间内注册并且充值到当前的金额
        $sumchargeAmount = BiDaysUserChargeModel::getInstance()->getModel()->where("date", ">=", $begin_date)
            ->where("uid", "exp", "in" . $buildSql)
            ->sum('amount');
        $sumchargeMoney = $sumchargeAmount / 10;

        //这段时间内充值的用户量
        $chargeRegisterUserNumber = BiDaysUserChargeModel::getInstance()->getModel()->where("date", ">=", $begin_date)
            ->where('date', '<', $end_date)
            ->where("uid", "exp", "in" . $buildSql)
            ->count('distinct uid');

        /*
        新增用户     每日计算
        累计新增充值用户(这段时间注册并且充值到当前的用户)
        累计充值金额（这段时间内注册并且充值到当前的金额
        新增次留(每日次留累计)  每日次留用户
        付费次留(每日次留累计)     每日次留用户
        新增充值率(新增的充值用户/新增用户)
        新增次留率(每日新增用户 在第二日的活跃)
        付费次留率(每日付费用户 在第二日的活跃)
         */
        return [
            "register_add" => $register_total, //注册总人数
            'sum_charge_user_number' => $sumchargeRegisterUserNumber, //累计充值总人数
            'sum_charge_amount' => $sumchargeMoney, //累计充值总金额
            'register_keep2' => $register_keep_count, //注册次留总人数
            'charge_keep2' => $charge_keep_count, //充值次留总人数
            'register_charge_rate' => $this->divedFunc($chargeRegisterUserNumber, $register_total), //新增充值率
            'register_keep2_rate' => $this->divedFunc($register_keep_count, $register_total), //新增次留率
            'charge_keep2_rate' => $this->divedFunc($charge_keep_count, $register_total), //付费次留率
        ];

    }

    public function array_merge_method($arr1, $arr2)
    {
        if ($arr2) {
            foreach ($arr2 as $item) {
                if (!in_array($item, $arr1)) {
                    array_push($arr1, $item);
                }
            }
        }
        return $arr1;
    }

}
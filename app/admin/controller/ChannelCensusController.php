<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\admin\script\analysis\CalCulateStats;
use app\admin\service\ExportExcelService;
use app\common\RedisCommon;
use think\facade\Db;
use think\facade\View;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);
class ChannelCensusController extends AdminBaseController
{
    public function channel_three($id)
    {
        $list = MarketChannelModel::getInstance()->getModel()->where('pid', $id)->where('channel_level', '>', 0)->field('id,channel_name')->select()->toArray();
        return $list;
    }

    public function Three()
    {
        $id = $this->request->param('id');
        $attiretype = MarketChannelModel::getInstance()->getModel()->where('pid', $id)->select()->toArray();
        foreach ($attiretype as $k => $v) {
            $type[] = $v;
        }
        return json_encode($type);
    }

    /**
     * 渠道新增统计
     * @return mixed
     */
    public function channelAddCensus()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    /**
     * 归属查询
     */
    public function channeAffiliation()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    /**
     * 主播查询
     * anchor_id
     */
    public function channeAnchor()
    {
        return View::fetch('channel/anchorindex');
    }

    /**
     * 房间查询
     * anchor_id
     */
    public function channeRoom()
    {
        return View::fetch('channel/roomindex');
    }

    /**
     * 房间绑定主播统计
     * anchor_id
     */
    public function channeAnchorFigure()
    {
        return View::fetch('channel/AnchorFigureindex');
    }

    /**
     * 消费统计
     */
    public function Statistics()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    /**
     * 消费明细
     */
    public function Statement()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    public function getGiftConf()
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $data = json_decode($redis->get('gift_conf'), true);
        foreach ($data as $k => $v) {
            $v['id'] = $v['giftId'];
            $v['gift_name'] = $v['name'];
            $v['gift_coin'] = $v['price']['count'];
            $rsc[$v['giftId']] = $v;
        }
        return $rsc;
    }

    /**
     * 获取用户id
     * @param $invitcode
     * @return string
     */
    public function userId($invitcode)
    {
        $userid = [];
        $uid = MemberModel::getInstance()->getWhereAllData([["invitcode", "in", $invitcode]], 'id');
        foreach ($uid as $k => $v) {
            $userid[] = $v['id'];
        }
        return implode(",", $userid);
    }

    /**
     * 渠道分析
     */
    public function StatAot()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    /**
     * 渠道分析
     */
    public function channelStats()
    {
        $channel = $this->channelId; //渠道id
        $channel_level = $this->channel_level; //渠道等级
        $level_1 = (int) $this->request->param('level_1', 0);
        $level_2 = (int) $this->request->param('level_2', 0);
        $level_3 = (int) $this->request->param('level_3', 0);
        $daochu = (int) $this->request->param('daochu', 0);

        //默认当日的时间
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        $pageNum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pageNum;

        $channel_one = [];
        $channel_two = [];
        $channel_sub = [];

        $channel_list = $this->channel_three($channel);
        if ($channel_level == 0) {
            $channel_one = $channel_list;
        }

        if ($channel_level == 1) {
            $channel_two = $channel_list;
        }

        if ($channel_level == 2) {
            $channel_sub = $channel_list;
        }

        if ($level_1) {
            $channel_two = $this->channel_three($level_1);
        }

        if ($level_2) {
            $channel_sub = $this->channel_three($level_2);
        }

        $where[] = ['status', '=', 1];
        $where[] = ['id', '>', 1];

        if ($level_1) {
            $where[] = ['one_level', '=', $level_1];
        }
        if ($level_2 && $channel_level < 2) {
            $where[] = ['two_level', '=', $level_2];
        }
        if ($level_3 && $channel_level < 3) {
            $where[] = ['three_level', '=', $level_3];
        }

        $list = MarketChannelModel::getInstance()->getModel();

        if ($channel_level == 1 && $level_2) {
            $where[] = ['two_level', '=', $level_2];
        }
        if ($channel_level == 3) {
            $where[] = ['three_level', '=', $channel];
        }
        if ($channel_level == 2) {
            $where[] = ['two_level', '=', $channel];
        }
        if ($channel_level == 1) {
            $where[] = ['one_level', '=', $channel];
        }

        $list = $list->where($where);
        $clone = clone $list;

        $count = $clone->count();
        $total_list = $clone->select()->toArray();

        if ($daochu == 0) {
            $list = $list->limit($page, $pageNum);
        }

        $list = $list->select()->toArray();

        $channel_map = MarketChannelModel::getInstance()->getModel()->column('channel_name', 'id');

        $data = [];
        if ($list) {
            $data = $this->getChannelStats($list, $channel_map, $start, $end);
        }

        //总计数据
        if ($total_list) {
            $total_data = $this->getChannelStats($total_list, $channel_map, $start, $end);
        }

        $total_reg_pay_amount = 0;
        $total_reg_pay_count = 0;
        $history_reg_pay_amount = 0;
        $history_reg_pay_count = 0;
        $reg_pay_amount = 0;
        $reg_pay_count = 0;

        if (!empty($total_data)) {
            $total_reg_pay_amount = array_sum(array_column($total_data, 'total_reg_pay_amount'));
            $total_reg_pay_count = array_sum(array_column($total_data, 'total_reg_pay_count'));
            $history_reg_pay_amount = array_sum(array_column($total_data, 'history_reg_pay_amount'));
            $history_reg_pay_count = array_sum(array_column($total_data, 'history_reg_pay_count'));
            $reg_pay_amount = array_sum(array_column($total_data, 'reg_pay_amount'));
            $reg_pay_count = array_sum(array_column($total_data, 'reg_pay_count'));
        }
        View::assign('total_reg_pay_amount', $total_reg_pay_amount);
        View::assign('total_reg_pay_count', $total_reg_pay_count);
        View::assign('history_reg_pay_amount', $history_reg_pay_amount);
        View::assign('history_reg_pay_count', $history_reg_pay_count);
        View::assign('reg_pay_amount', $reg_pay_amount);
        View::assign('reg_pay_count', $reg_pay_count);
        if ($daochu == 1) {
            $columns = [
                'time' => '统计日期',
                'channel_one' => '一级渠道',
                'channel_two' => '二级渠道',
                'channel_three' => '三级渠道',
                'today_login_count' => '登录账号',
                'reg_login_count' => '注册账号',
                'today_pay_count' => '充值人数',
                'today_pay_amount' => '充值金额',
                'today_panel_amount' => '直送',
                'today_bag_amount' => '包送',
                'total_reg_panel_amount' => '新增直送',
                'reg_bag_amount' => '新增包送',
                'total_reg_pay_count' => '新增充值人数',
                'total_reg_pay_amount' => '新增值金额',
                'reg_pay_count' => '新增累计充值人数',
                'reg_pay_amount' => '新增累计充值金额',
                'today_consume_rate' => '充值率',
                'reg_consume_rate' => '新增充值率',
                'today_pay_arpu' => '充值ARPU',
                'reg_pay_arpu' => '新增充值ARPU',
                'login_arpu' => '登录ARPU',

            ];
            ExportExcelService::getInstance()->export($data, $columns);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pageNum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('level_3', $level_3);
        View::assign('level_2', $level_2);
        View::assign('level_1', $level_1);
        View::assign('channel_level', $channel_level);
        View::assign('channel_sub', $channel_sub);
        View::assign('channel_two', $channel_two);
        View::assign('channel_one', $channel_one);
        View::assign('demo', $demo);
        return View::fetch('channel/channelStats');
    }

    public function getChannelStats($list, $channel_map, $start, $end)
    {
        $data = [];
        //获取渠道数据
        $channel_data = $this->sumChannelStats($list, $start, $end);
        $channel_day_data = $this->getChannelConsumeData($list, $start, $end);
        $channel_history_data = $this->sumHistoryChannelStats($list, $start, $end);
        foreach ($list as $k => $v) {
            $channel_id = $v['id'];
            $data[$k]['time'] = $start;

            $data[$k]['channel_one'] = $channel_map[$v['one_level']] ?? '--';
            $data[$k]['channel_two'] = $channel_map[$v['two_level']] ?? '--';
            $data[$k]['channel_three'] = $channel_map[$v['three_level']] ?? '--';
            $data[$k]['today_login_count'] = 0;
            $data[$k]['today_pay_amount'] = 0;
            $data[$k]['today_pay_users'] = [];
            $data[$k]['today_pay_count'] = 0;
            $data[$k]['today_panel_amount'] = 0;
            $data[$k]['today_bag_amount'] = 0;
            $data[$k]['reg_login_count'] = 0;
            $data[$k]['reg_login_users'] = [];
            $data[$k]['reg_pay_amount'] = 0;
            $data[$k]['reg_pay_users'] = [];
            $data[$k]['reg_pay_count'] = 0;
            $data[$k]['reg_panel_amount'] = 0;
            $data[$k]['reg_bag_amount'] = 0;

            $data[$k]['total_today_pay_amount'] = 0;
            $data[$k]['total_today_pay_users'] = [];
            $data[$k]['total_today_pay_count'] = 0;
            $data[$k]['total_today_panel_amount'] = 0;
            $data[$k]['total_today_bag_amount'] = 0;

            $data[$k]['total_reg_pay_amount'] = 0;
            $data[$k]['total_reg_pay_users'] = [];
            $data[$k]['total_reg_pay_count'] = 0;
            $data[$k]['total_reg_panel_amount'] = 0;
            $data[$k]['total_reg_bag_amount'] = 0;

            $data[$k]['today_consume_rate'] = 0;
            $data[$k]['reg_consume_rate'] = 0;
            $data[$k]['today_pay_arpu'] = 0;
            $data[$k]['reg_pay_arpu'] = 0;
            $data[$k]['login_arpu'] = 0;
            $data[$k]['total_reg_pay_amount'] = 0;
            $data[$k]['total_pay_count'] = 0;

            //至今充值
            if (isset($channel_history_data[$channel_id])) {
                $total_channel_stats = $channel_history_data[$channel_id];
                $data[$k]['history_reg_pay_amount'] = $total_channel_stats['reg_pay_amount'];

                $reg_pay_users = array_values(array_unique(array_filter(array_map('intval', explode(',', $total_channel_stats['reg_pay_users'])))));
                $channel_stats['reg_pay_users'] = $reg_pay_users;
                $channel_stats['reg_pay_count'] = count($reg_pay_users);
                $data[$k]['history_reg_pay_count'] = $channel_stats['reg_pay_count'];
            }

            if (isset($channel_data[$channel_id])) {
                $channel_stats = $channel_data[$channel_id];

                $reg_pay_users = array_values(array_unique(array_filter(array_map('intval', explode(',', $channel_stats['reg_pay_users'])))));
                $channel_stats['reg_pay_users'] = $reg_pay_users;
                $channel_stats['reg_pay_count'] = count($reg_pay_users);

                $today_pay_users = array_values(array_unique(array_filter(array_map('intval', explode(',', $channel_stats['today_pay_users'])))));
                $channel_stats['today_pay_users'] = $today_pay_users;
                $channel_stats['today_pay_count'] = count($today_pay_users);

                $today_login_users = array_values(array_unique(array_filter(array_map('intval', explode(',', $channel_stats['today_login_users'])))));
                $channel_stats['today_login_users'] = $today_login_users;
                $channel_stats['today_login_count'] = count($today_login_users);

                $reg_login_users = array_values(array_unique(array_filter(array_map('intval', explode(',', $channel_stats['reg_login_users'])))));
                $channel_stats['reg_login_users'] = $reg_login_users;
                $channel_stats['reg_login_count'] = count($reg_login_users);

                $data[$k]['today_login_count'] = $channel_stats['today_login_count'];
                $data[$k]['today_pay_amount'] = $channel_stats['today_pay_amount'];
                $data[$k]['today_pay_users'] = $channel_stats['today_pay_users'];
                $data[$k]['today_pay_count'] = $channel_stats['today_pay_count'];
                $data[$k]['today_panel_amount'] = $channel_stats['today_panel_amount'];
                $data[$k]['today_bag_amount'] = $channel_stats['today_bag_amount'];

                $data[$k]['reg_login_count'] = $channel_stats['reg_login_count'];
                $data[$k]['reg_login_users'] = $channel_stats['reg_login_users'];
                $data[$k]['reg_pay_amount'] = $channel_stats['reg_pay_amount'];
                $data[$k]['reg_pay_users'] = $channel_stats['reg_pay_users'];
                $data[$k]['reg_pay_count'] = $channel_stats['reg_pay_count'];
                $data[$k]['reg_panel_amount'] = $channel_stats['reg_panel_amount'];
                $data[$k]['reg_bag_amount'] = $channel_stats['reg_bag_amount'];
            }

            if (isset($channel_day_data[$channel_id])) {
                $channel_day_stats = $channel_day_data[$channel_id];
                $today_pay_users = array_values(array_unique(array_filter(array_map('intval', explode(',', $channel_day_stats['today_pay_users'])))));
                $channel_day_stats['today_pay_users'] = $today_pay_users;
                $channel_day_stats['today_pay_count'] = count($today_pay_users);

                $reg_pay_users = array_values(array_unique(array_filter(array_map('intval', explode(',', $channel_day_stats['reg_pay_users'])))));
                $channel_day_stats['reg_pay_users'] = $reg_pay_users;
                $channel_day_stats['reg_pay_count'] = count($reg_pay_users);

                $data[$k]['total_today_pay_amount'] = $channel_day_stats['today_pay_amount'];
                $data[$k]['total_today_pay_users'] = $channel_day_stats['today_pay_users'];
                $data[$k]['total_today_pay_count'] = $channel_day_stats['today_pay_count'];
                $data[$k]['total_today_panel_amount'] = $channel_day_stats['today_panel_amount'];
                $data[$k]['total_today_bag_amount'] = $channel_day_stats['today_bag_amount'];

                $data[$k]['total_reg_pay_amount'] = $channel_day_stats['reg_pay_amount'];
                $data[$k]['total_reg_pay_users'] = $channel_day_stats['reg_pay_users'];
                $data[$k]['total_reg_pay_count'] = $channel_day_stats['reg_pay_count'];
                $data[$k]['total_reg_panel_amount'] = $channel_day_stats['reg_panel_amount'];
                $data[$k]['total_reg_bag_amount'] = $channel_day_stats['reg_bag_amount'];
            }

            $today_consume_rate = $reg_consume_rate = $today_pay_arpu = $reg_pay_arpu = $login_arpu = 0;
            //消费率
            if ($data[$k]['today_login_count'] > 0) {
                $today_consume_rate = round($data[$k]['today_pay_count'] / $data[$k]['today_login_count'] * 100, 2);
            }
            //新消费率
            if ($data[$k]['reg_login_count'] > 0) {
                $reg_consume_rate = round($data[$k]['reg_pay_count'] / $data[$k]['reg_login_count'] * 100, 2);
            }

            //消费ARPU
            if ($data[$k]['today_pay_count'] > 0) {
                $today_pay_arpu = round($data[$k]['today_pay_amount'] / $data[$k]['today_pay_count'], 2);
            }

            //新消费ARPU
            if ($data[$k]['reg_pay_count'] > 0) {
                $reg_pay_arpu = round($data[$k]['total_reg_pay_amount'] / $data[$k]['reg_pay_count'], 2);
            }

            //登陆ARPU
            if ($data[$k]['today_pay_count'] > 0) {
                $login_arpu = round($data[$k]['today_login_count'] / $data[$k]['today_pay_count'], 2);
            }

            $data[$k]['today_consume_rate'] = $today_consume_rate;
            $data[$k]['reg_consume_rate'] = $reg_consume_rate;
            $data[$k]['today_pay_arpu'] = $today_pay_arpu;
            $data[$k]['reg_pay_arpu'] = $reg_pay_arpu;
            $data[$k]['login_arpu'] = $login_arpu;
        }

        return $data;
    }

    public function sumChannelStats($list, $start, $end)
    {
        $channelIds = array_column($list, 'id');
        Db::execute('SET SESSION group_concat_max_len = 1024000');

        //获取渠道数据
        $channel_data = Db::table('bi_days_market_channel_stats_by_invitcode')
            ->field("promote_channel,sum(today_pay_amount) today_pay_amount,group_concat(today_login_users,',') today_login_users,group_concat(today_pay_users,',') today_pay_users,sum(today_panel_amount) today_panel_amount,sum(reg_pay_count) reg_pay_count,sum(today_bag_amount) today_bag_amount,sum(reg_login_count) reg_login_count,sum(reg_pay_amount) reg_pay_amount,group_concat(reg_pay_users,',') reg_pay_users,group_concat(reg_login_users,',') reg_login_users,sum(reg_panel_amount) reg_panel_amount,sum(reg_bag_amount) reg_bag_amount")
            ->where('date', '>=', $start)
            ->where('date', '<', $end)
            ->where('retention_date', '>=', $start)
            ->where('retention_date', '<', $end)
            ->where('promote_channel', 'in', $channelIds)
            ->group('promote_channel')
            ->select()
            ->toArray();

        return array_column($channel_data, null, 'promote_channel');
    }

    public function sumHistoryChannelStats($list, $start, $end)
    {
        $channelIds = array_column($list, 'id');
        Db::execute('SET SESSION group_concat_max_len = 1024000');

        //获取渠道数据
        $channel_data = Db::table('bi_days_market_channel_stats_by_invitcode')
            ->field("promote_channel,sum(today_pay_amount) today_pay_amount,group_concat(today_login_users,',') today_login_users,group_concat(today_pay_users,',') today_pay_users,sum(today_panel_amount) today_panel_amount,sum(reg_pay_count) reg_pay_count,sum(today_bag_amount) today_bag_amount,sum(reg_login_count) reg_login_count,sum(reg_pay_amount) reg_pay_amount,group_concat(reg_pay_users,',') reg_pay_users,group_concat(reg_login_users,',') reg_login_users,sum(reg_panel_amount) reg_panel_amount,sum(reg_bag_amount) reg_bag_amount")
            ->where('date', '>=', $start)
            ->where('date', '<', $end)
            ->where('retention_date', '>=', $start)
            ->where('promote_channel', 'in', $channelIds)
            ->group('promote_channel')
            ->select()
            ->toArray();

        return array_column($channel_data, null, 'promote_channel');
    }

    public function getChannelConsumeData($list, $start, $end)
    {
        $channelIds = array_column($list, 'id');
        //新增数据累计
        Db::execute('SET SESSION group_concat_max_len = 1024000');
        $channel_data = Db::table('bi_days_market_channel_stats_by_invitcode')
            ->field("promote_channel,sum(today_pay_amount) today_pay_amount,group_concat(today_pay_users,',') today_pay_users,sum(today_panel_amount) today_panel_amount,sum(today_bag_amount) today_bag_amount,sum(reg_pay_amount) reg_pay_amount,group_concat(reg_pay_users,',') reg_pay_users,sum(reg_panel_amount) reg_panel_amount,sum(reg_bag_amount) reg_bag_amount")
            ->where('date', '>=', $start)
            ->where('date', '<', $end)
            ->where('retention_date', '>=', $start)
            ->where('retention_date', '<', $end)
            ->where('date = retention_date')
            ->where('promote_channel', 'in', $channelIds)
            ->group('promote_channel')
        // ->fetchSql(true)
            ->select()
            ->toArray();
        return array_column($channel_data, null, 'promote_channel');
    }

    public function getChannelData($list, $channel_map, $start, $end, $channel_user_datas, $newuser_datas_total, $newuser_datas_day)
    {
        $data = [];
        foreach ($list as $k => $v) {
            $channel_id = $v['id'];
            $data[$k]['time'] = $start;

            $data[$k]['channel_one'] = $channel_map[$v['one_level']] ?? '--';
            $data[$k]['channel_two'] = $channel_map[$v['two_level']] ?? '--';
            $data[$k]['channel_three'] = $channel_map[$v['three_level']] ?? '--';

            $AddPayArpu =
            $AddPayUserArpu =
            $LogARPU =
            $PayTro =
            $AddPayTro =
            $giftCoin =
            $packgift_Coin =
            $AddUserPay =
            $AddPaySum =
            $AddPayCount2 =
            $AddPaySum2 =
            $LogUser =
            $Payuser =
            $PaySum =
            $reg_gift_coin =
            $reg_packgift_coin =
            $AddUser = 0;

            $reg_users = $new_pay_users = $total_pay_users = [];
            //新增用户数据
            if (isset($newuser_datas_total[$channel_id])) {
                $reg_json = $newuser_datas_total[$channel_id];

                //当前邀请码新增注册的用户ID
                if (isset($reg_json['register'])) {
                    $channel_reg_user = $reg_json['register']['users'];
                    $reg_users = array_unique($channel_reg_user);
                    //新增用户注册人数
                    $AddUser = count($reg_users);
                }

                //新增用户充值总金额
                $today_reg_pay_sum = CalCulateStats::getInstance()->getChargeSum($reg_json, 'charge');

                //新增用户充值人数
                $today_reg_pay_users = CalCulateStats::getInstance()->getAgentChargeUsers($reg_json, 'charge');

                //新增代充总金额
                $today_reg_agent_pay_sum = CalCulateStats::getInstance()->getAgentChargeSum($reg_json, 'agentcharge');

                //新增代充人数
                $today_reg_agent_pay_users = CalCulateStats::getInstance()->getAgentChargeUsers($reg_json, 'agentcharge');

                //新增充值人数
                $new_pay_users = array_unique(array_merge($today_reg_pay_users, $today_reg_agent_pay_users));
                $AddUserPay = count($new_pay_users);

                $AddPaySum = $today_reg_agent_pay_sum + $today_reg_pay_sum;

                $regGiftData = CalCulateStats::getInstance()->getGiftData($reg_json, 'sendGift');

                list($reg_gift_coin, $reg_packgift_coin) = [$regGiftData['panel_amount'], $regGiftData['bag_amount']];
            }

            //新增用户数据
            if (isset($newuser_datas_day[$channel_id])) {
                $reg_json2 = $newuser_datas_day[$channel_id];

                //当前邀请码新增注册的用户ID
                if (isset($reg_json2['register'])) {
                    $channel_reg_user2 = $reg_json2['register']['users'];
                    $reg_users2 = array_unique($channel_reg_user2);
                    //新增用户注册人数
                    $AddUser = count($reg_users2);
                }

                //新增用户充值总金额
                $today_reg_pay_sum_2 = CalCulateStats::getInstance()->getChargeSum($reg_json2, 'charge');

                //新增代充总金额
                $today_reg_agent_pay_sum_2 = CalCulateStats::getInstance()->getAgentChargeSum($reg_json2, 'agentcharge');

                //新增用户充值人数
                $today_reg_pay_users = CalCulateStats::getInstance()->getAgentChargeUsers($reg_json2, 'charge');
                //新增代充人数
                $today_reg_agent_pay_users = CalCulateStats::getInstance()->getAgentChargeUsers($reg_json2, 'agentcharge');

                //新增充值人数
                $new_pay_users = array_unique(array_merge($today_reg_pay_users, $today_reg_agent_pay_users));
                $AddPayCount2 = count($new_pay_users);

                $AddPaySum2 = $today_reg_agent_pay_sum_2 + $today_reg_pay_sum_2;

            }

            if (isset($channel_user_datas[$channel_id])) {
                $total_json = $channel_user_datas[$channel_id];

                //当前邀请码新增注册的用户ID
                $active_users = $total_json['active_users'];

                $LogUser = count(array_unique($active_users));

                //充值总金额
                $pay_sum = CalCulateStats::getInstance()->getChargeSum($total_json, 'charge');

                //充值人数
                $pay_users = CalCulateStats::getInstance()->getAgentChargeUsers($total_json, 'charge');

                //代充总金额
                $agent_pay_sum = CalCulateStats::getInstance()->getAgentChargeSum($total_json, 'agentcharge');

                //代充人数
                $agent_pay_users = CalCulateStats::getInstance()->getAgentChargeUsers($total_json, 'agentcharge');

                //充值人数
                $total_pay_users = array_unique(array_merge($pay_users, $agent_pay_users));
                $Payuser = count($total_pay_users);

                $PaySum = $agent_pay_sum + $pay_sum;

                $giftData = CalCulateStats::getInstance()->getGiftData($total_json, 'sendGift');

                list($giftCoin, $packgift_Coin) = [$giftData['panel_amount'], $giftData['bag_amount']];
            }

            if ($PaySum > 0 && $Payuser > 0) { //消费ARPU
                $AddPayArpu = round($PaySum / $Payuser, 2);
            }
            if ($AddPaySum > 0 && $AddUserPay > 0) {
                $AddPayUserArpu = round($AddPaySum / $AddUserPay, 2);
            }

            if ($Payuser > 0 && $LogUser > 0) {
                $LogARPU = round($LogUser / $Payuser, 2);
            }

            if ($Payuser > 0 && $LogUser > 0) {
                $PayTro = round(($Payuser / $LogUser) * 100, 2);
            }

            if ($AddUser > 0 && $AddUserPay > 0) {
                $AddPayTro = round(($AddUserPay / $AddUser) * 100, 2);
            }

            $data[$k]['giftCoin'] = $giftCoin;
            $data[$k]['packgift_Coin'] = $packgift_Coin;
            $data[$k]['PaySum'] = $PaySum;
            $data[$k]['LogUser'] = $LogUser;
            $data[$k]['AddUser'] = $AddUser;
            $data[$k]['AddPaySum'] = $AddPaySum;
            $data[$k]['AddUserPay'] = $AddUserPay;
            $data[$k]['Payuser'] = $Payuser;
            $data[$k]['giftCoin'] = $giftCoin;
            $data[$k]['packgift_Coin'] = $packgift_Coin;
            $data[$k]['AddPayArpu'] = $AddPayArpu;
            $data[$k]['AddPayUserArpu'] = $AddPayUserArpu;
            $data[$k]['LogARPU'] = $LogARPU;
            $data[$k]['PayTro'] = $PayTro;
            $data[$k]['AddPayTro'] = $AddPayTro;
            $data[$k]['new_pay_users'] = $new_pay_users;
            $data[$k]['reg_users'] = $reg_users;
            $data[$k]['total_pay_users'] = $total_pay_users;
            $data[$k]['reg_gift_coin'] = $reg_gift_coin;
            $data[$k]['reg_packgift_coin'] = $reg_packgift_coin;
            $data[$k]['AddPaySum2'] = $AddPaySum2;
            $data[$k]['AddPayCount2'] = $AddPayCount2;
        }
        return $data;
    }

    public function _stataotDetailcsv($data)
    {
        $headerArray = ['统计日期', '一级渠道', '二级渠道', '三级渠道', '登录账号', '注册账号', '消费人数', '消费金额', '新用户消费人数', '新用户消费金额', '消费率', '新用户消费率', '消费ARPU', '新用户消费ARPU', '登录ARPU'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['time'] = $value['time']; //统计日期
            $outArray['channel_one'] = $value['channel_one']; //一级渠道
            $outArray['channel_two'] = $value['channel_two']; //二级渠道
            $outArray['channel_three'] = $value['channel_three']; //三级渠道
            $outArray['LogUser'] = $value['LogUser']; //登录账号
            $outArray['AddUser'] = $value['AddUser']; //注册账号
            $outArray['Payuser'] = $value['Payuser']; //消费账号
            $outArray['PaySum'] = $value['PaySum']; //消费金额
            $outArray['AddUserPay'] = $value['AddUserPay']; //新用户消费
            $outArray['AddPaySum'] = $value['AddPaySum']; //新用户消费金额
            $outArray['PayTro'] = $value['PayTro']; //消费率
            $outArray['AddPayTro'] = $value['AddPayTro']; //新用户消费率
            $outArray['AddPayArpu'] = $value['AddPayArpu']; //消费ARPU
            $outArray['AddPayUserArpu'] = $value['AddPayUserArpu']; //新用户消费ARPU
            $outArray['LogARPU'] = $value['LogARPU']; //登录ARPU
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = '渠道分析导出时间：' . date('Y-m-d H:i:s') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }
}
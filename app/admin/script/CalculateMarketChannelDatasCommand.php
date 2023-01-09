<?php
/**
 * 三人夺宝
 */

namespace app\admin\script;

use app\admin\model\BiDaysMarketChannelStateByInvitcodeModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\script\analysis\AnalysisCommon;
use app\admin\script\analysis\CalCulateFromDayData;
use app\admin\script\analysis\CalCulateStats;
use app\admin\script\analysis\InsertOrUpdateStats;
use app\common\ParseUserStateByUniqkey;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class CalculateMarketChannelDatasCommand extends Command
{
    protected $date;
    protected $cal_start_date;
    protected $time;

    const SECONDS_MINUTE_30 = 30 * 60;

    const SECONDS_MINUTE_5 = 5 * 60;
    const CALCULATE_NEXT_DAY = [3, 6]; //隔天统计昨天的数据
    const INTERVAL_TIME = [
        10 * 60,
        40 * 60,
    ];
    /**
     * 入库表
     */
    protected const TABLE_INSERT = 'bi_days_market_channel_stats_by_invitcode';

    protected $retention_map = [
        1 => 'pay_retention_sum_1',
        2 => 'pay_retention_sum_2',
        3 => 'pay_retention_sum_3',
        4 => 'pay_retention_sum_4',
        5 => 'pay_retention_sum_5',
        6 => 'pay_retention_sum_6',
        7 => 'pay_retention_sum_7',
        15 => 'pay_retention_sum_15',
        30 => 'pay_retention_sum_30',
    ];

    protected $retention_pay_map = [
        1 => 'pay_retention_count_1',
        2 => 'pay_retention_count_2',
        3 => 'pay_retention_count_3',
        4 => 'pay_retention_count_4',
        5 => 'pay_retention_count_5',
        6 => 'pay_retention_count_6',
        7 => 'pay_retention_count_7',
        15 => 'pay_retention_count_15',
        30 => 'pay_retention_count_30',
    ];

    protected $reg_retention_map = [
        1 => 'reg_retention_count_1',
        2 => 'reg_retention_count_2',
        3 => 'reg_retention_count_3',
        4 => 'reg_retention_count_4',
        5 => 'reg_retention_count_5',
        6 => 'reg_retention_count_6',
        7 => 'reg_retention_count_7',
        15 => 'reg_retention_count_15',
        30 => 'reg_retention_count_30',
    ];

    protected $reg_consume_map = [
        1 => 'reg_consume_count_1',
        2 => 'reg_consume_count_2',
        3 => 'reg_consume_count_3',
        4 => 'reg_consume_count_4',
        5 => 'reg_consume_count_5',
        6 => 'reg_consume_count_6',
        7 => 'reg_consume_count_7',
        15 => 'reg_consume_count_15',
        30 => 'reg_consume_count_30',
    ];

    protected function configure()
    {
        $this->setName('CalculateMarketChannelDatasCommand')
            ->setDescription('CalculateMarketChannelDatasCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '')
            ->addArgument('is_today', Argument::OPTIONAL, "is_today", 1);

        $this->date = date("Y-m-d");
        $this->time = date("Y-m-d H:i:s");
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $start = trim($input->getArgument('start'));
        $end = trim($input->getArgument('end'));
        $is_today = trim($input->getArgument('is_today')); //is_today=0只统计历史数据

        list($start, $end, $days) = AnalysisCommon::getInstance()->getStartEndDate($start, $end, [3, 6]);

        //设置统计开始日期
        $this->cal_start_date = $start;

        for ($i = 0; $i < $days; $i++) {
            $start_date = date("Y-m-d", strtotime("{$start} + {$i}days"));
            $end_date = date("Y-m-d", strtotime("{$start_date} + 1days"));
            echo "开始日期：{$start_date}-----结束日期：{$end_date}" . PHP_EOL;
            $this->date = $start_date;

            list($start_memory, $start_time) = array(memory_get_usage(), microtime(true));

            if (!empty($is_today)) {
                $this->dealTodayPromoteData($start_date, $end_date, $end);
            }

            if ($days > 1) {
                $history_start_date = $this->cal_start_date;
                $history_end_date = $this->date;
            } else {
                $history_start_date = date("Y-m-d", strtotime("$start - 120 day"));
                $history_end_date = $start;
            }

            $this->dealHistoryPromoteData($start_date, $end_date, $history_start_date, $history_end_date);

            list($end_memory, $end_time) = array(memory_get_usage(), microtime(true));

            $string = sprintf("内存占用: %f MB , 耗时： %.3f秒", (($end_memory - $start_memory) / 1024 / 1024), $end_time - $start_time);
            echo "统计{$start_date}日数据：======>" . $string . PHP_EOL;
        }
        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;
    }

    protected function calPromotionRetentionData($start, $end, $channel_data, $type)
    {
        $promote_channel = $channel_data['promote_channel'];
        $retention_date = $channel_data['date'];

        if (!empty($channel_data['reg_login_users']) && $start > $retention_date) {
            $users = explode(',', $channel_data['reg_login_users']);
            $users_data = $this->dealRetentionData($start, $end, $promote_channel, $users, $type);
            $reg_data = $this->getChannelData($start, $users_data);
            if (array_filter($reg_data)) {
                list(
                    $reg_login_users,
                    $reg_login_count,
                    $reg_normal_pay_amount,
                    $reg_normal_pay_users,
                    $reg_normal_pay_count,
                    $reg_agent_pay_amount,
                    $reg_agent_pay_users,
                    $reg_agent_pay_count,
                    $reg_pay_users,
                    $reg_pay_count,
                    $reg_pay_amount,
                    $reg_panel_amount,
                    $reg_bag_amount,
                    $reg_consume_users,
                    $reg_consume_count
                ) =
                    [
                    $reg_data['login_users'],
                    $reg_data['login_count'],
                    $reg_data['normal_pay_amount'],
                    $reg_data['normal_pay_users'],
                    $reg_data['normal_pay_count'],
                    $reg_data['agent_pay_amount'],
                    $reg_data['agent_pay_users'],
                    $reg_data['agent_pay_count'],
                    $reg_data['pay_users'],
                    $reg_data['pay_count'],
                    $reg_data['pay_amount'],
                    $reg_data['panel_amount'],
                    $reg_data['bag_amount'],
                    $reg_data['consume_users'],
                    $reg_data['consume_count'],
                ];

                $insert_data = [
                    'date' => $retention_date,
                    'retention_date' => $start,
                    'promote_channel' => $promote_channel,

                    'today_login_users' => 0,
                    'today_login_count' => 0,
                    'today_normal_pay_amount' => 0,
                    'today_normal_pay_users' => 0,
                    'today_normal_pay_count' => 0,
                    'today_agent_pay_amount' => 0,
                    'today_agent_pay_users' => 0,
                    'today_agent_pay_count' => 0,
                    'today_pay_amount' => 0,
                    'today_pay_users' => 0,
                    'today_pay_count' => 0,
                    'today_panel_amount' => 0,
                    'today_bag_amount' => 0,
                    'today_pay_rate' => 0,
                    'today_arpu' => 0,
                    'today_arppu' => 0,

                    'reg_login_users' => $reg_login_users,
                    'reg_login_count' => $reg_login_count,
                    'reg_normal_pay_amount' => $reg_normal_pay_amount,
                    'reg_normal_pay_users' => $reg_normal_pay_users,
                    'reg_normal_pay_count' => $reg_normal_pay_count,
                    'reg_agent_pay_amount' => $reg_agent_pay_amount,
                    'reg_agent_pay_users' => $reg_agent_pay_users,
                    'reg_agent_pay_count' => $reg_agent_pay_count,
                    'reg_pay_users' => $reg_pay_users,
                    'reg_pay_count' => $reg_pay_count,
                    'reg_pay_amount' => $reg_pay_amount,
                    'reg_panel_amount' => $reg_panel_amount,
                    'reg_bag_amount' => $reg_bag_amount,
                    'reg_consume_users' => $reg_consume_users,
                    'reg_consume_count' => $reg_consume_count,
                    'reg_pay_rate' => 0,
                    'reg_arpu' => 0,
                    'reg_arppu' => 0,

                    //充值金额留存
                    'pay_retention_sum_1' => 0,
                    'pay_retention_sum_2' => 0,
                    'pay_retention_sum_3' => 0,
                    'pay_retention_sum_4' => 0,
                    'pay_retention_sum_5' => 0,
                    'pay_retention_sum_6' => 0,
                    'pay_retention_sum_7' => 0,
                    'pay_retention_sum_15' => 0,
                    'pay_retention_sum_30' => 0,

                    //充值留存人数
                    'pay_retention_count_1' => 0,
                    'pay_retention_count_2' => 0,
                    'pay_retention_count_3' => 0,
                    'pay_retention_count_4' => 0,
                    'pay_retention_count_5' => 0,
                    'pay_retention_count_6' => 0,
                    'pay_retention_count_7' => 0,
                    'pay_retention_count_15' => 0,
                    'pay_retention_count_30' => 0,

                    //注册留存
                    'reg_retention_count_1' => 0,
                    'reg_retention_count_2' => 0,
                    'reg_retention_count_3' => 0,
                    'reg_retention_count_4' => 0,
                    'reg_retention_count_5' => 0,
                    'reg_retention_count_6' => 0,
                    'reg_retention_count_7' => 0,
                    'reg_retention_count_15' => 0,
                    'reg_retention_count_30' => 0,

                    //消费留存
                    'reg_consume_count_1' => 0,
                    'reg_consume_count_2' => 0,
                    'reg_consume_count_3' => 0,
                    'reg_consume_count_4' => 0,
                    'reg_consume_count_5' => 0,
                    'reg_consume_count_6' => 0,
                    'reg_consume_count_7' => 0,
                    'reg_consume_count_15' => 0,
                    'reg_consume_count_30' => 0,

                    'total_pay_amount' => 0,
                    'total_pay_count' => 0,
                    'total_normal_pay_amount' => 0,
                    'total_normal_pay_count' => 0,
                    'total_agent_pay_amount' => 0,
                    'total_agent_pay_count' => 0,
                    'total_panel_amount' => 0,
                    'total_bag_amount' => 0,
                ];
                 ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiDaysMarketChannelStateByInvitcodeModel::getInstance()->getModel(),
                     [$insert_data], ['date', 'promote_channel', 'retention_date','id']);
                //InsertOrUpdateStats::getInstance()->insertOrUpdate($insert_data, ['date', 'promote_channel', 'retention_date'], self::TABLE_INSERT);
            }
        }
    }

    /*
     * 处理历史总值和留存数据
     */
    protected function calPromotionHistoryData($start, $end, $channel_data, $type)
    {
        $promote_channel = $channel_data['promote_channel'];
        $retention_date = $channel_data['date'];

        if (!empty($channel_data['reg_login_users'])) {
            $users = explode(',', $channel_data['reg_login_users']);

            $users_data = $this->dealRetentionData($retention_date, $end, $promote_channel, $users, $type);

            //更新总数据
            $total_data = $this->getChannelData($promote_channel, $users_data);

            list(
                $total_normal_pay_amount,
                $total_normal_pay_users,
                $total_normal_pay_count,
                $total_agent_pay_amount,
                $total_agent_pay_users,
                $total_agent_pay_count,
                $total_pay_users,
                $total_pay_count,
                $total_pay_amount,
                $total_panel_amount,
                $total_bag_amount
            ) =
                [
                $total_data['normal_pay_amount'],
                $total_data['normal_pay_users'],
                $total_data['normal_pay_count'],
                $total_data['agent_pay_amount'],
                $total_data['agent_pay_users'],
                $total_data['agent_pay_count'],
                $total_data['pay_users'],
                $total_data['pay_count'],
                $total_data['pay_amount'],
                $total_data['panel_amount'],
                $total_data['bag_amount'],
            ];

            $updates = [];
            //充值金额累计
            $diff_day = AnalysisCommon::getDiffDays($end, $retention_date);
            if (in_array($diff_day, array_keys($this->retention_map))) {
                $retention_key = $this->retention_map[$diff_day];
                $updates[$retention_key] = $total_pay_amount;
            }

            $day_data = $this->dealRetentionData($start, $end, $promote_channel, $users, $type);
            //登陆人数
            $login_users = AnalysisCommon::getArrayKeyValue($day_data, 'active_users');
            $login_count = count(array_unique($login_users));

            //注册留存
            if (in_array($diff_day, array_keys($this->reg_retention_map))) {
                $retention_count_key = $this->reg_retention_map[$diff_day];
                $updates[$retention_count_key] = $login_count;
            }

            $pay_users = explode(',', $channel_data['reg_pay_users']);

            $day_data = $this->dealRetentionData($start, $end, $promote_channel, $pay_users, $type);
            //充值用户登陆人数
            $login_users = AnalysisCommon::getArrayKeyValue($day_data, 'active_users');
            $login_count = count(array_unique($login_users));

            //充值留存
            if (in_array($diff_day, array_keys($this->retention_pay_map))) {
                $retention_pay_count = $this->retention_pay_map[$diff_day];
                $updates[$retention_pay_count] = $login_count;
            }

            //消费留存
            $reg_consume_users = explode(',', $channel_data['reg_consume_users']);
            $day_data = $this->dealRetentionData($start, $end, $promote_channel, $reg_consume_users, $type);
            //充值用户登陆人数
            $consume_users = CalCulateStats::getInstance()->getAgentChargeUsers($day_data, 'sendGift');
            //消费用户人数
            $consume_count = count(array_unique(array_values($consume_users)));

            if (in_array($diff_day, array_keys($this->reg_consume_map))) {
                $retention_pay_count = $this->reg_consume_map[$diff_day];
                $updates[$retention_pay_count] = $consume_count;
            }

            $updates['total_pay_amount'] = $total_pay_amount;
            $updates['total_pay_count'] = $total_pay_count;
            $updates['total_normal_pay_amount'] = $total_normal_pay_amount;
            $updates['total_normal_pay_count'] = $total_normal_pay_count;
            $updates['total_agent_pay_amount'] = $total_agent_pay_amount;
            $updates['total_agent_pay_count'] = $total_agent_pay_count;

            BiDaysMarketChannelStateByInvitcodeModel::getInstance()->getModel()->where([
                'date' => $channel_data['date'],
                'retention_date' => $channel_data['retention_date'],
                'promote_channel' => $channel_data['promote_channel'],
            ])->update($updates);
        }
    }

    protected function dealRetentionData($start, $end, $column, $users, $type)
    {
        return CalCulateStats::getInstance()->dealDayStats($start, $end, $column, $type, false, $users);
    }

    protected function calPromotionDataFromJson($start, $end, $promote_channel)
    {
        try {
            //今日渠道总数据
            $day_data = $this->getTodayStats($start, $end, $promote_channel);
            $channel_data = $this->getChannelData($promote_channel, $day_data);

            list(
                $today_login_users,
                $today_login_count,
                $today_normal_pay_amount,
                $today_normal_pay_users,
                $today_normal_pay_count,
                $today_agent_pay_amount,
                $today_agent_pay_users,
                $today_agent_pay_count,
                $today_pay_users,
                $today_pay_count,
                $today_pay_amount,
                $today_panel_amount,
                $today_bag_amount,
                $today_consume_users,
                $today_consume_count,
                $today_pay_rate,
                $today_arpu,
                $today_arppu
            ) =
                [
                $channel_data['login_users'],
                $channel_data['login_count'],
                $channel_data['normal_pay_amount'],
                $channel_data['normal_pay_users'],
                $channel_data['normal_pay_count'],
                $channel_data['agent_pay_amount'],
                $channel_data['agent_pay_users'],
                $channel_data['agent_pay_count'],
                $channel_data['pay_users'],
                $channel_data['pay_count'],
                $channel_data['pay_amount'],
                $channel_data['panel_amount'],
                $channel_data['bag_amount'],
                $channel_data['consume_users'],
                $channel_data['consume_count'],
                $channel_data['pay_rate'],
                $channel_data['arpu'],
                $channel_data['arppu'],
            ];

            $today_reg_data = $this->getRegDayStats($start, $end, $promote_channel);
            $today_channel_data = $this->getChannelData($promote_channel, $today_reg_data);
            list(
                $reg_login_users,
                $reg_login_count,
                $reg_normal_pay_amount,
                $reg_normal_pay_users,
                $reg_normal_pay_count,
                $reg_agent_pay_amount,
                $reg_agent_pay_users,
                $reg_agent_pay_count,
                $reg_pay_users,
                $reg_pay_count,
                $reg_pay_amount,
                $reg_panel_amount,
                $reg_bag_amount,
                $reg_consume_users,
                $reg_consume_count,
                $reg_pay_rate,
                $reg_arpu,
                $reg_arppu
            ) =
                [
                $today_channel_data['login_users'],
                $today_channel_data['login_count'],
                $today_channel_data['normal_pay_amount'],
                $today_channel_data['normal_pay_users'],
                $today_channel_data['normal_pay_count'],
                $today_channel_data['agent_pay_amount'],
                $today_channel_data['agent_pay_users'],
                $today_channel_data['agent_pay_count'],
                $today_channel_data['pay_users'],
                $today_channel_data['pay_count'],
                $today_channel_data['pay_amount'],
                $today_channel_data['panel_amount'],
                $today_channel_data['bag_amount'],
                $today_channel_data['consume_users'],
                $today_channel_data['consume_count'],
                $today_channel_data['pay_rate'],
                $today_channel_data['arpu'],
                $today_channel_data['arppu'],
            ];

            $insert_data = [
                'date' => $start,
                'retention_date' => $start,
                'promote_channel' => $promote_channel,

                'today_login_users' => $today_login_users,
                'today_login_count' => $today_login_count,
                'today_normal_pay_amount' => $today_normal_pay_amount,
                'today_normal_pay_users' => $today_normal_pay_users,
                'today_normal_pay_count' => $today_normal_pay_count,
                'today_agent_pay_amount' => $today_agent_pay_amount,
                'today_agent_pay_users' => $today_agent_pay_users,
                'today_agent_pay_count' => $today_agent_pay_count,
                'today_pay_users' => $today_pay_users,
                'today_pay_count' => $today_pay_count,
                'today_pay_amount' => $today_pay_amount,
                'today_panel_amount' => $today_panel_amount,
                'today_bag_amount' => $today_bag_amount,
                'today_pay_rate' => $today_pay_rate,
                'today_arpu' => $today_arpu,
                'today_arppu' => $today_arppu,

                'reg_login_users' => $reg_login_users,
                'reg_login_count' => $reg_login_count,
                'reg_normal_pay_amount' => $reg_normal_pay_amount,
                'reg_normal_pay_users' => $reg_normal_pay_users,
                'reg_normal_pay_count' => $reg_normal_pay_count,
                'reg_agent_pay_amount' => $reg_agent_pay_amount,
                'reg_agent_pay_users' => $reg_agent_pay_users,
                'reg_agent_pay_count' => $reg_agent_pay_count,
                'reg_pay_amount' => $reg_pay_amount,
                'reg_pay_users' => $reg_pay_users,
                'reg_pay_count' => $reg_pay_count,
                'reg_panel_amount' => $reg_panel_amount,
                'reg_bag_amount' => $reg_bag_amount,
                'reg_consume_users' => $reg_consume_users,
                'reg_consume_count' => $reg_consume_count,
                'reg_pay_rate' => $reg_pay_rate,
                'reg_arpu' => $reg_arpu,
                'reg_arppu' => $reg_arppu,

                //充值金额留存
                'pay_retention_sum_1' => 0,
                'pay_retention_sum_2' => 0,
                'pay_retention_sum_3' => 0,
                'pay_retention_sum_4' => 0,
                'pay_retention_sum_5' => 0,
                'pay_retention_sum_6' => 0,
                'pay_retention_sum_7' => 0,
                'pay_retention_sum_15' => 0,
                'pay_retention_sum_30' => 0,

                //充值留存人数
                'pay_retention_count_1' => 0,
                'pay_retention_count_2' => 0,
                'pay_retention_count_3' => 0,
                'pay_retention_count_4' => 0,
                'pay_retention_count_5' => 0,
                'pay_retention_count_6' => 0,
                'pay_retention_count_7' => 0,
                'pay_retention_count_15' => 0,
                'pay_retention_count_30' => 0,

                //注册留存
                'reg_retention_count_1' => 0,
                'reg_retention_count_2' => 0,
                'reg_retention_count_3' => 0,
                'reg_retention_count_4' => 0,
                'reg_retention_count_5' => 0,
                'reg_retention_count_6' => 0,
                'reg_retention_count_7' => 0,
                'reg_retention_count_15' => 0,
                'reg_retention_count_30' => 0,

                //消费留存
                'reg_consume_count_1' => 0,
                'reg_consume_count_2' => 0,
                'reg_consume_count_3' => 0,
                'reg_consume_count_4' => 0,
                'reg_consume_count_5' => 0,
                'reg_consume_count_6' => 0,
                'reg_consume_count_7' => 0,
                'reg_consume_count_15' => 0,
                'reg_consume_count_30' => 0,

                'total_pay_amount' => 0,
                'total_pay_count' => 0,
                'total_normal_pay_amount' => 0,
                'total_normal_pay_count' => 0,
                'total_agent_pay_amount' => 0,
                'total_agent_pay_count' => 0,
                'total_panel_amount' => 0,
                'total_bag_amount' => 0,
            ];
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiDaysMarketChannelStateByInvitcodeModel::getInstance()->getModel(),
                [$insert_data],['date', 'promote_channel', 'retention_date','id']);
            //InsertOrUpdateStats::getInstance()->insertOrUpdate($insert_data, ['date', 'promote_channel', 'retention_date'], self::TABLE_INSERT);
        } catch (\Throwable $e) {
            Log::error(sprintf('VipHandler::calUserDiamond ex=%d:%s trace=%s', $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     *处理每日推广数据
     */
    protected function dealTodayPromoteData($start, $end)
    {
        $channelData = BiUserStats1DayModel::getInstance()->getModel()
            ->distinct(true)
            ->where('promote_channel', '>', 0)
            ->where('promote_channel', '<', 800000)
        // ->where('promote_channel', '=', 175)
            ->where('date', $start)
            ->column('promote_channel');

        foreach ($channelData as $channel) {
            $this->calPromotionDataFromJson($start, $end, $channel);
        }
    }

    /**
     *处理历史数据
     */
    protected function dealHistoryPromoteData($start, $end, $history_start_date, $history_end_date)
    {
        $history = BiDaysMarketChannelStateByInvitcodeModel::getInstance()->getModel()
            ->where('date', '>=', $history_start_date)
            ->where('date', '<=', $history_end_date);

        // ->where('promote_channel', '=', 175)
        $history = $history->where('date=retention_date')
        // ->fetchSql(true)
            ->select()
            ->toArray();
        if (!empty($history)) {
            foreach ($history as $channelData) {
                $this->calPromotionRetentionData($start, $end, $channelData, 1);
                $this->calPromotionHistoryData($start, $end, $channelData, 1);
            }
        }
    }

    public function getChannelData($channel_id, $channel_data)
    {
        $data = [
            'login_users' => '',
            'login_count' => 0,
            'normal_pay_amount' => 0,
            'normal_pay_users' => '',
            'normal_pay_count' => 0,
            'agent_pay_amount' => 0,
            'agent_pay_users' => '',
            'agent_pay_count' => 0,
            'pay_users' => '',
            'pay_count' => 0,
            'pay_amount' => 0,
            'panel_amount' => 0,
            'bag_amount' => 0,
            'consume_users' => '',
            'consume_count' => 0,
            'pay_rate' => 0,
            'arpu' => 0,
            'arppu' => 0,
        ];
        if (isset($channel_data[$channel_id])) {
            $total_json = $channel_data[$channel_id];
        } else {
            $total_json = $channel_data;
        }

        //当前邀请码新增注册的用户ID

        if (isset($total_json['active_users'])) {
            $active_users = array_unique(array_values($total_json['active_users']));
            $login_count = count($active_users);
            $login_users = implode(',', $active_users);

            //充值总金额
            $normal_pay_amount = CalCulateStats::getInstance()->getChargeSum($total_json, 'charge');
            //充值用户
            $normal_pay_users = CalCulateStats::getInstance()->getAgentChargeUsers($total_json, 'charge');
            //充值人数
            $normal_pay_users = array_unique(array_values($normal_pay_users));
            $normal_pay_count = count($normal_pay_users);

            //代充总金额
            $agent_pay_amount = CalCulateStats::getInstance()->getAgentChargeSum($total_json, 'agentcharge');
            //代充用户
            $agent_pay_users = CalCulateStats::getInstance()->getAgentChargeUsers($total_json, 'agentcharge');
            //代充人数
            $agent_pay_users = array_unique(array_values($agent_pay_users));
            $agent_pay_count = count($agent_pay_users);

            //充值用户
            $pay_users = array_unique(array_merge($normal_pay_users, $agent_pay_users));
            $pay_count = count($pay_users);
            $pay_users = implode(',', $pay_users);

            $pay_amount = $agent_pay_amount + $normal_pay_amount;

            $gift_data = CalCulateStats::getInstance()->getGiftData($total_json, 'sendGift');

            //消费用户
            $consume_users = CalCulateStats::getInstance()->getAgentChargeUsers($total_json, 'sendGift');
            //消费用户人数
            $consume_users = array_unique(array_values($consume_users));
            $consume_count = count($consume_users);

            list($panel_amount, $bag_amount) = [$gift_data['panel_amount'], $gift_data['bag_amount']];

            //充值率
            $pay_rate = round($pay_count / AnalysisCommon::numDivision($login_count) * 100, 2);
            //ARPU值
            $arpu = round($pay_amount / AnalysisCommon::numDivision($login_count) * 100, 2);
            //ARPPU值
            $arppu = round($pay_amount / AnalysisCommon::numDivision($pay_count) * 100, 2);

            $data['login_users'] = $login_users;
            $data['login_count'] = $login_count;
            $data['normal_pay_amount'] = $normal_pay_amount;
            $data['normal_pay_users'] = implode(',', $normal_pay_users);
            $data['normal_pay_count'] = $normal_pay_count;
            $data['agent_pay_amount'] = $agent_pay_amount;
            $data['agent_pay_users'] = implode(',', $agent_pay_users);
            $data['agent_pay_count'] = $agent_pay_count;
            $data['pay_users'] = $pay_users;
            $data['pay_count'] = $pay_count;
            $data['pay_amount'] = $pay_amount;
            $data['panel_amount'] = $panel_amount;
            $data['bag_amount'] = $bag_amount;
            $data['consume_users'] = implode(',', $consume_users);
            $data['consume_count'] = $consume_count;
            $data['pay_rate'] = $pay_rate;
            $data['arpu'] = $arpu;
            $data['arppu'] = $arppu;
        }
        return $data;
    }

    //渠道数据当日数据(新老用户)
    public function getTodayStats($start, $end, $promote_channel)
    {
        $where[] = [
            ['date', '>=', $start],
            ['date', '<', $end],
            ['promote_channel', '=', $promote_channel],
        ];

        return CalCulateFromDayData::getInstance()->calculate($where, 'promote_channel', 3000);
    }

    //渠道新增用户数据
    public function getRegDayStats($start, $end, $promote_channel)
    {
     /*   $sql = "SELECT
                    id
                FROM
                    bi_user_stats_1day
                WHERE
                    date >= '{$start}'
                AND date < '{$end}' AND promote_channel = {$promote_channel}
                AND DATE_FORMAT( register_time, '%Y-%m-%d' ) = date";*/

        $where=[
              ['date',">=",$start],
              ['date',"<",$end],
              ['promote_channel',"=",$promote_channel],
              ['register_time',">=",$start." 00:00:00"],
              ['register_time',"<",$end." 00:00:00"],
        ];

        $res = BiUserStats1DayModel::getInstance()->getModel()
            ->field("id")
            ->where($where)
            ->select()->toArray();
        //$res = Db::query($sql);
        $ids = array_column($res, 'id');
        return CalCulateFromDayData::getInstance()->calculateIds($ids, 'promote_channel', 3000);
    }
}

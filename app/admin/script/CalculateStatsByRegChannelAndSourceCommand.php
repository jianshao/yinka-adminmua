<?php
/*
 * 应用市场分包统计 register_channel source
 */

namespace app\admin\script;

use app\admin\model\BiChannelSourceDataModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\script\analysis\AnalysisCommon;
use app\admin\script\analysis\CalculateMultColumnStats;
use app\admin\script\analysis\InsertOrUpdateStats;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use Throwable;

ini_set('set_time_limit', 0);
ini_set('memory_limit', "256M");

class CalculateStatsByRegChannelAndSourceCommand extends Command
{
    protected $date;
    protected $cal_start_date;
    protected $time;

    const SECONDS_MINUTE_30 = 1800;
    const SECONDS_MINUTE_5 = 300;
    const CALCULATE_NEXT_DAY = [1, 3, 4];

    protected $retention_map = [
        3 => 'pay_retention_sum_3',
        7 => 'pay_retention_sum_7',
        15 => 'pay_retention_sum_15',
        30 => 'pay_retention_sum_30',
    ];

    protected function configure()
    {
        $this->setName('CalculateStatsByRegChannelAndSourceCommand')
            ->setDescription('CalculateStatsByRegChannelAndSourceCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '')
            ->addArgument('is_today', Argument::OPTIONAL, "is_today", 1);

        $this->date = date("Y-m-d");
        $this->time = date("Y-m-d H:i:s");
    }

    protected function getStartEndDate($start = '', $end = '')
    {
        if ($start && $end) {
            $start_date = date("Y-m-d", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m-d", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m-d", strtotime($start));
            $end_date = date("Y-m-d", strtotime("{$start} + 1days"));
        } elseif (in_array($this->getIntervalTime(), self::CALCULATE_NEXT_DAY)) {
            //每天凌晨30分钟统计前一天的数值
            $start_date = date("Y-m-d", strtotime("-1days"));
            $end_date = date("Y-m-d");
        } else {
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d", strtotime("+1days"));
        }

        return [$start_date, $end_date];
    }

    protected function getIntervalTime()
    {
        return floor((time() - strtotime(date("Y-m-d"))) / self::SECONDS_MINUTE_30);
    }

    protected function getStartEndMonth($start = '', $end = '')
    {
        if ($start && $end) {
            $start_date = date("Y-m", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m", strtotime($start));
            $end_date = date("Y-m", strtotime("{$start} + 1days"));
        } elseif (time() - strtotime(date("Y-m")) == 30 * 60) {
            //每天凌晨30分钟统计前一天的数值
            $start_date = date("Y-m", strtotime("-1days"));
            $end_date = date("Y-m");
        } else {
            $start_date = date("Y-m");
            $end_date = date("Y-m", strtotime("+1days"));
        }

        $months = $this->getMonthNum($start, $end);

        return [$start_date, $end_date, $months];
    }

    protected function getMonthNum($date1, $date2)
    {
        $date1_stamp = strtotime($date1);
        $date2_stamp = strtotime($date2);
        list($date_1['y'], $date_1['m']) = explode("-", date('Y-m', $date1_stamp));
        list($date_2['y'], $date_2['m']) = explode("-", date('Y-m', $date2_stamp));
        return abs(($date_2['y'] - $date_1['y']) * 12 + $date_2['m'] - $date_1['m']);
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $start = trim($input->getArgument('start'));
        $end = trim($input->getArgument('end'));
        $is_today = trim($input->getArgument('is_today')); //is_today=0只统计历史数据

        list($start, $end) = $this->getStartEndDate($start, $end);

        $days = round((strtotime($end) - strtotime($start)) / 3600 / 24);

        //设置统计开始日期
        $this->cal_start_date = $start;

        for ($i = 0; $i < $days; $i++) {
            $start_date = date("Y-m-d", strtotime("{$start} + {$i}days"));
            $end_date = date("Y-m-d", strtotime("{$start_date} + 1days"));
            echo "开始日期：{$start_date}-----结束日期：{$end_date}" . PHP_EOL;
            $this->date = $start_date;

            list($start_memory, $start_time) = array(memory_get_usage(), microtime(true));

            if (!empty($is_today)) {
                $this->dealTodayData($start_date, $end_date, $end);
            }

            if ($days > 1) {
                $history_start_date = $this->cal_start_date;
                $history_end_date = $this->date;
            } else {
                $history_start_date = date("Y-m-d", strtotime("$start - 30 day"));
                $history_end_date = $start;
            }

            $this->dealHistoryData($start_date, $end_date, $history_start_date, $history_end_date);

            list($end_memory, $end_time) = array(memory_get_usage(), microtime(true));

            $string = sprintf("内存占用: %f MB , 耗时： %.3f秒", (($end_memory - $start_memory) / 1024 / 1024), $end_time - $start_time);
            echo "统计{$start_date}日数据:======>" . $string . PHP_EOL;
        }
        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;
    }

    protected function calPromotionHistoryData($start, $end, $channel_data, $type)
    {
        $register_channel = $channel_data['channel'];
        $source = $channel_data['source'];

        $retention_date = date("Y-m-d", $channel_data['riq']);

        if (!empty($channel_data['today_reg_mebs'])) {
            $users = explode(',', $channel_data['today_reg_mebs']);
            $total_data = $this->dealRetentionData($retention_date, $end, ['register_channel' => $register_channel, 'source' => $source], $users, $type);

            //新增户充值总金额
            $pay_sum = CalculateMultColumnStats::getChargeSum($total_data, 'charge');

            //代充总金额
            $agent_pay_sum = CalculateMultColumnStats::getAgentChargeSum($total_data, 'agentcharge');

            //充值总额
            $retention_pay = (int) ($pay_sum + $agent_pay_sum);

            $updates = [];
            $diff_day = AnalysisCommon::getDiffDays($end, $retention_date);
            if (in_array($diff_day, array_keys($this->retention_map))) {
                $retention_key = $this->retention_map[$diff_day];
                $updates[$retention_key] = $retention_pay;
            }
            $updates['pay_retention_sum'] = $retention_pay;
            Db::name('bi_channel_source_data')->where('id', $channel_data['id'])->update($updates);
        }
    }

    protected function dealRetentionData($start, $end, $columns, $users, $type)
    {
        return CalculateMultColumnStats::getInstance()->dealDayStats($start, $end, $columns, $type, false, $users);
    }

    protected function calPromotionDataFromJson($start, $end, $columns, $type)
    {
        try {
            //渠道
            $reg_channel = $columns['register_channel'];
            $source = $columns['source'];

            $channelData['type'] = $type;
            $channelData['channel'] = $reg_channel;
            $channelData['source'] = $source;
            $channelData['riq'] = strtotime($start);

            //总数据
            $total_data = CalculateMultColumnStats::getInstance()->dealDayStats($start, $end, $columns, $type);

            // var_dump($total_data);die;

            //新增数据
            $today_reg_data = CalculateMultColumnStats::getInstance()->dealDayStats($start, $end, $columns, $type, true);

            //新增用户id
            $reg_members = AnalysisCommon::getArrayKeyValue($today_reg_data, 'active_users');

            $xinzeng_users = implode(',', $reg_members);
            $channelData['today_reg_mebs'] = $xinzeng_users;
            //新增
            $channelData['xinz'] = count($reg_members);

            //登陆人数
            $login_users = AnalysisCommon::getArrayKeyValue($total_data, 'active_users');

            $dlrs = count($login_users);

            //日活
            $channelData['rih'] = $dlrs;
            $channelData['cirlc'] = 0; //次留
            $channelData['sanrlc'] = 0; //三日次留
            $channelData['qirlc'] = 0; //七日次留
            //统计新增次日流程
            $channelData['xzlc'] = 0;
            $channelData['swrlc'] = 0;
            $channelData['ssrlc'] = 0;
            $channelData['czl'] = 0;
            $channelData['arpu'] = 0;
            $channelData['arppu'] = 0;
            $channelData['nczl'] = 0;
            $channelData['pay_retention_sum_3'] = 0;
            $channelData['pay_retention_sum_7'] = 0;
            $channelData['pay_retention_sum_15'] = 0;
            $channelData['pay_retention_sum_30'] = 0;
            $channelData['pay_retention_sum'] = 0;

            //充值总金额
            $pay_sum = CalculateMultColumnStats::getChargeSum($total_data, 'charge');

            //充值人数
            $pay_users = CalculateMultColumnStats::getAgentChargeUsers($total_data, 'charge');

            //新增户充值总金额
            $today_reg_pay_sum = CalculateMultColumnStats::getChargeSum($today_reg_data, 'charge');

            //新增户充值人数
            $today_reg_pay_users = CalculateMultColumnStats::getAgentChargeUsers($today_reg_data, 'charge');

            //代充总金额
            $agent_pay_sum = CalculateMultColumnStats::getAgentChargeSum($total_data, 'agentcharge');

            //代充人数
            $agent_pay_users = CalculateMultColumnStats::getAgentChargeUsers($total_data, 'agentcharge');

            //新增代充总金额
            $today_reg_agent_pay_sum = CalculateMultColumnStats::getAgentChargeSum($today_reg_data, 'agentcharge');

            //新增代充人数
            $today_reg_agent_pay_users = CalculateMultColumnStats::getAgentChargeUsers($today_reg_data, 'agentcharge');

            //充值人数
            $total_pay_users = array_unique(array_merge($pay_users, $agent_pay_users));

            //新增充值
            $today_reg_pay_users = array_unique(array_merge($today_reg_pay_users, $today_reg_agent_pay_users));

            $channelData['czrs'] = count($total_pay_users);

            $channelData['nczrs'] = count($today_reg_pay_users);

            $channelData['czzje'] = $pay_sum + $agent_pay_sum;

            $channelData['pay_retention_sum'] = $channelData['nczzje'] = $today_reg_pay_sum + $today_reg_agent_pay_sum;

            //充值率
            if ($dlrs > 0) {
                $channelData['czl'] = round($channelData['czrs'] / $this->getDivision($dlrs) * 100, 2) * 100;
            }

            //ARPU值
            if ($dlrs > 0) {
                $channelData['arpu'] = round($channelData['czzje'] / $this->getDivision($dlrs) * 100, 2);
            }

            //ARPPU值
            if ($channelData['czrs'] > 0) {
                $channelData['arppu'] = round($channelData['czzje'] / $this->getDivision($channelData['czrs']) * 100, 2);
            }

            //新增用户充值率(新增用户充值人数/新增用户人数)
            if ($channelData['nczrs'] > 0) {
                $channelData['nczl'] = round($channelData['nczrs'] / $this->getDivision($channelData['xinz']) * 100, 2) * 100;
            }

            $unique_keys = ['channel', 'type', 'riq', 'source'];

            // var_dump($channelData);die;
            InsertOrUpdateStats::getInstance()->insertOrUpdate($channelData, $unique_keys, 'bi_channel_source_data');
        } catch (Throwable $e) {
            echo $e->getCode(), $e->getMessage(), $e->getTraceAsString() . PHP_EOL;
            throw $e;
            Log::error(sprintf('VipHandler::calUserDiamond ex=%d:%s trace=%s', $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }

    protected function getDivision($num)
    {
        return $num == 0 ? 1 : $num;
    }

    /**
     *处理历史数据
     */
    protected function dealHistoryData($start, $end, $history_start_date, $history_end_date)
    {
        $history = BiChannelSourceDataModel::getInstance()->getModel()
            ->where('riq', '>=', strtotime($history_start_date))
            ->where('riq', '<=', strtotime($history_end_date))
            ->where('type', 2)
            ->select()
            ->toArray();

        foreach ($history as $channelData) {
            $this->calPromotionHistoryData($start, $end, $channelData, 2);
        }
    }

    /**
     *处理每日数据
     */
    protected function dealTodayData($start, $end)
    {
        $columns = BiUserStats1DayModel::getInstance()->getModel()
            ->field('register_channel, source')
            ->where('date', $start)
            ->where('promote_channel', 0)
            ->group('register_channel, source')
            ->select()
            ->toArray();

        foreach ($columns as $channel) {
            $this->calPromotionDataFromJson($start, $end, $channel, 2);
        }
    }
}

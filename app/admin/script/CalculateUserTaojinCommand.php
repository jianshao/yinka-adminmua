<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\model\BiActivityTimesModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\model\BiUserStats5MinsModel;
use app\admin\script\analysis\CalCulateFromFiveMinutesData;
use app\admin\script\analysis\InsertOrUpdateStats;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\App;
use think\facade\Db;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class CalculateUserTaojinCommand extends Command
{
    protected $date;
    protected $time;

    const LIMIT = 100;

    const SECONDS_MINUTE_5 = 5 * 60;
    const CALCULATE_NEXT_DAY = [3, 4]; //隔天统计昨天的数据

    protected function configure()
    {
        $this->setName('CalculateUserTaojinCommand')
            ->setDescription('CalculateUserTaojinCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '');

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

        list($start, $end) = $this->getStartEndDate($start, $end);

        $days = round((strtotime($end) - strtotime($start)) / 3600 / 24);

        for ($i = 0; $i < $days; $i++) {
            $start_date = date("Y-m-d", strtotime("{$start} + {$i}days"));
            $end_date = date("Y-m-d", strtotime("{$start_date} + 1days"));
            echo "开始日期：{$start_date}-----结束日期：{$end_date}" . PHP_EOL;
            $this->date = $start_date;
            $this->dealTodayData($start_date, $end_date, $end);
        }
        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;

        $runtime = number_format(microtime(true) - App::getBeginTime(), 10, '.', '');
        $reqs = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
        $mem = number_format((memory_get_usage() - App::getBeginMem()) / 1024, 2);
        echo '运行时间:' . $runtime . 's [ 吞吐率：' . $reqs . 'req/s ] 内存消耗：' . $mem . 'kb 文件加载：' . count(get_included_files());
    }

    /**
     *处理每日数据
     */
    protected function dealTodayData($start, $end)
    {
        $this->dealTaoJin($start, $end);
    }

    protected function dealTaoJin($start, $end)
    {
        $datas = $this->getTaoJinActivity($start, $end);
        foreach ($datas as $activity) {
            $this->dealTaoJinUserDataByDay($activity, $start, $end);
            $this->dealTaoJinActivityData($activity, $start, $end);
        }
    }
    protected function dealTaoJinActivityData($activity, $start, $end)
    {
        //每天统计数据{
        $activity_id = $activity['id'];

        $data = [];

        $data['steps'] =
        $data['bean_consume_amount'] =
        $data['bean_output_amount'] =
        $data['gold_output_amount'] =
        $data['silver_output_amount'] =
        $data['iron_output_amount'] =
        $data['fossil_output_amount'] = 0;

        $res = Db::table('bi_days_user_taojin_stats')->field('
            sum(steps) steps,
            sum(bean_consume_amount) bean_consume_amount,
            sum(bean_output_amount) bean_output_amount,
            sum(gold_output_amount) gold_output_amount,
            sum(silver_output_amount) silver_output_amount,
            sum(iron_output_amount) iron_output_amount,
            sum(fossil_output_amount) fossil_output_amount')->where('activity_id', $activity_id)->find();

        if (!empty($res)) {
            foreach ($res as $column => $num) {
                if (!empty($num)) {
                    $data[$column] = (int) $num;
                }
            }
        }
        BiActivityTimesModel::getInstance()->getModel()->where('id', $activity_id)->update($data);
    }

    protected function dealTaoJinUserDataByDay($activity, $start, $end)
    {
        $activity_id = $activity['id'];
        $start_time = max($activity['start_time'], $start);
        $end_time = min($activity['end_time'], $end);

        //渠道数据用户数据
        $conditions[] = [
            ['interval_time', '>=', $start_time],
            ['interval_time', '<', $end_time],
        ];

        $datas = CalCulateFromFiveMinutesData::getInstance()->calculate($conditions, 'uid');

        if (!empty($datas)) {
            foreach ($datas as $uid => $item) {
                if (strtotime($start) >= strtotime($start_time)) {
                    $insert_data['date'] = $start;
                    $insert_data['uid'] = $uid;
                    $insert_data['activity_id'] = $activity_id;

                    // $json_data = json_decode($item['json_data'], true);
                    if (array_key_exists('activity', $item) && array_key_exists('data', $item['activity'])) {
                        $data = [];
                        $activity_rooms = $item['activity']['data'];
                        foreach ($activity_rooms as $room_data) {
                            if (array_key_exists('taojin', $room_data)) {
                                $taojin_data = $room_data['taojin'];
                                foreach ($taojin_data as $game_id => $game_data) {
                                    $count = $game_data['count'];
                                    $consume = $game_data['consume'];
                                    $reward = $game_data['reward'];

                                    $bean_consume_amount = isset($consume['bank:game:score']) ? $consume['bank:game:score']['value'] : 0;
                                    $bean_output_amount = isset($reward['user:bean']) ? $reward['user:bean']['value'] : 0;
                                    $gold_output_amount = isset($reward['ore:gold']) ? $reward['ore:gold']['value'] : 0;
                                    $silver_output_amount = isset($reward['ore:silver']) ? $reward['ore:silver']['value'] : 0;
                                    $iron_output_amount = isset($reward['ore:iron']) ? $reward['ore:iron']['value'] : 0;
                                    $fossil_output_amount = isset($reward['ore:fossil']) ? $reward['ore:fossil']['value'] : 0;

                                    $data[$game_id]['steps'] = $count;

                                    if (!isset($data[$game_id]['bean_consume_amount'])) {
                                        $data[$game_id]['bean_consume_amount'] = $bean_consume_amount;
                                    } else {
                                        $data[$game_id]['bean_consume_amount'] += $bean_consume_amount;
                                    }

                                    if (!isset($data[$game_id]['bean_output_amount'])) {
                                        $data[$game_id]['bean_output_amount'] = $bean_output_amount;
                                    } else {
                                        $data[$game_id]['bean_output_amount'] += $bean_output_amount;
                                    }

                                    if (!isset($data[$game_id]['gold_output_amount'])) {
                                        $data[$game_id]['gold_output_amount'] = $gold_output_amount;
                                    } else {
                                        $data[$game_id]['gold_output_amount'] += $gold_output_amount;
                                    }

                                    if (!isset($data[$game_id]['silver_output_amount'])) {
                                        $data[$game_id]['silver_output_amount'] = $silver_output_amount;
                                    } else {
                                        $data[$game_id]['silver_output_amount'] += $silver_output_amount;
                                    }

                                    if (!isset($data[$game_id]['iron_output_amount'])) {
                                        $data[$game_id]['iron_output_amount'] = $iron_output_amount;
                                    } else {
                                        $data[$game_id]['iron_output_amount'] += $iron_output_amount;
                                    }

                                    if (!isset($data[$game_id]['fossil_output_amount'])) {
                                        $data[$game_id]['fossil_output_amount'] = $fossil_output_amount;
                                    } else {
                                        $data[$game_id]['fossil_output_amount'] += $fossil_output_amount;
                                    }
                                }
                            }
                        }

                        foreach ($data as $game_id => $game_data) {
                            $insert_data['game_id'] = $game_id;
                            $insert_data['steps'] = $game_data['steps'];
                            $insert_data['bean_consume_amount'] = $game_data['bean_consume_amount'];
                            $insert_data['bean_output_amount'] = $game_data['bean_output_amount'];
                            $insert_data['gold_output_amount'] = $game_data['gold_output_amount'];
                            $insert_data['silver_output_amount'] = $game_data['silver_output_amount'];
                            $insert_data['iron_output_amount'] = $game_data['iron_output_amount'];
                            $insert_data['fossil_output_amount'] = $game_data['fossil_output_amount'];
                            InsertOrUpdateStats::getInstance()->insertOrUpdate($insert_data, ['date' => $start, 'uid' => $uid, 'activity_id' => $activity_id, 'game_id' => $game_id], 'bi_days_user_taojin_stats');
                        }
                        echo "date=>{$start},uid=>{$uid}" . PHP_EOL;
                    }
                }
            }
        }
    }
    public function getStartEndDate($start = '', $end = '')
    {
        if ($start && $end) {
            $start_date = date("Y-m-d", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m-d", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m-d", strtotime($start));
            $end_date = date("Y-m-d", strtotime("{$start} + 1days"));
        } elseif (in_array(floor((time() - strtotime(date("Y-m-d"))) / self::SECONDS_MINUTE_5), self::CALCULATE_NEXT_DAY)) {
            //每天凌晨30分钟统计前一天的数值
            $start_date = date("Y-m-d", strtotime("-1days"));
            $end_date = date("Y-m-d");
        } else {
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d", strtotime("+1days"));
        }

        return [$start_date, $end_date];
    }

    /*
     * 当日淘金的用户
     */
    public function getTaoJinActivity($start, $end)
    {
        $query = BiActivityTimesModel::getInstance()->getModel()
            ->where(
                [
                    ['end_time', '>=', $start],
                    ['type', '=', 'taojin'],
                ]
            )
            ->field('*')
            ->select()
            ->toArray();
        return $query;
    }

    /*
     * 当日淘金的用户
     */
    public function getTaoJinActivityDatas($start, $end)
    {
        $query = BiUserStats5MinsModel::getInstance()->getModel()
            ->where(
                [
                    ['json_data', 'like', '%taojin%'],
                    ['date', '>=', $start],
                    ['date', '<=', $end],
                ]
            )
            ->field('*')
            ->select()
            ->toArray();
        return $query;
    }

    /*
     * 当日淘金的用户
     */
    public function getTaoJinUsers($start, $end)
    {
        $query = BiUserStats1DayModel::getInstance()->getModel()
            ->where(
                [
                    ['json_data', 'like', '%taojin%'],
                    ['date', '>=', $start],
                    ['date', '<', $end],
                ]
            )
            ->field('*')
            ->select()
            ->toArray();
        return $query;
    }
}
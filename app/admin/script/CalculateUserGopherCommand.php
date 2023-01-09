<?php
/**
 * 老的砸蛋日统计
 */

namespace app\admin\script;

use app\admin\common\CommonConst;
use app\admin\model\BiDaysUserActivityDatasModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\script\analysis\AnalysisCommon;
use app\common\ParseUserStateByUniqkey;
use function GuzzleHttp\json_decode;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);

class CalculateUserGopherCommand extends Command
{
    protected $date;
    protected $time;

    const LIMIT = 100;

    const SECONDS_MINUTE_5 = 5 * 60;
    const CALCULATE_NEXT_DAY = [3, 4]; //隔天统计昨天的数据

    protected static $asset_type = [
        'bank:game:score',
        'user:bean',
        // 'ore:gold',
        // 'ore:silver',
        // 'ore:iron',
        // 'ore:fossil',
    ];

    protected function configure()
    {
        $this->setName('CalculateUserGopherCommand')
            ->setDescription('CalculateUserGopherCommand')
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

    }

    /**
     *处理每日数据
     */
    protected function dealTodayData($start, $end)
    {
        //每日用户数据
        foreach (CommonConst::$game_map as $game => $_) {
            $users_data = $this->getActivityUsers($start, $end, $game);
            if (!empty($users_data)) {
                foreach ($users_data as $user_data) {
                    $stats = $this->getGopherData($user_data, $game);
                    if (!empty($stats)) {
                        $model = BiDaysUserActivityDatasModel::getInstance()->getModel();
                        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($model,$stats,['id','date','uid','activity','activity_sub']);
                        //AnalysisCommon::getInstance()->insertOrUpdateMul($stats, 'bi_days_user_activity_datas', 'consume_amount,output_amount,explodeRate');
                    }
                }
            }
        }
    }

    public function getGopherData($day_datas, string $activity)
    {
        $data = [];
        $sum_consumption = 0;

        if (!empty($day_datas)) {
            $box_data = [];

            $json_data = json_decode($day_datas['json_data'], true);
            if (isset($json_data['activity'])) {
                $activity_rooms = array_values($json_data['activity']);
                foreach ($activity_rooms as $room_data) {
                    if (array_key_exists($activity, $room_data)) {
                        $activity_data = $room_data[$activity];

                        foreach ($activity_data as $sub_game => $game_data) {

                            $box_data['date'] = $day_datas['date'];
                            $box_data['uid'] = (int) $day_datas['uid'];
                            $activity_ext2 = $sub_game;

                            $key = $day_datas['date'] . '_' . $day_datas['uid'] . '_' . $activity_ext2;

                            $consume_amount = 0;
                            if (!empty($game_data['consume']) && isset($game_data['consume']['bank:game:score'])) {
                                $consume_amount = $game_data['consume']['bank:game:score']['value'];
                            }
                            $sum_consumption += $consume_amount;

                            if (isset($data[$key]['consume_amount'])) {
                                $data[$key]['consume_amount'] += $consume_amount;
                            } else {
                                $data[$key]['consume_amount'] = $consume_amount;
                            }

                            $output_amount = 0;
                            if ($game_data['reward'] && isset($game_data['reward']) && isset($data[$key])) {
                                foreach ($game_data['reward'] as $assetId => $rewardItem) {
                                    if (in_array($assetId, self::$asset_type) || strpos($assetId, 'gift') !== false) {
                                        $output_amount += $rewardItem['value'];
                                    }
                                }
                            }

                            if (isset($data[$key]['output_amount'])) {
                                $data[$key]['output_amount'] += $output_amount;
                            } else {
                                $data[$key]['output_amount'] = $output_amount;
                            }

                            $data[$key]['explodeRate'] = 0;
                            if ($data[$key]['consume_amount'] > 0) {
                                $data[$key]['explodeRate'] = round($data[$key]['output_amount'] * 100 / $data[$key]['consume_amount'], 2);
                            }
                        }
                    }
                }
            }
        }

        $items = [];
        foreach ($data as $key => $user_data) {
            $key_arr = explode('_', $key);
            $date = $key_arr[0];
            $uid = $key_arr[1];
            $activity_sub = $key_arr[2];

            $user_data['date'] = $date;
            $user_data['uid'] = $uid;
            $user_data['activity'] = $activity;
            $user_data['activity_sub'] = $activity_sub;
            $items[] = $user_data;

        }
        return $items;
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
     * 当日砸蛋的用户
     */
    public function getActivityUsers($start, $end, $activity)
    {

        return BiUserStats1DayModel::getInstance()->getModel()
            ->where(
                [
                    ['json_data', 'like', "%{$activity}%"],
                    ['date', '>=', $start],
                    ['date', '<', $end],
                ]
            )
            ->field('*')
            ->select()
            ->toArray();
    }
}
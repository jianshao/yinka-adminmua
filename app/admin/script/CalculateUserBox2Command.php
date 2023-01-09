<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\model\BiDaysUserBoxDatasModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\script\analysis\InsertOrUpdateStats;
use app\common\ParseUserStateByUniqkey;
use function GuzzleHttp\json_decode;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);

class CalculateUserBox2Command extends Command
{
    protected $date;
    protected $time;

    const LIMIT = 100;

    const BOX_TYPE = [1 => '莫提斯', 2 => '宙斯', 3 => '盖亚'];
    const SECONDS_MINUTE_5 = 5 * 60;
    const CALCULATE_NEXT_DAY = [3, 4]; //隔天统计昨天的数据

    protected $duke_user_value = 'duke_user_value'; //祈福奖励榜前缀

    protected function configure()
    {
        $this->setName('CalculateUserBox2Command')
            ->setDescription('CalculateUserBox2Command')
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
        $users = $this->getBox2Users($start, $end);
        if (!empty($users)) {
            foreach ($users as $item) {
                $uid = $item['uid'];
                $box_data['date'] = $start;
                $box_data['uid'] = $uid;
                $box_data['type'] = 2;

                $json_data = json_decode($item['json_data'], true);
                if (isset($json_data['activity'])) {
                    $activity_rooms = array_values($json_data['activity']);
                    $box_data['big_consume_amount'] = 0;
                    $box_data['big_output_amount'] = 0;
                    $box_data['in_consume_amount'] = 0;
                    $box_data['in_output_amount'] = 0;
                    $box_data['small_consume_amount'] = 0;
                    $box_data['small_output_amount'] = 0;


                    foreach ($activity_rooms as $room_data) {
                        $small_consume = $small_output = $in_consume = $in_output = $big_consume = $big_output = 0;

                        if (array_key_exists('box2', $room_data)) {
                            $box2_data = $room_data['box2'];
                            //莫提斯
                            if (array_key_exists(1, $box2_data)) {
                                $small_consume = $box2_data[1]['consume']['bank:game:score']['value'] ?? 0;
                                $small_output = array_sum(array_column(array_values($box2_data[1]['reward']), 'value'));
                            }

                            //宙斯
                            if (array_key_exists(2, $box2_data)) {
                                $in_consume = $box2_data[2]['consume']['bank:game:score']['value'] ?? 0;
                                $in_output = array_sum(array_column(array_values($box2_data[2]['reward']), 'value'));
                            }

                            //盖亚
                            if (array_key_exists(3, $box2_data)) {
                                $big_consume = $box2_data[3]['consume']['bank:game:score']['value'] ?? 0 ;
                                $big_output = array_sum(array_column(array_values($box2_data[3]['reward']), 'value'));
                            }

                            $box_data['small_consume_amount'] += $small_consume;
                            $box_data['small_output_amount'] += $small_output;
                            $box_data['in_consume_amount'] += $in_consume;
                            $box_data['in_output_amount'] += $in_output;
                            $box_data['big_consume_amount'] += $big_consume;
                            $box_data['big_output_amount'] += $big_output;
                        }
                    }
                    echo "date=>{$start},uid=>{$item['uid']}" . PHP_EOL;
                    //InsertOrUpdateStats::getInstance()->insertOrUpdate($box_data, ['date' => $start, 'uid' => $uid, 'type' => 2], 'bi_days_user_box_datas');
                    ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiDaysUserBoxDatasModel::getInstance()->getModel(),[$box_data],['date','uid','type','id']
                    );

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
     * 当日砸蛋的用户
     */
    public function getBox2Users($start, $end)
    {
       $where =  [
            ['json_data', 'like', '%box2%'],
            ['date', '>=', $start],
            ['date', '<', $end],
        ];

        return  BiUserStats1DayModel::getInstance()->getModel()
            ->where($where)->field('*')->select()
            ->toArray();
    }
}
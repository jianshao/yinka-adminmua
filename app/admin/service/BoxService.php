<?php

namespace app\admin\service;

use app\admin\model\BoxGiftModel;
use app\common\RedisCommon;

class BoxService extends BoxGiftModel
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getBoxData($day_datas, string $activity, string $activity_sub, string $asset_str = 'bank:game:score')
    {
        $data = [];

        $sum_output_amount = 0;
        $sum_consumption = 0;

        if (!empty($day_datas)) {
            foreach ($day_datas as $item) {
                $box_data['date'] = $item['date'];
                $box_data['uid'] = $item['uid'];
                $box_data['consume_amount'] = 0;
                $box_data['output_amount'] = 0;
                $box_data['explodeRate'] = 0;

                $json_data = json_decode($item['json_data'], true);
                if (isset($json_data['activity'])) {
                    $activity_rooms = array_values($json_data['activity']);
                    foreach ($activity_rooms as $room_data) {
                        if (array_key_exists($activity, $room_data)) {
                            $consume = $output = 0;
                            $activity_data = $room_data[$activity];

                            if (array_key_exists($activity_sub, $activity_data) && isset($activity_data[$activity_sub]['consume'][$asset_str])) {
                                $consume = $activity_data[$activity_sub]['consume'][$asset_str]['value'];

                                foreach ($activity_data[$activity_sub]['reward'] as $asset => $reward) {
                                    if (strpos($asset, 'gift') !== false) {
                                        $output += $reward['value'];
                                    }
                                }

                                $box_data['consume_amount'] += $consume;
                                $sum_consumption += $consume;

                                $box_data['output_amount'] += $output;
                                $sum_output_amount += $output;

                                if ($box_data['consume_amount'] > 0) {
                                    $box_data['explodeRate'] = round($box_data['output_amount'] * 100 / $box_data['consume_amount'], 2);
                                }
                            }
                        }
                    }
                }
                if ($box_data['consume_amount'] > 0 && $box_data['output_amount'] > 0) {
                    $data[] = $box_data;
                }
            }
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $profit_pool_amount = $redis->hget('box_profits_pool', $activity_sub);

        $explodeRate = 0.00;
        if ($sum_consumption) {
            $explodeRate += round($sum_output_amount / $sum_consumption, 4);
        }

        return [
            (string) $activity_sub => [
                'output_amount' => $sum_output_amount,
                'consumption' => $sum_consumption,
                'profit_pool_amount' => $profit_pool_amount,
                'explodeRate' => $explodeRate,
                'data' => $data,
            ],
        ];
    }

    public static function getGopherData($day_datas, string $activity, string $activity_sub)
    {
        $data = $sum_data = [];

        $sum_output_amount = 0;
        $sum_consumption = 0;

        if (!empty($day_datas)) {
            foreach ($day_datas as $item) {
                $box_data = [];

                $json_data = json_decode($item['json_data'], true);
                if (isset($json_data['activity'])) {
                    $activity_rooms = array_values($json_data['activity']);

                    foreach ($activity_rooms as $room_data) {
                        if (array_key_exists($activity, $room_data)) {
                            $activity_data = $room_data[$activity];
                            foreach ($activity_data as $sub_game => $game_data) {

                                $box_data['date'] = $item['date'];
                                $box_data['uid'] = $item['uid'];
                                $activity_ext2 = $sub_game;

                                $key = $item['date'] . '_' . $item['uid'] . '_' . $activity_ext2;

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
                                if ($game_data['reward'] && isset($game_data['reward']['bank:game:score']) && isset($data[$key])) {
                                    $output_amount = $game_data['reward']['bank:game:score']['value'];
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

                                $total_key = $item['date'] . '_' . $item['uid'];
                                if (isset($sum_data[$total_key]['consume_amount'])) {
                                    $sum_data[$total_key]['consume_amount'] += $consume_amount;
                                } else {
                                    $sum_data[$total_key]['consume_amount'] = $consume_amount;
                                }
                                $sum_output_amount += $output_amount;
                                if (isset($sum_data[$total_key]['output_amount'])) {
                                    $sum_data[$total_key]['output_amount'] += $output_amount;
                                } else {
                                    $sum_data[$total_key]['output_amount'] = $output_amount;
                                }

                                $sum_data[$total_key]['explodeRate'] = 0;
                                if ($sum_data[$total_key]['consume_amount'] > 0) {
                                    $sum_data[$total_key]['explodeRate'] = round($sum_data[$total_key]['output_amount'] * 100 / $sum_data[$total_key]['consume_amount'], 2);
                                }
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

            $total_key = $date . '_' . $uid;

            $items[$date][$uid]['sum_data'] = $sum_data[$total_key];
            $gopher = $key_arr[2];
            $userItem['gopherId'] = $gopher;
            $items[$date][$uid][$gopher] = $user_data;

            $items[$date][$uid]['total_consume_amount'] = 0;
            $items[$date][$uid]['total_output_amount'] = 0;
            $items[$date][$uid]['total_consume_amount'] += $user_data['consume_amount'];
            $items[$date][$uid]['total_output_amount'] += $user_data['output_amount'];
        }

        $res = [];
        foreach ($items as $key => $item) {
            foreach ($item as $uid => $user_game) {
                $user_game['date'] = $key;
                $user_game['uid'] = $uid;
                $res[] = $user_game;
            }
        }

        $redis = RedisCommon::getInstance()->getRedis();
        $profit_pool_amount = $redis->hget('box_profits_pool', $activity_sub);

        $explodeRate = 0.00;
        if ($sum_consumption) {
            $explodeRate += round($sum_output_amount / $sum_consumption, 4);
        }

        return [
            (string) $activity_sub => [
                'output_amount' => $sum_output_amount,
                'consumption' => $sum_consumption,
                'profit_pool_amount' => $profit_pool_amount,
                'explodeRate' => $explodeRate,
                'data' => $res,
            ],
        ];
    }
}

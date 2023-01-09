<?php
namespace app\admin\script\analysis;

use app\admin\model\BiUserStats1DayModel;
use think\facade\Db;

class CalCulateStats
{
    protected const CALCULATE_COLUMN = [
        1 => 'promote_channel',
        2 => 'register_channel',
    ];

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getRegUsers($start, $end, $register_channel, $promote_channel = 0)
    {
        $query = BiUserStats1DayModel::getInstance()->getModel()
            ->where('register_time', '>=', $start)
            ->where('register_time', '<', $end)
            ->where('register_channel', '=', $register_channel)
            ->where('promote_channel', '=', $promote_channel)
            ->column('id');
        return $query;
    }

    public function getLoginUsers($start, $end, $register_channel, $promote_channel = 0)
    {
        $query = BiUserStats1DayModel::getInstance()->getModel()
            ->where('date', $start)
            ->where('register_channel', '=', 'appStore')
            ->where('promote_channel', '=', $promote_channel)
            ->column('id');
        return $query;
    }

    public function getChargeSum($data, $json_key)
    {
        $amount = 0;
        if (array_key_exists($json_key, $data) && array_key_exists('data', $data[$json_key])) {
            foreach ($data[$json_key]['data'] as $channelCharge) {
                foreach ($channelCharge as $charge_data) {
                    $amount += $charge_data['amount'];
                }
            }
        }
        return $amount / 10;
    }

    public function getAgentChargeSum($data, $json_key)
    {
        $amount = 0;
        if (array_key_exists($json_key, $data) && array_key_exists('data', $data[$json_key])) {
            foreach ($data[$json_key]['data'] as $charge_data) {
                $amount += $charge_data['amount'];
            }
        }

        return $amount / 10;
    }

    public function getAgentChargeUsers($data, $json_key)
    {
        $users = [];
        if (array_key_exists($json_key, $data) && array_key_exists('users', $data[$json_key])) {
            $users = $data[$json_key]['users'];
        }

        return $users;
    }

    /*
     * 送礼
     */
    public function getGiftData($data, $json_key)
    {
        $res = ['bag_amount' => 0, 'panel_amount' => 0];
        if (array_key_exists($json_key, $data) && array_key_exists('data', $data[$json_key])) {
            $json_data = $data[$json_key]['data'];

            $bag = $panel = [];
            foreach ($json_data as $room_data) {
                if ($room_data['bag']) {
                    if (isset($room_data['bag']['395'])) {
                        unset($room_data['bag']['395']);
                    }
                    if (isset($room_data['bag']['376'])) {
                        unset($room_data['bag']['376']);
                    }
                    $bag[] = $room_data['bag'];
                }
                if ($room_data['panel']) {
                    if (isset($room_data['panel']['395'])) {
                        unset($room_data['panel']['395']);
                    }
                    if (isset($room_data['panel']['376'])) {
                        unset($room_data['panel']['376']);
                    }
                    $panel[] = $room_data['panel'];
                }
            }

            $bag_amount = $panel_amount = 0;
            foreach ($bag as $bag_gift) {
                $bag_amount += array_sum(array_column($bag_gift, 'amount'));
            }

            foreach ($panel as $panel_gift) {
                $panel_amount += array_sum(array_column($panel_gift, 'amount'));
            }

            $res['bag_amount'] = $bag_amount;
            $res['panel_amount'] = $panel_amount;
        }

        return $res;
    }

    public function getAgentChargeCount($data, $json_key)
    {
        $count = 0;
        if (array_key_exists($json_key, $data) && array_key_exists('users', $data[$json_key])) {
            $count = $data[$json_key]['users'];
        }
        return $count;
    }

    public function dealDayStats($start, $end, $stats_cloumn, $type, $is_reg = false, $users = [])
    {
        $where = [
            ['date', '>=', $start],
            ['date', '<', $end],
            // ['uid', '=', 1021100],
        ];

        $column = self::CALCULATE_COLUMN[$type];

        $where[] = [
            [$column, '=', $stats_cloumn],
        ];

        if ($type == 2) {
            $where[] = [
                ['promote_channel', '=', 0],
            ];
        }

        if ($is_reg) {
            $where[] = [
                ['register_time', '>=', $start],
                ['register_time', '<', $end],
            ];
        }

        if ($users) {
            $where[] = [
                ['uid', 'in', $users],
            ];
        }

        $data = $obj = [];
        $this->calculate($where, $data, $obj, $column);

        return isset($data[$stats_cloumn]) ? $data[$stats_cloumn] : [];
    }

    protected function calculate($where, &$data, &$obj, $column)
    {
        $count = $this->getStats($where);
        $page = ceil($count / AnslysisConst::LIMIT);

        for ($i = 0; $i < $page; $i++) {
            $offset = $i * AnslysisConst::LIMIT;
            $res = $this->getStats($where, $offset, true);
            $this->calDayData($res, $data, $obj, $column);
        }
    }

    protected function getStats($where, $offset = 0, $is_count = false)
    {
        $res = BiUserStats1DayModel::getInstance()->getModel()
            ->where($where)
            ->field("*");

        if (!$is_count) {
            return $res->count();
        }

        return $res->limit($offset, AnslysisConst::LIMIT)->select()->toArray();
    }

    protected function calDayData($res, &$data, &$obj, $column)
    {
        foreach ($res as $mins5_data) {
            $uid = $mins5_data['uid'];
            // $promote_channel = $mins5_data['promote_channel'];
            // $source = $mins5_data['source'];
            // $register_channel = $mins5_data['register_channel'];
            // $register_time = $mins5_data['register_time'];

            $user_json = json_decode($mins5_data['json_data'], true);

            $stats_column = "{$mins5_data[$column]}";

            if (!isset($data[$stats_column]['active_users'])) {
                $data[$stats_column]['active_users'] = [];
            }

            if (!in_array($uid, $data[$stats_column]['active_users'])) {
                array_push($data[$stats_column]['active_users'], $uid);
            }

            foreach ($user_json as $json_type => $json) {
                if (!empty($json)) {
                    if (array_key_exists($json_type, AnslysisConst::CLASS_MAP)) {
                        $class = AnslysisConst::CLASS_MAP[$json_type];

                        $user_obj = new $class($stats_column);
                        $user_obj->fromJson($json);

                        if (!isset($obj[$stats_column][$json_type])) {
                            $obj[$stats_column][$json_type] = $user_obj;
                        } else {
                            $obj[$stats_column][$json_type] = $obj[$stats_column][$json_type]->merge($user_obj);
                        }

                        $data[$stats_column][$json_type]['data'] = $obj[$stats_column][$json_type]->toJson();
                    } elseif (!array_key_exists($json_type, AnslysisConst::CLASS_MAP)) {
                        $data[$stats_column][$json_type]['data'] = $json;
                    }

                    if (!isset($data[$stats_column][$json_type]['users'])) {
                        $data[$stats_column][$json_type]['users'][] = $uid;
                    }
                    if (!in_array($uid, $data[$stats_column][$json_type]['users'])) {
                        array_push($data[$stats_column][$json_type]['users'], $uid);
                    }
                }
            }
        }
    }

    public function calFiveMinutesData($res, &$data, &$obj)
    {
        foreach ($res as $mins5_data) {
            $uid = $mins5_data['uid'];
            $promote_channel = $mins5_data['promote_channel'];
            $source = $mins5_data['source'];
            $register_channel = $mins5_data['register_channel'];
            $register_time = $mins5_data['register_time'];

            $user_json = json_decode($mins5_data['json_data'], true);

            $key = "{$uid}&{$source}&{$register_channel}&{$register_time}";
            foreach ($user_json as $json_type => $json) {
                if (array_key_exists($json_type, AnslysisConst::CLASS_MAP) && !empty($json)) {
                    $class = AnslysisConst::CLASS_MAP[$json_type];

                    $user_obj = new $class($uid);
                    $user_obj->fromJson($json);

                    if (!isset($obj[$key][$json_type])) {
                        $obj[$key][$json_type] = $user_obj;
                    } else {
                        $obj[$key][$json_type] = $obj[$key][$json_type]->merge($user_obj);
                    }

                    $data[$key][$json_type] = $obj[$key][$json_type]->toJson();
                } elseif (!array_key_exists($json_type, AnslysisConst::CLASS_MAP) && !empty($json)) {
                    $data[$key][$json_type] = $json;
                }
            }
        }
    }
}
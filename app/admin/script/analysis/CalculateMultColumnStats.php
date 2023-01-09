<?php
namespace app\admin\script\analysis;

use app\admin\model\BiUserStats1DayModel;
use think\facade\Db;

class CalculateMultColumnStats
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

    public static function getRegUsers($start, $end, $register_channel, $promote_channel = 0)
    {
        $query = BiUserStats1DayModel::getInstance()->getModel()
            ->where('register_time', '>=', $start)
            ->where('register_time', '<', $end)
            ->where('register_channel', '=', $register_channel)
            ->where('promote_channel', '=', $promote_channel)
            ->column('id');
        return $query;
    }

    public static function getLoginUsers($start, $end, $register_channel, $promote_channel = 0)
    {
        $query = BiUserStats1DayModel::getInstance()->getModel()
            ->where('date', $start)
            ->where('register_channel', '=', 'appStore')
            ->where('promote_channel', '=', $promote_channel)
            ->column('id');
        return $query;
    }

    public static function getChargeSumByRegChannel($start, $end, $register_channel, $type)
    {

    }

    public static function getChargeCountByRegChannel($start, $end, $register_channel, $type)
    {

    }

    public static function getChargeSum($data, $json_key)
    {
        $amount = 0;
        if (array_key_exists($json_key, $data)) {
            foreach ($data[$json_key]['data'] as $channelCharge) {
                foreach ($channelCharge as $charge_data) {
                    $amount += $charge_data['amount'];
                }
            }
        }
        return $amount / 10;
    }

    public static function getAgentChargeSum($data, $json_key)
    {
        $amount = 0;
        if (array_key_exists($json_key, $data)) {
            foreach ($data[$json_key]['data'] as $charge_data) {
                $amount += $charge_data['amount'];
            }
        }

        return $amount / 10;
    }

    public static function getAgentChargeUsers($data, $json_key)
    {
        $users = [];
        if (array_key_exists($json_key, $data)) {
            $users = $data[$json_key]['users'];
        }

        return $users;
    }

    public static function getAgentChargeCount($data, $json_key)
    {
        $count = 0;
        if (array_key_exists($json_key, $data)) {
            $count = $data[$json_key]['users'];
        }
        return $count;
    }

    public function dealDayStats($start, $end, array $stats_cloumn, $type, $is_reg = false, $users = [])
    {
        $where = [
            ['date', '>=', $start],
            ['date', '<', $end],
        ];

        foreach ($stats_cloumn as $column => $column_value) {
            $where[] = [
                [$column, '=', $column_value],
            ];
        }

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

        $cloumn_key = implode(':', array_values($stats_cloumn));

        $this->calculate($where, $data, $obj, $cloumn_key);

        return isset($data[$cloumn_key]) ? $data[$cloumn_key] : [];
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

    protected function calculate($where, &$data, &$obj, $cloumn_key)
    {
        $count = $this->getStats($where);
        $page = ceil($count / AnslysisConst::LIMIT);

        for ($i = 0; $i < $page; $i++) {
            $offset = $i * AnslysisConst::LIMIT;
            $res = $this->getStats($where, $offset, true);
            $this->calDayData($res, $data, $obj, $cloumn_key);
        }
    }

    protected function calDayData($res, &$data, &$obj, $stats_column)
    {
        foreach ($res as $mins5_data) {
            $uid = $mins5_data['uid'];
            // $promote_channel = $mins5_data['promote_channel'];
            // $source = $mins5_data['source'];
            // $register_channel = $mins5_data['register_channel'];
            // $register_time = $mins5_data['register_time'];

            $user_json = json_decode($mins5_data['json_data'], true);

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
}

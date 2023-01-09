<?php

namespace app\admin\service;

use app\admin\model\LogindetailModel;
use think\facade\Db;

class RetentionService
{
    public $days = ['day_0' => 0, 'day_1' => 1, 'day_2' => 2, 'day_3' => 3, 'day_4' => 4, 'day_5' => 5, 'day_6' => 6, 'day_7' => 7, 'day_14' => 14, 'day_15' => 15, 'day_29' => 29, 'day_30' => 30];
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getRetentionDays($start)
    {
        $start_date = date("Y-m-d", strtotime($start));
        $retention_days = [];
        foreach ($this->days as $k => $day) {
            $retention_days[$k] = date("Y-m-d", strtotime("{$start_date} + {$day}day"));
        }
        return $retention_days;
    }

    public function getRetentionByDays($start, $days = [2, 3, 7, 30])
    {
        $start_date = date("Y-m-d", strtotime($start));
        $retention_days = [];
        foreach ($this->days as $k => $day) {
            if (in_array($day, $days)) {
                $retention_days[$k] = date("Y-m-d", strtotime("{$start_date} + {$day}day"));
            }
        }
        return $retention_days;
    }

    public function LoginRetention(string $users, $start)
    {
        if (!empty($users)) {
            $start_timestamp = strtotime($start);
            $end_timestamp = strtotime("{$start} + 30days");

            $models = LogindetailModel::getInstance()->getModels(explode(",",$users));
            $returnResData = [];
            foreach ($models as $model) {
                if($model->getList()){
                    $res = $model->getModel()->field("DATE_FORMAT(FROM_UNIXTIME(ctime),'%Y-%m-%d') date,count(DISTINCT(user_id)) as count")
                        ->where("user_id", "in", $model->getList())
                        ->where("ctime", ">=", $start_timestamp)
                        ->where("ctime", "<=", $end_timestamp)
                        ->group("date")
                        ->select()->toArray();
                    $returnResData = array_merge($returnResData, $res);
                }

            }

            $result = [];
            foreach ($returnResData as $item) {
                if (isset($result[$item['date']])) {
                    $result[$item['date']]['count'] += $item['count'];
                } else {
                    $result[$item['date']]['date'] = $item['date'];
                    $result[$item['date']]['count'] = $item['count'];
                }
            }
            return array_values($result);
        } else {
            return [];
        }
    }

    public function getRetention(string $users, $start)
    {
        $retention = $this->getRetentionDays($start);
        $login = $this->LoginRetention($users, $start);
        $login_count = array_column($login, 'count', 'date');
        $data = [];

        $count_day_0 = $login_count[$start] ?? 1;
        foreach ($retention as $key => $date) {
            $count = $login_count[$date] ?? 0;
            $data[$key] = round($count * 100 / $count_day_0, 2);
        }
        return $data;
    }

    //计算留存
    public function getRetentionByType(array $users, $type, $start_date, $room_id = 0)
    {

        $users = array_filter($users);
        $days = [2 => '次日留存', 3 => '3日留存', 7 => '7日留存', 30 => '30日留存'];
        $base_count = count($users);
        $keep_info = $this->getRetentionByTypeAndDate($type, $start_date, $room_id);

        $data = [];
        if ($users && $keep_info != null) {
            foreach ($days as $day => $retention_name) {
                $column = 'keep_' . $day;
                $retention_day = 'day_' . $day;

                $keep_users_str = $keep_info[$column];
                $keep_users = explode(",", $keep_users_str);

                $retention_users = array_values(array_intersect($users, $keep_users));
                $count = count($retention_users);

                $retention_date = $day - 1;
                $data[$retention_day]['start_date'] = date("Y-m-d", strtotime("{$start_date} + {$retention_date}day"));
                $data[$retention_day]['end_date'] = date("Y-m-d", strtotime("{$start_date} + {$day}day"));
                $data[$retention_day]['users'] = implode(',', $retention_users);
                $data[$retention_day]['count'] = $count;
                $data[$retention_day]['retention_name'] = $retention_name;
                $data[$retention_day]['rate'] = $base_count == 0 ? 0 : round($count * 100 / $base_count, 2) . '%';
            }
        }

        return $data;
    }

    //计算留存
    public function getRetentionByType2($type, $date, $users)
    {

        $retention_days = $this->getRetentionByDays($date);

        $keep_info = $this->getRetentionByTypeAndDate($type, $date);

        $count_day_0 = $login_count[$date] ?? 1;
        foreach ($retention_days as $key => $date) {
            $count = $login_count[$date] ?? 0;
            $data[$key] = round($count * 100 / $count_day_0, 2);
        }

        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day]['count'] = 0;
            $data[$retention_day]['users'] = [];
            if (array_key_exists($retention_date, $keep_info)) {
                $data[$retention_day]['count'] = $keep_info[$retention_date]['count'];
                $data[$retention_day]['users'] = explode(',', $keep_info[$retention_date]['users']);
            }
        }
        return $data;
    }

    public function getRetentionByTypeAndDate($type, $date, $room_id = 0)
    {
        return Db::table('bi_user_keep_day')
            ->where('date', '=', $date)
            ->where('type', '=', $type)
            ->where('room_id', '=', $room_id)
            ->find();
    }
}

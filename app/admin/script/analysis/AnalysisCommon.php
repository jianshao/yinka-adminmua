<?php
namespace app\admin\script\analysis;

use think\facade\Db;

class AnalysisCommon
{
    const SECONDS_MINUTE_5 = 5 * 60;
    const SECONDS_MINUTE_30 = 30 * 60;
    const SECONDS_DAY_1 = 3600 * 24;
    const LIMIT = 500;

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getStartEndDate($start = '', $end = '', array $next_day = [])
    {
        if ($start && $end) {
            $start_date = date("Y-m-d", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m-d", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m-d", strtotime($start));
            $end_date = date("Y-m-d", strtotime("{$start} + 1days"));
        } elseif (in_array($this->getIntervalTime(self::SECONDS_MINUTE_5), $next_day)) {
            //每天凌晨30分钟统计前一天的数值
            $start_date = date("Y-m-d", strtotime("-1days"));
            $end_date = date("Y-m-d");
        } else {
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d", strtotime("+1days"));
        }

        $days = (strtotime($end_date) - strtotime($start_date)) / self::SECONDS_DAY_1;
        return [$start_date, $end_date, $days];
    }

    protected function calCulateStartEndDate($start = '', $end = '', array $next_day = [], $interval = self::SECONDS_MINUTE_5)
    {
        if ($start && $end) {
            $start_date = date("Y-m-d", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m-d", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m-d", strtotime($start));
            $end_date = date("Y-m-d", strtotime("{$start} + 1days"));
        } elseif (in_array($this->getIntervalTime($interval), $next_day)) {
            //隔天统计
            $start_date = date("Y-m-d", strtotime("-1days"));
            $end_date = date("Y-m-d");
        } else {
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d", strtotime("+1days"));
        }

        $days = (strtotime($end_date) - strtotime($start_date)) / self::SECONDS_DAY_1;
        return [$start_date, $end_date, $days];
    }

    public function getLoopCount($start, $end, $seconds)
    {
        return ceil((strtotime($end) - strtotime($start)) / $seconds);
    }

    public function getTime($start, $interval, $date_format, $i = 1)
    {
        return date($date_format, (strtotime($start) + (int) $interval * $i));
    }

    public function getIntervalTime($seconds)
    {
        return floor((time() - strtotime(date("Y-m-d"))) / $seconds);
    }

    public function getStartEndMonth($start = '', $end = '')
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

    public static function getArrayKeyValue(array $data, $key)
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }
        return [];
    }

    public static function getDiffDays($end, $start)
    {
        return (strtotime($end) - strtotime($start)) / 24 / 60 / 60;
    }

    public static function numDivision($num)
    {
        return $num == 0 ? 1 : $num;
    }

    /*
     *获取表数据
     */
    public static function getStatsItems($model, $where, $offset = 0, $limit = self::LIMIT)
    {
        return $model->where($where)
            ->field("*")
            ->limit($offset, $limit)
            ->select()
            ->toArray();
    }

    /*
     *获取表数据条数
     */
    public static function getStatsCount($table, $where)
    {
        return Db::table($table)->where($where)->count();
    }

    /*
     *获取表数据条数
     */
    public static function getPage($count, $limit = self::LIMIT)
    {
        return ceil($count / $limit);
    }

    /**
     *更新同步表数据ID
     */
    public function insertOrUpdate($data, $table)
    {
        Db::table($table)->extra("IGNORE")
            ->duplicate("date,interval_time,uid,promote_channel,register_channel,register_time,source,json_data")
            ->insert($data);
    }

    /**
     *批量更新同步表数据ID
     */
    public function insertOrUpdateMul($data, $table, string $columns)
    {
        //$data为二维数据
        Db::table($table)->extra("IGNORE")->duplicate($columns)->insertall($data);
    }
}
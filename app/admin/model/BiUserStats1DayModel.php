<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiUserStats1DayModel extends ModelDao
{
    protected $table = 'bi_user_stats_1day';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiUserStats1DayModel();
        }
        return self::$instance;
    }

    /**
     * 根据时间段来获取注册的用户
     * @param $startTime 时间戳
     * @param $endTime
     * @param string $field
     */
    public function getRegisterUserByTime($startTime, $endTime, $field = "*")
    {
        $where = [];
        //判断endTime 是否整天数据
        if ($endTime % (24 * 3600) == 0) {
            //如果是整天数据前闭后开
            $where[] = ['date', "<", date('Y-m-d', $endTime)];
        } else {
            //如果是整天数据前闭后闭
            $where[] = ['date', "<=", date('Y-m-d', $endTime)];
        }
        $where[] = ['date', ">=", date('Y-m-d', $startTime)];
        $where[] = ['register_time', ">=", date('Y-m-d H:i:s', $endTime)];
        $where[] = ['register_time', "<", date('Y-m-d H:i:s', $endTime)];
        return $this->getModel()->where($where)->field($field)->select()->toArray();
    }

    /**
     * 按日期段切分每天日期
     * @param $st '2021-07-01'
     * @param $et $st '2021-07-01'
     * @return array
     */
    public function getdateEveryDate($st, $et)
    {
        $restimeNode = [];
        $stime = strtotime($st);
        $etime = strtotime($et);
        while (true) {
            $node = strtotime(date('Y-m-d', strtotime("+1days", $stime)));
            echo date('Y-m-d', $stime) . PHP_EOL;
            if ($node < $etime) {
                $restimeNode[] = ["st" => $stime, "et" => $node];
                $stime = $node;
            } else {
                $restimeNode[] = ["st" => $stime, "et" => $etime];
                break;
            }
        }
        return $restimeNode;
    }
}

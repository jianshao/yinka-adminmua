<?php
namespace app\common\model;

use think\Model;


class MonitoringModel extends Model
{
    protected $table = 'zb_monitoring';
    protected $pk = 'monitoring_id';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MonitoringModel();
        }
        return self::$instance;
    }
}
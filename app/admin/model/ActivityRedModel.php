<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class ActivityRedModel extends ModelDao
{
    protected $table = 'zb_activity_red';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ActivityRedModel();
        }
        return self::$instance;
    }
}

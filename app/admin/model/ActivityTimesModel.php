<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class ActivityTimesModel extends ModelDao
{
    protected $table = 'bi_activity_times';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

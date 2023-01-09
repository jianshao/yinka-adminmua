<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class DaysUserTurntableDatasModel extends ModelDao
{
    protected $table = 'bi_days_user_turntable_datas';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DaysUserTurntableDatasModel();
        }
        return self::$instance;
    }
}

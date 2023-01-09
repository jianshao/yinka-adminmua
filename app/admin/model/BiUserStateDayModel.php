<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiUserStateDayModel extends ModelDao
{

    protected $table = 'bi_user_stats_1day';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SELF();
        }
        return self::$instance;
    }
}

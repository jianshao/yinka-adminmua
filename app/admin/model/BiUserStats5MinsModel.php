<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiUserStats5MinsModel extends ModelDao
{
    protected $table = 'bi_user_stats_5mins';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

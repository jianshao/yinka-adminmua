<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class DukeModel extends ModelDao
{
    protected $table = 'zb_duke';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DukeModel();
        }
        return self::$instance;
    }
}

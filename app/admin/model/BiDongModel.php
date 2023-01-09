<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiDongModel extends ModelDao
{
    protected $table = 'bi_dong';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiDongModel();
        }
        return self::$instance;
    }
}

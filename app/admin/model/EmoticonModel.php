<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class EmoticonModel extends ModelDao
{
    protected $table = 'zb_emoticon';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new EmoticonModel();
        }
        return self::$instance;
    }
}

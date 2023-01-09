<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiRegisterChannelModel extends ModelDao
{
    protected $table = 'bi_register_channel';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiRegisterChannelModel();
        }
        return self::$instance;
    }

}

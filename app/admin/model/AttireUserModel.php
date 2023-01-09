<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class AttireUserModel extends ModelDao
{
    protected $table = 'zb_attire_user';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AttireUserModel();
        }
        return self::$instance;
    }
}

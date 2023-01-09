<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserChargeStaticsModel extends ModelDao
{
    protected $table = 'zb_user_charge_statics';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserChargeStaticsModel();
        }
        return self::$instance;
    }
}

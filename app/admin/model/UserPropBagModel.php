<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserPropBagModel extends ModelDao
{
    protected $table = 'zb_user_prop_bag';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserPropBagModel();
        }
        return self::$instance;
    }
}

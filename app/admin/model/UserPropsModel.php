<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserPropsModel extends ModelDao
{
    protected $table = 'zb_user_props';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserPropsModel();
        }
        return self::$instance;
    }
}

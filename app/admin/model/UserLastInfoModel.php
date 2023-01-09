<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserLastInfoModel extends ModelDao
{
    protected $table = 'zb_user_last_info';
    protected $pk = 'user_id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserLastInfoModel();
        }
        return self::$instance;
    }
}

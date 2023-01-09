<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserBlackModel extends ModelDao
{
    protected $table = 'zb_user_black';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserBlackModel();
        }
        return self::$instance;
    }
}

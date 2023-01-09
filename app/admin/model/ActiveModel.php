<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class ActiveModel extends ModelDao
{
    //活动配置表
    protected $table = 'zb_active';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ActiveModel();
        }
        return self::$instance;
    }
}

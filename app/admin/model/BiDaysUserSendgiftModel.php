<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiDaysUserSendgiftModel extends ModelDao
{
    protected $table = 'bi_days_user_sendgift';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiDaysUserSendgiftModel();
        }
        return self::$instance;
    }

}

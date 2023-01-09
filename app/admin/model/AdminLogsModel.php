<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class AdminLogsModel extends ModelDao
{
    protected $table = 'yyht_admin_logs';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AdminLogsModel();
        }
        return self::$instance;
    }
}

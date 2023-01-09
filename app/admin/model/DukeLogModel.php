<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class DukeLogModel extends ModelDao
{
    protected $table = 'zb_duke_log';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DukeLogModel();
        }
        return self::$instance;
    }
}

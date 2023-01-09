<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class InvitePointHistoryModel extends ModelDao
{
    protected $table = 'invite_point_history';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new InvitePointHistoryModel();
        }
        return self::$instance;
    }

}

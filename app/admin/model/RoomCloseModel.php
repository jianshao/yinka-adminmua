<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class RoomCloseModel extends ModelDao
{
    protected $table = 'zb_room_close';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //改成30分钟，1小时，24小时，3天、7天、15天、30天
    const TIMENODE = [
        "30分钟" => 1800,
        "1小时" => 3600,
        "24小时" => 86400,
        "3天" => 259200,
        "7天" => 604800,
        "15天" => 1296000,
        "30天" => 2592000,
    ];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomCloseModel();
        }
        return self::$instance;
    }

}

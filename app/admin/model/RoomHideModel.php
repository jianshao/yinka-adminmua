<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class RoomHideModel extends ModelDao
{
    protected $table = 'zb_room_hide';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomHideModel();
        }
        return self::$instance;
    }

}

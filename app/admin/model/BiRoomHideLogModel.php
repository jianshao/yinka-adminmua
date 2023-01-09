<?php
//对账明细数据

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiRoomHideLogModel extends ModelDao
{
    protected $table = 'bi_room_hide_log';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

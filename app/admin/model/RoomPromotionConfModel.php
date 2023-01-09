<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;

class RoomPromotionConfModel extends ModelDao
{
    protected $table = 'zb_room_promotion_conf';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomPromotionConfModel();
        }
        return self::$instance;
    }

}

<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;

class PromotionRoomTimesConfModel extends ModelDao
{
    protected $table = 'zb_promote_room_times_conf';
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

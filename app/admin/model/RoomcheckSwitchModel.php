<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;


class RoomcheckSwitchModel extends ModelDao
{
    protected $table = 'zb_roomcheck_switch';
    protected static $instance;
    protected  $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomcheckSwitchModel();
        }
        return self::$instance;
    }
}

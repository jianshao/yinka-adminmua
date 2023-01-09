<?php
/**
 * @author ly
 * 用户举报表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class DeviceIpModel extends ModelDao
{
    protected $table = 'zb_device_ip';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DeviceIpModel();
        }
        return self::$instance;
    }

}
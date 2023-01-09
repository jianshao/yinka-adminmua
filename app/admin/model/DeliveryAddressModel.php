<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class DeliveryAddressModel extends ModelDao
{
    protected $table = 'zb_delivery_address';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DeliveryAddressModel();
        }
        return self::$instance;
    }
}

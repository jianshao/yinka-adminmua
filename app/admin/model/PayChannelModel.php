<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class PayChannelModel extends ModelDao
{

    protected $table = 'zb_paychannel';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PayChannelModel();
        }
        return self::$instance;
    }
}

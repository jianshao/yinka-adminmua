<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class MarketChannelModel extends ModelDao
{

    protected $table = 'zb_market_channel';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MarketChannelModel();
        }
        return self::$instance;
    }
}
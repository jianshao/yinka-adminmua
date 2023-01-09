<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;

class GoldCoinModel extends ModelDao
{

    protected $table = 'zb_goldcoin_box';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GoldCoinModel();
        }
        return self::$instance;
    }

}

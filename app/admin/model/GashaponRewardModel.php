<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class GashaponRewardModel extends ModelDao
{
    protected $table = 'zb_gashapon_reward';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GashaponRewardModel();
        }
        return self::$instance;
    }

}

<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class RedPacketsDetailModel extends ModelDao
{
    protected $table = 'zb_redpackets_detail';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RedPacketsDetailModel();
        }
        return self::$instance;
    }
}

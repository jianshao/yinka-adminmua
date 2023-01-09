<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class SiteconfigModel extends ModelDao
{
    protected $table = 'zb_siteconfig';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SiteconfigModel();
        }
        return self::$instance;
    }
}

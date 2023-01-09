<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class PhotoWallModel extends ModelDao
{
    protected $table = 'zb_photo_wall';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PhotoWallModel();
        }
        return self::$instance;
    }
}

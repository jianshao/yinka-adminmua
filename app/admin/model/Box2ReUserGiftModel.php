<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class Box2ReUserGiftModel extends ModelDao
{
    protected $table = 'yyht_box2_re_user_gift';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Box2ReUserGiftModel();
        }
        return self::$instance;
    }
}

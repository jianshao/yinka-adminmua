<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class InviteQrcodesModel extends ModelDao
{
    protected $table = 'invite_qrcodes';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new InviteQrcodesModel();
        }
        return self::$instance;
    }

}

<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class InviteQrcodeMemberRelationModel extends ModelDao
{
    protected $table = 'invite_qrcode_member_relation';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new InviteQrcodeMemberRelationModel();
        }
        return self::$instance;
    }

}

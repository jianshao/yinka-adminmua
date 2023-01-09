<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class InviteGuildAnchorsModel extends ModelDao
{
    protected $table = 'invite_guild_anchors';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new InviteGuildAnchorsModel();
        }
        return self::$instance;
    }

}

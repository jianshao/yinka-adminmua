<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class ChannelPointsModel extends ModelDao
{
    protected $table = 'bi_channel_points';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChannelPointsModel();
        }
        return self::$instance;
    }

}

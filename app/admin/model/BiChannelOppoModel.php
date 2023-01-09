<?php
/**
 * @author ly
 * 用户举报表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiChannelOppoModel extends ModelDao
{
    protected $table = 'bi_channel_oppo';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiChannelOppoModel();
        }
        return self::$instance;
    }
}

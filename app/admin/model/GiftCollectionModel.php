<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;

class GiftCollectionModel extends ModelDao
{
    protected $table = 'cfg_gift_collection';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

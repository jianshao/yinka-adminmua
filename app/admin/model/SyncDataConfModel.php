<?php
/**
 * User: li
 * Date: 2019
 * 金币
 */
namespace app\admin\model;
use app\core\mysql\ModelDao;

class SyncDataConfModel extends ModelDao {

    protected $table = 'sync_data_conf';
    protected $pk = 'id';
    protected static $instance;
    protected  $serviceName = 'bi';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}
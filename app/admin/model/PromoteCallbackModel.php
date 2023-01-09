<?php
/**
 * @author ly
 * 用户举报表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class PromoteCallbackModel extends ModelDao
{
    protected $table = 'zb_promote_callback';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SELF();
        }
        return self::$instance;
    }
}

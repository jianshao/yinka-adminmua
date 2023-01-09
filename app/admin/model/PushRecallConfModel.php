<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */

namespace app\admin\model;

use app\core\mysql\ModelDao;

class PushRecallConfModel extends ModelDao
{
    protected $table = 'zb_push_recall_conf';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PushRecallConfModel();
        }
        return self::$instance;
    }


}

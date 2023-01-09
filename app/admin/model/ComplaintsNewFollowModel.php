<?php
//对账数据

namespace app\admin\model;

use app\core\mysql\ModelDao;

class ComplaintsNewFollowModel extends ModelDao
{
    protected $table = 'zb_complaints_new_follow';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}

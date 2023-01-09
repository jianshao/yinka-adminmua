<?php
/**

 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class MembercashModel extends ModelDao
{

    protected $table = 'zb_member_cash';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MembercashModel();
        }
        return self::$instance;
    }

}

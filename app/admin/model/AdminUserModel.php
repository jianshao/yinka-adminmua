<?php
/**
 * @author ly
 * 后台用户表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class AdminUserModel extends ModelDao
{
    protected $table = 'yyht_admin';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AdminUserModel();
        }
        return self::$instance;
    }

    public function addUser($data)
    {
        return $this->getModel()->insertGetId($data);
    }

    public function selfVisiableArray()
    {
        $array = $this->toArray();
        $outArray = array_diff_key($array, ['created' => null, 'updated' => null, 'last_login_time' => null]);
        return $outArray;
    }

    public function getAdminInfo($where)
    {
        return $this->getModel()->where($where)->findOrEmpty()->toArray();
    }

    public function getAdminList($where, $field = '*')
    {
        return $this->getModel()->field($field)->where($where)->select()->toArray();
    }
}

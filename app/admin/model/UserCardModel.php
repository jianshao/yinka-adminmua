<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserCardModel extends ModelDao
{
    protected $table = 'zb_user_card';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserCardModel();
        }
        return self::$instance;
    }

    public function getUserCardBywhereOne(array $where, $field = '*')
    {
        return $this->getModel()->field($field)->where($where)->find();
    }

    /**获取所有实名认证用户
     * @return array
     */
    public function getList($where, $offset, $limit)
    {
        $res = $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

}

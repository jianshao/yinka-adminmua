<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserAttireModel extends ModelDao
{
    protected $table = 'zb_attire_user';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserAttireModel();
        }
        return self::$instance;

    }
    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setAttire($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }

    /*
     * 查询列表
     */
    public function AttierList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('id', 'asc')->limit($offset, $limit)->select()->toArray();
    }
}

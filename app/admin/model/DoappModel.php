<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class DoappModel extends ModelDao
{
    protected $table = 'zb_doapp';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DoappModel();
        }
        return self::$instance;
    }

    /**获取列表
     * @param $where    where条件
     * @param $offset   偏移
     * @param $limit    条数
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

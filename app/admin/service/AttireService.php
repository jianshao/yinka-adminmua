<?php

namespace app\admin\service;

use app\admin\model\AttireModel;

class AttireService extends AttireModel
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AttireService();
        }
        return self::$instance;
    }

    public function addAttire(array $data)
    {
        return AttireModel::getInstance()->getModel()->insert($data);
    }

    public function getAttierList(array $where, $field = '*', array $limit)
    {
        return AttireModel::getInstance()->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->select()->toArray();
    }

    public function getAttireCountNum(array $where)
    {
        return AttireModel::getInstance()->getModel()->where($where)->count();
    }

    /*
     * 查询礼物列表
     */
    public function AttierList($where, $offset, $limit)
    {
        return $this->where($where)->order('id', 'asc')->limit($offset, $limit)->select()->toArray();
    }
}

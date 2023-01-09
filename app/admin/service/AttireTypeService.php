<?php

namespace app\admin\service;

use app\admin\model\AttireTypeModel;

class AttireTypeService extends AttireTypeModel
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AttireTypeService();
        }
        return self::$instance;
    }

    public function addAttireType(array $data)
    {
        return AttireModel::getInstance()->getModel()->insert($data);
    }

    public function getAttierTypeList(array $where, $field = '*', array $limit)
    {
        return AttireModel::getInstance()->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->select()->toArray();
    }

    public function getAttireTypeCountNum(array $where)
    {
        return AttireModel::getInstance()->getModel()->where($where)->count();
    }

    /*
     * 查询礼物列表
     */
    public function AttierTypeList($where = [])
    {
        if (empty($where)) {
            return $this->order('id', 'asc')->select()->toArray();
        }
        return $this->where($where)->order('id', 'asc')->select()->toArray();
    }
    public function TypeList()
    {
        return $this->order('id', 'asc')->select()->toArray();
    }
}

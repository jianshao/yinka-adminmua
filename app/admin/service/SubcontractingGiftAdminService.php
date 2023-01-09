<?php

namespace app\admin\service;

use app\admin\model\SubcontractingGiftAdminModel;

class SubcontractingGiftAdminService extends SubcontractingGiftAdminModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SubcontractingGiftAdminService();
        }
        return self::$instance;
    }

    public function getSubcontractingGiftAdminListByWhere(array $where, $field = '*', $limit = [1, 20])
    {
        return SubcontractingGiftAdminModel::getInstance()->getModel()->field($field)->where($where)->select()->toArray();
    }

    public function getSubcontractingGiftAdminCountByWhere(array $where)
    {
        return SubcontractingGiftAdminModel::getInstance()->getModel()->where($where)->count();
    }

    public function addSubcontractingGiftAdmin($data)
    {
        return SubcontractingGiftAdminModel::getInstance()->getModel()->save($data);
    }

    public function editSubcontractingGiftAdmin(array $where, array $data)
    {
        return SubcontractingGiftAdminModel::getInstance()->getModel()->where($where)->save($data);
    }

    public function subcontractingGiftAdminInfo(array $where, $field = '*')
    {
        return SubcontractingGiftAdminModel::getInstance()->getModel()->field($field)->where($where)->find();
    }
}

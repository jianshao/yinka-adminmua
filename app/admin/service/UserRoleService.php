<?php

namespace app\admin\service;

use app\admin\model\UserRoleModel;

class UserRoleService extends UserRoleModel
{
    protected static $instance;

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserRoleService();
        }
        return self::$instance;
    }

    public function getUserRole(array $where, string $field)
    {
        return UserRoleModel::getInstance()->getUserRoleFind($where, $field);
    }

    public function addUserRole(array $data)
    {
        return UserRoleModel::getInstance()->getModel()->insertGetId($data);
    }

    public function editUserToRole(array $data, array $where)
    {
        return UserRoleModel::getInstance()->getModel()->where($where)->save($data);
    }
}

<?php

namespace app\admin\service;

use app\admin\model\RoleModel;

class RoleService extends RoleModel
{
    protected static $instance;

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoleService();
        }
        return self::$instance;
    }

    public function getRoleList(array $data, $field = '*', $page = 1, $size = 20, $order = ' order by id desc')
    {
        $where = '';
        if ($data) {
            foreach ($data as $key => $val) {
                $where .= $key . ' = ' . "'" . $val . "'" . ' and ';
            }
            $where = substr($where, 0, -4);
        }
        return RoleModel::getInstance()->getRoleLists($where, $field, $page, $size, $order);
    }

    public function getRoleListCount(array $where)
    {
        return RoleModel::getInstance()->getRoleListCounts($where);
    }

    public function getRoleItem(array $where, $field = '*')
    {
        return RoleModel::getInstance()->getModel()->field($field)->where($where)->find();
    }

    public function addRole(array $data)
    {
        return RoleModel::getInstance()->getModel()->insert($data);
    }

    public function editRole(array $data, array $where)
    {
        return RoleModel::getInstance()->getModel()->where($where)->save($data);
    }

    public function getRoleData(array $where, $field = '*')
    {
        return RoleModel::getInstance()->getModel()->field($field)->where($where)->select()->toArray();
    }

}

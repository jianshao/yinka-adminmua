<?php

namespace app\admin\service;

use app\admin\model\RoleMenuModel;

class RoleMenuService extends RoleMenuModel
{
    protected static $instance;

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoleMenuService();
        }
        return self::$instance;
    }

    /*
     * ����role_id||menu_id||id��ȡ��ɫ���в˵�
     * @param user_id
     * @param id
     * @return array
     */
    public function getRoleMenu($role_id, $field)
    {
        //��ѯ��ɫ�˵�
        return RoleMenuModel::getInstance()->getRoleMenuList($role_id, $field);
    }

    /*
     *
     * @�û���ǰ��ɫ��Ӧ�Ĳ˵���id
     */
    public function getRoleToMenuLists(array $where, $field = '*', $sort = array('id', 'desc'))
    {
        return RoleMenuModel::getInstance()->getModel()->field($field)->where($where)->order($sort[0], $sort[1])->select()->toArray();
    }

    public function editRoleToMenu(array $data, array $where)
    {
        return RoleMenuModel::getInstance()->getModel()->where($where)->save($data);
    }

    public function addRoleToMenu(array $data)
    {
        return RoleMenuModel::getInstance()->getModel()->insert($data);
    }

    public function addRoleToManyMenu(array $data)
    {
        return RoleMenuModel::getInstance()->getModel()->insertAll($data);
    }

}

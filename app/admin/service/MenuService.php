<?php

namespace app\admin\service;

use app\admin\model\MenuModel;

class MenuService extends MenuModel
{
    protected static $instance;

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MenuService();
        }
        return self::$instance;
    }

    public function getMenuLists(array $where, $field = '*')
    {
        return MenuModel::getInstance()->getModel()->field($field)->where($where)->order('seq desc')->select();
    }

    public function getMenuItems(array $where, $field = '*')
    {
        return MenuModel::getInstance()->getModel()->field($field)->where($where)->find();
    }

    public function addMenuItems(array $data)
    {
        return MenuModel::getInstance()->getModel()->save($data);
    }

    public function editMenuItems(array $data, array $where)
    {
        return MenuModel::getInstance()->getModel()->where($where)->save($data);
    }
    public function editMenuItemsAll(array $data)
    {
        return MenuModel::getInstance()->getModel()->saveAll($data);
    }
    public function getMenuItemsWhereIn($where, $field = '*', $where_in)
    {
        return MenuModel::getInstance()->getModel()->field($field)->whereIn($where_in, $where)->order('seq desc')->select()->toArray();
    }

}
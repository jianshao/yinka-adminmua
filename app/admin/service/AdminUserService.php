<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */

namespace app\admin\service;

use app\admin\model\AdminUserModel;

class AdminUserService extends AdminUserModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AdminUserService();
        }
        return self::$instance;
    }

    public function getAdminUserInfo(array $where, $field = '*')
    {
        return AdminUserModel::getInstance()->getModel()->field($field)->where($where)->find();
    }

    public function addAdminuser(array $data)
    {
        return AdminUserModel::getInstance()->addUser($data);
    }

    /*
     * @获取后台用户列表
     */
    public function getUserList(array $data, $field = '*', $page = 1, $size = 20, $order = 'order by id desc', $group = '')
    {
        if ($data) {
            $where = 'where ';
            foreach ($data as $key => $val) {
                $where .= $key . ' = ' . "'" . $val . "'" . ' and ';
            }
            $where = substr($where, 0, -4);
        } else {
            $where = '';
        }

        return AdminUserModel::getUserLists($where, $field, $page, $size, $order, $group);
    }

    public function editUserItems(array $data, array $where)
    {
        return AdminUserModel::getInstance()->getModel()->where($where)->save($data);
    }
    public function getUserListCount(array $where)
    {
        return AdminUserModel::getInstance()->getModel()->where($where)->count();
    }
}

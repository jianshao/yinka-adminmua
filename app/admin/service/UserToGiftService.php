<?php

namespace app\admin\service;

use app\admin\model\UserToGiftModel;

class UserToGiftService extends UserToGiftModel
{
    protected static $instance;

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserToGiftService();
        }
        return self::$instance;
    }

    public function addUserToGift($data)
    {
        return UserToGiftModel::getInstance()->addUserToGift($data);
    }

    public function getUserToGiftLists($where, $field, $page = 1, $size = 20, $order_field = 'id', $sorts = 'desc')
    {
        return UserToGiftModel::getInstance()->getUserToGiftLists($where, $field, $page, $size, $order_field, $sorts);
    }

    public function getUserToGiftNum($where = array())
    {
        return UserToGiftModel::getInstance()->getUserToGiftNum($where);
    }

    public function getUserToGiftItem(array $where, $field = '*')
    {
        return UserToGiftModel::getInstance()->getUserToGiftItem($where);
    }

    public function updateUserToGiftItem(array $data, array $where)
    {
        return UserToGiftModel::getInstance()->getModel()->where($where)->save($data);
    }
}

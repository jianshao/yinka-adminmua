<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserToGiftModel extends ModelDao
{
    protected $table = 'yyht_re_user_gift';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserToGiftModel();
        }
        return self::$instance;
    }

    public function addUserToGift($data)
    {
        return $this->getModel()->insert($data);
    }

    public function getUserToGiftLists($where, $field, $page = 1, $size = 20, $order_field = 'id', $sorts = 'desc')
    {
        return $this->getModel()->field($field)->where($where)->order($order_field, $sorts)->limit($page, $size)->select()->toArray();
    }

    public function getUserToGiftNum($where = array())
    {
        return $this->getModel()->where($where)->count();
    }

    public function getUserToGiftItem(array $where, $field = '')
    {
        return $this->getModel()->field($field)->where($where)->find();
    }
}

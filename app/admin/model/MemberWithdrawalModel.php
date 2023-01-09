<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class MemberWithdrawalModel extends ModelDao
{
    protected $table = 'yyht_member_withdrawal';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberWithdrawalModel();
        }
        return self::$instance;
    }

    /*
     * 根据where
     */
    public function getMemberWithdrawalByWhere(array $where, $field = '*', $limit = [0, 20], $sort = 'id desc')
    {
        return $this->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->order($sort)->select()->toArray();
    }

    public function getMemberWithdrawCountByWhere(array $where)
    {
        return $this->getModel()->where($where)->count();
    }

    public function getByWhere($where, $field)
    {
        return $this->getModel()->field($field)->where($where)->find();
    }

    public function getMemberWithdrawalByWhereCount(array $where, $sum = 'money')
    {
        return $this->getModel()->where($where)->sum($sum);
    }

    public function addMemberWithdrawal($data)
    {
        return $this->getModel()->insert($data);
    }

    public function withdrawalList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }
    public function exitWithdrawal($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
}

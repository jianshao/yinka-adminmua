<?php
/*
 *
 *类
 *Author:zhuziqi
 *email:zhuziqi@axingxing.com
 *Date:2019-11-02 17:54
 *
 */
namespace app\admin\service;

use app\admin\model\MemberMoneyModel;

class MemberMoneyService extends MemberMoneyModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberMoneyService();
        }
        return self::$instance;
    }

    public function getMemberMoneyByWhere(array $where, $field = '*')
    {
        return MemberMoneyModel::getInstance()->getModel()->field($field)->where($where)->select()->toArray();
    }
    public function addMemberMoney(array $data)
    {
        return MemberMoneyModel::getInstance()->getModel()->insertGetId($data);
    }
}

<?php

namespace app\admin\service;

use app\admin\model\BoxGiftModel;

class BoxGiftService extends BoxGiftModel
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BoxGiftService();
        }
        return self::$instance;
    }

    public function addAttire(array $data)
    {
        return AttireModel::getInstance()->getModel()->insert($data);
    }

    public function getAttierList(array $where, $field = '*', array $limit)
    {
        return AttireModel::getInstance()->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->select()->toArray();
    }

    public function getAttireCountNum(array $where)
    {
        return AttireModel::getInstance()->getModel()->where($where)->count();
    }

    /*
     * 查询礼物列表
     */
    public function BoxGiftList($where, $offset, $limit)
    {
        if (!empty($where)) {
            return $this->getModel()->field('giftid,num,type')->where($where)->select()->toArray();
        } else {
            return $this->getModel()->field('giftid,num,type')->select()->toArray();
        }

//        return $this->alias('a')->field('a.*,b.gift_name')->join('zb_gift b', 'a.giftid = b.id')->where($where)->order('a.id','asc')->limit($offset,$limit)->select()->toArray();
    }
}

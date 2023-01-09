<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */

namespace app\admin\service;

use app\admin\model\ChargedetailModel;

class ChargedetailService extends ChargedetailModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChargedetailService();
        }
        return self::$instance;
    }

    /**所有充值列表
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回值
     */
    public function getList($where, $offset, $limit)
    {
        $res = ChargedetailModel::getInstance()->getList($where, $offset, $limit);
        return $res;
    }

    /**根据id获取字段值
     * @param $id
     * @param $field
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $res = ChargedetailModel::getInstance()->getOneById($id, $field);
        return $res;
    }

    public function getChargeDetailByWhere(array $where, $field = '*')
    {
        return ChargedetailModel::getInstance()->getModel()->field($field)->where($where)->order('id asc')->select();
    }

}
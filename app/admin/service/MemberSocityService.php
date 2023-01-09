<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */
namespace app\admin\service;

use app\admin\model\MemberSocityModel;

class MemberSocityService extends MemberSocityModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberSocityService();
        }
        return self::$instance;
    }

    /**统计当前数据
     * @param $where
     * @return mixed
     */
    public function countes($where)
    {
        $res = MemberSocityModel::getInstance()->count($where);
        return $res;
    }

    /**获取所有公会的列表接口
     * @param $where    where条件
     * @param $limit    limit条数
     * @return mixed
     */
    public function getList($where, $limit)
    {
        $res = MemberSocityModel::getInstance()->getListNew($where, $limit);
        return $res;
    }

    /**根据id获取字段值
     * @param $id
     * @param $field
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $res = MemberSocityModel::getInstance()->getOneById($id, $field);
        return $res;
    }

}

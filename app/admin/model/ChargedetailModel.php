<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class ChargedetailModel extends ModelDao
{
    protected $table = 'zb_chargedetail';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ChargedetailModel();
        }
        return self::$instance;
    }

    /**查询充值列表
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回值
     */
    public function getList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('addtime', 'desc')->limit($offset, $limit)->select()->toArray();
    }

    /**根据id获取该字段值
     * @param $where
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $where['id'] = $id;
        return $this->getModel()->where($where)->value($field);
    }
    /*
     * 根据条件查询
     */
    public function getByWhere($where, $field)
    {
        return $this->getModel()->where($where)->field($field)->select()->toArray();
    }
    /*
     * 查询总数
     */
    public function getByCount($where, $field, $sum)
    {
        return $this->getModel()->where($where)->find($field)->sum($sum);
    }

    /*
     * 查询列表
     */
    public function getUserList($where, $offset, $limit, $field)
    {
        return $this->alias('c')
            ->field($field)
            ->where($where)
            ->limit($offset, $limit)
            ->group('uid')
            ->select()->toArray();
    }

    /**统计
     * @param $where
     * @return array
     */
    public function getUserCount($where)
    {
        return $this->alias('c')
            ->where($where)
            ->group('uid')
            ->count();
    }

}

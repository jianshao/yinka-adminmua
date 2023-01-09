<?php
/**
 * @author ly
 * 用户举报表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class ComplaintsModel extends ModelDao
{
    protected $table = 'zb_complaints';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ComplaintsModel();
        }
        return self::$instance;
    }

    /**统计当前数据*/
    public function count($where)
    {
        return $this->getModel()->where($where)->count();
    }

    /**获取用户列表数据
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回值
     */

    public function getComplaintsListPage($where, $limit, $order)
    {
        return $this->getModel()->where($where)->limit($limit[0], $limit[1])->order($order[0], $order[1])->select()->toArray();
    }
}

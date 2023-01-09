<?php
/**
 * @author ly
 * 公告表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class MonitoringModel extends ModelDao
{
    protected $table = 'zb_monitoring';
    protected $pk = 'monitoring_id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MonitoringModel();
        }
        return self::$instance;
    }
    /*
     * 总数
     */
    public function count($where)
    {
        return $this->getModel()->where($where)->count();
    }
    /*
     * 列表
     */
    public function monitoringList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->limit($offset, $limit)->select()->toArray();
    }
    /*
     * 修改
     */
    public function monitoringEdit($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
    /*
     * 查找
     */
    public function searchUser($where)
    {
        return $this->getModel()->where($where)->find();
    }
}

<?php
/**
 * @author ly
 * 用户关注房间表
 * $date 2019
 */
namespace app\common\model;

use app\core\mysql\ModelDao;

class RoomFollowModel extends ModelDao
{
    protected $table = 'zb_room_follow';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomFollowModel();
        }
        return self::$instance;
    }

    /**统计当前数据
     * @param $where
     */
    public function count($where)
    {
        return $this->where($where)->count();
    }

    //查询单条
    public function getOne($where)
    {
        $res = $this->where($where)->find();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

}
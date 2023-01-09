<?php
/**
 * @author ly
 * 房间统计列表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class RoomLogintimeModel extends ModelDao
{
    protected $table = 'zb_room_logintime';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomLogintimeModel();
        }
        return self::$instance;
    }

    /*
     * 查询列表
     */
    public function giftList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }

    /*
     * 根据id获取该字段值
     * @param $where
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $where['id'] = $id;
        return $this->getModel()->where($where)->field($field)->find();
    }

}

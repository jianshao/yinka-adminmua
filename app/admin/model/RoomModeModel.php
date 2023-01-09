<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class RoomModeModel extends ModelDao
{
    protected $table = 'zb_room_mode';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomModeModel();
        }
        return self::$instance;
    }

    /**
     * 获取所有房间类型数据
     */
    public function getList($where)
    {
        $field = "id,room_mode";
        return $this->getModel()->field($field)->where($where)->select()->toArray();
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
    public function setRoom($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }

}

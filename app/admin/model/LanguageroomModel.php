<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class LanguageroomModel extends ModelDao
{
    protected $table = 'zb_languageroom';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'roomMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LanguageroomModel();
        }
        return self::$instance;
    }

    /*查询房间列表信息
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed
     */

    public function RoomList($where, $whereOr, $offset, $limit, $room_id)
    {
        return $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }

    public function RoomListNew($where, $whereOr, $offset, $limit)
    {
        $data = [];

        if ($whereOr) {
            $data = $this->getWhereOrData($whereOr, $offset, $limit);
        }

        if ($where) {
            $data = $this->getWhereData($where, $offset, $limit);
        }

        return $data;
    }

    public function getCount($where, $whereOr, $offset, $limit)
    {
        $data = $this->RoomListNew($where, $whereOr, $offset, $limit);
        return count($data);
    }

    /**根据id获取该字段值
     * @param $where
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $where['id'] = $id;
        return $this->getModel($id)->where($where)->value($field);
    }

    public function getValueById($id, $field)
    {
        $where['id'] = $id;
        return $this->getModel($id)->where($where)->value($field);
    }

    public function getList($where, $room_id = 0, $fields = '*')
    {
        $data = [];
        if ($where) {
            $models = $this->getAllModels();
            foreach ($models as $model) {
                $res = $model->getModel($room_id)->field($fields)->whereOr($where)->select();
                if ($res) {
                    $query = $res->toArray();
                    $data = array_merge($query, $data);
                }
            }
        }
        return $data;
    }

    public function getGuildRoomListMap()
    {
        $room_list = LanguageroomModel::getInstance()->getWhereAllData([['guild_id', '>', 0]], 'user_id, id, room_name, guild_id');
        return array_column($room_list, null, 'id');
    }

    /*更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function exitRoom($where, $data, $room_id)
    {
        return $this->getModel($room_id)->where($where)->update($data);

    }
    public function setRoom($where, $data, $room_id)
    {
        return $this->getModel($room_id)->where($where)->update($data);
    }
}

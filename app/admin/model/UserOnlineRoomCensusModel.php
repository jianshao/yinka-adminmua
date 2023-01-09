<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;
use think\facade\Db;

class UserOnlineRoomCensusModel extends ModelDao
{
    protected $table = 'zb_user_online_room_census';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserOnlineRoomCensusModel();
        }
        return self::$instance;
    }

    public function getOnlineUsersByRoom($where, $fields, $group)
    {
        $online_room_list = [];
        if ($where) {
            //模型里面的用户没有分库完整 所有先获取所有的用户 然后去初始化模型
            $uids = $this->getuids($where);
            $models = $this->getModels($uids);

            foreach ($models as $model) {
                Db::execute('SET SESSION group_concat_max_len = 1024000');
                $res = $model->model->where("user_id","in",$model->getList())->field($fields);
                if ($where) {
                    $res = $res->where($where);
                }

                if ($group) {
                    $res = $res->group($group);
                }

                $res = $res->select()->toArray();
                $online_room_list = array_merge($res, $online_room_list);
            }
        }

        $online_room_data = $online_room_info = [];

        if ($online_room_list) {
            foreach ($online_room_list as $item) {
                $online_room_info[$item['room_id']][] = $item['uids'];
            }

            foreach ($online_room_info as $room_id => $uid_list) {

                $online_room_arr['room_id'] = $room_id;
                $users = implode(',', $uid_list);

                $users_arr = array_values(array_filter(explode(',', $users)));

                $online_room_arr['uids'] = implode(',', $users_arr);
                $online_room_arr['count'] = count($users_arr);
                $online_room_data[$room_id] = $online_room_arr;

            }
        }

        return $online_room_data;
    }


    public function getuids($where){
        $models = $this->getallModel();
        $uids = [];
        foreach($models as $model){
            $res  =  $model->getModel()->where($where)->distinct(true)->column("user_id");
            $uids = array_merge($uids,$res);
        }
        return array_unique($uids);
    }
}

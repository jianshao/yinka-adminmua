<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;
use think\facade\Db;

class UserAssetLogModel extends ModelDao
{
    protected $table = 'zb_user_asset_log';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    /*   public static function getInstance()
    {
    if (!isset(self::$instance)) {
    self::$instance = new self();
    }
    return self::$instance;
    }*/

    //单例
    public static function getInstance($part = '')
    {
        if ($part) {
            $table = "zb_user_asset_log_" . $part;
        } else {
            $table = "zb_user_asset_log";
        }

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->table = $table;
        return self::$instance;
    }

    /*
     * 查询工会派对房列表
     */
    public function getRoomcoinNew($roomWhere, $assetWhere, $offset, $limit, $online_room_data, $room_id, $is_daochu = false)
    {
        $count = 0;
        if ($is_daochu) {
            $data = LanguageroomModel::getInstance()->getWhereAllData($roomWhere, 'id room_id,room_name,user_id,guild_id');
        } else {
            $data = LanguageroomModel::getInstance()->getModel($room_id)
                ->where($roomWhere)
                ->field('id room_id,room_name,user_id,guild_id')
                ->limit($offset, $limit)
                ->select()
                ->toArray();
        }

        $room_ids = array_column($data, 'room_id');
        $assetWhere2 = $assetWhere;
        $assetWhere[4] = ['send_type', 'in', [1, 2, 3]];
        $totailcoin_info = Db::table('bi_days_room_datas_bysend_type')->where('room_id', 'in', $room_ids)->where($assetWhere)->field('room_id,sum(abs(reward_amount)) amount')->group('room_id')->select()->toArray();
        $totailcoin_room_map = array_column($totailcoin_info, null, 'room_id');
        $assetWhere2[4] = ['send_type', '=', 2];
        //背包礼物
        $packagecoin_info = Db::table('bi_days_room_datas_bysend_type')->where('room_id', 'in', $room_ids)->where($assetWhere2)->field('room_id,sum(abs(reward_amount)) amount')->group('room_id')->select()->toArray();
        $packagecoin_room_map = array_column($packagecoin_info, null, 'room_id');

        foreach ($data as $k => $v) {
            $guild_info = MemberGuildModel::getInstance()->getOne($v['guild_id']);

            $data[$k]['ghuid'] = '';
            $data[$k]['ghname'] = '';
            $data[$k]['phone'] = '';

            if ($guild_info) {
                $data[$k]['ghuid'] = $guild_info->user_id;
                $data[$k]['ghname'] = $guild_info->nickname;
                $data[$k]['phone'] = $guild_info->phone;
            }

            $room_id = $v['room_id'];
            $totailcoin = isset($totailcoin_room_map[$room_id]) ? $totailcoin_room_map[$room_id]['amount'] : 0;
            $data[$k]['totailcoin'] = $totailcoin;
            $data[$k]['packagecoin'] = isset($packagecoin_room_map[$room_id]) ? $packagecoin_room_map[$room_id]['amount'] : 0;
            $data[$k]['othercoin'] = $totailcoin - $data[$k]['packagecoin'];
            $data[$k]['online_count'] = isset($online_room_data[$v['room_id']]) ? $online_room_data[$v['room_id']]['count'] : 0;

            $users = [];
            if (isset($online_room_data[$v['room_id']])) {
                $users = array_values(array_unique(array_filter(array_map('intval', explode(',', $online_room_data[$v['room_id']]['uids'])))));
            }
            $data[$k]['online_users'] = $users;
        }
        $count = LanguageroomModel::getInstance()->getModel($room_id)->where($roomWhere)->count();
        return ['count' => $count, 'data' => $data];
    }

    /**查询消费列表
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

    public function setTable()
    {
        $this->table = $this->getTable();

        return $this;
    }

    public function getTable()
    {
        $date = date("Y-m-d");
        $table = 'zb_user_asset_log' . $date;
        return $table;
    }

    /**
     * 根据用户uid 来获取对应的数据
     * @param $uids
     * @param $where
     * @param $field
     * @return array
     */
    public function getdataByUids($uids, $where, $field)
    {
        $res = [];
        $models = $this->getModels($uids);
        foreach ($models as $modelObject) {
            $res[] = $modelObject->getModel()
                ->field($field)
                ->where("uid", "in", $modelObject->getList())
                ->where($where)
                ->select()->toarray();
        }
        return $res;
    }



    public function getuids($where){
        $models = $this->getallModel();
        $uids = [];
        foreach($models as $model){
            $res  =  $model->getModel()->where($where)->distinct(true)->column("uid");
            $uids = array_merge($uids,$res);
        }
        return array_unique($uids);
    }
}

<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class LogindetailModel extends ModelDao
{
    protected $table = 'zb_login_detail_new';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LogindetailModel();
        }
        return self::$instance;
    }

    //获取配置来获取所有的model
    public function getallModel()
    {
        $shareconfig = config("shard");
        $count = count($shareconfig[$this->serviceName]);
        $key = [];
        for ($i = 0; $i < $count; $i++) {
            $key[] = $i;
        }
        return $this->getModels($key);
    }

    public function getCount($where, $uid = 0)
    {
        $count = 0;
        if ($where) {
            $models = $this->getallModel();
            foreach ($models as $model) {
                $count += $model->getModel($uid)->where($where)->group('user_id')->count();
            }
        }
        return $count;
    }

    public function getListPage($where, $offset, $limit, $uid = 0)
    {
        $data = [];
        if ($where) {
            $models = $this->getAllModels();
            foreach ($models as $model) {
                $res = $model->getModel($uid)
                    ->where($where)
                    ->order('ctime desc')
                    ->group('user_id')
                    ->select()
                    ->toArray();
                $data = array_merge($res, $data);
            }
        }
        return $data;
    }

    public function getAllModels()
    {
        $shareconfig = config("shard");
        $count = count($shareconfig[$this->serviceName]);
        $key = [];
        for ($i = 0; $i < $count; $i++) {
            $key[] = $i;
        }
        return $this->getModels($key);
    }

    //获取用户id根据条件
    public function getUidsByWhere($where, $uids=[])
    {
        if ($uids) {
            $models = $this->getModels($uids);
            $uidsList = [];
            foreach ($models as $model) {
                $result = $model->getModel()->where($where)->where("user_id", "in", $model->getList())->field("user_id")->select()->toArray();
                $uidsList = array_merge($uidsList, $result);
            }
        } else {
            $uidsList = $this->getWhereAllData($where, "user_id");
        }

        return array_unique(array_column($uidsList, "user_id"));
    }
}

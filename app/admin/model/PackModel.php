<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class PackModel extends ModelDao
{
    protected $table = 'zb_pack';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PackModel();
        }
        return self::$instance;
    }

    public function getByWhere($where)
    {
        if(isset($where['user_id'])){
            return $this->getModel($where['user_id'])->where($where)->order('endtime', 'desc')->select()->toArray();
        }

    }

    public function getOneByWhere($where)
    {
        if(isset($where['user_id'])){
            return $this->getModel($where['user_id'])->where($where)->find();
        }
    }

    public function editOneByWhere($where, $data)
    {
        if(isset($where['user_id'])){
            return $this->getModel($where['user_id'])->where($where)->update($data);
        }

    }
    public function addPack($data)
    {
        if(isset($data['user_id'])){
            return $this->getModel($data['user_id'])->insert($data);
        }

    }

}

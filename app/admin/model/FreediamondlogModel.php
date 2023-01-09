<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;
use think\Model;

class FreeDiamondLogModel extends ModelDao
{
    protected $table = 'zb_free_diamond_log';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new FreeDiamondLogModel();
        }
        return self::$instance;
    }

    /**用户提现列表数据
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回值
     */
    public function getList($where, $offset, $limit)
    {
        $res = $this->getModel()->where($where)->select();
        if ($res) {
            return $res->toArray();
        } else {
            return [];
        }
        return $this->getModel()->where($where)->limit($offset, $limit)->select()->toArray();
    }

    public function getOneById($user_id)
    {
        $where['uid'] = $user_id;
        $res = $this->getModel()->where($where)->select();
        if ($res) {
            return $res->toArray();
        } else {
            return array();
        }
    }

}
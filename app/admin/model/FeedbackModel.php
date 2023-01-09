<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class FeedbackModel extends ModelDao
{
    protected $table = 'zb_feedback';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new FeedbackModel();
        }
        return self::$instance;
    }

    //列表数据
    public function getList(array $where, $field = '*', $limit = [0, 20], $sort = 'id desc')
    {
        return $this->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->order($sort)->select()->toArray();
    }

}

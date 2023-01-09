<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiChargeModel extends ModelDao
{
    protected $table = 'bi_charge_data';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiChargeModel();
        }
        return self::$instance;
    }

    //列表数据
    public function getList(array $where, $field = '*', $limit = [0, 20], $sort = 'id desc')
    {
        return $this->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->order($sort)->select()->toArray();
    }
}

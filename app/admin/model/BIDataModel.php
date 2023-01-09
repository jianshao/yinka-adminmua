<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BIDataModel extends ModelDao
{
    protected $table = 'bi_data';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BIDataModel();
        }
        return self::$instance;
    }

    //列表数据
    public function getBIDataByWhereList(array $where, $field = '*', $limit = [0, 20], $sort = 'id desc')
    {
        return $this->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->order($sort)->select()->toArray();
    }

    //统计数据
    public function getBIDataByWhereCount(array $where)
    {
        return $this->getModel()->where($where)->count();
    }

    /**更新数据
     * @param $where    where条件
     * @param $data     更新的数据结构
     */
    public function setBiData($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
}

<?php


namespace app\bI\model;


use think\Model;

class BIDataModel extends Model
{
    protected $table = 'bi_data';
    protected $pk = 'id';
    // protected $connection = '';
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BIDataModel();
        }
        return self::$instance;
    }

    public function getBIDataByWhereList(array $where, $field = '*', $limit = [0, 20], $sort = 'id desc')
    {
        return $this->field($field)->where($where)->limit($limit[0],$limit[1])->order($sort)->select()->toArray();
    }
    public function getBIDataByWhereCount(array $where)
    {
        return $this->where($where)->count();
    }
}

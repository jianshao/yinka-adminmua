<?php
/**
 * User: li
 * Date: 2019
 * 活动数据表
 */
namespace app\common\model;
use think\Model;

class ActiveModel extends Model{

    protected $table = 'zb_active';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ActiveModel();
        }
        return self::$instance;
    }
    public function getById($id,$field){
        $where = ['id' => $id];
        return $this->where($where)->field($field)->find();
    }

}
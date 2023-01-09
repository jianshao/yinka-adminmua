<?php
/**
 * User: yond
 * Date: 2019
 * 动态表
 */
namespace app\common\model;
use think\Model;

class ForumModel extends Model{

    protected $table = 'zb_forum';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumModel();
        }
        return self::$instance;
    }
    
    public function getList($where,$order='id desc',$page=1,$pagesize=20,$column='*'){
        $ret = $this->where($where)->order($order)->field($column)->limit(($page-1)*$pagesize,$pagesize)->select();
        if($ret){
            return $ret->toArray();
        }else{
            return array();
        }
    }




}
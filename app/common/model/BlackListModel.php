<?php
/**
 * User: yond
 * Date: 2019
 * 黑名单表
 */
namespace app\common\model;
use think\Model;

class BlackListModel extends Model{

    protected $table = 'zb_black_list';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BlackListModel();
        }
        return self::$instance;
    }
    
    /**
     * 根据id获取单个
     *
     * @param $id
     * @param int $uid
     * @return array
     */
	public function getBlackById($id){
		$where['uid'] = $id;
		$where['status'] = 1;
		$res = $this->where($where)->find();
		if(!$res){
			return [];
		}
		return $res->toArray();
	}




}
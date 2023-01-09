<?php
/**
 * User: li
 * Date: 2019
 * 广告表
 */
namespace app\common\model;
use think\Model;

class AdvertModel extends Model{

    protected $table = 'zb_advert';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new AdvertModel();
        }
        return self::$instance;
    }

    /**获取所有广告位数据
     * @return array
     */
	public function getList(){
		$res = $this->select();
		if(!$res){
			return [];
		}
		return $res->toArray();
	}




}
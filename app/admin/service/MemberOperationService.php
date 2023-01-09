<?php
/**
 * @author yond
 * 用户操作类
 * $date 2019
 */
namespace app\admin\service;

use think\Log;
use think\Exception;
use think\cache\driver\Redis;
use app\admin\common\AdminCommonConfig;
use app\admin\model\BlackListModel;
use app\admin\model\BlackRankModel;
use think\facade\Config;


class MemberOperationService extends MemberModel{

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MemberOperationService();
        }
        return self::$instance;
    }

    public function getInfo($uid)
    {
    	$url = Config::get('config.APP_URL_image');
    	$field = 'id as uid,nickname,sex,avatar,role,roomnumber,lv_dengji,like_status,rich_tatus';
    	$data = $this->fieldFind($uid,$field);
    	if ($data) {
    		$data['avatar'] = $data['avatar']?$url.'/'.$data['avatar']:'';
    		$data['sex_name'] = AdminCommonConfig::SEX[$data['sex']];
    		$data['black_status'] = 2;
    		$blackData = BlackListModel::getInstance()->selectBan($uid);
    		if ($blackData) {
    			$data['black_status'] = $blackData['status'];
    		}
    		
    	}
    	return $res;
    }

    public function getSeachList($uid,$type)
    {
    	# code...
    }

   
    
  

    

}
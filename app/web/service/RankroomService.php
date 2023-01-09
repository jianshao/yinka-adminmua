<?php
/**
 * @author yond
 * 房间排行榜操作类
 * $date 2019
 */
namespace app\web\service;

use app\common\model\CoindetailModel;
use app\common\model\MemberModel;
use think\Log;
use think\Exception;
use think\cache\driver\Redis;


class RankroomService {

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RankroomService();
        }
        return self::$instance;
    }

    /**查询每个房间收到的礼物值
     * @param $where
     * @param $field
     * @return mixed
     */
    public function getRoomList($param){
    	$info = [];
    	$where = [
    		['addtime','>',$param['startTime']],
    		['addtime','<',$param['endTime']],
    		['action','=','sendgift'],
            ['giftid','in',$param['giftid']],
    		['room_id','notin',$param['notin_room_id']],
    	];
        $res = CoindetailModel::getInstance()->getCoinRoomRank($where);
        if (empty($res)) {
        	return [];
        }
        $data = [];
        $uidArr = [];
        foreach ($res as $key => $value) {
        	array_push($uidArr, $value['user_id']);
        	$data[$value['room_id']]['room_image'] = Config('config.APP_URL_image').'/'.'Public/Uploads/image/logo.png';
        	$data[$value['room_id']]['room_id'] = $value['room_id'];
        	$data[$value['room_id']]['user_id'] = $value['user_id'];
        	$data[$value['room_id']]['pretty_room_id'] = $value['pretty_room_id'];
			$data[$value['room_id']]['room_name'] = $value['room_name'];
			//判断时间减半和加倍
			if (strtotime($value['addtime']) >= strtotime($param['startTime']) && strtotime($value['addtime']) < strtotime($param['firstTime'])) {
				@$data[$value['room_id']]['coin'] += $value['coin'] * 2;
			}elseif(strtotime($value['addtime']) >= strtotime($param['lastTime']) && strtotime($value['addtime']) < strtotime($param['endTime'])){
				@$data[$value['room_id']]['coin'] += $value['coin'] / 2;
			}else{
				@$data[$value['room_id']]['coin'] += $value['coin'];
			}
        }
        $userInfo = MemberModel::getInstance()->getMemberListNoPage([['id','in',$uidArr]],'id,avatar');
        foreach ($data as $key => $value) {
        	$data[$value['room_id']]['room_image'] = Config('config.APP_URL_image').'/'.$userInfo[$value['user_id']]?:Config('config.APP_URL_image').'/'.'Public/Uploads/image/logo.png';
        }
        $coinTmp = array_column($data,'coin');
		$roomTmp = array_column($data,'pretty_room_id');
		array_multisort($coinTmp,SORT_DESC,$roomTmp,SORT_ASC,$data);
		//判断自己
		// foreach ($data as $key => $value) {
		// 	$data[$key]['coin'] = floor($value['coin']);
		// 	if ($key < 9) {
		// 	    $num = '0'.($key + 1);
		// 	}else{
		// 	    $num = $key + 1;
		// 	}
		// 	$data[$key]['num'] = $num;
		// 	//判断自己rank
		// 	if ($selfUid == $value['touid']) {
		// 		$info = $value;
		// 	    $info['num'] = $num;
		// 	}
		// }
  		//return [$data,$info];
  		return $data;
    }
    


    

}
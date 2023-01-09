<?php
/**
 * 首页排行榜，房间收到礼物
 */

namespace app\web\controller;

use think\facade\Request;
use app\BaseController;
use app\common\model\CoindetailModel;
use app\web\service\RankroomService;
use think\facade\View;


class RankroomController extends BaseController
{
    /*
     *国庆房间礼物排行
     */
    public function RoomRankList()
    {

		//十一定义时间
		$param['startTime'] = '2019-10-01 00:00:00';
		$param['endTime'] = '2019-10-08 00:00:00';
		$param['firstTime'] = '2019-10-02 00:00:00';
        $param['lastTime'] = '2019-10-07 00:00:00';
        $param['giftid'] = '311,312';
		$param['notin_room_id'] = '12,100464';
        //测试时间
        /*$param['startTime'] = '2019-09-27 15:30:00';
        $param['endTime'] = '2019-09-27 16:00:00';
        $param['firstTime'] = '2019-09-27 15:40:00';
        $param['lastTime'] = '2019-09-27 15:50:00';*/

		$list = RankroomService::getInstance()->getRoomList($param);
        
        if (empty($list)) {
            $list = 1;
        } else {
            foreach($list as $key=>$value){
                if ($key < 9) {
                    $num = '0'.($key + 1);
                }else{
                    $num = $key+1;
                }
                $list[$key]['num'] = $num;
            }
            // $list = array_slice($list, 0, 10);
        }
        
        //渲染数据
        View::assign([
            'list' => $list,
        ]);
        $url = "http://img.57xun.com";
        View::assign('url',$url);
        //分配模板
        return View::fetch('/RoomRankList');
    }


}
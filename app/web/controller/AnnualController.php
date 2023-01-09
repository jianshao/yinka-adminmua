<?php

namespace app\web\controller;

use app\admin\model\MemberModel;
use app\BaseController;
use app\web\model\CoindetailModel;
use constant\CodeConstant as coder;
use think\facade\Db;
use think\facade\Request;

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header('Access-Control-Allow-Methods: GET, POST, PUT');
class AnnualController extends BaseController
{
    /**
     * 年度盛典富豪榜
     * @param $mtoken    用户mtoken值
     */
    public function richList()
    {
        //获取数据
        $mtoken = Request::param('mtoken');
        if (!$mtoken) {
            return rjson([], coder::CODE_Token错误, coder::CODE_PARAMETER_ERR_MAP[coder::CODE_Token错误]);
        }
        $redis = $this->getRedis();
        $userid = $redis->get($mtoken);
        if (!$userid) {
            return rjson([], 500, '用户信息错误');
        }
        $time = time();
        if ($time < strtotime('2019-11-18')) {
            return rjson([], 500, '时间未到');
        }

        //帅选条件
        $startTime = '2019-11-18 00:00:00';
        $endTime = '2019-12-07 23:59:59';
        $where = [
            ['addtime', '>', $startTime],
            ['addtime', '<', $endTime],
            ['action', '=', 'sendgift'],
        ];
        //$limit = [0,100];
        //$field = "m.id,m.nickname,m.avatar,sum(c.coin) as coin";
        $field = "m.id,m.nickname,m.avatar,c.coin,c.giftid";
        $gift_id = [317, 318]; //新礼物双倍
        $newData = [];
        $selfData = [];
        $list = CoindetailModel::getInstance()->getList($field, $where);
        foreach ($list as $key => $value) {
            if ($value['id'] == $userid) {
                $selfData['id'] = $value['id'];
                $selfData['nickname'] = $value['nickname'];
                $selfData['avatar'] = getavatar($value['avatar']);
                if (in_array($value['giftid'], $gift_id)) {
                    @$selfData['coin'] += $value['coin'] * 2;
                } else {
                    @$selfData['coin'] += $value['coin'];
                }
            }
            $newData[$value['id']]['id'] = $value['id'];
            $newData[$value['id']]['nickname'] = $value['nickname'];
            $newData[$value['id']]['avatar'] = getavatar($value['avatar']);
            if (in_array($value['giftid'], $gift_id)) {
                @$newData[$value['id']]['coin'] += $value['coin'] * 2;
            } else {
                @$newData[$value['id']]['coin'] += $value['coin'];
            }
        }
        //排序
        $resRoundTmp = array_column($newData, 'coin');
        array_multisort($resRoundTmp, SORT_DESC, $newData);
        $list = array_slice($newData, 0, 20);
        $selfNum = '未上榜';
        for ($i = 0; $i < count($newData); $i++) {
            if ($newData[$i]['id'] == $userid) {
                $selfNum = $i + 1;
                break;
            }
        }
        //判断当前登录人如果没上榜 单独查询登录人信息
        if (empty($selfData)) {
            $selfRes = MemberModel::getInstance()->getModel($userid)->where(['id' => $userid])->find();
            if (empty($selfRes)) {
                $selfData['id'] = 0;
                $selfData['nickname'] = '';
                $selfData['avatar'] = getavatar('');
                $selfData['coin'] = 0;
            } else {
                $selfData['id'] = $selfRes['id'];
                $selfData['nickname'] = $selfRes['nickname'];
                $selfData['avatar'] = getavatar($selfRes['avatar']);
                $selfData['coin'] = 0;
            }

        }
        $selfData['num'] = $selfNum;

        $result = [
            "rich_list" => $list,
            "self" => $selfData,
        ];
        return rjson($result);

    }

    /**年度房间日榜数据(房间)
     * @param $token    token值
     */
    public function roomDayList()
    {
        //获取数据
        $mtoken = Request::param('mtoken');
        if (!$mtoken) {
            return rjson([], coder::CODE_Token错误, coder::CODE_PARAMETER_ERR_MAP[coder::CODE_Token错误]);
        }
        //帅选条件
        $time = time();
        if ($time < strtotime('2019-11-18')) {
            return rjson([], 500, '时间未到');
        }
        $startTime = date('Y-m-d 00:00:00', $time);
        $endTime = date('Y-m-d 23:59:59', $time);
        //超时时间
        if ($time > strtotime('2019-12-07')) {
            $startTime = '2019-11-18 00:00:00';
            $endTime = '2019-12-07 23:59:59';
        }

        $where = [
            ['addtime', '>', $startTime],
            ['addtime', '<', $endTime],
            ['action', '=', 'sendgift'],
        ];
        $field = "l.id,l.room_name,l.user_id,c.coin,c.room_id,c.giftid,m.avatar";
        $gift_id = [317, 318]; //新礼物双倍
        $newData = [];
        $daily_list = CoindetailModel::getInstance()->getRoomList($field, $where);
        foreach ($daily_list as $key => $value) {
            $newData[$value['room_id']]['id'] = $value['id'];
            $newData[$value['room_id']]['room_name'] = $value['room_name'];
            $newData[$value['room_id']]['avatar'] = getavatar($value['avatar']);
            if (in_array($value['giftid'], $gift_id)) {
                @$newData[$value['room_id']]['coin'] += $value['coin'] * 2;
            } else {
                @$newData[$value['room_id']]['coin'] += $value['coin'];
            }
        }
        //排序
        $resRoundTmp = array_column($newData, 'coin');
        array_multisort($resRoundTmp, SORT_DESC, $newData);
        $daily_list = array_slice($newData, 0, 20);
        $result = [
            "daily_list" => $daily_list,
        ];
        return rjson($result);
    }

    /**年度房间总榜数据(房间)
     * @param $token
     */
    public function roomList()
    {
        //获取数据
        $mtoken = Request::param('mtoken');
        if (!$mtoken) {
            return rjson([], coder::CODE_Token错误, coder::CODE_PARAMETER_ERR_MAP[coder::CODE_Token错误]);
        }
        //帅选条件
        $startTime = '2019-11-18 00:00:00';
        $endTime = '2019-12-07 23:59:59';
        //查询房间分数表统计总分
        $sql = "select * from zb_fenshu";
        $res = Db::query($sql);
        $coutNum = []; //房间对应的总分
        $roomidArr = []; //房间id数组
        foreach ($res as $key => $value) {
            @$coutNum[$value['roomid']] += $value['grade'];
            array_push($roomidArr, $value['roomid']);
        }

        //只查询分数表里面的roomid的房间信息
        $field = "l.id,l.room_name,l.user_id,c.coin,c.room_id,c.giftid,m.avatar";
        $where = [
            ['c.room_id', 'in', $roomidArr],
        ];
        $newData = [];
        $general_list = CoindetailModel::getInstance()->getRoomList($field, $where);
        foreach ($general_list as $key => $value) {
            $newData[$value['room_id']]['id'] = $value['id'];
            $newData[$value['room_id']]['room_name'] = $value['room_name'];
            $newData[$value['room_id']]['avatar'] = getavatar($value['avatar']);
            $newData[$value['room_id']]['coin'] = isset($coutNum[$value['room_id']]) ? $coutNum[$value['room_id']] : 0;
        }

        // $where = [
        //     ['addtime', '>', $startTime],
        //     ['addtime', '<', $endTime],
        //     ['action', '=', 'sendgift'],
        // ];
        // $field = "l.id,l.room_name,l.user_id,c.coin,c.room_id,c.giftid,m.avatar";
        // $gift_id = [20011,30011];           //新礼物双倍
        // $newData = [];
        // $general_list = CoindetailModel::getInstance()->getRoomList($field,$where);
        // foreach ($general_list as $key => $value) {
        //     $newData[$value['room_id']]['id'] = $value['id'];
        //     $newData[$value['room_id']]['room_name'] = $value['room_name'];
        //     $newData[$value['room_id']]['avatar'] = getavatar($value['avatar']);
        //     if(in_array($value['giftid'],$gift_id)){
        //         @$newData[$value['room_id']]['coin'] += $value['coin'] * 2;
        //     }else{
        //         @$newData[$value['room_id']]['coin'] += $value['coin'];
        //     }
        // }
        //排序
        $resRoundTmp = array_column($newData, 'coin');
        array_multisort($resRoundTmp, SORT_DESC, $newData);
        $general_list = array_slice($newData, 0, 20);
        $result = [
            "general_list" => $general_list,
        ];
        return rjson($result);
    }

}

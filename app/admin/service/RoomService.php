<?php

namespace app\admin\service;

use app\admin\common\RedisKeysConst;
use app\admin\model\LanguageroomModel;
use app\admin\script\analysis\GiftsCommon;
use app\common\RedisCommon;
use think\facade\Log;
use think\facade\Request;

class RoomService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomService();
        }
        return self::$instance;
    }

    public static $MUANEWROOMRECOMMEND = 'mua_new_room_recommend';
    public static $MUAROOMKINGKONG = 'mua_room_king_kong';
    public static $room_photo_conf = 'room_photo_conf';
    public static $room_conf = 'room_conf';

    //首页房间推荐
    public function roomHomepage()
    {
        $data = [];
        foreach (RedisKeysConst::$roomHomePageKeys as $action => $redis_key) {
            $info = $this->getRedisMembers($redis_key);
            Log::info('获取首页房间配置信息:redis_key====>{redis_key},redis结果====>{info}', ['redis_key' => $redis_key, 'info' => json_encode($info)]);
            $data[$action] = $info;
        }
        return $data;
    }

    //获取派对页房间活跃值
    public function roomPartyValue()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->HGETALL(RedisKeysConst::POPULAR_VALUE_ROOM_KEY);
    }

    //获取派对页房间活跃值
    public function homePageValue()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->HGETALL(RedisKeysConst::POPULAR_HOME_ROOM_KEY);
    }

    //获取派对页房间活跃值
    public function enjoyValue()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->HGETALL(RedisKeysConst::POPULAR_ENJOY_ROOM_KEY);
    }

    //获取首页推荐位
    public function homePageRooms()
    {
        $res = CurlApiService::getInstance()->getHomePageRooms();

        if (isset($res['list']) && !empty($res['list'])) {
            return array_column($res['list'], 'sumHot', 'roomId');
        }
        return [];
    }

    //获取娱乐页推荐位
    public function enjoyRooms()
    {
        $res = CurlApiService::getInstance()->getEnjoyRooms();
        if (isset($res['list']) && !empty($res['list'])) {
            return array_column($res['list'], 'sumHot', 'roomId');
        }
        return [];
    }

    public function getRedisMembers($redis_key)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->SMEMBERS($redis_key);
    }

    //检测该房间是否是工会房间
    public function checkRoomIsGuildRoom($room_id)
    {
        $res = LanguageroomModel::getInstance()->getModel($room_id)->where('id', $room_id)->where('guild_id', '>', 0)->find();
        if (empty($res)) {
            return_json(500, [], "房间:{$room_id}不属于任何公会", 1);
            die;
        }
    }

    //首页房间保存
    public function roomHomepageSave($action, $room, $count = [])
    {
        $redis_key = RedisKeysConst::$roomHomePageKeys[$action];
        $redis = RedisCommon::getInstance()->getRedis();

        $cache_room_list = $this->getRedisMembers($redis_key);
        foreach ($cache_room_list as $k => $room_id) {
            $redis->srem($redis_key, $room_id);
        }

        $room_filter = array_filter($room);
        foreach ($room_filter as $_ => $room_id) {
            $this->checkRoomIsGuildRoom($room_id);
            $redis->sadd($redis_key, $room_id);
        }

        if (in_array($action, [3, 4, 5])) {
            $popular_key = RedisKeysConst::$popularRedisKey[$action];

            //获取count和room对应值
            $room_data = [];
            foreach ($room as $idx => $room_id) {
                if ($room_id) {
                    $room_data[$room_id] = $count[$idx];
                }
            }

            $rooms = array_unique(array_merge($cache_room_list, array_keys($room_data)));
            foreach ($rooms as $idx => $room_id) {
                if (in_array($room_id, array_keys($room_data))) {

                    $this->checkRoomIsGuildRoom($room_id);
                    $active_count = (int)$room_data[$room_id];

                    $this->checkValue($active_count);

                    $redis->hset($popular_key, $room_id, $active_count);
                    $redis->hset($popular_key . ':' . $room_id, 'orignal', $active_count);
                }
            }
        }


        echo json_encode(['code' => 200, 'msg' => '操作完成']);
    }

    public function checkValue($count)
    {
        return true;
        if ($count < 0) {
            echo json_encode(['code' => 500, 'msg' => '活跃值不能小于0']);
            die;
        }
    }

    //  mua配置-新厅推荐
    public function muaNewRoomRecommend()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->SMEMBERS(RoomService::$MUANEWROOMRECOMMEND);
    }

    //  mua配置-新厅推荐保存
    public function muaNewRoomRecommendSave($room)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $set = $redis->SMEMBERS(RoomService::$MUANEWROOMRECOMMEND);
        foreach ($set as $k => $v) {
            $redis->sRem(RoomService::$MUANEWROOMRECOMMEND, $v);
        }

        foreach ($room as $k => $v) {
            if (empty($v)) {
                unset($room[$k]);
            }
        }

        foreach ($room as $k => $v) {
            $redis->SADD(RoomService::$MUANEWROOMRECOMMEND, $v);
        }
        echo json_encode(['code' => 200, 'msg' => '操作完成']);
    }

    // 房间礼物墙礼物配置
    public function roomPhoto()
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $list = json_decode($redis->get(RoomService::$room_photo_conf), true);
        return $list['gifts'];
    }

    // 房间礼物墙礼物配置保存
    public function roomPhotoSave($gifts)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $data = [];
        if ($gifts) {
            $gifts = array_unique($gifts);
            foreach ($gifts as $gift_id) {
                //校验礼物ID
                $this->checkGiftId($gift_id);
                $data[] = (int)$gift_id;
            }
        }
        $redis->set('room_photo_conf', json_encode(['gifts' => $data]));
        echo json_encode(['code' => 200, 'msg' => '操作完成']);
    }

    // 房间最高在线人数配置
    public function roomTopOnline()
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        return (int)$redis->hGet(RoomService::$room_conf, 'max_count');
    }

    // 房间最高在线人数保存
    public function roomTopOnlineSave($count)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $redis->hset('room_conf', 'max_count', (int)$count);
        echo json_encode(['code' => 200, 'msg' => '操作完成']);
    }

    public function checkGiftId($gift_id)
    {
        $gifts = GiftsCommon::getInstance()->getGifts();
        if (!in_array($gift_id, array_keys($gifts))) {
            echo json_encode(['code' => 500, 'msg' => '礼物ID:' . $gift_id . '不存在']);
            die;
        }
    }

    //  mua配置-房间金刚位
    public function muaRoomKingKong()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->SMEMBERS(RoomService::$MUAROOMKINGKONG);
    }

    //  mua配置-房间金刚位保存
    public function muaRoomKingKongSave($room)
    {
        foreach ($room as $k => $v) {
            if (empty($v)) {
                unset($room[$k]);
            }
        }
        if (count($room) >= 6) {
            $redis = RedisCommon::getInstance()->getRedis();
            $set = $redis->SMEMBERS(RoomService::$MUAROOMKINGKONG);
            foreach ($set as $k => $v) {
                $redis->sRem(RoomService::$MUAROOMKINGKONG, $v);
            }

            foreach ($room as $k => $v) {
                $redis->SADD(RoomService::$MUAROOMKINGKONG, $v);
            }
            echo json_encode(['code' => 200, 'msg' => '操作完成']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '房间不可少于6个']);
        }
    }


    public function newOldUserComeRoom($action, $rooms, $sorts, $msgs)
    {
        //新老用户的匹配进厅
        $popular_key = RedisKeysConst::$USERCOMEROOMKEYS[$action];
        $savedata = [];
        foreach ($rooms as $k => $room_id) {
            $this->checkRoomIsGuildRoom($room_id);
            $sort = $sorts[$k] ?? 1;
            $msg = $msgs[$k] ?? '';
            if ($msg == '') {
                $msg = "小可爱,进来聊聊天吧!";
            }
            if($sort == ''){
                $sort = 1;
            }
            $savedata[] = ["room_id" => $room_id, "sort" => $sort, "msg" => $msg];
        }
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $redis->set($popular_key, json_encode($savedata));
        echo json_encode(['code' => 200, 'msg' => '操作完成']);
    }


    public function getNewOldUserComeRoom($mark)
    {
        //新老用户的匹配进厅
        $popular_key = RedisKeysConst::$USERCOMEROOMKEYS[$mark];
        $data = RedisCommon::getInstance()->getRedis(['select'=>3])->get($popular_key);
        return json_decode($data,true);
    }


}

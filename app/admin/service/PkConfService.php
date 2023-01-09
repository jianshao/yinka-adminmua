<?php

namespace app\admin\service;

use app\admin\common\RedisKeysConst;
use app\admin\model\LanguageroomModel;
use app\common\RedisCommon;

class PkConfService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pkInfo()
    {
        return $this->getPkInfo(RedisKeysConst::PK_CONF);
    }

    public function getPkInfo($redis_key)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->HGETALL($redis_key);
    }

    //检测该房间是否是工会房间
    public function checkRoomIsGuildRoom($room_id)
    {
        $res = LanguageroomModel::getInstance()->getModel($room_id)->where('id', $room_id)->whereOr('pretty_room_id', $room_id)->where('guild_id', '>', 0)->find();
        if (empty($res)) {
            return_json(500, [], "房间:{$room_id}不属于任何公会", 1);die;
        }
        return $res;
    }

    //首页房间保存
    public function save($params)
    {
        $action = (int) $params['action'];
        if ($action == 1) {
            $room_ids = $params['room_id'];
            $type = (int) $params['type'];
            $this->checkType($type);

            foreach ($room_ids as $_ => $room_id) {
                //校验房间
                $this->checkRoomIsGuildRoom($room_id);
            }

            $room_ids = json_encode($room_ids);
            $res = CurlApiService::getInstance()->pkRoomsConf($room_ids, $type);
            if ($res['code'] != 200) {
                throw new \Exception($res['desc']);
            }
        } elseif ($action == 2) {
            $start_time = $params['start_time'];
            $stop_time = $params['stop_time'];
            $this->checkStartStop($start_time, $stop_time);
            $redis = RedisCommon::getInstance()->getRedis();
            $redis->hset(RedisKeysConst::PK_CONF, 'start_time', $start_time);
            $redis->hset(RedisKeysConst::PK_CONF, 'stop_time', $stop_time);
        }
    }

    //首页房间保存
    public function startCrossPk($params)
    {
        $start_room_id = (int) $params['start_room_id'];
        $pk_room_id = (int) $params['pk_room_id'];

        $start_room_info = $this->checkRoomIsGuildRoom($start_room_id);
        $params['start_room_id'] = $start_room_info->id;
        $end_room_info = $this->checkRoomIsGuildRoom($pk_room_id);
        $params['pk_room_id'] = $end_room_info->id;

        $this->checkRoomIsCount($params['count']);

        $res = CurlApiService::getInstance()->startCrossPk($params);
        if ($res['code'] != 0) {
            throw new \Exception($res['desc']);
        }
    }

    //首页房间保存
    public function endCrossPk($params)
    {
        $start_room_id = $params['start_room_id'];
        $pk_room_id = $params['pk_room_id'];

        $start_room_info = $this->checkRoomIsGuildRoom($start_room_id);
        $params['start_room_id'] = $start_room_info->id;
        $end_room_info = $this->checkRoomIsGuildRoom($pk_room_id);
        $params['pk_room_id'] = $end_room_info->id;

        $res = CurlApiService::getInstance()->endCrossPk($params);
        if ($res['code'] != 0) {
            throw new \Exception($res['desc']);
        }
    }

    public function checkStartStop($start_time, $stop_time)
    {
        if (strtotime($start_time) >= strtotime($stop_time)) {
            throw new \Exception("开始时间需小于结束时间");
        }
    }

    public function checkRoomIsCount($count)
    {
        if ($count > 3600 || $count < 60) {
            throw new \Exception("时间超出范围");
        }
    }
    public function checkType($type)
    {
        if (!in_array($type, [1, 2, 4, 8, 16])) {
            throw new \Exception("类型不合法");
        }
    }
}

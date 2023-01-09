<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\LanguageroomModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ChannelComeRoomController extends AdminBaseController
{
    const ASAROOMTEMPSAVE = "channel_puton_room_come_temp";
    const ASAROOMSAVE = "channel_puton:come_room:type:";
    const CACHE_KEY = "%s:%s";

    /**
     * 推荐房间列表
     */
    public function channelComeRoomList()
    {
        $s_type = Request::param('s_type', ''); //结束的时间
        if ($this->request->param("isRequest") == 1) {
            $redis = $this->getRedis();
            $getRes = $redis->hGetAll(SELF::ASAROOMTEMPSAVE);
            $content = array_values($getRes);
            $data = [];
            foreach ($content as $k => $v) {
                //循环将数组的键值拼接起来
                $params = json_decode($v, true);
                $room_id = $params['room_id'] ?? 0;
                $roominfo = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->find();
                if (!empty($roominfo)) {
                    if (!empty($s_type)) {
                        if ($params['type'] != $s_type) {
                            continue;
                        }
                    }
                    $data[$k]['begin_time'] = $params['begin_time'] ?? '';
                    $data[$k]['room_id'] = $params['room_id'] ?? '';
                    $data[$k]['end_time'] = $params['end_time'] ?? '';
                    $data[$k]['type'] = $params['type'] ?? '';
                    $data[$k]['room_name'] = $roominfo['room_name'] ?? '';
                }
            }

            echo json_encode(["msg" => '', "count" => count($content), "code" => 0, "data" => $data]);
        } else {
            View::assign('token', $this->request->param('token'));
            return View::fetch('asacomeroom/channelconfiglist');
        }

    }

    /**
     * 添加推荐房间列表
     */
    public function channelComeRoomAdd()
    {
        $room_id = Request::param('room_id', 0); //房间ID
        $begin_time = Request::param('begin_time', ''); //开始的时间
        $end_time = Request::param('end_time', ''); //结束的时间
        $type = Request::param('type', ''); //数据类型
        if (!$room_id) {
            echo json_encode(["code" => 1, "msg" => "房间ID为空"]);
            die;
        }
        $res = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->find();
        if (!$res) {
            echo json_encode(["code" => 1, "msg" => "房间ID为空"]);
            die;
        }

        if (strtotime($begin_time) >= strtotime($end_time) || strtotime($end_time) < time()) {
            echo json_encode(["code" => 1, "msg" => "时间设置错误"]);
            die;
        }

        if (empty($type)) {
            echo json_encode(["code" => 1, "msg" => "数据类型设置错误"]);
            die;
        }

        $hashkey = sprintf(SELF::CACHE_KEY, $room_id, $type);

        $redis = $this->getRedis();
        $body = [
            "room_id" => $room_id,
            "begin_time" => $begin_time,
            "end_time" => $end_time,
            "type" => $type,
        ];

        $haveRes = $redis->hget(SELF::ASAROOMTEMPSAVE, $hashkey);
        if ($haveRes) {
            echo json_encode(["code" => 1, "msg" => "房间已经配置存在"]);
            die;
        }
        $redis->hset(SELF::ASAROOMTEMPSAVE, $hashkey, json_encode($body));
        echo json_encode(["code" => 0, "msg" => "插入成功"]);
        die;
    }

    /**
     * 取消推荐房间列表
     */
    public function channelComeRoomDel()
    {
        $room_id = Request::param('room_id'); //房间ID
        $type = Request::param('type'); //数据类型
        $hashkey = sprintf(SELF::CACHE_KEY, $room_id, $type);
        $redis = $this->getRedis();
        $result = $redis->hGet(SELF::ASAROOMTEMPSAVE, $hashkey);
        if (!$result) {
            echo json_encode(["code" => -1, "msg" => "获取配置失败"]);
            Log::info('channelcomeroomdel:error:' . $this->token['username'] . '@' . json_encode(["room_id" => $room_id, "type" => $type]));
            exit;
        } else {
            $redis->hDel(SELF::ASAROOMTEMPSAVE, $hashkey);
            $redis->srem(SELF::ASAROOMSAVE . $type, $room_id);
            Log::info('channelcomeroomdel:success:' . $this->token['username'] . '@' . json_encode(["room_id" => $room_id, "type" => $type]));
            echo json_encode(["code" => 0, "msg" => "操作成功"]);
            die;
        }

    }

}

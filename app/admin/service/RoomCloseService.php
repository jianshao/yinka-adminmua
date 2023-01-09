<?php

namespace app\admin\service;

use app\admin\model\LanguageroomModel;
use app\admin\model\RoomCloseModel;
use think\facade\Log;

class RoomCloseService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomCloseService();
        }
        return self::$instance;
    }

    public function banRoom(array $data, $token_info)
    {
        $time = time();
        $room_id = $data['room_id'];
        $data['end_time'] = (int) ($time + $data['longtime']);

        //先判断是否存在记录中
        $haveRes = RoomCloseModel::getInstance()->getModel()->where("room_id", $room_id)->find();
        if ($haveRes) { //编辑
            $data['utime'] = $time;
            if ($haveRes['status'] == 1) {
                throw new \Exception('此房间正处于封禁中', 500);
            }
            $data['status'] = 0;
            RoomCloseModel::getInstance()->getModel()->where("id", $haveRes['id'])->save($data);
        } else {
            //新增
            $data['utime'] = $time;
            $data['ctime'] = $time;
            RoomCloseModel::getInstance()->getModel()->insert($data);
        }

        $format_reason = sprintf("您的房间因存在%s违规行为已被封禁,%s前无法进入", $data['reason'], date('Y-m-d H:i:s', $data['end_time']));
        $requestBody = ["roomId" => $room_id, "operator" => (int) $token_info['id'], "reasonInfo" => $format_reason];
        $requestBody['isBan'] = 1; //isBan 1是封禁 2解禁
        $resMsg = CurlApiService::getInstance()->banRoom($requestBody);

        if ($resMsg['code'] != 0) {
            $desc = $resMsg['desc'] ?? '操作异常';
            throw new \Exception($desc, 500);
        }

        RoomCloseModel::getInstance()->getModel()->where("room_id", $room_id)->save(['status' => 1]);
        //设置房间封禁状态
        LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->save(["is_block" => 1]);

        $user_id = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->value("user_id");
        $params = [
            'userId' => (int) $user_id,
            'msg' => $format_reason,
        ];
        $requestApi = config('config.app_api_url') . 'api/inner/game/sendAssistantMsg';

        try {
            $res = curlData($requestApi, $params);
            Log::debug(sprintf('-----RoomCloseService@banRoom-----, url=====>%s, params=====>%s, res=====>%s', $requestApi, json_encode($params)), $res);

        } catch (\Throwable $th) {
            Log::error(sprintf('-----RoomCloseService@banRoom-----, url=====>%s, params=====>%s', $requestApi, json_encode($params)));
        }
        Log::info(sprintf('roomcloseedit:success roomId=%d  data=%s resMsg=%s operator=%s', $room_id, json_encode($data), json_encode($resMsg), $token_info['username']));
    }
}

<?php

namespace app\admin\script;

use app\admin\common\ApiUrlConfig;
use app\admin\model\LanguageroomModel;
use app\admin\model\RoomCloseModel;
use app\admin\service\ApiService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class RoomCloseCommand extends Command
{

    protected function configure()
    {
        $this->setName('RoomCloseCommand')->setDescription('RoomCloseCommand');
    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        $res = RoomCloseModel::getInstance()->getModel()->where("status", 1)->where("longtime", ">", 0)->select()->toArray();
        foreach ($res as $item) {
            $currentTimestamp = time();
            if ($item['end_time'] < $currentTimestamp && $item['status'] == 1) {
                $room_id = $item['room_id'];
                $operator = $item['operator'];
                $requestBody = ["roomId" => (int)$room_id, "operator" => (int)$operator, "reasonInfo" => "您的房间已被解除封禁，请严格遵守平台规范", "isBan" => 2];
                $resMsg = $this->banRoom($requestBody);
                $parseMsg = json_decode($resMsg, true);
                if (isset($parseMsg['code']) && $parseMsg['code'] == 0) {
                    try {
                        //status= 2 逻辑上标识解封
                        RoomCloseModel::getInstance()->getModel()->where("room_id", $room_id)->save(['utime' => $currentTimestamp, "status" => 2]);
                        $params = [
                            'room_id' => (int)$room_id,
                            'profile' => json_encode(["is_block" => 0]),
                        ];
                        $apires = ApiService::getInstance()->curlApi(ApiUrlConfig::$update_roominfo, $params, true);
                        if ($apires['code'] == 200) {
                            //LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->save(["is_block" => 0]);
                            $user_id = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->value("user_id"); //房主
                            $params = [
                                'userId' => (int)$user_id,
                                'msg' => "您的房间已被解除封禁，请严格遵守平台规范",
                            ];
                            $requestApi = config('config.app_api_url') . 'api/inner/game/sendAssistantMsg';
                            curlData($requestApi, $params);
                            Log::info(sprintf('roomclosecommand::success roomId=%d resMsg=%s', $room_id, $resMsg));
                        }
                    } catch (\Throwable $e) {
                        Log::info(sprintf('roomclosecommand::error roomId=%d errormsg=%s', $room_id, $e->getMessage()));
                    }

                } else {
                    Log::info(sprintf('roomclosecommand::error roomId=%d   resMsg=%s', $room_id, $resMsg));
                }
            }
        }

    }

    //操作房间封禁
    public function banRoom($body)
    {
        $data = json_encode($body);
        $url = config('config.socket_url_base') . 'iapi/banRoom';
        return curlData($url, $data, 'POST', 'json');
    }

}

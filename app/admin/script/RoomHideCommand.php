<?php

namespace app\admin\script;

use app\admin\common\ApiUrlConfig;
use app\admin\model\BiRoomHideLogModel;
use app\admin\model\RoomHideModel;
use app\admin\service\ApiService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class RoomHideCommand extends Command
{

    protected function configure()
    {
        $this->setName('RoomHideCommand')->setDescription('RoomHideCommand');

    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {

        $currentTime = time();
        $res = RoomHideModel::getInstance()->getModel()->select()->toArray();
        foreach ($res as $item) {
            try {
                if ($item['start_time'] < $currentTime && $item['end_time'] > $currentTime && $item['success_time'] == 0) { //已经开始
                    $params = ['room_id' => (int)$item['room_id'], 'profile' => json_encode(["is_hide" => 1])];
                    ApiService::getInstance()->curlApi(ApiUrlConfig::$update_roominfo, $params, true);
                    //数据更新设置成功
                    RoomHideModel::getInstance()->getModel()->where("room_id", $item['room_id'])->update(["success_time" => time()]);
                } elseif ($item['end_time'] < $currentTime) { //已经结束
                    $params = ['room_id' => (int)$item['room_id'], 'profile' => json_encode(["is_hide" => 0])];
                    ApiService::getInstance()->curlApi(ApiUrlConfig::$update_roominfo, $params, true);
                    unset($item['id']);
                    unset($item['success_time']);
                    BiRoomHideLogModel::getInstance()->getModel()->transaction(function()use($item){
                        BiRoomHideLogModel::getInstance()->getModel()->insert($item);
                        RoomHideModel::getInstance()->getModel()->where("room_id", $item['room_id'])->delete();
                    });
                }
            } catch (\Throwable $e) {
                echo ($e->getMessage().$e->getFile().$e->getLine());
                Log::error(sprintf("roomspecialcommand::roomhide::error::%s", $e->getMessage()));
            }
        }
    }

}

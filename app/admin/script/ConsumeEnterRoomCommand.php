<?php

namespace app\admin\script;

use app\admin\model\BiMessageEnterRoomModel;
use app\admin\service\ConsumeService;
use app\common\ParseUserStateByUniqkey;
use app\common\RabbitMQCommand;
use app\core\mysql\Sharding;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

//用户进房间消费者
class ConsumeEnterRoomCommand extends Command
{
    const COMMAND_NAME = "ConsumeEnterRoomCommand";
    const QUEUE_NAME = "q_admin_user_enter_room"; //队列名字
    const ROUTERKEY = ["EnterRoomEvent","LeaveRoomEvent"]; //routerkey


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        $config = ['queue_name' => SELF::QUEUE_NAME, "exchange_name" => config("config.rabbitmq_exchange_name"), "routerkey" => SELF::ROUTERKEY];
        ConsumeService::getInstance()->handler($config, [$this, "callback"]);
    }

    public  function callback($msg){
        Log::info(self::COMMAND_NAME . ":原始数据:" .$msg->body);
        if ($msg->body) {
            $body = json_decode($msg->body, true);
            if ($body && isset($body['body'])) {
                $parseRes = json_decode($body['body'],true);
                $type = $parseRes['type'] ?? '';
                $room_id = $parseRes['roomId'] ?? 0;
                $uid = $parseRes['userId'] ?? 0;
                $ctime = $parseRes['timestamp'] ?? 0;
                $message_id = $body['messageId'] ?? '';
                $duration = $parseRes['duration'] ?? 0;
                $data = ["type" => $type, "room_id" => $room_id, "uid" => $uid,'ctime' => $ctime,'duration'=>$duration,"message_id"=>$message_id];
                Sharding::getInstance()->getConnectModel("bi","")->transaction(function()use($data){
                    $tableName = date('Ym',$data['ctime']);
                    ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiMessageEnterRoomModel::getInstance($tableName)->getModel(),[$data],["id","message_id"]);
                });
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }
    }




}

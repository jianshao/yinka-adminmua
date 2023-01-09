<?php

namespace app\admin\script;
use app\admin\model\BiMessageRoomChatModel;
use app\admin\model\BiUserRoomChatModel;
use app\admin\service\ConsumeService;
use app\common\ParseUserStateByUniqkey;
use app\common\RabbitMQCommand;
use app\core\mysql\Sharding;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

//用户进房间消费者
class ConsumeRoomChatCommand extends Command
{
    const COMMAND_NAME = "ConsumeRoomChatCommand";
    const QUEUE_NAME = "q_admin_user_room_chat"; //队列名字
    const ROUTERKEY = "RoomChatEvent"; //routerkey


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
                $room_id = $parseRes['roomId'] ?? 0;
                $uid = $parseRes['userId'] ?? 0;
                $ctime = $parseRes['timestamp'] ?? 0;
                $message_id = $body['messageId'] ?? '';
                $content = $parseRes['content'] ?? '';
                $data = ["room_id" => $room_id, "uid" => $uid,'ctime' => $ctime,'content'=>$content,"message_id"=>$message_id];
                Sharding::getInstance()->getConnectModel("bi","")->transaction(function()use($data){
                    $tableName = date('Ym',$data['ctime']);
                    ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiMessageRoomChatModel::getInstance($tableName)->getModel(),[$data],["id","message_id"]);
                    //兼容老的数据:公屏消息总表
                    $insertdata=[];
                    $insertdata=["uid"=>$data['uid'],"room_id"=>$data['room_id'],"content"=>$data['content'],"created_time"=>$data['ctime']];
                    BiUserRoomChatModel::getInstance()->getModel()->insert($insertdata);
                });
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }
    }




}

<?php

namespace app\admin\script;

use app\admin\model\BiRoomActionModel;
use app\admin\model\BIUserEnterRoomModel;
use app\admin\model\BiUserRoomChatModel;
use app\common\ParseUserStateByUniqkey;
use app\common\RabbitMQCommand;
use app\core\mysql\Sharding;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;


class RoomConsumeCommand extends Command
{

    const  UPDATE_TABLE_NAME = 'bi_room_action'; //数据表
    const COMMAND_NAME = "RoomConsumeCommand";


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        $config = config("config.ampq_room");
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['password']);
        $channel = $connection->channel();
        //声明队列
        $channel->queue_declare($config['queue_name'], false, true, false, false);
        //消息确认之前  不接受其他消息
        //$channel->basic_qos(null, 1, null);
        //从队列中异步获取数据 //$no_ack 是否关闭确认消息收到
        try {
            $channel->basic_consume($config['queue_name'], '', false, $no_ack = false, false, false,[$this,"callback"]);
            while (count($channel->callbacks)) {
                try {
                    $channel->wait();
                } catch (\Throwable $e) {
                    throw $e;
                }

            }
        } catch (\Throwable $e) {
            Log::error(self::COMMAND_NAME . "error:" . $e->getMessage());
            $channel->close();
            $connection->close();
        }
    }

    public  function callback($msg){
        Log::info(self::COMMAND_NAME . "原始数据:" . $msg->body);
        if ($msg->body) {
            $parseRes = json_decode($msg->body, true);
            if ($parseRes) {
                $type = $parseRes['type'] ?? '';
                $room_id = $parseRes['roomId'] ?? 0;
                $uid = $parseRes['userId'] ?? 0;
                $logTime = $parseRes['logTime'] ?? 0;
                $data = ["type" => $type, "content" => $msg->body, "room_id" => $room_id, "uid" => $uid,
                    'create_time' => time(), 'logTime' => $logTime
                ];
                Sharding::getInstance()->getConnectModel("bi","")->transaction(function()use($data,$parseRes){
                    ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiRoomActionModel::getInstance()->getModel(),[$data]);
                    if($parseRes['type'] == 'user_room_chat'){
                        $this->handleRoomChat($parseRes);
                    }
                    if($parseRes['type'] == 'user_enter_room' || $parseRes['type'] == 'user_leave_room'){
                        $this->handleEnterRoom($parseRes);
                    }
                });
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }
    }


    //进房间
    public function handleEnterRoom($data){
        //"type":"user_enter_room","userId":1700469,"roomId":154228,"logTime":1658216432}
        //{"type":"user_leave_room","userId":1151183,"roomId":122053,"duration":104,"logTime":1658216430,"leaveTime":1658216534,"reason":4}
        $insertData = [];
        $insertData['uid'] = $data['userId'] ?? 0;
        $insertData['room_id'] = $data['roomId'] ?? 0 ;
        $insertData['type'] = $data['type'] ?? '';
        $insertData['duration'] = $data['duration'] ?? 0 ;
        $insertData['log_time'] = $data['logTime'] ?? 0 ;
        $insertData['leave_time'] = $data['leaveTime'] ?? 0 ;
        $insertData['reason'] = $data['reason'] ?? '' ;
        $insertData['created_time'] = time();
        Sharding::getInstance()->getConnectModel('bi','')->transaction(function()use($insertData){
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BIUserEnterRoomModel::getInstance()->getModel(),[$insertData]);
            if(isset($insertData['log_time']) && $insertData['log_time'] > 0){
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BIUserEnterRoomModel::getInstance(date('Ym',$insertData['log_time']))->getModel(),[$insertData]);
            }
        });


    }

    //公屏消息
    public function handleRoomChat($data){
        $insertData = [];
        $insertData['uid'] = $data['userId'] ?? 0;
        $insertData['room_id'] = $data['roomId'] ?? 0 ;
        $insertData['content'] = $data['content'] ?? '';
        $insertData['created_time'] = $data['createTime'] ?? 0 ;
        Sharding::getInstance()->getConnectModel('bi','')->transaction(function()use($insertData){
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiUserRoomChatModel::getInstance()->getModel(),[$insertData]);
            if(isset($insertData['created_time']) && $insertData['created_time'] > 0){
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiUserRoomChatModel::getInstance(date('Ym',$insertData['created_time']))->getModel(),[$insertData]);
            }
        });

    }


}

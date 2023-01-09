<?php

namespace app\admin\script;

use app\admin\service\ConsumeService;
use app\admin\service\ElasticsearchService;
use app\common\RabbitMQCommand;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use think\facade\Log;


class  ConsumeLoginDetaiCommand extends Command
{

    const COMMAND_NAME = "ConsumeLoginDetaiCommand";
    const QUEUE_NAME = "q_admin_user_login_detail"; //队列名字
    const ESINDEX = 'zb_login_detail_new';
    const ROUTERKEY = "UserLoginEvent"; //routerkey

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

    //消息的业务逻辑处理
    public function callback($msg)
    {
        Log::info(self::COMMAND_NAME . "原始数据:" . $msg->body);
        if ($msg->body) {
            $body = json_decode($msg->body, true);
            if ($body && isset($body['body'])) {
                $parseRes = json_decode($body['body'], true);
                //只需要某些特定的字段
                $simulator = $parseRes['clientInfo']['simulator'] ?? '';
                $data = [
                    "user_id" => $parseRes['userId'] ?? 0,
                    "ctime" => $parseRes['timestamp'] ?? 0,
                    "channel" => $parseRes['clientInfo']['channel'] ?? '',
                    "device_id" => $parseRes['clientInfo']['deviceId'] ?? '',
                    "login_ip" => $parseRes['clientInfo']['clientIp'] ?? '',
                    "mobile_version" => $parseRes['clientInfo']['device'] ?? '',
                    "idfa" => $parseRes['clientInfo']['idfa'] ?? '',
                    "version" => $parseRes['clientInfo']['version'] ?? '',
                    "simulator" => (int)$simulator,
                    "imei" => $parseRes['clientInfo']['imei'] ?? '',
                    "app_id" => $parseRes['clientInfo']['appId'] ?? '',
                ];
                $message_id = $body['messageId'] ?? '';
                $bulkResult = $this->bulk([$data], $message_id);
                if ($bulkResult['errors'] == false) {
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                } else {
                    Log::error(SELF::COMMAND_NAME . "error:bulk:" . json_encode($bulkResult));
                }
            }

        }

    }


    /**数据写入es中
     * @param $dataByMin
     * @param $dataByDay
     */
    private function bulk($data, $message_id = '')
    {
        $body = [];
        foreach ($data as $item) {
            if ($message_id) {
                $_id = $message_id;
            } else {
                $_id = $this->getUniqkey($item);
            }
            $body[] = ['index' => ['_index' => self::ESINDEX, '_id' => $_id]];
            $body[] = $item;
        }
        return ElasticsearchService::getInstance()->bulkData(self::ESINDEX, $body);
    }


    //获取不重复的key
    public function getUniqkey($data)
    {
        $keyList = ["id", "user_id", "channel", "device_id", "login_ip", "idfa", "version", "mobile_version"];
        $params = "";
        foreach ($keyList as $item) {
            if (isset($data[$item])) {
                $params .= $data[$item];
            }
        }
        return md5($params);
    }


}

<?php

namespace app\admin\script;
use app\admin\service\ElasticsearchService;
use app\common\RabbitMQCommand;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;


class CheckImConsumeCommand extends Command
{

    const COMMAND_NAME = "CheckImConsumeCommand";


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        $config = config("config.im_queue");
        $connection = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['password']);
        $channel = $connection->channel();
        //声明队列
        $channel->queue_declare($config['queue_name'], false, true, false, false);
        //echo " [*] Waiting for messages. To exit press CTRL+C\n";
        $callback = function ($msg) {
            Log::info(self::COMMAND_NAME . "原始数据:" . $msg->body);
            if ($msg->body) {
                $parseRes = json_decode($msg->body, true);
                if ($parseRes) {
                    ElasticsearchService::getInstance()->bulk("zb_check_im_message",[$parseRes]);
                }
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }

        };
        //消息确认之前  不接受其他消息
        //$channel->basic_qos(null, 1, null);
        //从队列中异步获取数据 //$no_ack 是否关闭确认消息收到
        $channel->basic_consume($config['queue_name'], '', false, $no_ack = false, false, false, $callback);
        while (count($channel->callbacks)) {
            try {
                $channel->wait();
            } catch (\Throwable $e) {
                echo self::COMMAND_NAME . "error:" . $e->getMessage();
                Log::info(self::COMMAND_NAME . "error:" . $e->getMessage());
                break;
            }
        }
        $channel->close();
        $connection->close();
    }




}

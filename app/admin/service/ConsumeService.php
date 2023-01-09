<?php

namespace app\admin\service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use think\facade\Log;

class ConsumeService
{
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function handler($config, $callback)
    {
        $mqconfig = config("config.rabbitmq");
        $connection = new AMQPStreamConnection($mqconfig['host'], $mqconfig['port'], $mqconfig['user'], $mqconfig['password']);
        $channel = $connection->channel();
        $queueName = $config['queue_name'] ?? '';
        $exchangeName = $config['exchange_name'] ?? '';
        $routerkey = $config['routerkey'] ?? '';
        if (empty($queueName)) {
            throw  new Exception("queue name is empty");
        }
        //声明队列
        $channel->queue_declare($config['queue_name'], false, true, false, false);
        if(is_array($routerkey)){
            foreach($routerkey as $router_key){
                $channel->queue_bind($queueName, $exchangeName,$router_key);
            }
        }

        if(is_string($routerkey)){
            $channel->queue_bind($queueName, $exchangeName, $routerkey);
        }

        //消息确认之前  不接受其他消息
        $channel->basic_qos(null, 1, null);
        //从队列中异步获取数据 //$no_ack 是否关闭确认消息收到
        try {
            $channel->basic_consume($config['queue_name'], '', false, $no_ack = false, false, false, $callback);
            while (count($channel->callbacks)) {
                try {
                    $channel->wait();
                } catch (\Throwable $e) {
                    throw $e;
                }
            }
        } catch (\Throwable $e) {
            Log::error( $queueName. "error:" . $e->getMessage());
            $channel->close();
            $connection->close();
        }
    }



}

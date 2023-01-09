<?php

namespace app\common;
use think\App;
use think\facade\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPStrea;
use PhpAmqpLib\Message\AMQPMessage;

class PushMsgAmq
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PushMsgAmq();
        }
        return self::$instance;
    }

    /**AMQ消息发送
     * @param string $queue
     * @param string $content
     */
    public function send($queue = '', $content = '')
    {
        $conf = config('config.amq');
        $connection = new AMQPStreamConnection($conf['host'], $conf['port'], $conf['user'], $conf['pwd']);
        $channel = $connection->channel();
        $queue = $conf['prefix'].'_'.$queue;
        $channel->queue_declare($queue, false, false, false, false);

        //消息内容
        $msg = new AMQPMessage($content);
        $channel->basic_publish($msg, '', $queue);

        //关闭连接
        $channel->close();
        $connection->close();
    }

}

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


class LoginDetailConsumeCommand extends Command
{

    const COMMAND_NAME = "LoginDetailConsumeCommand";
    const QUEUE_NAME = "q_login_detail_message"; //队列名字
    const EXCHANGE_NAME = "ex_login_detail_message";//交换机名字
    const ESINDEX = 'zb_login_detail_new';
    const ROUTERKEY = ""; //routerkey


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        $config = ['queue_name' => SELF::QUEUE_NAME, "exchange_name" => SELF::EXCHANGE_NAME, "routerkey" => SELF::ROUTERKEY];
        ConsumeService::getInstance()->handler($config, [$this, "callback"]);
    }

    //消息的业务逻辑处理
    public function callback($msg)
    {
        Log::info(self::COMMAND_NAME . "原始数据:" . $msg->body);
        if ($msg->body) {
            $parseRes = json_decode($msg->body, true);
            if ($parseRes) {
                $this->bulk([$parseRes]);
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }

    }



    /**数据写入es中
     * @param $dataByMin
     * @param $dataByDay
     */
    private function bulk($data)
    {
        $body = [];
        foreach ($data as $item) {
            $body[] = ['index' => ['_index' => self::ESINDEX, '_id' => $this->getUniqkey($item)]];
            $body[] = $item;
        }
        return ElasticsearchService::getInstance()->bulkData( self::ESINDEX,$body);
    }


    //获取不重复的key
    public function getUniqkey($data){
        $keyList = ["id","user_id","channel","device_id","login_ip","mobile_version","idfa","version"];
        $params="";
        foreach($keyList as $item){
            if(isset($data[$item])){
                $params.= $data[$item];
            }
        }
        return md5($params);
    }


}

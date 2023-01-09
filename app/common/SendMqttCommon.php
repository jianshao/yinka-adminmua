<?php
/**
 * 公共MQTT消息
 * yond
 *
 */

namespace app\common;

use think\App;
use think\facade\Log;
use think\facade\Request;
use think\cache\driver\Redis;
use constant\CodeConstant as coder;


class SendMqttCommon
{

    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new SendMqttCommon();
        }
        return self::$instance;
    }

    public function sendMsg($send,$client,$msg,$sess=false)
    {
        $conf = config('config.MQTT');
        $accessKey = $conf['accessKey'];
        $secretKey = $conf['secretKey'];
        $endPoint = $conf['endPointIn'];
        $instanceId = $conf['instanceId'];
        $topic = $conf['topic'].'/'.$send;
        $groupId = $conf['groupId'];

        $port = 1883;
        $keepalive = 90;
        if ($sess) {
            $qos = 0;
            $cleanSession = true;
        }else{
            $qos = 1;
            $cleanSession = false;
        }
        // $clientId = $deviceId;
        $client = $client.'@@@'.'gonggao';
        $myToken = $this->getMToken($send,$client);
        $mqttClient = new \Mosquitto\Client($client, $cleanSession);
        // ## 设置鉴权参数，参考 MQTT 客户端鉴权代码计算 username 和 password
        // $username = 'Signature|' . $accessKey . '|' . $instanceId;
        // $sigStr = hash_hmac("sha1", $client, $secretKey, true);
        // $password = base64_encode($sigStr);

        $mqttClient->setCredentials($myToken['username'], $myToken['password']);
        $mqttClient->connect($endPoint, 1883, 5);

        //send msg
        $mqttClient->loop();
        $mid = $mqttClient->publish($topic, json_encode($msg), 1);
        $mqttClient->loop();
        return true;

    }



    public function p2pMsg($cleanSession,$receiverid,$msg,$client)
    {

        $conf = config('config.MQTT');
        // $redis = $this->getRedis();

        $mqttClient = new \Mosquitto\Client($client, $cleanSession);
        // ## 设置鉴权参数，参考 MQTT 客户端鉴权代码计算 username 和 password
        $username = 'Signature|' . $conf['accessKey'] . '|' . $conf['instanceId'];
        $sigStr = hash_hmac("sha1", $client, $conf['secretKey'], true);
        $password = base64_encode($sigStr);

        $mqttClient->setCredentials($username, $password);
        $mqttClient->connect($conf['endPoint'], 1883, 5);

        //send msg
        $mqttClient->loop();
        $mqttP2PTopic = $conf['topic'] . "/p2p/".$receiverid;
        $mid = $mqttClient->publish($mqttP2PTopic, json_encode($msg), 1, 0);
        $mqttClient->loop();
        if ($mid > 0) {
            return true;
        }
        return false;

    }

    //发送创建请求
    protected function sendMqtt($conf,$roomid,$uid,$resources='',$action='')
    {
        $tokenUrl = $conf['tokenurl'].'/token/apply';
        if (empty($resources)) {
            $action = 'R,W';
            $resources = $conf['topic'].'/'.$roomid; //多个需要字典排序
        }
        $expireTime = msectime() + 1728000000; //过期时间加20天毫秒
        $instanceId = $conf['instanceId'];
        $str = sprintf('actions=%s&expireTime=%d&instanceId=%s&resources=%s&serviceName=mq',$action,$expireTime,$instanceId,$resources);
        $signature = base64_encode(hash_hmac("sha1", $str, $conf['secretKey'],true));

        $params['accessKey'] = $conf['accessKey'];
        $params['actions'] = $action;
        $params['expireTime'] = $expireTime;
        $params['instanceId'] = $instanceId;
        $params['proxyType'] = 'MQTT';
        $params['resources'] = $resources;
        $params['serviceName'] = 'mq';
        $params['signature'] = $signature;
        $res = curlData($tokenUrl,$params,'POST','form-data');
        return [$res,1720000];
    }

    /*
     *获取token
     */
    public function getMToken($roomid,$userid)
    {
        $redis = RedisCommon::getInstance()->getRedis();

        $conf = config('config.MQTT');
        //判断缓存是否存在MQTTtoken
        $MqttKey = 'MQTT_SELF_'.$roomid.'_'.$userid;
        $MqttToken = $redis->get($MqttKey);
        $MqttTokenTtl = $redis->ttl($MqttKey);
        if ($MqttToken && $MqttTokenTtl > 172800) {
            $result['username'] = 'Token|' . $conf['accessKey'] . '|' . $conf['instanceId'];
            $result['password'] = 'RW|' . $MqttToken;
            return $result;
        }
        //获取token
        $result = [];
        list($res,$expireTime) = $this->sendMqtt($conf,$roomid,$userid);
        $res = json_decode($res,true);
        if (isset($res['code']) && $res['code'] == 200) {
   $redis->setex($MqttKey,$expireTime,$res['tokenData']);
            // $result['token'] = $res['tokenData'];
            $result['username'] = 'Token|' . $conf['accessKey'] . '|' . $conf['instanceId'];
            $result['password'] = 'RW|' . $res['tokenData'];
            return $result;
        }else{
            return false;
        }


    }




}

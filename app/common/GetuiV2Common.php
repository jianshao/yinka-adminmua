<?php

namespace app\common;

use think\facade\Log;

class GetuiV2Common
{
    protected static $instance;
    protected $redis = null;
    protected $token = '';

    //单例
    public static function getInstance($source)
    {
        if (!isset(self::$instance)) {
            self::$instance = new GetuiV2Common($source);
        }
        return self::$instance;
    }

    private $host = '';
    private $appkey = '';
    private $appid = '';
    private $mastersecret = '';

    private $base_url = 'https://restapi.getui.com/v2/';

    private function init($source)
    {
        // header("Content-Type: text/html; charset=utf-8");
        $this->appid = config("$source.getui.appid");
        $this->appkey = config("$source.getui.appkey");
        $this->mastersecret = config("$source.getui.mastersecret");
        $this->host = config("$source.getui.host");
    }

    public function __construct($source)
    {
        $this->init($source);
        $this->base_url = $this->base_url . '/' . $this->appid . '/';
        $this->token = $this->getToken();
    }

    public function getToken()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $token = $redis->get("getui:" . $this->appid);
        if (!$token) {
            $url = $this->base_url . 'auth';
            $data['timestamp'] = msectime();
            $data['appkey'] = $this->appkey;
            $data['sign'] = hash("sha256", $this->appkey . $data['timestamp'] . $this->mastersecret);
            $res = curlData($url, json_encode($data), 'POST');
            if ($res) {
                $resData = json_decode($res, true);
                if ($resData['code'] == 0 && $resData['msg'] == 'success') {
                    $token = $resData['data']['token'];
                    $redis->setex("getui:" . $this->appid, 86400, $token);
                }
            }
        }
        return $token;
    }

    /**
     * app透传消息(封禁)
     * @param $cid
     * @param $type
     * @param $content
     * @return bool|string
     */
    public function toSingleTransmission($cid, $type, $content)
    {
        $url = $this->base_url . 'push/single/alias';
        $data['request_id'] = uniqid();
        $data['settings'] = ['ttl' => 3600000];
        $data['audience'] = ['alias' => [$cid]];
        $data['push_message'] = ['transmission' => json_encode(['content' => $content, 'type' => $type])];
        $res = curlData($url, json_encode($data), 'POST', 'json', ["token:$this->token"]);
        Log::info('GetuiV2Common : toSingleTransmission response---' . $res);
        if ($res) {
            $resData = json_decode($res, true);
            if ($resData['code'] == 10001) {
                $this->token = $this->getToken();
                $this->toSingleTransmission($cid, $type, $content);
            }
        }
        return $res;
    }

    /**
     * app透传消息(封禁)
     * @param $cid
     * @param $type
     * @param $content
     * @return bool|string
     */
    public function toSingleTransmission2($uid, $content)
    {
        //发送消息
        $socket_url = config('config.socket_url_base') . 'iapi/globalUserNotify';

        $msg = [
            'userId' => (int) $uid,
            'msg' => json_encode([
                'type' => 'blackUser',
                'data' => ['userId' => (int) $uid, 'msg' => (string) $content],
            ]),
        ];

        $msgData = json_encode($msg);
        Log::info('toSingleTransmission2 : toSingleTransmission2 msgData---' . $msgData);
        $res = curlData($socket_url, $msgData, 'POST', 'json');
        Log::info('toSingleTransmission2 : toSingleTransmission2 response---' . $res);
        return $res;
    }

    /**
     * app透传消息(新消息通知)
     */
    public function toAppTransmission()
    {
        $url = $this->base_url . 'push/all';
        $data['request_id'] = uniqid();
        $data['settings'] = ['ttl' => 3600000];
        $data['audience'] = 'all';
        $data['push_message'] = ['transmission' => json_encode(['type' => 0])];
        $res = curlData($url, json_encode($data), 'POST', 'json', ["token:$this->token"]);
        Log::info('GetuiV2Common : toAppTransmission response---' . $res);
        if ($res) {
            $resData = json_decode($res, true);
            if ($resData['code'] == 10001) {
                $this->token = $this->getToken();
                $this->toAppTransmission();
            }
        }
        return $res;
    }

}

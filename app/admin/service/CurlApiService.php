<?php

namespace app\admin\service;

use app\admin\common\ApiUrlConfig;
use think\facade\Log;

class CurlApiService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register()
    {
        $app_version = config('config.app_version');
        if (!empty($app_version) && $app_version == 'v2') {
            $channel = config('config.channel');
            $time = time();
            $data = json_encode(['time' => $time, 'sign' => $this->getApiSign($time), 'channel' => $channel]);
            $socket_url = config('config.app_api_url') . 'api/v2/init/register';
            $this->curl($data, $socket_url);
        }
        return true;
    }

    public function changeMemberInfo($id, $status)
    {
        $time = time();
        $data = json_encode(['time' => $time, 'sign' => $this->getApiSign($time), 'mdaId' => $id, 'status' => $status]);
        $socket_url = config('config.app_api_url') . 'api/v1/memberDetailAudit';
        $res = $this->curl($data, $socket_url);
        return $res;
    }

    public function blockUserNotice($userId, $status,$permanentBlock=0)
    {
        $time = time();
        $data = json_encode(['userId' => $userId, 'sign' => $this->getApiSign($time), 'status' => $status,'permanentBlock'=>$permanentBlock]);
        $socket_url = config('config.app_api_url') . ApiUrlConfig::$block_user_notice;
        $res = $this->curl($data, $socket_url);
        return $res;
    }

    //PK房间配置接口
    public function pkRoomsConf($roomIds, $type)
    {
        $time = time();
        $data = json_encode(['roomIds' => $roomIds, 'sign' => $this->getApiSign($time), 'rank' => $type]);
        $socket_url = config('config.app_api_url') . ApiUrlConfig::$pk_room_conf;
        $res = $this->curl($data, $socket_url);
        return $res;
    }

    //跨房PK房间开始接口
    public function startCrossPk($params)
    {
        $data = [
            'createRoomId' => (int) $params['start_room_id'],
            'pkRoomId' => (int) $params['pk_room_id'],
            'punishment' => (string) $params['desc'],
            'countdown' => (int) $params['count'],
        ];
        $socket_url = config('config.socket_url_base') . ApiUrlConfig::$pk_cross_start;
        $res = curlData($socket_url, json_encode($data), 'POST', 'json');
        Log::info('请求地址:{url},参数====>{data},返回值===>{res}', ['url' => $socket_url, 'data' => json_encode($data), 'res' => $res]);

        return json_decode($res, true);
    }

    //跨房PK房间结束接口
    public function endCrossPk($params)
    {
        $data = [
            'createRoomId' => (int) $params['start_room_id'],
            'pkRoomId' => (int) $params['pk_room_id'],
        ];
        $socket_url = config('config.socket_url_base') . ApiUrlConfig::$pk_cross_end;
        $res = curlData($socket_url, json_encode($data), 'POST', 'json');
        Log::info('请求地址:{url},参数====>{data},返回值===>{res}', ['url' => $socket_url, 'data' => json_encode($data), 'res' => $res]);

        return json_decode($res, true);
    }

    //封禁房间接口
    public function banRoom($data)
    {
        $socket_url = config('config.socket_url_base') . ApiUrlConfig::$ban_room;
        $res = curlData($socket_url, json_encode($data), 'POST', 'json');
        Log::info('请求地址:{url},参数====>{data},返回值===>{res}', ['url' => $socket_url, 'data' => json_encode($data), 'res' => $res]);

        return json_decode($res, true);
    }

    public function curl($data, $socket_url)
    {
        Log::info("curl:socket_url====>{$socket_url},data====>{data}", ['data' => $data]);
        $res = curlData($socket_url, $data, 'POST');
        Log::info("curl:socket_url====>{$socket_url},res====>{res}", ['res' => $res]);
        return json_decode($res, true);
    }

    public function getApiSign($time)
    {
        return md5(sprintf("%s%s", 'registerfanqie', $time));
    }

    public function getHomePageRooms()
    {
        $data = json_encode([]);
        $socket_url = config('config.app_api_url') . ApiUrlConfig::$home_room_list;
        $res = $this->curl($data, $socket_url);

        // if ($res['code'] != 200) {
        //     throw new \Exception($res['desc'], $res['code']);
        // }

        return $res['data'];
    }

    public function getEnjoyRooms()
    {
        $data = json_encode([]);
        $socket_url = config('config.app_api_url') . ApiUrlConfig::$enjoy_room_conf;
        $res = $this->curl($data, $socket_url);

        // if ($res['code'] != 200) {
        //     throw new \Exception($res['desc'], $res['code']);
        // }

        return $res['data'];
    }
}
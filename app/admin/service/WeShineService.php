<?php

namespace app\admin\service;

use think\Exception;
use think\facade\Log;

class WeShineService
{
    protected static $instance;
    protected $secret = 'bfdc1d6e403ac3db9cf0f1d4e165ed76';
    protected $openId = '1640939269';
    protected $url = [
        'shineSearch' => "http://api.open.weshineapp.com/1.0/search",
        'shineAlbumList' => "http://api.open.weshineapp.com/1.0/trending/albums", //专辑列表
        'shineAlbumSearch' => "http://api.open.weshineapp.com/1.0/album/search", //专辑搜索
        'shineHotLook' => "http://api.open.weshineapp.com/1.0/hot",
        'keywords' => "http://api.open.weshineapp.com/1.0/keywords",
        'shineAlbumItems' => "http://api.open.weshineapp.com/1.0/album/items",
        'shineAlbumGifs' => "http://api.open.weshineapp.com/1.0/album/gifs",
    ];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new WeShineService();
        }
        return self::$instance;
    }

    public function shineAlbumSearch($keyWord)
    {
        $data = ['keyword' => $keyWord];
        return $this->getResponse('shineSearch', $data);
    }

    public function shineAlbumItem($keyWord)
    {
        $data = [
            'id' => $keyWord,
            'limit' => 100,
            'offset' => 0,
        ];

        return $this->getResponse('shineAlbumGifs', $data);
    }

    public function getSign($timeStamp)
    {
        return strtoupper(md5(sprintf('%s#%s#%s', $this->openId, $this->secret, $timeStamp)));
    }

    public function buildParams(&$data)
    {
        $timestamp = msectime();
        $data['openid'] = $this->openId;
        $data['timestamp'] = $timestamp;
        $data['sign'] = $this->getSign($timestamp);
        return http_build_query($data);
    }

    public function getResponse($name, $data)
    {
        $paramsString = $this->buildParams($data);
        $url = $this->url[$name] . '?' . $paramsString;

        Log::info(sprintf('sWeShineService:request name:%s url:%s', $name, $url));
        try {
            $response = curlData($url, []);
            Log::info(sprintf('WeShineService:request name:%s url:%s response:%s', $name, $url, $response));
            $result = json_decode($response, true);

            $res = [];
            if (isset($result['meta']['status']) && $result['meta']['status'] == 200) {
                if (isset($result['data'])) {
                    $res['list'] = $result['data'];
                }

                if (isset($result['pagination'])) {
                    $res['pageInfo'] = [
                        'totalCount' => $result['pagination']['totalCount'] ?? 0,
                        'totalPage' => $result['pagination']['totalPage'] ?? 0,
                        'count' => $result['pagination']['count'] ?? 0,
                        'offset' => $result['pagination']['offset'] ?? 0,
                    ];
                }
                return $res;
            } else {
                throw new Exception('未知错误，请重试', 500);
            }
        } catch (Exception $e) {
            Log::error(sprintf('WeShineService:response:url:%s error:%s,strace:%s', $url, $e->getMessage(), $e->getTraceAsString()));
        }
    }

}

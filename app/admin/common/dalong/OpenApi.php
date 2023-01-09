<?php
namespace app\admin\common\dalong;

/**
 * 封装请求初始化JWT
 * User: huashan
 * Date: 2020/10/21
 * Time: 10:34
 */

use app\admin\common\dalong\config\Config;
use app\admin\common\dalong\request\OpenapiRequest;
use app\admin\common\dalong\request\OpenapiRequestData;
use app\common\RedisCommon;
use think\facade\Log;

class OpenApi
{
    public $secret;
    protected $jwt;
    protected $warUrl;
    protected $urlMap;
    protected $config;


    public function __construct()
    {
        ini_set('date.timezone', 'Asia/Shanghai');
        //初始化
        $this->jwt = $this->getJwt();
        //$this->config = Config::$config;
        //$this->warUrl = Config::$config['openAPIUrl'];
        //$this->urlMap = Config::$urlMap;
        $this->config = config("config.dalongconfig");
        $this->warUrl = config("config.dalongconfig")['openAPIUrl'];
        $this->urlMap = Config::$urlMap;

    }

    public function getJwt()
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 8]);
        if($jwt = $redis->get("daLongapi:jwt")){
            return $jwt;
        }else{

            $params =[
                //'appKey'=>Config::$config['app_key'],
                //'secret'=>Config::$config['secret']
                'appKey'=> config("config.dalongconfig")['app_key'],
                'secret'=> config("config.dalongconfig")['secret']
            ];
            $result = self::curl($params,config("config.dalongconfig")['openAPIUrl'].Config::$urlMap['JWTLOGIN'],"POST",true);
            $parseResult = json_decode($result, true);
            $jwt = $parseResult['data']['jwt'];
            $redis->setex("daLongapi:jwt",6000,$jwt);
            return $jwt;
        }

    }


    protected function getSignParams($params)
    {
        ksort($params);
        $attachString = "";
        foreach ($params as $k => $v) {
            $attachString .= $k . trim($v);
            // $attachString .= $k . "=" . trim($v) . "&";
        }
        return trim($attachString, "&");
    }

    protected static function getSignParams2($params)
    {

        ksort($params);
        $attachString = "";
        foreach ($params as $k => $v) {
            // $attachString .= $k . trim($v);
            $attachString .= $k . "=" . trim($v) . "&";
        }
        return trim($attachString, "&");
    }

    /**
     * md5方式签名
     * @param  array $params 待签名参数
     * @return string
     */
    protected function generateMd5Sign($params,$secret)
    {
        $string = $secret.$this->getSignParams($params) . $secret;
        return strtoupper(md5($string));
    }

    /**
     * @param $params
     * @return string
     */
    public function execute(OpenapiRequest $request,$method = "POST")
    {
        //设置 获取业务参数
        $apiParams = $request->getApiParas();
        //公共请求参数
        $sysData = new OpenapiRequestData();
        $sysData->setAppKey($this->config['app_key']);
        $sysData->setDataMame($request->getName());
        $sysData->setFormat('json');
        $sysData->setVersion($request->getVersion());
        $sysData->setTimestampName(date('Y-m-d H:i:s'));
        $sysParams = $sysData->getValues();
        //签名
        $sysParams["sign"] = $this->generateMd5Sign(array_merge($apiParams, $sysParams),$this->config['secret']);

        //发起HTTP请求
        try {
            $respObject = self::curl(json_encode(array_merge($sysParams, $apiParams)), $this->warUrl.'api',$method,false,$this->jwt);
        } catch (\Throwable $e) {
            throw $e;
            return false;
        }

        return $respObject;

    }

    /**
     * @param int $length
     * @return string
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * @param $json
     * @param $url
     * @param int $second
     * @return mixed
     */
    public static function curl($json, $url,$method = "POST",$is_jwt = false,$header=null,$second = 30)
    {

        // 初始化curl
        $ch = curl_init();
        // 设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        if(!$is_jwt){
            if($method == "GET"){
                $arr = json_decode($json,true);
                $arr["timestamp"] = rawurlencode($arr["timestamp"]);

                if(isset($arr["data"])){
                    if($arr["data"] != null){
                        $arr["data"] = rawurlencode($arr["data"]);
                    }
                }

                $urlsign = self::getSignParams2($arr);
                $url = $url."?".$urlsign;
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

        // 设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        // 要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // post提交方式
        if($method == "POST"){
            curl_setopt($ch, CURLOPT_POST, TRUE);
            if($is_jwt){
                $json = is_array($json) ? json_encode($json,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $json;
            }else{
                $json = json_decode($json,true);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS,$json );
        }

        if (!empty($header)) {
            $headers = array();
            $headers[] = 'Authorization:Bearer ' . $header;
            $headers[] = 'Accept:*/*';
            $headers[] = 'Accept-Language: zh-CN,zh;q=0.9';

        }else{
            $headers = ["Content-Type:application/json;charset=UTF-8"];
        }
        if (is_array($headers) && 0 < count($headers))
        {
            curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
        }
        // 运行curl
        $data = curl_exec($ch);
        // 返回结果
        if ($data) {
            Log::info("dalongapi:request:res".$data);
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            Log::error("dalongapi:request:error".$error);
            throw new \Exception("curl出错，错误码:$error");
        }
    }
}

<?php

namespace app\admin\service;

use app\exceptions\ApiExceptionHandle;
use think\facade\Log;

class ApiService
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

    protected $curl_url;

    public function __construct()
    {
        $this->curl_url = config('config.app_api_url');
    }

    public function curlApi($url, $params, $is_form_data = false, $is_throw = true)
    {
        $curl_url = $this->curl_url . $url;

        try {
            if ($is_form_data) {
                $res = curlData($curl_url, $params, 'POST', 'form-data');

            } else {
                $res = curlData($curl_url, json_encode($params), 'POST');
            }
        } catch (\Throwable $th) {
            Log::error(__CLASS__ . "@curlApi, curl_url====>{$curl_url}, data====>{data}", ['data' => json_encode($params)]);
            throw new ApiExceptionHandle('网络错误', 500);
        }

        Log::info(__CLASS__ . "@curlApi, curl_url====>{$curl_url}, params====>{params}, res====>{$res}", ['params' => json_encode($params)]);

        $res = json_decode($res, true);

        if ($res['code'] != 200 && $is_throw) {
            Log::error(json_encode($res));
            throw new ApiExceptionHandle($res['desc'], 500);
        }

        return $res;
    }

    /**
     * curl请求
     * @param $url
     * @param $data
     * @param string $method
     * @param string $type
     * @return bool|string
     */
    public function curlData($url, $data, $method = 'GET', $type = 'json', $head = [], $connnectTimer = 2)
    {
        $start_time = msectime();
        Log::info(sprintf('curlData url=%s startTime=%d', $url, $start_time));
        //初始化
        $ch = curl_init();
        $headers_type = [
            'form-data' => ['Content-Type: multipart/form-data'],
            'json' => ['Content-Type: application/json'],
        ];
        $headers = array_merge($headers_type[$type], $head);

        if ($method == 'GET') {
            if ($data) {
                $querystring = http_build_query($data);
                $url = $url . '?' . $querystring;
            }
        }
        // 请求头，可以传数组
        // $headers[]  =  "Authorization: Bearer ". $accessToken;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 执行后不直接打印出来
        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // 请求方式
            curl_setopt($ch, CURLOPT_POST, true); // post提交
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // post的变量
        }
        if ($method == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if ($method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connnectTimer);
        curl_setopt($ch, CURLOPT_TIMEOUT, $connnectTimer);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
        $output = curl_exec($ch); //执行并获取HTML文档内容
        curl_close($ch); //释放curl句柄
        $end_time = msectime();
        Log::info(sprintf('curlData url=%s endTime=%d response=%d', $url, $end_time, $end_time - $start_time));
        return $output;
    }
}

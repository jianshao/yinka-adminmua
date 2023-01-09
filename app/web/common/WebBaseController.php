<?php


namespace app\web\common;

use app\BaseController;
use Firebase\JWT\JWT;
use think\facade\Session;

class WebBaseController extends BaseController
{
    protected $jwtKet = "xin_yue_yyht_jwt_key";
    protected $returnCode = null;
    protected $returnMsg = '';
    protected $returnData = null;
    protected $userinfo = [];
    protected $filterRouter = [
        '/web/webUserWithdrawal/webUserWithdrawalCodeCheck',
        '/web/webUserWithdrawal/webUserWithdrawalLogin',
        '/web/webUserWithdrawal/withdrawalLogin',
    ];

    public function initialize()
    {
        if (!in_array(explode('?', $this->request->server('REQUEST_URI'))[0], $this->filterRouter)) {
            $userinfo = Session::All();
            if (empty($userinfo)) {
                header('Location: /web/webUserWithdrawal/withdrawalLogin');
                die;
//                return $this->return_json(\constant\CodeConstant::CODE_请重新登录, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请重新登录]);
            }
//            if ($userinfo['ip'] != $this->request->ip()) {
//                return $this->return_json(\constant\CodeConstant::CODE_请重新登录, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请重新登录]);
//            }

            $this->userinfo = $userinfo;
        }
    }

    protected function return_json($code = 200, $data = array(), $msg = '', $is_die = 0)
    {
        $out['code'] = $code ?: 0;
        $out['msg'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
        $out['data'] = $data ?: [];
        if ($is_die) {
            echo json_encode($out);
            return;
        } else {
            return json_encode($out);
        }
    }


    protected function getIdByToken(string $token)
    {
        try {
            $decoded = JWT::decode($token, $this->jwtKet, array('HS256'));
            $tokenInfo = (array)$decoded;
        } catch (\Exception $exception) {
            return null;
        }

        if (empty($tokenInfo['id'])) {
            return null;
        }
        return $tokenInfo;
    }

    protected function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.redis');
        $param['host'] = $redis_result['host'];
        $param['port'] = $redis_result['port'];
        $param['password'] = $redis_result['password'];
        $param['select'] = 0;
        if (!empty($arr)) {
            foreach ($arr as $v => $v) {
                $param[$v] = $v;
            }
        }

        $this->handler = new \Redis;
        $this->handler->connect($param['host'], $param['port'], 0);
        if ('' != $param['password']) {
            $this->handler->auth($param['password']);
        }

        if (0 != $param['select']) {
            $this->handler->select($param['select']);
        }
        return $this->handler;
    }

    //客户端ip
    protected function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }


    /**
     * @param string $url get请求地址
     * @param int $httpCode 返回状态码
     * @return mixed
     */
    protected function curl_get($url, $time = 3)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 不做证书校验,部署在linux环境下请改为true
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time);
        curl_setopt($ch, CURLOPT_TIMEOUT, $time);

        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
    }

    protected function checkPhone($phone)
    {
        //校验手机号
        $pattern = "/^1[3456789]\d{9}$/";
        if (!preg_match($pattern, $phone)) {
            return false;
        }
        return true;
    }

    /* 生成随机字符串.
    *
    * @param int $length 需要生成的长度.
    * @param string $table 需要生成的字符串集合.
    *
    * @return string
    */
    protected function generateRandomStr($length = 6, $table = '0123456789')
    {
        $code = '';
        if ($length <= 0 || empty($table)) {
            return $code;
        }
        $max_size = strlen($table) - 1;
        while ($length-- > 0) {
            $code .= $table[rand(0, $max_size)];
        }
        return $code;
    }
}
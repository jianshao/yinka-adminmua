<?php

namespace app\admin\common;

use app\admin\model\AdminUserModel;
use app\admin\model\ConfigModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\admin\service\AdminLogsService;
use app\admin\service\ConfigService;
use app\admin\service\MenuService;
use app\admin\service\RoleMenuService;
use app\admin\service\UserRoleService;
use app\BaseController;
use app\common\RedisCommon;
use Firebase\JWT\JWT;
use think\App;
use think\Exception;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;

class AdminBaseController extends BaseController
{
    protected $jwtKet = "xin_yue_yyht_jwt_key";
    protected $handler;
    public $token;
    public $channelId = 0;
    public $pid = 0;
    public $channel_level = 0;
    public $code_ok = \constant\CodeConstant::CODE_成功;
    public $code_ok_map = \constant\CodeConstant::CODE_OK_MAP;
    public $code_parameter_err_map = \constant\CodeConstant::CODE_PARAMETER_ERR_MAP; //参数错误
    public $code_inside_err_map = \constant\CodeConstant::CODE_INSIDE_ERR_MAP; //内部错误
    public $url_map = \constant\RouterUrlConstant::URL_MAP;
    public $user_role_menu;

    public $start_time;
    public $end_time;
    public $end_time2;
    public $img_url;
    public $default_date;
    const LIMIT = 20;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->start_time = date("Y-m-d");
        $this->end_time = date("Y-m-d", strtotime('+1 days'));
        $this->end_time2 = date("Y-m-d", strtotime('-1 days'));
        $this->img_url = config('config.APP_URL_image');
        $this->default_date = $this->start_time . ' - ' . $this->end_time;
    }

    public function initialize()
    {
        $res = [];
        $res['id'] = 0;
        $master_url = $this->request->param('master_url');

        //记录日志
        Log::record("\n\r", 'debug');
        Log::record("----------------------------", 'debug');
        Log::record('请求方法地址 : ' . $master_url, 'debug');
        Log::record("\n\r", 'debug');

        Log::record('请求参数 : ' . json_encode($this->request->request()), 'debug');
        Log::record('服务器参数 : ' . json_encode($this->request->server()), 'debug');
        Log::record("----------------------------", 'debug');
        Log::record("\n\r", 'debug');

        if ($master_url == '') {
            echo $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
            exit;
        }
        $features = isset(\constant\RouterUrlConstant::URL_ALL_MAP[$master_url]) ? \constant\RouterUrlConstant::URL_ALL_MAP[$master_url] : '';
        if (!$features) {
            // echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            // exit;
        }
        $admin_url = config('config.admin_url');
        $token = $this->request->param('token', '');
        if ($token) {
            $res = $this->getAdminIdByToken($token);
            if (empty($res)) {
                echo "<script>top.location.href='$admin_url/loginIndex?master_url=/admin/loginIndex';</script>";
                die;
            }

            //是否登录
            $redis = $this->getRedis();
            $cache_token = $redis->get('admin_token_' . $res['id']);
            if (empty($redis->get(\constant\CommonConstant::ADMIN_USER_UID . $res['id'])) || $cache_token != md5($token)) {
                echo "<script>top.location.href='$admin_url/loginIndex?master_url=/admin/loginIndex';</script>";
                die;
            }
            $res['admin_token'] = $cache_token;

            if (session('username') != $res['username']) {
                echo "<script>top.location.href='$admin_url/loginIndex?master_url=/admin/loginIndex';</script>";
                die;
            }
        }

        if (!in_array($this->request->param('master_url'), \constant\CommonConstant::TOKEN_NO_CHECK_URI_MAP)) {

            //查询当前用户对应的角色
            $where_in = $this->_getUserRoleMenu($res['id'], 'router');
            if (empty($where_in)) {
                echo $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
                exit;
            }
            $router = array_column($where_in, 'router');
            if (!in_array($this->request->param('master_url'), $router)) {
                echo $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
                exit;
            }
            $this->user_role_menu = $router;
        }
        //获取该用户权限
        $this->token = $res;
        if ($res['id'] != 0) {
            $where = ['id' => $res['id']];
            $admin_info = AdminUserModel::getInstance()->getAdminInfo($where);
            if ($admin_info && $admin_info['channel'] > 0) {
                $this->channelId = $admin_info['channel'];
                $channelInfo = MarketChannelModel::getInstance()->getModel()->where(['id' => $admin_info['channel']])->findOrEmpty()->toArray();
                $this->pid = $channelInfo['pid'];
                $this->channel_level = $channelInfo['channel_level'];
            }
        }
        $log['admin_id'] = isset($this->token['id']) ? $this->token['id'] : '';
        $log['admin_user'] = isset($this->token['username']) ? $this->token['username'] : '';
        $log['features'] = $features;
        $log['features_url'] = $master_url;
        $log['content'] = json_encode($this->request->param());
        $log['created_time'] = time();
        AdminLogsService::getInstance()->addAdminLogs($log);
    }

    public function _getUserRoleMenu($id, $field = '*')
    {
        //查询当前用户对应的角色
        $role_id = UserRoleService::getInstance()->getUserRole(array('user_id' => $id, 'status' => 1), 'role_id');
        if (empty($role_id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
            exit;
        }
        //查询角色对应的菜单
        $menu = RoleMenuService::getInstance()->getRoleToMenuLists(array('role_id' => $role_id->role_id, 'status' => 1), 'menu_id');
        if (empty($menu)) {
            echo $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
            exit;
        }
        return MenuService::getInstance()->getMenuItemsWhereIn(implode(',', array_column($menu, 'menu_id')), $field, 'id');
    }

    private function _checkParam()
    {
        $token = Request::header('token');
        if ($token) {
            $decoded = JWT::decode($token, $this->jwtKet, array('HS256'));
        }

    }

//     private  static $redis = null;
    //     /*获取redis对象*/
    //     protected function getRedis(){
    //         if(!self::$redis instanceof \Redis){
    //             self::$redis = new \Redis();
    //             self::$redis->connect('127.0.0.1', 6379);
    // //            self::$redis->auth(config('Redis.auth_password'));
    //             self::$redis->select(0);
    //         }
    //         var_json()
    //         return self::$redis;
    //     }

    public function return_json($code = 200, $data = array(), $msg = '', $is_die = false)
    {
        $out['code'] = $code ?: 0;
        $out['msg'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
        $out['data'] = $data ?: [];
        // $runtime    = round(microtime(true) - THINK_START_TIME, 10);
        // $out['runtime'] = $runtime;

        if ($is_die) {
            echo json_encode($out, JSON_UNESCAPED_UNICODE);
            return;
        } else {
            return json_encode($out, JSON_UNESCAPED_UNICODE);
        }
    }

    public function getAdminIdByToken(string $token)
    {
        try {
            $decoded = JWT::decode($token, $this->jwtKet, array('HS256'));
            $tokenInfo = (array) $decoded;
        } catch (Exception $exception) {
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
            foreach ($arr as $k => $v) {
                $param[$k] = $v;
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
    public function getUserIpAddr()
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

    //校验用户权限
    //    public function checkUserRule($token = '')
    //    {
    //        $id = isset($token['id']) ? $token['id'] : $this->token['id'];
    //        $where = array('user_id' => $id, 'status' => 1);
    //
    //        //查询当前用户权限
    //        $user_role = UserRoleService::getInstance()->getUserRole($where, '*');
    //        if (empty($user_role)) {
    //            return $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
    //        }
    //        $role_id = $user_role->role_id;
    //        //获取该用户权限
    //        $menu_id = RoleMenuService::getInstance()->getRoleToMenuLists(array('role_id' => $role_id, 'status' => 1), 'menu_id as id');
    //        if (empty($menu_id)) {
    //            return $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
    //        }
    //        $getAppointMenuLists = $this->_getAppointMenuLists($menu_id);
    //        $where_in = MenuService::getInstance()->getMenuItemsWhereIn(implode(',', $getAppointMenuLists), '*', 'id');
    //        $list = $this->_getMenuLists($where_in);
    //        return $list;
    //查询当前权限下的菜单
    //        $role_menu = RoleMenuService::getInstance()->getRoleMenu($role_id,'*');
    //        $router = array_column($role_menu, 'router');
    //        if(empty($router) || !in_array($url_router,$router)){
    //            return $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
    //        }
    //        return true;
    //    }

    /**
     * @param string $url get请求地址
     * @param int $httpCode 返回状态码
     * @return mixed
     */
    public function curl_get($url, $time = 3)
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

    /*
     *@递归查询该id下所有节点
     */
    public function _getAppointMenuLists($id, &$result = [])
    {
        $tmp = [];
        foreach ($id as $k => $v) {
            $tmp = MenuService::getInstance()->getMenuLists(array('parent' => $v['id'], 'status' => 1), 'id')->toArray();
            $result[$v['id']] = $v['id'];
            if (!empty($tmp)) {
                $this->_getAppointMenuLists($tmp, $result);
            }
        }
        return $result;
    }

    public function _getMenuLists($items, $menu_id = [])
    {
        $result = [];
        foreach ($items as $k => $v) {
            $result[$v['id']] = $v;
            $result[$v['id']]['state']['opened'] = false;
            $result[$v['id']]['iid'] = $v['id'];
            $result[$v['id']]['children'] = array();
            unset($result[$v['id']]['id']);
        }
        $tree = array(); //格式化好的树
        foreach ($result as $item) {
            if ($menu_id) {
                if (in_array($result[$item['id']]['id'], $menu_id)) {
                    $result[$item['iid']]['is_role'] = 1;
                } else {
                    $result[$item['iid']]['is_role'] = 2;
                }
            }

            if (isset($result[$item['parent']])) {
                $result[$item['parent']]['children'][] = &$result[$item['iid']];
            } else {
                $tree[] = &$result[$item['iid']];
            }
        }
        return $tree;
    }

    public function bitSplit($n)
    {
        $n |= 0;
        $pad = 0;
        $arr = array();
        while ($n) {
            if ($n & 1) {
                array_push($arr, 1 << $pad);
            }

            $pad++;
            $n >>= 1;
        }
        return $arr;
    }

    /**
     * @param $tilie
     * @param $data
     * @董波钊
     * @2020-12-09 12:09
     */
    public function _Daochu($string, $name)
    {
        $filename = $name . date('Y-m-d H:i:s') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    /**
     * 方法一：获取随机字符串
     * @param number $length 长度
     * @param string $type 类型
     * @param number $convert 转换大小写
     * @return string 随机字符串
     */
    public function random($length = 6, $type = 'string', $convert = 0)
    {
        $config = array(
            'number' => '1234567890',
            'letter' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'string' => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789',
            'all' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        );

        if (!isset($config[$type])) {
            $type = 'string';
        }

        $string = $config[$type];

        $code = '';
        $strlen = strlen($string) - 1;
        for ($i = 0; $i < $length; $i++) {
            // $code .= $string{mt_rand(0, $strlen)};
        }
        if (!empty($convert)) {
            $code = ($convert > 0) ? strtoupper($code) : strtolower($code);
        }
        return $code;
    }

    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key 私钥字符串
     * return 签名结果
     */
    public function rsaSign($data, $private_key)
    {

        $search = [
            "-----BEGIN RSA PRIVATE KEY-----",
            "-----END RSA PRIVATE KEY-----",
            "\n",
            "\r",
            "\r\n",
        ];

        $private_key = str_replace($search, "", $private_key);
        $private_key = $search[0] . PHP_EOL . wordwrap($private_key, 64, "\n", true) . PHP_EOL . $search[1];
        $res = openssl_get_privatekey($private_key);
        if ($res) {
            openssl_sign($data, $sign, $res);
            openssl_free_key($res);
        } else {
            exit("私钥格式有误");
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * @return string
     * @生成订单号
     */
    public function orderNo()
    {
        $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int) (substr($order_id_main, $i, 1));
        }
        $osn = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
        return $osn;
    }

    /**
     * @return bool|mixed|string
     * 获取第三方token
     */
    public function GetToken()
    {
        $redis = $this->getRedis();
        $token = $redis->get('rongYiFuToken');
        $GetToken = 'https://slb-authorize.lastmiles.cn/lastmiles-authorize-api/merchant/authorization';
        $data = [
            'appId' => '0852557045',
            'appSecret' => 'bYAsQKd6SZRbeqMdCXGr8mvUW44VwOFY',
        ];
        if (empty($token)) {
            $res = curlData($GetToken, $data);
            $parameter = json_decode($res, true);
            if ($parameter['code'] == 0) {
                $redis->SETEX('rongYiFuToken', 7200, $parameter['data']);
                return $parameter['data'];
            } else {
                return false;
            }
        } else {
            return $token;
        }
    }

    /**
     * RSA验签
     * @param $data 待签名数据
     * @param $public_key 公钥字符串
     * @param $sign 要校对的的签名结果
     * return 验证结果
     */
    public function rsaCheck($data, $public_key, $sign)
    {
        $search = [
            "-----BEGIN PUBLIC KEY-----",
            "-----END PUBLIC KEY-----",
            "\n",
            "\r",
            "\r\n",
        ];
        $public_key = str_replace($search, "", $public_key);
        $public_key = $search[0] . PHP_EOL . wordwrap($public_key, 64, "\n", true) . PHP_EOL . $search[1];
        $res = openssl_get_publickey($public_key);
        if ($res) {
            $result = (bool) openssl_verify($data, base64_decode($sign), $res);
            openssl_free_key($res);
        } else {
            exit("公钥格式有误!");
        }
        return $result;
    }

    /**
     * 数组分页函数  核心函数  array_slice
     * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
     * $count   每页多少条数据
     * $page   当前第几页
     * $array   查询出来的所有数组
     * order 0 - 不变     1- 反序
     */

    public function page_array($count, $page, $order, $key)
    {
        $array = $this->JsonEscape($key);

        global $countpage; #定全局变量
        $page = (empty($page)) ? '1' : $page; #判断当前页面是否为空 如果为空就表示为第一页面
        $start = ($page - 1) * $count; #计算每次分页的开始位置
        if ($order == 1 && is_array($array)) {
            $array = array_reverse($array);
            $totals = count($array);
            $countpage = ceil($totals / $count); #计算总页面数
            $pagedata = array();
            $pagedata['list'] = array_slice($array, $start, $count);
            $pagedata['count'] = count($array);
            return $pagedata; #返回查询数据
        } else {
            return []; #返回查询数据
        }
    }

    public function pageArray($count, $page, $order, $array)
    {
        global $countpage; #定全局变量
        $page = (empty($page)) ? '1' : $page; #判断当前页面是否为空 如果为空就表示为第一页面
        $start = ($page - 1) * $count; #计算每次分页的开始位置
        if ($order == 1) {
            $array = array_reverse($array);
        }
        $totals = count($array);
        $countpage = ceil($totals / $count); #计算总页面数
        $pagedata = array();
        $pagedata['list'] = array_slice($array, $start, $count);
        $pagedata['count'] = count($array);
        return $pagedata; #返回查询数据
    }

    /**
     * @return mixed
     * @json转义
     * @dongbozhao
     * @2021-01-05 02:05
     */
    public function JsonEscape($key, $keyId = '', $id = '')
    {
        $array = ConfigModel::getInstance()->getModel()->where('name', $key)->field('json')->select()->toArray();
        $array = json_decode($array[0]['json'], true);
        if ($keyId && $id) {
            foreach ($array as $k => $v) {
                if ($array[$k]["$keyId"] == $id) {
                    return $array[$k];
                }
            }
        }
        return $array;
    }

    /**
     * @获取最大id
     * @dongbozhao
     * @2021-01-06 14:30
     */
    public function bigId($key, $keyId)
    {
        $array = $this->JsonEscape($key);
        if (is_array($array)) {
            $array = array_reverse($array);
            return $array[0][$keyId] + 1;
        } else {
            return 1;
        }

    }

    //过滤
    public function pregReplace($value)
    {
        $regex = "/\"|\,|\\\|\|/";
        return preg_replace($regex, "", $value); //二级渠道
    }

    /**
     * @param $uid
     * @param $assetId
     * @param $change
     * @param $operatorId
     * userId uid
     * assetId 豆： user:bean 钻石： user:diamond 金币： user:coin 礼物：gift:id 装扮：prop:id
     * change num
     * operatorId 管理员id
     * token 管理员token
     */
    public function inner($uid, $assetId, $change, $operatorId, $reason = '')
    {
        $this->checkMemberStatus($uid);
        $redis = $this->getRedis();
        $cache_token = $redis->get('admin_token_' . $operatorId);
        $data = [
            'userId' => $uid,
            'assetId' => $assetId,
            'change' => $change,
            'operatorId' => $operatorId,
            'reason' => $reason,
            'token' => $cache_token,
        ];
        $socket_url = config('config.app_api_url') . 'api/inner/adjustUserAsset';
        log::info('AdminBaseController@inner::{socket_url}', ['socket_url' => $socket_url]);
        $res = curlData($socket_url, $data);
        log::info('AdminBaseController@inner::{res}', ['res' => $res]);
        $parameter = json_decode($res, true);
        if ($parameter['code'] == 200) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    //清楚缓存
    public function updateRedisConfig($type)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $json = ConfigModel::getInstance()->getModel()->where('name', $type)->value('json');
        $is = $redis->set($type, $json);

        if ($type == 'gift_conf') {
            $this->setGiftMp4List($json, $redis);
        }
        //通知客户端更新资源
        ConfigService::getInstance()->register();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    public function setGiftMp4List($json, $redis)
    {
        $gifts = json_decode($json, true);
        foreach ($gifts as $_ => $gift) {
            if (isset($gift['giftMp4Animation']) && !empty($gift['giftMp4Animation'])) {
                $redis->hset('gift_mp4_list', $gift['giftId'], $gift['giftMp4Animation']);
            }
        }
    }

    public function checkMemberStatus($uid)
    {
        $cancel_user_status = MemberModel::getInstance()->getModel($uid)->where('id', $uid)->value('cancel_user_status');
        if ($cancel_user_status != 0) {
            echo json_encode(['code' => 500, 'msg' => '用户已注销或申请注销中']);
            die;
        }
    }

    //弹窗通知
    public function alert()
    {
        $alert = '$(function(){$.tConfirm.open({body:"您没有权限",type:"warning"});})';
        echo '<link href="http://apps.bdimg.com/libs/bootstrap/3.3.4/css/bootstrap.css" rel="stylesheet">';
        echo '<link href="http://apps.bdimg.com/libs/fancybox/2.1.5/jquery.fancybox.min.css" rel="stylesheet">';
        echo '<script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js" type="text/javascript"></script>';
        echo '<script src="http://apps.bdimg.com/libs/fancybox/2.1.5/jquery.fancybox.js" type="text/javascript"></script>';
        echo '<script src="/admin/js/tConfirm.js" type="text/javascript"></script>';
        echo '<link href="/admin/css/tConfirm.css" rel="stylesheet">';
        echo "<script>$alert</script>";
        exit;
    }

    // 渠道导出csv
    // data是数据  headerarray是表头
    public function exportcsv($data, $headerArray)
    {
        $string = implode(",", array_values($headerArray)) . "\n";
        $table_key = array_keys($headerArray);
        foreach ($data as $key => $value) {
            $outArray = [];
            foreach ($table_key as $key) {
                if (array_key_exists($key, $value)) {
                    $outArray[$key] = $value[$key];
                }
            }

            $string .= implode(",", $outArray) . "\n";
        }

        $filename = date('Ymd') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    /**
     * 批量导出功能
     * @param $db 数据模型
     * @param $columns 数据列项
     * @param string $fileName 导出的文件名称
     */
    public function dataExportCsv($db, $columns, $fileName = '')
    {
        set_time_limit(0);
        if (empty($fileName)) {
            $fileName = date('YmdHis') . mt_rand(1000, 9999) . '.csv';
        }
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'a'); //打开output流
        mb_convert_variables('GBK', 'UTF-8', $columns);
        fputcsv($fp, $columns); //将数据格式化为CSV格式并写入到output流中
        $pageLimit = 2000;
        $page = 1;
        $res = $db->page($page, $pageLimit)->select()->toarray();
        while ($res) {
            foreach ($res as $items) {
                $rowData = $items;
                mb_convert_variables('GBK', 'UTF-8', $rowData);
                fputcsv($fp, $rowData);
            }
            $page++;
            $res = $db->page($page, $pageLimit)->select()->toarray();
            //刷新输出缓冲到浏览器
            ob_flush();
            flush(); //必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        }

        fclose($fp);
        exit();
    }

}
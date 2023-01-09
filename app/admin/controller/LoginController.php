<?php

namespace app\admin\controller;

ini_set('memory_limit', '1024M');
use app\admin\common\AdminBaseController;
use app\admin\model\AdminUserModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\MemberModel;
use app\admin\model\UserIdentityModel;
use app\admin\model\UserPropsModel;
use app\exceptions\ApiExceptionHandle;
use Firebase\JWT\JWT;
use think\facade\Log;
use think\facade\View;

class LoginController extends AdminBaseController
{

    public function iossUser()
    {
        $headerArray = ['用户ID', '昵称', '手机号', '豆剩余', '累计充值', '用户等级', '用户装扮', '是否靓号', '身份证号', '设备', 'QQ', 'VX'];
        $string = implode(",", $headerArray) . "\n";

        $member_list = MemberModel::getInstance()->getWhereAllData([['source', '=', 'mua'], ['lv_dengji', '>', 1]], "id");
        $date = array_column($member_list, 'id');
        foreach ($date as $k => $v) {
            $a = MemberModel::getInstance()->getModel($v)->where('id', $v)->find();
            $outArray['id'] = $v;
            $outArray['nickname'] = str_replace(',', '', str_replace('"', '', $a['nickname']));
            $outArray['username'] = $a['username'];
            $outArray['dou'] = $a['totalcoin'] - $a['freecoin'];
            $outArray['chongzhi'] = ChargedetailModel::getInstance()->getModel()->where([['uid', '=', $v], ['status', 'in', [1, 2]]])->sum('rmb');
            $outArray['lv'] = $a['lv_dengji'];
            $outArray['attr'] = UserPropsModel::getInstance()->getModel()->where([['uid', '=', $v], ['expires_time', '>', 'update_time']])->count();
            $outArray['pretty'] = $a['id'] != $a['pretty_id'] ? $a['pretty_id'] : '/';
            $outArray['a'] = UserIdentityModel::getInstance()->getModel()->where([['uid', '=', $a['id']], ['status', '=', 1]])->value('certno');
            $outArray['deviceid'] = $a['deviceid'];
            $outArray['qopenid'] = $a['qopenid'];
            $outArray['wxopenid'] = $a['wxopenid'];
            $string .= implode(",", $outArray) . "\n";
        }

        $filename = '用户维度.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    public function index()
    {
        View::assign('master_url', $this->url_map[\constant\RouterUrlConstant::URL_登录操作]);
//        View::assign('go_url', \constant\RouterUrlConstant::URL_登录操作);
        return View::fetch('user/login');
    }

    /*
     *后台用户登录
     * @username 账号名
     * @password 密码
     * @master_url 路由
     * */
    public function login()
    {
        $admin_username = $this->request->param('username');
        $admin_password = $this->request->param('password');
        session('username', md5($admin_username));
        Log::record('登录开始账号:' . $admin_username . ':密码:' . $admin_password . '-' . md5($admin_password));
        //参数验证
        if (strlen($admin_username) == 0) {
            return $this->return_json(\constant\CodeConstant::CODE_账户错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_账户错误]);
        }
        if (strlen($admin_password) == 0) {
            return $this->return_json(\constant\CodeConstant::CODE_密码错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_密码错误]);
        }
        //登陆
        try {
            $loginResult = $this->_login($admin_username, md5($admin_password));
            $loginResult = json_decode(json_encode($loginResult), true);
            $loginAdmin = $loginResult['admin'];
            $token = $loginResult['token'];
            Log::record('登录开始账号:' . $admin_username . ':生成的token:' . $token);
            //登录成功后将当前用户信息存入redis有效期为1天
            $key_lose = strtotime(date('Y-m-d ', strtotime('+1 day'))) - time();
            $redis = $this->getRedis();
            $redis->SETEX(\constant\CommonConstant::ADMIN_USER_UID . $loginAdmin['id'], $key_lose, json_encode($loginAdmin));
            $redis->SETEX('admin_token_' . $loginAdmin['id'], 10800, md5($token));
            //将登录用户的ip及时间绑定在redis中 用于统计用户的登录次数以及上一次登录的时间和ip
            $login_time = $loginAdmin['last_login_time'] . ',' . sprintf("%u", ip2long($this->getUserIpAddr()));
            $redis->lPush(\constant\CommonConstant::ADMIN_USER_LOGIN_HISTORY . $loginAdmin['id'], $login_time);
            Log::record('管理员登录:操作人:' . $loginAdmin['username'], 'login');
            session("username", $loginAdmin['username']);
            echo $this->return_json($this->code_ok, array('token' => $token), $this->code_ok_map[$this->code_ok]);
        } catch (ApiExceptionHandle $e) {
            Log::error("login:error:" . $e->getMessage());
            return $this->return_json(500, null, '登录失败');
        }

    }

    private function _login(string $admin_username, string $admin_password)
    {
        //更新mysql
        $where = array(
            'username' => $admin_username,
            'password' => $admin_password,
            'is_del' => [1, 3],
            'status' => 1,
        );

        $loginModel = AdminUserModel::getInstance()->getModel();
        $loginAdmin = $loginModel->where($where)->findOrEmpty()->toArray();

        if (empty($loginAdmin)) {
            throw new ApiExceptionHandle('用户为空', 500);
        }
        $currentTimestamp = time();
        $saveData['last_login_time'] = $currentTimestamp;
        $loginModel->where($where)->save($saveData);
        //创建token
        $tokenInfo = [
            'username' => $admin_username,
            'id' => $loginAdmin['id'],
            'last_login_time' => $currentTimestamp,
            'atime' => time(),

        ];
        $token = JWT::encode($tokenInfo, $this->jwtKet);
        if (empty($token)) {
            throw new ApiExceptionHandle('token为空', 500);
        }
        return array('token' => $token, 'admin' => $loginAdmin);
    }

    public function loginOut()
    {
        $redis = $this->getRedis();
        //退出登陆
        $redis->del(\constant\CommonConstant::ADMIN_USER_UID . $this->token['id']);
        header('Location: /admin/loginIndex?master_url=/admin/loginIndex');
        die;
    }
}
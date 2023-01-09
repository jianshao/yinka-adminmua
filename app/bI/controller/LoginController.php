<?php

namespace app\bI\controller;

use app\bI\common\BIBaseController;
use app\bI\model\AdminUserModel;
use Firebase\JWT\JWT;
use think\facade\Log;
use think\facade\View;

class LoginController extends BIBaseController
{
    public function index()
    {
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
        //参数验证
        if (strlen($admin_username) == 0) {
            echo $this->return_json(\constant\CodeConstant::CODE_账户错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_账户错误]);
            die;
        }
        if (strlen($admin_password) == 0) {
            echo $this->return_json(\constant\CodeConstant::CODE_密码错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_密码错误]);
            die;
        }

        //登陆
        $loginResult = $this->_login($admin_username, md5($admin_password));
        if (empty($loginResult)) {
            echo $this->return_json($loginResult['code'], null, $loginResult['msg']);
            die;
        }
        $loginResult = json_decode(json_encode($loginResult), true);
        $loginAdmin = $loginResult['admin'];
        $token = $loginResult['token'];

        Log::record('登录开始账号:' . $admin_username . ':生成的token:' . $token);
        //登录成功后将当前用户信息存入redis有效期为1天
        $key_lose = strtotime(date('Y-m-d ', strtotime('+1 day'))) - time();
        $redis = $this->getRedis();
        $redis->SETEX(\constant\CommonConstant::BI_ADMIN_USER_UID . $loginAdmin['id'], $key_lose, json_encode($loginAdmin));
//        //将登录用户的ip及时间绑定在redis中 用于统计用户的登录次数以及上一次登录的时间和ip
//        $login_time = $loginAdmin['last_login_time'] . ',' . sprintf("%u", ip2long($this->getUserIpAddr()));
//        $redis->lPush(\constant\CommonConstant::ADMIN_USER_LOGIN_HISTORY . $loginAdmin['id'], $login_time);
        Log::record('BI管理员登录:操作人:' . $loginAdmin['username'], 'login');
        echo $this->return_json($this->code_ok, array('token' => $token), $this->code_ok_map[$this->code_ok]);
        die;
    }

    private function _login(string $admin_username, string $admin_password)
    {
        //更新mysql
        $where = array(
            'username' => $admin_username,
            'password' => $admin_password,
            'is_del' => 1,
        );
        $loginAdmin = AdminUserModel::getInstance()->getModel()->where($where)->find();
        if (empty($loginAdmin)) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户不存在, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_用户不存在]);
            die;
        }

        $loginAdmin->last_login_time = time();
        $loginAdmin->save();
        //创建token
        $tokenInfo = [
            'username' => $admin_username,
            'id' => $loginAdmin->id,
            'last_login_time' => $loginAdmin->last_login_time,
            'ip' => $this->getUserIpAddr(),
        ];
        $token = JWT::encode($tokenInfo, $this->jwtKet);
        if (empty($token)) {
            return false;
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

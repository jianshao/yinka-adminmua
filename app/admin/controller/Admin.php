<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AdminUserModel;
use Firebase\JWT\JWT;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class Admin extends AdminBaseController
{

    public function SignUp(string $admin_username, string $admin_password)
    {
        //参数验证
        if (strlen($admin_username) == 0) {
            return $this->return_json(1, null, '参数错误');
        }
        if (strlen($admin_password) == 0) {
            return $this->return_json(1, null, '参数错误');
        }

        //用户已存在
        try {
            $searchAdmin = AdminUserModel::getInstance()->getModel()->where('admin_username', $admin_username)->find();
        } catch (DataNotFoundException $e) {
            return $this->return_json($e->getCode(), null, '内部错误');
        } catch (ModelNotFoundException $e) {
            return $this->return_json($e->getCode(), null, '内部错误');
        } catch (DbException $e) {
            return $this->return_json($e->getCode(), null, '内部错误');
        }
        if (!empty($searchAdmin)) {
            return $this->return_json(2, null, '用户已存在');
        }

        //创建新用户
        $insertAdmin = AdminUserModel::getInstance()->getModel();
        $insertAdmin->admin_username = $admin_username;
        $insertAdmin->admin_password = $admin_password;
        if ($insertAdmin->save() == false) {
            return $this->return_json(-3, null, '内部错误');
        }

        //登陆
        $loginResult = $this->_login($admin_username);
        if (empty($loginResult)) {
            return $this->return_json(-3, null, '内部错误');
        }
        $loginAdmin = $loginResult['admin'];
        $token = $loginResult['token'];

        return $this->return_json(0, ['admin' => $loginAdmin->selfVisiableArray(), 'token' => $token], '注册成功');
    }

    public function SignIn(string $admin_username, string $admin_password)
    {
        //参数验证
        if (strlen($admin_username) == 0) {
            return $this->return_json(1, null, '参数错误');
        }
        if (strlen($admin_password) == 0) {
            return $this->return_json(1, null, '参数错误');
        }

        //登陆
        $loginResult = $this->_login($admin_username);
        if (empty($loginResult)) {
            return $this->return_json(-3, null, '内部错误');
        }
        $loginAdmin = $loginResult['admin'];
        $token = $loginResult['token'];

        return $this->return_json(0, ['admin' => $loginAdmin->selfVisiableArray(), 'token' => $token], '登陆成功');
    }

    public function SignOut(string $token)
    {
        //参数验证
        if (empty($token)) {
            return $this->return_json(1, null, '参数错误');
        }

        //解析token
        try {
            $decoded = JWT::decode($token, $this->jwtKet, array('HS256'));
            $tokenInfo = (array) $decoded;
        } catch (\Exception $exception) {
            return $this->return_json(4, null, 'token错误');
        }

        if (empty($tokenInfo['admin_id'])) {
            return $this->return_json(5, null, 'token错误');
        }

        $admin_id = $tokenInfo['admin_id'];

//        $redis = $this->getRedis();
        //        if (!$redis->hExists('SIGN_IN_USER', $admin_id)) {
        //            return $this->return_json(6, null, '未登陆');
        //        }

        //退出登陆
        //        $redis->hDel('SIGN_IN_USER', $admin_id);

        if (empty($tokenInfo)) {
            return $this->return_json(1, null, '参数错误');
        }

        return $this->return_json(0, null, '成功');
    }

    private function _login(string $admin_username)
    {
        //更新mysql
        try {
            $loginAdmin = AdminUserModel::getInstance()->getModel()->where('admin_username', $admin_username)->find();
        } catch (DataNotFoundException $e) {
            return $this->return_json($e->getCode(), null, '内部错误');
        } catch (ModelNotFoundException $e) {
            return $this->return_json($e->getCode(), null, '内部错误');
        } catch (DbException $e) {
            return $this->return_json($e->getCode(), null, '内部错误');
        }
        if (empty($loginAdmin)) {
            return $this->return_json(8, null, '用户不存在');
        }
        $loginAdmin->admin_last_login = new \DateTime();
        $loginAdmin->save();

        //创建token
        $tokenInfo = [
            'admin_username' => $admin_username,
            'admin_id' => $loginAdmin->admin_id,
            'admin_last_login' => $loginAdmin->admin_last_login,
        ];
        $token = JWT::encode($tokenInfo, $this->jwtKet);

        //更新redis
        //        $this->getRedis()->hSet('SIGN_IN_USER', $loginAdmin->admin_id, $token);

        //返回结果
        return ['token' => $token, 'admin' => $loginAdmin];
    }

}
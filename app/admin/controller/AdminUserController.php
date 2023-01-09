<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AdminUserModel;
use app\admin\service\AdminUserService;
use app\admin\service\RoleService;
use app\admin\service\UserRoleService;
use Mua\constant\CodeConstant;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class AdminUserController extends AdminBaseController
{
    /*
     *添加管理员
     *@username 账号名
     *@password 密码
     */
    public function addAdminUser()
    {
        $username = $this->request->param('username');
        $password = $this->request->param('password');
        $role_id = $this->request->param('role_id');
        $status = $this->request->param('status');
        if (!$username || !$password || !$role_id || !$status) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        //查询此管理员名称是否存在
        $db_username = AdminUserService::getInstance()->getAdminUserInfo(array('username' => $username, 'is_del' => 1), 'id');
        if (!empty($db_username)) {
            echo $this->return_json(\constant\CodeConstant::CODE_管理员名称已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_管理员名称已存在]);
            die;
        }

        //入库
        $data = [];
        $data['username'] = $username;
        $data['password'] = md5($password);
        $data['plaintexts'] = base64_encode($password);
        $data['status'] = $status;
        $data['created'] = time();
        $data['create_user'] = $this->token['username'];
        $data['updated'] = $data['created'];
        $data['update_user'] = $data['create_user'];

        $data_1 = [];
        $data_1['created'] = $data['created'];
        $data_1['create_user'] = $data['create_user'];
        $data_1['updated'] = $data['updated'];
        $data_1['update_user'] = $data['update_user'];
        $data_1['role_id'] = $role_id;
        //开启事物
        try {
            AdminUserService::getInstance()->getModel()->startTrans();
            $user_id = AdminUserService::getInstance()->addAdminuser($data);
            $data_1['user_id'] = $user_id;
            UserRoleService::getInstance()->addUserRole($data_1);

            //role_id = 46 添加并更新渠道channel
            if (in_array($role_id, [46, 48])) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, '禁止新增渠道账号');
                die;
            }
            AdminUserService::getInstance()->getModel()->commit();
            $ok = 1;
        } catch (\Exception $e) {
            $ok = 2;
            AdminUserService::getInstance()->getModel()->rollback();
        }
        if ($ok == 1) {
            Log::record('添加后台管理员成功:' . json_encode($data), 'addAdminUser');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        }
        Log::record('添加后台管理员失败:' . json_encode($data), 'addAdminUser');
        echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
        die;
    }

    /*
     *编辑管理员
     *@username 账号名
     *@password 密码
     */
    public function editAdminUserInfo()
    {
        $type = $this->request->param('type');
        $id = $this->request->param('id');
        if (!$id || !$type) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        if ($type == 1) {
            //查询用户信息
            $user = AdminUserService::getInstance()->getAdminUserInfo(array('id' => $id), 'username,real_name,status,plaintexts');
            if (empty($user)) {
                echo $this->return_json(\constant\CodeConstant::CODE_用户ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户ID错误]);
                die;
            }
            //查询用户角色信息
            $user_role = UserRoleService::getInstance()->getUserRole(array('user_id' => $id), 'role_id');
            if (empty($user_role)) {
                echo $this->return_json(\constant\CodeConstant::CODE_添加的用户没有指定角色, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_添加的用户没有指定角色]);
                die;
            }
            //查询角色信息
            $role = RoleService::getInstance()->getRoleItem(array('id' => $user_role->role_id), 'id,name');
            if (empty($role)) {
                echo $this->return_json(\constant\CodeConstant::CODE_没有该角色, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_没有该角色]);
                die;
            }
            //查询所有角色
            $data = [];
            $data['username'] = $user->username;
            $data['password'] = base64_decode($user->plaintexts);
            $data['id'] = $id;
            $data['role_id'] = $user_role->role_id;
//            $data['role'] = $role_list;
            $data['real_name'] = $user->real_name;
            $data['status']['status'] = $user->is_del;
            $data['status']['status_name'] = $user->status == 1 ? '正常' : '删除';
            Log::record('修改后台管理员详情:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addAdminUser');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        } elseif ($type == 2) {
            //修改
            $username = $this->request->param('username');
            $username_master = $this->request->param('username_master');
            $role_id = $this->request->param('role_id');
            $password = $this->request->param('password');
//            $real_name = $this->request->param('real_name');
            //            $real_name_master = $this->request->param('real_name_master');
            $status = $this->request->param('status');
            if (!$username || !$password || !$role_id || !$status) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            if ($username != $username_master) {
                //查询此管理员名称是否存在
                $db_username = AdminUserService::getInstance()->getAdminUserInfo(array('username' => $username, 'is_del' => 1), 'id');
                if (!empty($db_username)) {
                    echo $this->return_json(\constant\CodeConstant::CODE_管理员名称已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_管理员名称已存在]);
                    die;
                }
            }
            //入库
            $data = [];
            $data['username'] = $username;
            $data['password'] = md5($password);
            $data['status'] = $status;
            $data['updated'] = time();
            $data['update_user'] = $this->token['username'];
            $data['plaintexts'] = base64_encode($password);
            $where = [];
            $where['id'] = $id;
            $data_1 = [];
            $data_1['updated'] = $data['updated'];
            $data_1['update_user'] = $data['update_user'];
            $data_1['role_id'] = $role_id;
            $where_1['user_id'] = $id;
            //开启事物
            try {
                AdminUserService::getInstance()->getModel()->startTrans();
                AdminUserService::getInstance()->editUserItems($data, $where);
                UserRoleService::getInstance()->editUserToRole($data_1, $where_1);
                $ok = 1;
                AdminUserService::getInstance()->getModel()->commit();
            } catch (\Exception $e) {
                $ok = 2;
                AdminUserService::getInstance()->getModel()->rollback();
            }
            if ($ok == 1) {
                Log::record('修改后台管理员成功:操作人:' . $this->token['username'] . '@' . json_encode($this->request->param()), 'addAdminUser');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            }
            Log::record('修改后台管理员失败:操作人:' . $this->token['username'] . '@' . json_encode($this->request->param()), 'addAdminUser');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }

    }

    /*
     *后台用户列表
     * @admin_username 账号名
     * @admin_password 密码
     * */
    public function adminUserLists()
    {
        $user_name = $this->request->param('user_name', '');
        $role_id = $this->request->param('role_id');
        $size = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $size;

        $where = [];
        if ($user_name) {
            $where[] = ['A.username', '=', $user_name];
        }
        if ($role_id) {
            $where[] = ['B.role_id', '=', $role_id];
        }
        $where[] = ['A.is_del', '=', 1];
        $where[] = ['A.channel', '=', 0];

        //查询管理员列表
        $query = AdminUserModel::getInstance()->getModel()
            ->alias('A')
            ->leftJoin('yyht_re_user_role B', 'A.id = B.user_id')
            ->leftJoin('yyht_role C', 'B.role_id = C.id')
            ->field('A.id,C.name role_name,A.username,A.status')
            ->where($where);
        $clone = clone $query;
        $count = $clone->count();

        $data['user_list'] = $query->limit($offset, $size)->order('A.id desc')->select()->toArray();
        if (!empty($data['user_list'])) {
            foreach ($data['user_list'] as $key => &$val) {
                $redis_key = \constant\CommonConstant::ADMIN_USER_LOGIN_HISTORY . $val['id'];
                //根据当前用户id查询此用户历史登录记录
                $redis = $this->getRedis();
                $login_num = $redis->Llen($redis_key);
                $val['login_num'] = $login_num;
                $last_login = $redis->Lindex($redis_key, 2);
                if ($last_login) {
                    $last_login_array = explode(',', $last_login);
                    $login_last_time = date('Y-m-d H:i:s', $last_login_array[0]);
                    $login_last_ip = long2ip($last_login_array[1]);
                }
                $val['login_last_time'] = !empty($login_last_time) ? $login_last_time : '';
                $val['login_last_ip'] = !empty($login_last_ip) ? $login_last_ip : '';
                $val['status'] = $val['status'] == 1 ? '正常' : '禁用';
            }
        }

        Log::record('后台管理员列表:操作人:' . $this->token['username'] . '@' . json_encode($where), 'adminUserLists');
        //获取角色列表
        $where = array('status' => 1);
        $role_list = RoleService::getInstance()->getRoleList($where, 'id,name', 0, 9999);
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $size);
        View::assign('login_info', $this->token);
        View::assign('id', 66);
        View::assign('username', $user_name);
        View::assign('role_id', $role_id);
        View::assign('user_list', $data['user_list']);
        View::assign('role_list', $role_list);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('user/index');
    }

    /*
     *后台用户删除
     * @int id 用户id
     * */
    public function delAdminUser()
    {
        $id = $this->request->param('id');
        $data['updated'] = time();
        $data['update_user'] = $this->token['username'];
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        try {
            AdminUserService::getInstance()->getModel()->startTrans();
            $data['is_del'] = 2;
            AdminUserService::getInstance()->editUserItems($data, array('id' => $id));
            unset($data['is_del']);
            $data['status'] = 2;
            UserRoleService::getInstance()->editUserToRole($data, array('user_id' => $id));
            RoleService::getInstance()->getModel()->commit();
            $ok = 1;
        } catch (\Exception $e) {
            $ok = 2;
            RoleService::getInstance()->getModel()->rollback();
        }
        if ($ok == 1) {
            Log::record('删除管理员成功:id:' . $id . '@' . json_encode($data), 'deladminUser');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }
        Log::record('删除管理员失败:id:' . $id . '@' . json_encode($data), 'deladminUser');
        echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_删除失败]);
        die;
    }

}
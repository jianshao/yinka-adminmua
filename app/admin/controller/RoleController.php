<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\MenuService;
use app\admin\service\RoleMenuService;
use app\admin\service\RoleService;
use app\admin\service\UserRoleService;
use think\facade\Log;
use think\facade\View;

class RoleController extends AdminBaseController
{

    /*
     *@角色管理列表0
     *@param string $token token值
     */
    public function getRoleLists()
    {
        $size = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $size;

        $role_name = $this->request->param('role_name');
        $where = array('status' => 1);
        if ($role_name) {
            $where['name'] = $role_name;
        }
        $field = 'id,name';
        //列表
        $data['list'] = RoleService::getInstance()->getRoleList($where, $field, $page, $size);
        //总数
        $list_num = RoleService::getInstance()->getRoleListCount($where);
//        $data['page'] = $this->request->param('page');
        //        $msg = !empty($data['list']) ? $this->code_ok_map[\constant\CodeConstant::CODE_成功] : $this->code_ok_map[\constant\CodeConstant::CODE_暂无数据];
        //查询当前登录用户该有的权限
        $list = MenuService::getInstance()->getMenuLists(array('status' => 1), 'id,name as text,parent')->toArray();
        $result = $this->_getMenuLists($list, '');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($list_num / $size);
        Log::record('后台权限列表:操作人:' . $this->token['username'], 'getRoleLists');
        //分页显示输出
        View::assign('login_info', $this->token);
        View::assign('id', 27);
        View::assign('role_list', $data['list']);
        View::assign('token', $this->request->param('token'));
        View::assign('role_menu_list', json_encode($result));
        View::assign('page', $page_array);
        View::assign('role_name', $role_name);
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('role/index');
    }

    /*
     *@角色添加接口
     *@param string $token token值
     */
    public function addRole()
    {
        $role_name = $this->request->param('name');
        if (!$role_name) {
            echo $this->return_json(\constant\CodeConstant::CODE_请输入正确的角色名称, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_请输入正确的角色名称]);
            die;
        }

        //查询此名称是否已存在
        $get_role_item = RoleService::getInstance()->getRoleItem(array('name' => $role_name), 'id');
        if (!empty($get_role_item)) {
            echo $this->return_json(\constant\CodeConstant::CODE_角色名称已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_角色名称已存在]);
            die;
        }
        //创建角色
        $data = [];
        $data['name'] = $role_name;
        $data['created'] = time();
        $data['create_user'] = $this->token['username'];
        $data['updated'] = $data['created'];
        $data['update_user'] = $this->token['username'];
        $data['status'] = 1;
        $add_role = RoleService::getInstance()->addRole($data);
        if ($add_role) {
            Log::record('添加角色成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addRole');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        }
        Log::record('添加角色失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addRole');
        echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入失败]);
        die;
    }

    /*
     *@角色分配菜单
     *@param string $token token值
     */
    public function editRoleToMenu()
    {
        $type = $this->request->param('type');
        $id = $this->request->param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        if ($type == 1) {
            //查询当前角色该有的菜单
            $user_menu = RoleMenuService::getInstance()->getRoleToMenuLists(array('role_id' => $id, 'status' => 1));
//            $role['id'] = (int)$id;
            if (!empty($user_menu)) {
                $user_menu_id = array_column($user_menu, 'menu_id');
            } else {
                $user_menu_id = array();
            }
            Log::record('角色分配菜单详情:操作人:' . $this->token['username'] . '@' . json_encode($user_menu_id), 'editRoleToMenu');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $user_menu_id, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
            //查询当前所有菜单
        } elseif ($type == 2) {
            //原来的
            $menu_form = $this->request->param('menu_form');
            //现在的
            $menu_now = $this->request->param('menu_now');

//            if (!$menu_now || !$menu_form) {
            //
            //                return $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            //            }

            $menu_form_array = explode(',', $menu_form);
            $menu_now_array = explode(',', $menu_now);
            //得到取消的顶级菜单
            $update_role = array_diff($menu_form_array, $menu_now_array);
            //得到新增的顶级菜单
            $insert_role = array_diff($menu_now_array, $menu_form_array);
            $data = [];
            $where = [];
            $data['updated'] = time();
            $data['update_user'] = $this->token['username'];
            $where['role_id'] = $id;

            try {
                RoleMenuService::getInstance()->getModel()->startTrans();
                if (!empty($update_role)) {
                    $data['status'] = 2;
                    foreach ($update_role as $k => $v) {
                        $where['menu_id'] = $v;
                        RoleMenuService::getInstance()->editRoleToMenu($data, $where);
                    }
                }
                unset($where['menu_id']);
                $data['role_id'] = $id;
                if (!empty($insert_role)) {

                    $data['status'] = 1;
                    foreach ($insert_role as $ke => $ve) {
                        $data['menu_id'] = $ve;
                        RoleMenuService::getInstance()->addRoleToMenu($data);
                    }
                }
                RoleMenuService::getInstance()->getModel()->commit();
                $ok = 1;
            } catch (\Exception $e) {
                $ok = 2;
                RoleMenuService::getInstance()->getModel()->rollback();
            }
            if ($ok == 1) {
                Log::record('角色权限分配:操作人:' . $this->token['username'] . '@原权限:' . json_encode($menu_form_array) . ':现权限:' . json_encode($menu_now_array) . '数据:' . json_encode($this->request->param()), 'editRoleToMenu');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            }
            Log::record('角色权限分配:操作人:' . $this->token['username'] . '@原权限:' . json_encode($menu_form_array) . ':现权限:' . json_encode($menu_now_array) . '数据:' . json_encode($this->request->param()), 'editRoleToMenu');

            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     *@角色删除
     *@param string $token token值
     */
    public function delRole()
    {
        $id = $this->request->param('id');
        $data['updated'] = time();
        $data['update_user'] = $this->token['username'];
        $data['status'] = 2;

        try {
            RoleService::getInstance()->getModel()->startTrans();
            RoleService::getInstance()->editRole($data, array('id' => $id));
            UserRoleService::getInstance()->editUserToRole($data, array('role_id' => $id));
            RoleMenuService::getInstance()->editRoleToMenu($data, array('role_id' => $id));
            RoleService::getInstance()->getModel()->commit();
            $ok = 1;
        } catch (\Exception $e) {
            $ok = 2;
            RoleService::getInstance()->getModel()->rollback();
        }
        if ($ok == 1) {
            Log::record('角色删除成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'delRole');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }
        Log::record('角色删除失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'delRole');
        echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_删除失败]);
        die;
    }

    /*
     *@角色编辑
     *@param string $token token值
     */
    public function editRole()
    {
        $id = $this->request->param('id');
        $type = $this->request->param('type');
        if (!$id || !$type) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        if ($type == 1) {
            //查询当前角色名称
            $role_name = RoleService::getInstance()->getRoleItem(array('id' => $id, 'status' => 1), 'name');
            if (!empty($role_name)) {
                $data['id'] = (int) $id;
                $data['name'] = $role_name->name;
                return $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            }
            return $this->return_json(\constant\CodeConstant::CODE_没有该角色, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_没有该角色]);
        } elseif ($type == 2) {
            $name = $this->request->param('name');
            $master_name = $this->request->param('master_name');
            if (!$name || !$master_name) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }
            if ($name == $master_name) {
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
                die;
            }
            //查询角色名是否存在
            $get_role_item = RoleService::getInstance()->getRoleItem(array('name' => $name), 'id');
            if (!empty($get_role_item)) {
                echo $this->return_json(\constant\CodeConstant::CODE_角色名称已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_角色名称已存在]);
                die;
            }
            $data = [];
            $data['updated'] = time();
            $data['update_user'] = $this->token['username'];
            $data['name'] = $name;
            $edit = RoleService::getInstance()->editRole($data, array('id' => $id));
            if ($edit) {
                Log::record('更新角色成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'editRole');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            }
            Log::record('更新角色失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'editRole');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }
}
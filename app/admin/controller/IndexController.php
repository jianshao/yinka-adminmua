<?php


namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\AdminUserService;
use app\admin\service\RoleService;
use app\admin\service\UserRoleService;
use think\facade\View;

class IndexController extends AdminBaseController
{
    public function index()
    {
        //用户信息
        $userinfo['user_name'] = $this->token['username'];
        $userinfo['token'] = $this->request->param('token');
        $user_id = $this->token['id'];
        //角色名称
        $role_id = UserRoleService::getInstance()->getUserRole(array('user_id' => $user_id), 'role_id')->toArray();
        $userinfo['role_name'] = RoleService::getInstance()->getRoleItem(array('id' => $role_id['role_id']), 'name')->toArray()['name'];
        //查询当前用户所有权限
        $user_role_menu = $this->_getUserRoleMenu($user_id, 'id,name as text,router,parent,operations');
        $menu_list = array('id' => 99999, 'text' => '控制台', 'router' => $this->url_map[\constant\RouterUrlConstant::URL_控制台], 'parent' => 0, 'operations' => 1, 'children' => array());
        $menu_list = [];
        if (!empty($user_role_menu)) {
            if ($menu_list) {
                array_push($user_role_menu, $menu_list);
            }
            foreach ($user_role_menu as $k => &$v) {
                if ($v['operations'] == 2) {
                    unset($user_role_menu[$k]);
                }
                if ($v['parent'] !== 0) {
                    $v['router'] .= '?master_url=' . $v['router'] . '&token=' . $userinfo['token'];
                }
            }
            //查询当前用户所有权限
            $menu_list = $this->_getMenuLists($user_role_menu);
        }

        if (in_array($role_id['role_id'], [33, 40, 43])) {

            $master_url = \constant\RouterUrlConstant::URL_图表;
            $go_url = $this->url_map[\constant\RouterUrlConstant::URL_图表];
        } else {
            $master_url = \constant\RouterUrlConstant::URL_控制台;
            $go_url = $this->url_map[\constant\RouterUrlConstant::URL_控制台];
        }

        View::assign('master_url', $master_url);
        View::assign('go_url', $go_url);
        View::assign('userinfo', $userinfo);
        View::assign('menu_list', $menu_list);
        return View::fetch('index/index');
    }

    public function indexConsole()
    {
        return View::fetch('index/indexConsole');
    }
}

<?php


namespace app\bI\controller;

use app\bI\common\BIBaseController;
use think\facade\View;

class IndexController extends BIBaseController
{
    public function index()
    {
        //角色名称
        //查询当前用户所有权限
        $menu_list[] = array('id' => 1, 'text' => '数据管理', 'router' => '#', 'parent' => 0, 'operations' => 1,'iid'=>1, 'children' => array(
            array('id' => 2, 'text' => '数据列表', 'router' => '/bI/dataManagementIndex', 'parent' => 1, 'operations' => 1,'iid'=>2, 'children' => array()))
            );
        $go_url = '/bI/indexConsole';
        View::assign('go_url', $go_url);
        View::assign('userinfo', $this->userinfo);
        View::assign('menu_list', $menu_list);
        return View::fetch('index/index');
    }

    public function indexConsole()
    {

        return View::fetch('index/indexConsole');
    }
}

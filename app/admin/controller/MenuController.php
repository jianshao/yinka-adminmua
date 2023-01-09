<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\MenuModel;
use app\admin\service\MenuService;
use app\admin\service\RoleMenuService;
use think\facade\Db;
use think\facade\Log;
use think\facade\View;

class MenuController extends AdminBaseController
{

    /*
     *@菜单管理列表
     *@param string $token token值
     *@return json
     */
    public function getMenuLists($menu_id = '')
    {
        Log::record('查看节点列表:' . json_encode(array('time' => date('Y-m-d H:i:s'), 'user' => $this->token['username'])), 'getMenuLists');
        //查询当前主菜单
        $list = MenuService::getInstance()->getMenuLists(array('status' => 1), 'id,name as text,parent')->toArray();
        if (!empty($list)) {
            $result = $this->_getMenuLists($list, $menu_id);
            View::assign('master_url', $this->url_map[\constant\RouterUrlConstant::URL_菜单添加]);
            View::assign('token', $this->request->param('token'));
            View::assign('menu_list', json_encode($result));
            return View::fetch('menu/index');
        }
        return $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_暂无数据]);
    }

    /*
     *@添加节点
     *@param string $token token值
     *@return json
     */
    public function addMenuItems()
    {
        $name = $this->request->param('name');
        $router = $this->request->param('router');
        $seq = $this->request->param('seq');
        $status = $this->request->param('status');
        $icon = empty($this->request->param('icon')) ? '' : $this->request->param('icon');
        $parent = !empty($this->request->param('parent')) ? $this->request->param('parent') : 0;
        $operations = (int) $this->request->param('operations');
        if (!$name || !is_numeric($parent) || !is_numeric($operations)) {
            return $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
        }
        //查询此节点名称是否存在
        $items = MenuService::getInstance()->getMenuItems(array('name' => $name, 'status' => 1), 'id');
        if (empty($items)) {
            //插入数据库
            $data = [];
            $data['name'] = $name;
            $data['router'] = $router;
            $data['seq'] = $seq;
            $data['parent'] = $parent;
            $data['status'] = $status;
            $data['icon'] = $icon;
            $data['created'] = time();
            $data['create_user'] = $this->token['username'];
            $data['updated'] = $data['created'];
            $data['update_user'] = $data['create_user'];
            $data['operations'] = $operations;

            $add = Db::transaction(function () use ($data) {
                $menu_id = MenuModel::getInstance()->getModel()->insertGetId($data);
                $roles = [33, 40];

                $role_menus = [];
                foreach ($roles as $_ => $rol_id) {
                    $role_menus[] = [
                        'role_id' => $rol_id,
                        'status' => 1,
                        'menu_id' => $menu_id,
                        'updated' => time(),
                        'update_user' => 'admin',
                    ];
                }
                return $add = RoleMenuService::getInstance()->addRoleToManyMenu($role_menus);
            });

            //超管角色自动加入角色权限
            if ($add) {
                Log::record('添加节点成功:' . json_encode($data), 'addMenuItems');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                die;
            }
            Log::record('添加节点失败:' . json_encode($data), 'addMenuItems');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
        echo $this->return_json(\constant\CodeConstant::CODE_节点名称已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_节点名称已存在]);
        die;
    }

    /*
     *@编辑节点
     *@param string $token token值
     *@param int    $id    节点id
     *@param int    $type  1=>编辑；2=>提交
     *@return json
     */
    public function editMenuItems()
    {
        $id = $this->request->param('id');
        $type = $this->request->param('type');
        if (!$id || !$type) {
            return $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
        }
        if ($type == 1) {
            //查询当前id详情
            $item = MenuService::getInstance()->getMenuItems(array('id' => $id), 'id,name,router,icon,id,operations,status,parent,seq');
            if (empty($item)) {
                return $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_暂无数据]);
            }
            $item = $item->toArray();

            if ($item['parent'] !== 0) {
                //查询当前id的父节点
                $p_item = MenuService::getInstance()->getMenuItems(array('id' => $item['parent']), 'name,id')->toArray();
                $item['p_id'] = $p_item['id'];
                $item['p_name'] = $p_item['name'];
            } else {
                $item['p_id'] = 0;
                $item['p_name'] = '顶级节点';
            }
            Log::record('修改节点详情:' . json_encode($item), 'editMenuItems');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $item, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        } elseif ($type == 2) {
            //编辑
            $name = $this->request->param('name');
            $router = $this->request->param('router');
            $seq = $this->request->param('seq');
            $master_name = $this->request->param('master_name');
            $operations = $this->request->param('operations');
            $status = $this->request->param('status');
            if (!$name || !$master_name || !$operations) {
                return $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            }
            if ($name != $master_name) {
                //查询这个名字是否存在
                $item = MenuService::getInstance()->getMenuItems(array('name' => $name), 'id');
                if (!empty($item)) {
                    echo $this->return_json(\constant\CodeConstant::CODE_节点名称已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_节点名称已存在]);
                    die;
                }
            }
            $data = [];
            $data['name'] = $name;
            $data['router'] = $router;
            $data['seq'] = $seq;
            $data['updated'] = time();
            $data['update_user'] = $this->token['username'];
            $data['operations'] = $operations;
            $data['status'] = $status;
            $updata_menu = $this->_getAppointMenuLists(array(array('id' => $id)));
            try {
                MenuService::getInstance()->getModel()->startTrans();
                MenuService::getInstance()->editMenuItems($data, array('id' => $id));
                //修改子级隐藏
                foreach ($updata_menu as $key => $val) {
                    $data_1 = [];
                    $data_1['updated'] = $data['updated'];
                    $data_1['update_user'] = $data['update_user'];
                    $data_1['status'] = $data['status'];
                    MenuModel::getInstance()->getModel()->where('id', $val)->save($data_1);
                }

                MenuService::getInstance()->getModel()->commit();
                $ok = 1;
            } catch (\Exception $e) {
                MenuService::getInstance()->getModel()->rollback();
                $ok = 2;
            }

            //查询当前id下所有子节点

            if ($ok == 1) {
                Log::record('修改节点成功:' . json_encode($data), 'editMenuItems');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            }
            Log::record('修改节点失败:' . json_encode($data), 'editMenuItems');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     *@删除节点
     *@param string $token token值
     *@param int    $id    节点id
     *@return json
     */
    public function delMenuItems()
    {
        $id = $this->request->param('id');

        if (!$id || !is_numeric($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $data = [];
        $data_1 = [];
        $time = time();
        $username = $this->token['username'];
        $result = $this->_getAppointMenuLists(array(array('id' => $id)));

        if (!empty($result)) {
            try {
                MenuService::getInstance()->getModel()->startTrans();
                //节点删除后将角色绑定的节点删除
                foreach ($result as $k => $v) {
                    $data_1['status'] = 2;
                    $data_1['updated'] = $time;
                    $data_1['update_user'] = $username;

                    MenuService::getInstance()->editMenuItems($data_1, array('id' => $v));
                    RoleMenuService::getInstance()->editRoleToMenu($data_1, array('menu_id' => $v));
                }
                MenuService::getInstance()->getModel()->commit();

                Log::record('删除节点成功:id:' . $id . '@' . json_encode($data), 'delMenuItems');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除成功]);
                die;
            } catch (\Exception $e) {
                MenuService::getInstance()->getModel()->rollback();
                Log::record('删除节点失败:id:' . $id . '@' . json_encode($data), 'delMenuItems');
                echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_删除失败]);
                die;
            }
        }
    }
}

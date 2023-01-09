<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\SubcontractingGiftAdminService;
use think\facade\Log;
use think\facade\View;

class SubcontractingGiftAdminController extends AdminBaseController
{
    public function subcontractingGiftAdminList()
    {
        $username = $this->request->param('username');
        $size = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $size;
        $where = [];
        if ($username) {
            $where['username'] = $username;
        }
        $where['status'] = 1;
        $num = 0;
        $list = SubcontractingGiftAdminService::getInstance()->getSubcontractingGiftAdminListByWhere($where, 'id,username,phone,created_user,created_time,status', array($page, $size));
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
                $list[$k]['status_name'] = $v['status'] == 1 ? '正常' : '删除';
            }

            //查询管理员总数
            $num = SubcontractingGiftAdminService::getInstance()->getSubcontractingGiftAdminCountByWhere($where);
        }
        Log::record('外包礼物兑换后台管理员列表:操作人:' . $this->token['username'], 'giftAdminList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $size);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        View::assign('username', $username);
        return View::fetch('subcontracting/gift/index');
    }

    public function addSubcontractingGiftAdmin()
    {
        $username = $this->request->param('username');
        $phone = $this->request->param('phone');
        $status = $this->request->param('status');
        //校验手机号
        $pattern = "/^1(3[0-9]|5[012356789]|8[0256789]|7[0678])\d{8}$/";
        if (!preg_match($pattern, $phone)) {
            echo $this->return_json(\constant\CodeConstant::CODE_此手机号不合法, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此手机号不合法]);
            die;
        }
        //查询用户名是否存在
        $userinfo = SubcontractingGiftAdminService::getInstance()->getSubcontractingGiftAdminListByWhere(array('username' => $username, 'status' => 1));
        if (!empty($userinfo)) {
            echo $this->return_json(\constant\CodeConstant::CODE_管理员名称已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_管理员名称已存在]);
            die;
        }
        $userinfo = [];
        $userinfo = SubcontractingGiftAdminService::getInstance()->getSubcontractingGiftAdminListByWhere(array('phone' => $phone, 'status' => 1));
        if (!empty($userinfo)) {
            echo $this->return_json(\constant\CodeConstant::CODE_此手机号已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此手机号已存在]);
            die;
        }
        $data = [];
        $data['username'] = $username;
        $data['phone'] = $phone;
        $data['created_time'] = time();
        $data['created_user'] = $this->token['username'];
        $data['updated_time'] = $data['created_time'];
        $data['updated_user'] = $data['created_user'];
        $data['status'] = $status;
        $ok = SubcontractingGiftAdminService::getInstance()->addSubcontractingGiftAdmin($data);
        if ($ok) {
            Log::record('外包礼物兑换后台管理员添加成功:操作人:' . $this->token['username'] . ':添加数据:' . json_encode($data), 'addSubcontractingGiftAdmin');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        }
        Log::record('外包礼物兑换后台管理员添加失败:操作人:' . $this->token['username'] . ':添加数据:' . json_encode($data), 'addSubcontractingGiftAdmin');
        echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
        die;
    }

    public function editSubcontractingGiftAdmin()
    {

    }

    public function subcontractingGiftAdminItem()
    {
        $id = $this->request->param('id');
        $info = SubcontractingGiftAdminService::getInstance()->subcontractingGiftAdminInfo(array('id' => $id), 'username,phone,status');
        if (!empty($info)) {
            $info = $info->toArray();
        }
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $info, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    public function delSubcontractingGiftAdmin()
    {
        $id = $this->request->param('id');
        //查询此id是否存在
        $userinfo = SubcontractingGiftAdminService::getInstance()->getSubcontractingGiftAdminListByWhere(array('id' => $id, 'status' => 1));
        if (empty($userinfo)) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户不存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户不存在]);
            die;
        }
        $ok = SubcontractingGiftAdminService::getInstance()->editSubcontractingGiftAdmin(array('id' => $id), array('status' => 2));
        if ($ok) {
            Log::record('外包礼物兑换后台管理员删除成功:操作人:' . $this->token['username'] . ':id:' . $id, 'delSubcontractingGiftAdmin');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除成功]);
            die;
        }
        Log::record('外包礼物兑换后台管理员删除失败:操作人:' . $this->token['username'] . ':id:' . $id, 'delSubcontractingGiftAdmin');
        echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_删除失败]);
        die;
    }
}
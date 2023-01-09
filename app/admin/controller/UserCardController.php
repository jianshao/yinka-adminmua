<?php
//实名认证

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\UserCardModel;
use app\admin\model\UserIdentityModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class UserCardController extends AdminBaseController
{

    /**实名认证列表
     * @param string $token token值
     * @param string $user_id 用户id
     * @return mixed
     */
    public function userCardList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id'); //用户id
        $name = Request::param('name'); //用户名称
        $Idcard = Request::param('Idcard'); //用户名称
        $where = [];
        if ($user_id) {
            $where[] = ['uid', '=', $user_id];
        }
        if ($name) {
            $where[] = ['name', '=', $name];
        }
        if ($Idcard) {
            $where[] = ['idCard', '=', $Idcard];
        }
        //统计
        $count = UserCardModel::getInstance()->getModel()->where($where)->count();
        //获取数据
        $data = UserCardModel::getInstance()->getList($where, $page, $pagenum);
        foreach ($data as $k => $v) {
            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('userCardList列表获取成功:操作人:' . $this->token['username'], 'userCardList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_id', $user_id);
        View::assign('Idcard', $Idcard);
        View::assign('name', $name);
        return View::fetch('member/usercard');
    }

    /**新实名认证列表
     * @param string $token token值
     * @param string $user_id 用户id
     * @return mixed
     */
    public function newUserCardList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id'); //用户id
        $name = Request::param('name'); //用户名称
        $Idcard = Request::param('Idcard'); //用户名称
        $where = [];
        $where[] = ['status', '=', 1];
        if ($user_id) {
            $where[] = ['uid', '=', $user_id];
        }
        if ($name) {
            $where[] = ['certname', '=', $name];
        }
        if ($Idcard) {
            $where[] = ['certno', '=', $Idcard];
        }
        //统计
        $count = UserIdentityModel::getInstance()->getModel()->where($where)->group('uid,certname,certno,status')->count();
        $data = UserIdentityModel::getInstance()->getModel()->where($where)->group('uid,certname,certno,status')->order('create_time desc')->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['name'] = $v['certname'];
            $data[$k]['idCard'] = $v['certno'];
            $data[$k]['sex'] = '未知';
            $data[$k]['area'] = '未知';
            $data[$k]['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('userCardList列表获取成功:操作人:' . $this->token['username'], 'userCardList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_id', $user_id);
        View::assign('name', $name);
        View::assign('Idcard', $Idcard);
        return View::fetch('member/newUserCardList');
    }

}

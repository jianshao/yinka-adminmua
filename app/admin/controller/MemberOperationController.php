<?php
/**
 * 用户操作类
 */
namespace app\admin\controller;

use think\facade\Request;
use app\admin\common\AdminBaseController;
use think\cache\driver\Redis;
use app\admin\model\MemberModel;
use app\admin\service\MemberOperationService;


class MemberOperationController extends AdminBaseController
{
    //通过id查询用户
    public function getUserInfo()
    {
        $token = Request::param('token');
        $uid = Request::param('uid');
        if (!$uid || !$token) {
            return $this->return_json(500, [], '参数不能为空');
        }
        $res = $this->getAdminIdByToken($token);
        if (empty($res)) {
            return $this->return_json(500, [], 'token错误');
        }

        $data = MemberOperationService::getInstance()->getInfo($uid);
        if (empty($data)) {
            return rjson([]);
        }
        return rjson($data, 200);

    }

    //筛选用户列表
    public function searchUserList()
    {
        $token = Request::param('token');
        $uid = Request::param('uid');
        $type = Request::param('type');
        $page = Request::param('page');
        $pagenum = Request::param('pagenum');
        if (!$token) {
            return $this->return_json(500, [], '参数不能为空');
        }
        $res = $this->getAdminIdByToken($token);
        if (empty($res)) {
            return $this->return_json(500, [], 'token错误');
        }
        $start = ($page - 1) * $pagenum;
        $data = MemberOperationService::getInstance()->getSeachList($uid, $type);
        if (empty($data)) {
            return rjson([]);
        }
        return rjson($data, 200);


    }


}
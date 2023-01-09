<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AdminUserModel;
use app\common\RedisCommon;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class TeaWhiteController extends AdminBaseController
{
    public $pagenum = 20;

    public function addTeaVoiceWhiteUser()
    {
        $userId = Request::param('userId');
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->zAdd('YLCCWhiteList', time() . $this->token['id'], $userId);
        Log::info(sprintf('TeaWhiteController addUser 操作人：%s 茶茶用户id：%s', $this->token['username'], $userId));
        echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
        die;
    }

    public function getTeaVoiceWhiteUserList()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $page = Request::param('page', 1);
        $start = ($page - 1) * $this->pagenum;
        $end = $start + $this->pagenum - 1;
        $lists = $redis->zRevRange('YLCCWhiteList', $start, $end, 'WITHSCORES');
        $arr = [];
        foreach ($lists as $userId => $score) {
            $data = [];
            $data['userId'] = $userId;
            $data['adminId'] = substr($score, 10);
            $data['time'] = substr($score, 0, 10);
            $arr[] = $data;
        }
        View::assign('data', $arr);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('/');
    }

    public function teawhiteuserList()
    {
        if ($this->request->param("isRequest") == 1) {
            $page = $this->request->param('page', 1);
            $limit = $this->request->param("limit", 30);
            $redis = RedisCommon::getInstance()->getRedis();
            $start = ($page - 1) * $limit;
            $end = $start + $limit - 1;
            $lists = $redis->ZREVRANGE('YLCCWhiteList', $start, $end, true);
            $count = $redis->zCard("YLCCWhiteList");
            $data = [];
            foreach ($lists as $userId => $score) {
                $data[$userId]['userId'] = $userId;
                $data[$userId]['adminId'] = substr($score, 10);
                $data[$userId]['date'] = date('Y-m-d H:i:s', substr($score, 0, 10));
            }
            $adminids = array_column($data, "adminId");
            $adminInfo = AdminUserModel::getInstance()->getModel()->where("id", "in", $adminids)->column("username", "id");
            foreach ($data as $key => $item) {
                $data[$key]['admin_username'] = $adminInfo[$item['adminId']] ?? '';
            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => array_values($data)];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            return View::fetch('user/teawhiteuserlist');
        }
    }

    private function returnIscharge($param)
    {
        if ($param === '') {
            return "暂无数据";
        } elseif ($param === false) {
            return "无充值";
        } elseif ($param === true) {
            return "有充值";
        } else {
            return "此类型不存在";
        }

    }

    public function teablackuserList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $ccuserid = $this->request->param("ccuserid", 0);

        if ($this->request->param("isRequest") == 1) {
            $redis = RedisCommon::getInstance()->getRedis();
            $count = $redis->hLen('YLCCCheckLogin');
            $collectRes = ["total_count" => $count];
            $datacompa = $redis->hget("YLCCCheckLogin", $ccuserid); //数据对比结果
            $data = [];
            if ($datacompa) {
                $yldatacompa = json_decode($datacompa, true);
                $data[] = [
                    "yluserid" => $yldatacompa['ylUserId'] ?? 0,
                    "ccuserid" => $yldatacompa['ccUserId'] ?? 0,
                    "reason" => $yldatacompa['reason'] ?? '',
                    "ccmobile" => $yldatacompa['ccData']['mobile'] ?? '',
                    'ischarge' => $this->returnIscharge($yldatacompa['isCharge'] ?? ''),
                    'holdtime' => date('Y-m-d H:i:s', ($yldatacompa['ccData']['lastLoginTime'] ?? 0)),
                ];

            }
            $data = ["msg" => '', "count" => 0, "code" => 0, "data" => ($data), "hz" => $collectRes];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            return View::fetch('user/teablackuserlist');
        }
    }

}
<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\RoomScreenMsgModel;
use think\facade\Request;
use think\facade\View;

class RoomMsgController extends AdminBaseController
{
    /*动态列表
     * @param string $token token值
     * @param string $page  分页
     * @param int $pagenum  条数
     * @return mixed
     */
    public function getList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $pagenum;
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $uid = (int) $this->request->param('uid');
        $room_id = (int) $this->request->param('room_id');
        $where = [];
        $where[] = ['created_time', '>=', strtotime($start)];
        $where[] = ['created_time', '<', strtotime($end)];

        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }

        if ($room_id) {
            $where[] = ['room_id', '=', $room_id];
        }

        $count = RoomScreenMsgModel::getInstance()->where($where)->count();
        $list = RoomScreenMsgModel::getInstance()->where($where)->order('created_time desc')->limit($pagenum, $offset)->select()->toArray();
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('list', $list);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('room_id', $room_id);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('room/roomScreenMsg');
    }
}

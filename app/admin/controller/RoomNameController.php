<?php
/**
 * Created by PhpStorm.
 * User: pussycat
 * Date: 2019/7/23
 * Time: 21:01
 */

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\RoomModeModel;
use app\admin\model\RoomNameModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class RoomNameController extends AdminBaseController
{
    /*
     * 推荐房间名称列表
     */
    public function roomModelName()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $rm_id = empty(Request::param('rmid')) ? 0 : Request::param('rmid');
        $rm_pid = empty(Request::param('rmpid')) ? 0 : Request::param('rmpid');
        $where[] = ['pid', '<>', ''];
        if ($rm_id) {
            $where[] = ['rm_id', '=', $rm_id];
        }
        if ($rm_pid) {
            $where[] = ['pid', '=', $rm_pid];
        }
        $count = RoomNameModel::getInstance()->getModel()->where($where)->count();
        $list = RoomNameModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        $roomModel = RoomModeModel::getInstance()->getModel()->field('id,pid,room_mode')->select()->toArray();
        foreach ($roomModel as $k => $v) {
            $rm[$v['id']] = $v['room_mode'];
        }
        foreach ($list as $k => $v) {
            $list[$k]['time'] = date('Y-m-d H:i:s', $v['creat_time']);
            $list[$k]['rm_id'] = empty($rm[$list[$k]['rm_id']]) ? '无' : $rm[$list[$k]['rm_id']];
            $list[$k]['pid'] = empty($rm[$list[$k]['pid']]) ? '无' : $rm[$list[$k]['pid']];
        }
        Log::record('房间类型列表:操作人:' . $this->token['username'], 'roomTypeList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('rm_id', $rm_id);
        View::assign('rm_pid', $rm_pid);
        View::assign('roomModel', $roomModel);
        return View::fetch('room/roomModelName');
    }
    public function addRoomModelName()
    {
        $name = Request::param('name');
        $rmid = empty(Request::param('rm_id')) ? 0 : Request::param('rm_id');
        $pid = empty(Request::param('pid')) ? 0 : Request::param('pid');
        if ($name == '' || $rmid == 0 || $pid == 0) {
            return json_encode(['code' => '500', 'msg' => '参数必填']);
        }
        $is = RoomNameModel::getInstance()->getModel()->where(['name' => $name])->select()->toArray();
        if (empty($is)) {
            $data = [
                'name' => $name,
                'status' => 1,
                'rm_id' => $rmid,
                'pid' => $pid,
                'creat_time' => time(),
            ];
            $res = RoomNameModel::getInstance()->getModel()->save($data);
            if ($res) {
                return json_encode(['code' => '200', 'msg' => '添加成功']);
                die;
            } else {
                return json_encode(['code' => '500', 'msg' => '添加失败']);
                die;
            }
        } else {
            return json_encode(['code' => '500', 'msg' => '名称已存在']);
            die;
        }
    }
    public function getRoomModel()
    {
        $attiretype = RoomModeModel::getInstance()->getModel()->where(['is_show' => 1])->select()->toArray();
        foreach ($attiretype as $k => $v) {
            $type[] = $v;
        }
        return json_encode($type);
    }

    public function roomName()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $name = Request::param('name');
        $where = [];
        if ($name) {
            $where[] = ['name', '=', $name];
        }
        $count = RoomNameModel::getInstance()->getModel()->where($where)->count();
        $list = RoomNameModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        $roomModel = RoomModeModel::getInstance()->getModel()->where([['pid', '=', 0]])->field('id,room_mode')->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['time'] = date('Y-m-d H:i:s', $v['creat_time']);
        }
        Log::record('房间类型列表:操作人:' . $this->token['username'], 'roomTypeList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('roomModel', $roomModel);
        return View::fetch('room/roomname');
    }

    public function roomModel()
    {
        $roomModel = RoomModeModel::getInstance()->getModel()->where([['pid', '=', 0]])->field('id,room_mode')->select()->toArray();
        return $roomModel;
    }

    /**
     * 添加推荐房间名称
     */
    public function addRoomName()
    {
        $name = Request::param('name');
        $type = Request::param('type');
        if ($name == '') {
            return json_encode(['code' => '500', 'msg' => '房间名称不可为空']);
        } else {
            $is = RoomNameModel::getInstance()->getModel()->where(['name' => $name])->select()->toArray();
            if (empty($is)) {
                $data = [
                    'name' => $name,
                    'status' => 1,
                    'type' => $type,
                    'creat_time' => time(),
                ];
                $res = RoomNameModel::getInstance()->getModel()->save($data);
                if ($res) {
                    return json_encode(['code' => '200', 'msg' => '添加成功']);
                    die;
                } else {
                    return json_encode(['code' => '500', 'msg' => '添加失败']);
                    die;
                }
            } else {
                return json_encode(['code' => '500', 'msg' => '名称已存在']);
                die;
            }

        }

    }

    /**
     * 切换展示状态
     */
    public function updateRoomName()
    {
        $id = Request::param('id');
        $status = Request::param('status');
        $type = Request::param('type');

        if (empty($id)) {
            return json_encode(['code' => '500', 'msg' => '参数为空添加失败']);
            die;
        }

        if (!empty($status)) {
            $is = RoomNameModel::getInstance()->getModel()->where(['id' => $id])->save(['status' => $status]);
            if ($is) {
                return json_encode(['code' => '200', 'msg' => '切换成功']);
                die;
            } else {
                return json_encode(['code' => '500', 'msg' => '切换失败']);
                die;
            }

        } else {
            $is = RoomNameModel::getInstance()->getModel()->where(['id' => $id])->save(['type' => $type]);
            if ($is) {
                return json_encode(['code' => '200', 'msg' => '切换成功']);
                die;
            } else {
                return json_encode(['code' => '500', 'msg' => '切换失败']);
                die;
            }
        }

    }
    /**
     * 删除房间名称
     */
    public function delRoomName()
    {
        $id = Request::param('id');
        if (empty($id)) {
            return json_encode(['code' => '500', 'msg' => '参数不可为空']);
            die;
        } else {
            $is = RoomNameModel::getInstance()->getModel()->where(['id' => $id])->delete();
            if ($is) {
                return json_encode(['code' => '200', 'msg' => '删除成功']);
                die;
            } else {
                return json_encode(['code' => '500', 'msg' => '删除失败']);
                die;
            }
        }
    }

}

<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\LanguageroomModel;
use app\admin\service\VsitorExternnumberService;
use Exception;
use think\facade\Log;

class VsitorExternnumberController extends AdminBaseController
{
    private $vsitor_externnumber_key = 'vsitor_externnumber_time_';

    public function vsitorExternnumberLists()
    {
        $room_id = $this->request->param('id');
        $id = $this->request->param('v_id');
        $where = [];
        if ($room_id) {
            $where = array(['room_id', '=', $room_id], ['status', '<>', 3]);
        }
        if ($id) {
            $where = array(['id', '=', $id], ['status', '<>', 3]);
        }
        $data = VsitorExternnumberService::getInstance()->getLists($where, 'id,room_id,created_user,status,start_time,end_time,visitor_externnumber');
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $data[$k]['status_name'] = $v['status'] == 1 ? '未开始' : '开始中';
                $data[$k]['start_time'] = date('Y-m-d H:i:s', $v['start_time']);
                $data[$k]['end_time'] = date('Y-m-d H:i:s', $v['end_time']);
            }
        }
        Log::record('房间热度值列表:操作人:' . $this->token['username'], 'vsitorExternnumberLists');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /*
     * 房间热度值新增接口 涉及到范围 需要redis 故单独开个接口
     */

    public function addRoomVisitorExternnumber()
    {
        $id = $this->request->param('id');
        $visitor_externnumber = $this->request->param('visitor_externnumber');
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_ID错误]);
            die;
        }
        if (!$start_time) {
            echo $this->return_json(\constant\CodeConstant::CODE_开始时间不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_开始时间不可为空]);
            die;
        }
        if (!$end_time) {
            echo $this->return_json(\constant\CodeConstant::CODE_结束时间不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间不可为空]);
            die;
        }
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        if ($start_time > $end_time || $start_time == $end_time) {
            echo $this->return_json(\constant\CodeConstant::CODE_结束时间必须大于开始时间, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间必须大于开始时间]);
            die;
        }
        //查询当前房间存在的未开始及已开始的热度设置
        $db_data = VsitorExternnumberService::getInstance()->getLists(array(['room_id', '=', $id], ['status', '<>', 3]), 'id,start_time,end_time');

        $data = [];
        $data['room_id'] = $id;
        $data['visitor_externnumber'] = $visitor_externnumber;
        $data['created_user'] = $this->token['username'];
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $data['created_time'] = time();
        $data['updated_time'] = $data['created_time'];
        $data['updated_user'] = $data['created_user'];

        if (empty($db_data)) {
            $ok = $this->_addRoomVisitorExternnumber($start_time, $data, $id, $visitor_externnumber);
            if ($ok === false) {
                echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
                die;
            }
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $ok, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }

        //如存在则匹配当前的开始时间或者结果时间是否已经存在数据库中
        $yes = 0;
        foreach ($db_data as $k => $v) {
            if (($start_time >= $v['start_time'] && $start_time <= $v['end_time']) || ($end_time >= $v['start_time'] && $end_time <= $v['end_time'])) {
                $yes = 1;
            }
        }

        $ok = $this->_addRoomVisitorExternnumber($start_time, $data, $id, $visitor_externnumber);
        if ($ok === false) {
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, $ok, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $ok, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;

    }

    private function _addRoomVisitorExternnumber($start_time, $data, $id, $visitor_externnumber)
    {
        //如设置的开始小于等于当前时间则直接执行
        $data['status'] = ($start_time < time()) ? 2 : 1;
        try {
            VsitorExternnumberService::getInstance()->getModel()->startTrans();

            $insert_id = VsitorExternnumberService::getInstance()->addVsitorExternnumber($data);
            if ($data['status'] == 2) {
                LanguageroomModel::getInstance()->setRoom(array('id' => $id), array('visitor_externnumber' => $visitor_externnumber), $id);
                //判断当前房间是C还是派对且房间不上锁的状态,更新热门值(且手动添加的热度值也在变化)
                VsitorExternnumberService::getInstance()->saveRoomNumber($id, $visitor_externnumber);
            }

            VsitorExternnumberService::getInstance()->getModel()->commit();
            Log::record('房间热度值即时生效设置成功:操作人:' . $this->token['username'] . ':redis_key', '_addRoomVisitorExternnumber');
            return $insert_id;
        } catch (Exception $e) {
            VsitorExternnumberService::getInstance()->getModel()->rollback();
            Log::record('房间热度值即时生效设置失败:操作人:' . $this->token['username'] . ':redis_key', '_addRoomVisitorExternnumber');
            return false;
        }
    }

    public function editRoomVisitorExternnumber()
    {
        $id = $this->request->param('id');
        $visitor_externnumber = $this->request->param('visitor_externnumber');
        $start_time = $this->request->param('start_time');
        $end_time = $this->request->param('end_time');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_ID错误]);
            die;
        }
        if ($visitor_externnumber < 1) {
            echo $this->return_json(\constant\CodeConstant::CODE_请正确输入热度值, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_请正确输入热度值]);
            die;
        }
        if (!$start_time) {
            echo $this->return_json(\constant\CodeConstant::CODE_开始时间不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_开始时间不可为空]);
            die;
        }
        if (!$end_time) {
            echo $this->return_json(\constant\CodeConstant::CODE_结束时间不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间不可为空]);
            die;
        }
        $start_time = strtotime($start_time);
        $end_time = strtotime($end_time);
        if ($start_time > $end_time || $start_time == $end_time) {
            echo $this->return_json(\constant\CodeConstant::CODE_结束时间必须大于开始时间, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间必须大于开始时间]);
            die;
        }
        $db_data = VsitorExternnumberService::getInstance()->getLists(array(['id', '=', $id], ['status', '=', 1]), 'id,start_time,end_time,room_id,visitor_externnumber');

        if (empty($db_data)) {
            echo $this->return_json(\constant\CodeConstant::CODE_此手动热度已开始或已删除, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此手动热度已开始或已删除]);
            die;
        }

        if ($start_time == $db_data[0]['start_time'] && $end_time == $db_data[0]['end_time'] && $visitor_externnumber == $db_data[0]['visitor_externnumber']) {
            echo $this->return_json(\constant\CodeConstant::CODE_内容没有进行修改, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_内容没有进行修改]);
            die;
        }
        //查修该房间所有热度值
        $data = [];
        $data['visitor_externnumber'] = $visitor_externnumber;
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        $data['updated_time'] = time();
        $data['updated_user'] = $this->token['username'];
        $data['status'] = ($start_time < time()) ? 2 : 1;

        try {
            $room_id = $db_data[0]['room_id'];
            VsitorExternnumberService::getInstance()->getModel()->startTrans();

            VsitorExternnumberService::getInstance()->editVsitorExternnumber(array('id' => $id), $data);
            if ($data['status'] == 2) {
                LanguageroomModel::getInstance()->setRoom(array('id' => $room_id), array('visitor_externnumber' => $visitor_externnumber), $room_id);
            }

            VsitorExternnumberService::getInstance()->getModel()->commit();
            Log::record('房间热度值编辑后即时生效设置成功:操作人:' . $this->token['username'], 'editRoomVisitorExternnumber');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, 1, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (Exception $e) {
            VsitorExternnumberService::getInstance()->getModel()->rollback();
            Log::record('房间热度值编辑后即时生效设置失败:操作人:' . $this->token['username'], 'editRoomVisitorExternnumber');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    public function delRoomVisitorExternnumber()
    {
        $id = $this->request->param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_ID错误]);
            die;
        }
        //查询此id信息
        $list = VsitorExternnumberService::getInstance()->getLists(array(['id', '=', $id], ['status', '<>', 3]), 'id,room_id,visitor_externnumber,status,start_time');
        if (empty($list)) {
            echo $this->return_json(\constant\CodeConstant::CODE_此手动热度已被删除, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此手动热度已被删除]);
            die;
        }
        try {
            $room_id = $list[0]['room_id'];
            VsitorExternnumberService::getInstance()->getModel()->startTrans();
            VsitorExternnumberService::getInstance()->editVsitorExternnumber(array(['id', '=', $list[0]['id']], ['status', '<>', 3]), array('status' => 3));

            LanguageroomModel::getInstance()->setRoom(array('id' => $room_id), array('visitor_externnumber' => 0), $room_id);
            VsitorExternnumberService::getInstance()->getModel()->commit();

            //删除热门缓存里面的数据(当前房间是用户还是公会),并且是开始中的热度值
            if ($list[0]['status'] == 2) { //开始中的热度值删除
                // $redis = $this->getRedis();
                // $guildRedisKey = 'guild_room_hot:' . $list[0]['room_id'];
                // $nowNumber = $redis->hGet($guildRedisKey, 'orignal');
                // if ($nowNumber > $list[0]['visitor_externnumber']) {
                //     $number = -$list[0]['visitor_externnumber'];
                // } else {
                //     $number = -$nowNumber;
                // }
                $number = 0;
                VsitorExternnumberService::getInstance()->saveRoomNumber($list[0]['room_id'], $number);
            }
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        } catch (Exception $e) {
            VsitorExternnumberService::getInstance()->getModel()->rollback();
            echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_删除失败]);
            die;
        }
    }

}

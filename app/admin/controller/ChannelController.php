<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\MarketChannelModel;
use app\admin\service\AdminUserService;
use think\facade\Log;
use think\facade\Request;

class ChannelController extends AdminBaseController
{
    //渠道分组
    public function group()
    {
        $channel_level = $this->request->param('channel_level'); //渠道等级
        $pid = $this->request->param('pid'); //渠道等级
        $id = $this->request->param('channelIdVal'); //渠道id
        $channel = $this->channelId; //渠道id
        $data = [ //值
            'channel_level' => $channel_level,
            'pid' => $pid,
            'updatetime' => time(),
            'one_level' => $channel,
        ];
        if ($channel_level == 2) {
            $data['two_level'] = $id;
            $data['three_level'] = 0;
        } else {
            $data['two_level'] = $pid;
            $data['three_level'] = $id;
        }
        $is = MarketChannelModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            Log::record('渠道分组成功:' . json_encode('执行人ID：' . $channel), 'saveAdminUser');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        }
        Log::record('渠道分组失败:' . json_encode('执行人ID：' . $channel), 'saveAdminUser');
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }
    //获取渠道信息
    public function level()
    {
        $level = $this->request->param('level'); //渠道等级
        $channel_level = $level - 1;
        $channel = $this->channelId; //渠道id
        if ($channel_level == 1) {
            $where[] = ['channel_level', '=', $channel_level];
            $where[] = ['one_level', '=', $channel];
        } else {
            $where[] = ['channel_level', '=', $channel_level];
            $where[] = ['pid', '=', $channel];
        }
        $data = MarketChannelModel::getInstance()->getModel()->where($where)->column('id,channel_name');
        return json_encode($data);
    }

    /**
     * @return mixed
     * 渠道列表
     */
    public function channelList()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    /**
     * @return mixed
     * 渠道列表
     */
    public function channelListNew()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    /**
     * 删除渠道管理员
     */
    public function delChannel()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    /**
     * 添加渠道
     */
    public function addChannel()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
    }

    /**
     * 根据id获取下级渠道的列表
     */
    public function getChannelOfId()
    {
        $channel_id = $this->request->param('id');
        $channelList = [];
        if (empty($channel_id)) {
            return $this->return_json(404, $channelList, '没有子类');
        }
        $channelList = MarketChannelModel::getInstance()->getModel()->where(['pid' => $channel_id])->select()->toArray();
        return $this->return_json(200, $channelList, '返回成功');
    }

    /**
     * 更新渠道管理员信息
     */
    public function editChannel()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;

        $id = Request::param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $data_1 = [ //渠道表
            'channel_name' => Request::param('channel_name'),
            'room_id' => Request::param('room_id'),
            'anchor_id' => Request::param('anchor_id'),
            'updatetime' => time(),
        ];
        $where1 = ['id' => $id];
        $pwd = Request::param('password');
        if ($pwd) {
            $data_2 = [ //admin表
                'password' => md5($pwd),
                'status' => Request::param('status'), //1 启用   2禁用
                'updated' => time(),
            ];
        } else {
            $data_2 = [ //admin表
                'status' => Request::param('status'), //1 启用   2禁用
                'updated' => time(),
            ];
        }
        $where2 = ['channel' => $id];
        //开启事物
        $ok1 = AdminUserService::getInstance()->getModel()->where($where2)->save($data_2);
        $ok2 = MarketChannelModel::getInstance()->getModel()->where($where1)->save($data_1);
        if ($ok1 || $ok2) {
            Log::record('更新渠道管理员成功:' . json_encode(array_merge($data_1, $data_2)), 'saveAdminUser');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        }
        Log::record('更新渠道管理员失败:' . json_encode(array_merge($data_1, $data_2)), 'saveAdminUser');
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }

}
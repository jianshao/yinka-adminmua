<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ChannelPackage;
use think\facade\Log;
use think\facade\Request;

class ChannelPackageController extends AdminBaseController
{
    /**
     * 添加渠道包
     */
    public function addChannelPackage()
    {
        $typeid = Request::param('typeid');
        if ($typeid != 0) {
            $data['pid'] = $typeid;
        } else {
            $data['pid'] = 0;
        }
        $data = [
            'name' => Request::param('channelName'), //渠道名称  例如：总渠道：KuaiShouDanDan  分渠道：KuaiShouDanDan01,KuaiShouDanDan02
            'status' => Request::param('status'), //渠道状态，0下架  1上架
            'created_time' => time(),
        ];
        $res = ChannelPackage::getInstance()->getModel()->save($data);
        if ($res) {
            Log::record('渠道包添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addChannelPackage');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('渠道包添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addChannelPackage');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }

    }

    /**
     * 总渠道汇总
     */
    public function channelPackageList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $channel_name = Request::param('channel_name');
        $where[] = ['status', '=', 1];
        $where[] = ['pid', '=', 0];
        if ($channel_name) {
            $where[] = ['name', '=', $channel_name];
        }
        $count = ChannelPackage::getInstance()->getModel()->where($where)->count();
        $totalChannelList = ChannelPackage::getInstance()->getPackageList();
        if (!empty($totalChannelList)) {
            foreach ($totalChannelList as $k => $v) {
                $totalChannelList[$k][''];
            }
        }

    }
}

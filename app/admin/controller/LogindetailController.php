<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AdminUserModel;
use app\admin\model\BlackLogModel;
use app\admin\model\LogindetailModel;
use app\admin\service\MemberBlackService;

class LogindetailController extends AdminBaseController
{
    /**用户登录记录列表
     * @param $token    token值
     * @param $uid      用户id
     */
    public function getLoginList()
    {
        $uid = $this->request->param('uid');
        //根据用户查询最近20条记录
        $list = LogindetailModel::getInstance()->getModel($uid)->where(array("user_id" => $uid))->order('id desc')->limit(0, 20)->select()->toArray();
        $data['list'] = array_reverse($list);
        foreach ($data['list'] as $k => $v) {
            $data['list'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
        }
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /**用户封禁记录列表
     * @param $token    token值
     * @param $uid      用户id
     */
    public function getForbidList()
    {
        $uid = $this->request->param('uid');
        //根据用户查询最近20条记录
        $list = BlackLogModel::getInstance()->getModel()
            ->where('user_id', $uid)
            ->order('id desc')
            ->limit(0, 20)
            ->select()
            ->toArray();
        $data['list'] = array_reverse($list);
        foreach ($data['list'] as $k => &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', $v['update_time']);
            if ($v['forbid_type'] == 0) {
                $v['time'] = MemberBlackService::getInstance()->blackTimeFormat($v['time']);
            } else {
                $v['time'] = '';
            }
            $v['forbid_type'] = $v['forbid_type'] == 0 ? '封禁' : '解封';

            $v['admin_name'] = AdminUserModel::getInstance()->getModel()->where('id', $v['admin_id'])->value('username');
        }
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }





}

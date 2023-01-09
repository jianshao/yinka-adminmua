<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\ChargedetailService;
use think\facade\Log;

class ChargeDetailController extends AdminBaseController
{
    public function getChargeDetailList()
    {
        $uid = $this->request->param('uid');
        $where = [];
        if ($uid) {
            $where[] = ['status', 'in', '1,2'];
            $where[] = ['uid', '=', $uid];
        }
        $field = 'id,platform,rmb,addtime,content,channel';
        $list = ChargedetailService::getInstance()->getChargeDetailByWhere($where, $field);

        // if (!empty($list)) {
        //     foreach ($list as $k => $v) {
        //         if ($v['channel'] == 'PublicNumber' && $v['content'] == '官方支付宝支付') {
        //             $v['platform'] = '支付宝生活号';
        //         } else {
        //             $v['platform'] = $v['content'];
        //         }

        //         switch ($v['platform']) {
        //             case 0:
        //                 $list[$k]['platform'] = '支付宝';
        //                 break;
        //             case 1:
        //                 $list[$k]['platform'] = '微信';
        //                 break;
        //             case 2:
        //                 $list[$k]['platform'] = '苹果支付';
        //                 break;
        //             case 3:
        //                 $list[$k]['platform'] = '微信公众号代充';
        //                 break;
        //         }

        //     }
        // }
        Log::record('用户客户端充值记录:', 'getChargeDetailList');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
}

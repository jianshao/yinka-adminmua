<?php
namespace app\admin\controller;

use app\admin\model\MemberWithdrawalModel;
use app\admin\model\MemberWithdrawCallbackModel;
use app\admin\service\WithdrawalService;
use think\facade\Log;

class DalongController
{

    //转账结果的异步回调
    public function withdrawalcbackNotice()
    {
        $orderNo = request()->param("orderNo");
        $status = request()->param("status");
        $reason = request()->param("reason");
        $spareSign = request()->param("spareSign");
        $requestparams = json_encode(request()->param());
        Log::INFO("withdrawalcbacknotice:" .$requestparams );
        $res = MemberWithdrawalModel::getInstance()->getModel()->where(["order_id" => $orderNo])->find();

        $params = ["orderNo" => $res['order_id'], "money" => sprintf("%.2f",$res['money'])];
        if (hash("sha256", json_encode($params)) == $spareSign) {
            //合法的回调
            MemberWithdrawCallbackModel::getInstance()->getModel()->insert(["order_id"=>$orderNo,"create_time"=>time(),"status"=>$status,"content"=>$requestparams]);
            if ($status == 3 || $status == 4) { //失败或者退单
                WithdrawalService::getInstance()->changeWithdrawalStatus($res, ['mark' => 'fail', 'msg' => $reason, 'ext_1' => 'dalong']);
            } elseif ($status == 2) { //成功
                WithdrawalService::getInstance()->changeWithdrawalStatus($res, ['mark' => 'success', 'ext_1' => 'dalong']);
            }
            echo "success";
        };
    }

}

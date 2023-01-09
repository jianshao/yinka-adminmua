<?php

namespace app\admin\script;

use app\admin\model\BiFirstChargeModel;
use app\admin\model\ChargedetailModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

//每日首冲数据统计
class NewFirstChargeCommand extends Command
{
    protected function configure()
    {
        $this->setName('NewFirstChargeCommand')->setDescription('NewFirstChargeCommand')
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d', strtotime("-1 days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d', strtotime("-1 days")));
    }

    protected function execute(Input $input, Output $output)
    {
        $start_time = $input->getArgument('start_time');
        $end_time = $input->getArgument('end_time');
        //每日首冲数据
        $this->executeFirstDirectCharge($start_time, $end_time);
    }

    /**处理每天的首次充值*/
    protected function executeFirstDirectCharge($start_time, $end_time)
    {
        $timeList = ParseUserStateDataCommmon::getInstance()->getTimeNode($start_time, $end_time, true);
        try {
            foreach ($timeList as $timenode) {
                dump("runging...", $timenode);
                $currentTimestamp = strtotime($timenode);
                $begin_addtime = date('Y-m-d H:i:s', $currentTimestamp);
                $nextTimestamp = strtotime("+1 days", $currentTimestamp);
                $end_addtime = date('Y-m-d H:i:s', $nextTimestamp);

                $res = ChargedetailModel::getInstance()->getModel()
                    ->field("uid,rmb,addtime")
                    ->where("status", "in", [1, 2])
                    ->where("addtime", ">=", $begin_addtime)
                    ->where("addtime", "<", $end_addtime)
                    ->order("addtime","desc")
                    ->select()->toArray();

                $chargeByUid = array_column($res,NULL,"uid");

                BiFirstChargeModel::getInstance()->getModel()->chunk(5000,function($users)use(&$chargeByUid){
                     foreach($users as $item){
                          if(isset($chargeByUid[$item['uid']])){
                              unset($chargeByUid[$item['uid']]);
                          }
                     }
                });

                $firstRealResult = array_values($chargeByUid);
                foreach ($firstRealResult as $item) {
                        $insertData[] = [
                            "uid" => $item['uid'],
                            'date' => date('Y-m-d', strtotime($item['addtime'])),
                            'charge_amount' => $item['rmb'],
                            'charge_type' => 1,
                            'charge_time' => strtotime($item['addtime']),
                        ];
                        $isrepeat[] = $item['uid'];
                }
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiFirstChargeModel::getInstance()->getModel(), $insertData, ["uid", "date"]);
            }

        } catch (\Throwable $e) {
            Log::error("newfirstchargecommand:insertdata" . $e->getMessage() . $e->getLine());
        }
    }

}

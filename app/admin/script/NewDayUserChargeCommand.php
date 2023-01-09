<?php

namespace app\admin\script;

use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiDaysUserChargeNewModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\script\analysis\UserChargeEveryday;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;


//统计每天的日常数据量 日活 新增 充值人数 充值总金额
class NewDayUserChargeCommand extends Command
{

    const  UPDATE_SOURCE_TABLE_NAME = 'bi_user_stats_1day';
    const  UPDATE_TARGET_TABLE_NAME = 'bi_days_user_charge';
    const  UPDATE_TARGET_TABLE_NAME_NEW = 'bi_days_user_charge_new';

    protected function configure()
    {
        // 指令配置
        $this->setName('NewDayUserChargeCommand')
            ->setDescription('NewDayUserChargeCommand')
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d'))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d'));
    }


    protected function execute(Input $input, Output $output)
    {
        //bi_days_user_charge 表中没有设有唯一键
        $start_time = $input->getArgument('start_time');
        $end_time = $input->getArgument('end_time');

        //每天的凌晨到凌晨1点这段时间 重新执行前一天的数据 保证数据完整
        if (date('H') >= "00" && date('H') <= "01") {
            $start_time = date('Y-m-d', strtotime("-1days"));
        }

        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($start_time, $end_time,true);
        foreach ($dateList as $node) {
            $insertdata = [];
            $targetData = BiUserStats1DayModel::getInstance()->getModel()->where('date', '=', $node)->select()->toArray();
            foreach ($targetData as $listItem) {
                UserChargeEveryday::getInstance()->parseChargeNew($listItem, $insertdata);
            }

            try {
                $insertdataChunk = array_chunk($insertdata, 100); //对数据分组
                foreach ($insertdataChunk as $chunkItem) {
                    ParseUserStateByUniqkey::getInstance()
                        ->insertOrUpdateModel(BiDaysUserChargeModel::getInstance()->getModel(),$chunkItem, ["uid", "type", "date", "id"]);
                }
                $this->chargeNewUser($node);
            } catch (\Exception $e) {
                Log::error("NewDayUserCharge:insertdata" . $e->getMessage());
            }
        }
    }


    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }


    //记录每天的新增充值
    public function chargeNewUser($node)
    {
        $res = BiDaysUserChargeModel::getInstance()->getModel()
            ->where("date", "=", $node)
            ->where("register_time", ">=", $node . " 00:00:00")
            ->where("register_time", "<", date("Y-m-d", strtotime("+1days", strtotime($node))) . " 00:00:00")
            ->withoutField("id")
            ->select()
            ->toArray();
        if ($res) {
            $insertdataChunk = array_chunk($res,100);
            foreach($insertdataChunk as $chunkItem){
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiDaysUserChargeNewModel::getInstance()->getModel(),$chunkItem, ["uid", "type", "date", "id"]);
            }

        }
    }


}

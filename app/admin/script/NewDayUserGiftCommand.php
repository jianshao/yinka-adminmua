<?php

namespace app\admin\script;

use app\admin\script\analysis\RoomSendRecevieGift;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

//未使用的脚本 jobby没此任务脚本
//统计每天的日常数据 房间内用户送礼收礼
class NewDayUserGiftCommand extends Command
{

    const  UPDATE_SOURCE_TABLE_NAME = 'bi_user_stats_1day';
    const  UPDATE_TARGET_TABLE_NAME = 'bi_days_user_gift';
    const  COMMAND_NAME = "NewDayUserGiftCommand";

    protected function configure()
    {
        // 指令配置
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME)
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d',strtotime("-1 days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d',strtotime("-1 days")));
    }


    protected function execute(Input $input, Output $output)
    {
        $start_time = $input->getArgument('start_time');
        $end_time = $input->getArgument('end_time');
        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($start_time, $end_time, true);
        foreach ($dateList as $node) {
            echo SELF::COMMAND_NAME.":Runing...: ".$node;
            $handle = [];
            $targetData = Db::table(SELF::UPDATE_SOURCE_TABLE_NAME)->where('date', '=', $node)->select()->toArray();
            foreach ($targetData as $listItem) {
                $key = ParseUserStateDataCommmon::getInstance()->identifyMerge($listItem['date'], $listItem['uid']);
                $handle[$key] = json_decode($listItem['json_data'], true);
            }

            $insertdata = RoomSendRecevieGift::getInstance()->parseGift($handle);

            // 启动事务
            Db::startTrans();
            try {
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul($insertdata, SELF::UPDATE_TARGET_TABLE_NAME,["id,date"]);
                Db::commit();
            } catch (\Exception $e) {
                Log::error(SELF::COMMAND_NAME . ":insertdata".$e->getMessage());
                Db::rollback();
            }
        }

    }


}

<?php
/**
 * 同步脚本
 */

namespace app\admin\script;
use app\admin\model\BiDaysUserGiftDatasBysendTypeModel;
use app\admin\model\BiRoomEveryroomConsume;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class RoomEverydayConsumeCommand extends Command
{
    const COMMAND_NAME = "RoomEverydayConsumeCommand";
    const TABLENAME = "bi_room_everyroom_consume";


    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d'))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d',strtotime("+1days")))
            ->setDescription(SELF::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        //已经在CalculateUserGetAndSendGiftsByGiftTypeCommand  脚本中调用
        //保证数据的时间前后的一致性
        return false;



        $begin_date = $input->getArgument("start_time");
        $end_date = $input->getArgument("end_time");
        //每天的凌晨到凌晨1点这段时间 重新执行前一天的数据 保证数据完整

        if (date('H') >= "00" && date('H') <= "03") {
            $begin_date = date('Y-m-d', strtotime("-1days"));
        }
        $this->handler($begin_date, $end_date);
    }


    //处理数据
    public function handler($begin_date, $end_date)
    {
        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($begin_date, $end_date, false);
        foreach ($dateList as $nodes) {
            echo $nodes;
            $returnRes = [];
            $maxLimit = 2000;
            $page = 1;
            while (true) {
                //bi_days_user_gift_datas_bysend_type
                $res = BiDaysUserGiftDatasBysendTypeModel::getInstance()->getModel()
                    ->where("date", ">=", $nodes)
                    ->where("date", "<", date('Y-m-d', strtotime("+1days", strtotime($nodes))))
                    ->where("type", 1)
                    ->where("room_id", ">", 0)
                    ->page($page, $maxLimit)
                    ->select()
                    ->toArray();
                if ($res) {
                    $this->loop($res, $returnRes);
                    $page++;
                } else {
                    break;
                }
            }
            try {
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiRoomEveryroomConsume::getInstance()->getModel(),$returnRes, ["id", "date", "send_type", "room_id"]);
            } catch (\Throwable $e) {
                Log::info(sprintf("roomeverydayconsumecommand:error:%s", $e->getMessage()));
            }
        }

    }


    public function loop($data, &$returnRes)
    {
        foreach ($data as $item) {
            $room_id = $item['room_id'];
            $date = $item['date'];
            $send_type = $item['send_type'];
            $uniq = $date . ":" . $room_id . ":" . $send_type;
            if (isset($returnRes[$uniq])) {
                $returnRes[$uniq]['reward_amount'] += $item['reward_amount'];
                $returnRes[$uniq]['consume_amount'] += $item['consume_amount'];
            } else {
                $returnRes[$uniq]['room_id'] = $room_id;
                $returnRes[$uniq]['date'] = $date;
                $returnRes[$uniq]['send_type'] = $send_type;
                $returnRes[$uniq]['reward_amount'] = $item['reward_amount'] ?: 0;
                $returnRes[$uniq]['consume_amount'] = $item['consume_amount'] ?: 0;
            }
        }
    }


}

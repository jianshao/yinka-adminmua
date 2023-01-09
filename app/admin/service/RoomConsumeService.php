<?php

namespace app\admin\service;

use app\admin\model\BiDaysUserGiftDatasBysendTypeModel;
use app\admin\model\BiRoomEveryroomConsume;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\facade\Log;

class RoomConsumeService
{
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
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
                    ->where("type", 2)
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

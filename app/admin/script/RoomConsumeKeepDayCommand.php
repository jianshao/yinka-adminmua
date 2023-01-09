<?php

namespace app\admin\script;

use app\admin\model\BiDayUserGiftDatasBysendTypeModel;
use app\admin\model\BiUserKeepDayModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

/**
 * 房间内消费留存：房间消费
 * Class RoomConsumeKeepDayCommand
 * @package app\admin\script
 */
class RoomConsumeKeepDayCommand extends Command
{
    const UPDATE_TABLE_NAME = 'bi_user_keep_day'; //数据表
    const COMMAND_NAME = "RoomConsumeKeepDayCommand";
    const MAXLIMIT = 2000; //最大执行的条数

    //可以自定义修改配置 保证数据库建立好对应的字段即可
    protected $keepconfig = [
        "keep_2" => 2,
        "keep_3" => 3,
        "keep_4" => 4,
        "keep_5" => 5,
        "keep_6" => 6,
        "keep_7" => 7,
        "keep_8" => 8,
        "keep_9" => 9,
        "keep_10" => 10,
        "keep_15" => 15,
        "keep_30" => 30,
    ];

    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d', strtotime("-" . max($this->keepconfig) . "days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d', strtotime("-1days")))
            ->setDescription(self::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        $begin_date = $input->getArgument("start_time");
        $end_date = $input->getArgument("end_time");

        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($end_date, $end_date, true);
        foreach ($dateList as $currentDate) {
            echo $currentDate . PHP_EOL;
            $where = [];
            $where[] = ["date", "=", $currentDate];
            $where[] = ['room_id', ">", 0]; //房间id
            $where[] = ['type', "=", 1]; //送礼
            $res = $this->getReceiveData($where);
            $insertData = [];
            try {
                foreach ($res as $room_id => $items) {
                    $insertData[] = [
                        "date" => $currentDate,
                        "type" => 'room_consume',
                        "room_id" => $room_id,
                        "source" => join(",", array_unique($items))];
                }
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiUserKeepDayModel::getInstance()->getModel(), $insertData, ["date", "type", "room_id", "id"]);
                //ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul($insertData, self::UPDATE_TABLE_NAME, ["date", "type", "room_id", "id"]);
                //历史留存用户数据
            } catch (\Throwable $e) {
                Log::ERROR(SELF::COMMAND_NAME . ":error:" . $e->getMessage());
            }
        }
        $this->history($begin_date, $end_date);
    }

    // 分片解析数据
    private function getReceiveData($where)
    {
        $page = 1;
        $returnData = [];
        $source = BiDayUserGiftDatasBysendTypeModel::getInstance()->getModel()
            ->where($where)
            ->field("uid,room_id");
        $res = $source->page($page, self::MAXLIMIT)->select()->toArray();
        while ($res) {
            foreach ($res as $item) {
                if (isset($returnData[$item['room_id']])) {
                    if (!in_array($item['uid'], $returnData[$item['room_id']])) {
                        $returnData[$item['room_id']][] = $item['uid'];
                    }
                } else {
                    $returnData[$item['room_id']][] = $item['uid'];
                }
            }
            $page++;
            $res = $source->page($page, self::MAXLIMIT)->select()->toArray();
        }
        return $returnData;
    }

    /**
     * 临时用跑历史数据用的
     */
    private function history($begin_date, $end_date)
    {
        $page = 1;
        $maxLimit = 500;
        $userkeepdayModel = BiUserKeepDayModel::getInstance()->getModel();
        $res = $userkeepdayModel->where("date", ">=", $begin_date)
            ->where("date", "<=", $end_date)
            ->where("type", "=", "room_consume")
            ->page($page, $maxLimit)
            ->select()->toArray();

        while (true) {
            if (empty($res)) {
                break;
            }
            foreach ($res as $item) {
                $date = $item['date'];
                $room_id = $item['room_id'];
                $source = $item['source'];
                $primaryid = $item['id'];
                echo $date . PHP_EOL;
                $begindate = date('Y-m-d', strtotime($date . " +1days"));
                $enddate = date('Y-m-d', strtotime($date . " +30days"));

                $compRes = $userkeepdayModel->field("source,date")
                    ->where("date", ">=", $begindate)
                    ->where("date", "<=", $enddate)
                    ->where("type", "=", "room_consume")
                    ->where("room_id", "=", $room_id)
                    ->select()->toArray();

                $update['keep_2'] = 0;
                $update['keep_3'] = 0;
                $update['keep_4'] = 0;
                $update['keep_5'] = 0;
                $update['keep_6'] = 0;
                $update['keep_7'] = 0;
                $update['keep_8'] = 0;
                $update['keep_9'] = 0;
                $update['keep_10'] = 0;
                $update['keep_15'] = 0;
                $update['keep_30'] = 0;

                foreach ($compRes as $comitem) {
                    $key = (strtotime($comitem['date']) - strtotime($date)) / 3600 / 24 + 1;
                    if (!in_array($key, $this->keepconfig)) {
                        continue;
                    }
                    $source_par = explode(",", $source);
                    $next_source_par = explode(",", $comitem['source']);
                    $currsource = join(",", array_intersect($source_par, $next_source_par));
                    $update['keep_' . $key] = $currsource;
                }

                $userkeepdayModel->where("id", $primaryid)->update($update);
            }

            $page++;
            $res = $userkeepdayModel->where("date", ">=", $begin_date)
                ->where("date", "<=", $end_date)
                ->where("type", "=", "room_consume")
                ->page($page, $maxLimit)->select()->toArray();
        }
    }

}
<?php

namespace app\admin\script;

use app\admin\model\BiUserKeepDayModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\script\analysis\ParseUserActionCommon;
use app\admin\script\analysis\UserBehavior;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use app\common\RabbitMQCommand;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;


class UserKeepDayCommand extends Command
{

    const  UPDATE_TABLE_NAME = 'bi_user_keep_day'; //数据表
    const COMMAND_NAME = "UserKeepDayCommand";

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
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d',strtotime("-".max($this->keepconfig)."days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d', strtotime("-1days")))
            ->setDescription(self::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        $begin_date = $input->getArgument("start_time");
        $end_date = $input->getArgument("end_time");

        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($begin_date, $end_date, true);
        $insertData = [];
        $userkeepdayModel = BiUserKeepDayModel::getInstance()->getModel();
        foreach ($dateList as $currentDate) {
            echo $currentDate . PHP_EOL;
            $haveRes = $userkeepdayModel->where('date', $currentDate)->select()->toArray();
            if (empty($haveRes)) {
                $res = $this->parseData($currentDate);
                Log::info(SELF::COMMAND_NAME . ":parserecode" . $currentDate ."parse:" . json_encode($res));
                foreach ($res as $type => $items) {
                    $insertData[] = ["date" => $currentDate, "type" => $type, "source" => join(",", $items)];
                }
            }
        }
        try {
            //ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul($insertData, self::UPDATE_TABLE_NAME, ["date", "type", "id","room_id"]);
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($userkeepdayModel,$insertData,["date", "type", "id","room_id"]);
            $this->setUserKeep($begin_date, $end_date);
        } catch (\Throwable $e) {
            Log::ERROR(SELF::COMMAND_NAME . "error:" . $e->getMessage());
        }
    }


    public function setUserKeep($begin_date, $end_date)
    {
        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($begin_date, $end_date, true);
        $userkeepdayModel = BiUserKeepDayModel::getInstance()->getModel();
        foreach ($dateList as $currentDate) {
            $res =$userkeepdayModel->where('date', "=", $currentDate)->where("room_id","=",0)->select()->toArray();
            foreach ($res as $key => $items) {
                $updateRes = [];
                foreach ($this->keepconfig as $conf_key => $conf_item) {
                    if (empty($items[$conf_key])) {
                        $stopDate = date('Y-m-d', strtotime("+$conf_item days -1days", strtotime($items['date'])));
                        if ($stopDate >= date('Y-m-d')) { //今天的数据不完整所以不写入
                            continue;
                        }
                        $res = BiUserStats1DayModel::getInstance()->getModel()->field('uid')
                            ->where("date", "=", $stopDate)
                            ->where("uid", "in", explode(",", $items['source']))
                            ->select()->toArray();
                        if ($res) {
                            $updateRes[$conf_key] = join(",", array_column($res, "uid"));
                        }
                    }
                }
                try{
                   $userkeepdayModel->where("id", "=", $items['id'])->update($updateRes);
                }catch (\Throwable $e){
                    Log::error(self::COMMAND_NAME.$e->getMessage());
                }

            }
        }

    }


    public function parseData($curentDate)
    {
        //user_all 日活
        //charge_user:直充
        //agentcharge_user:代充
        //注册用户量
        $userList = [
            'active' => [],      //日活
            'register' => [],      //注册
            'charge' => [],      //充值
            'charge_add' => [],    //新增充值
        ];
        $userbehavior = new UserBehavior();
        $res = BiUserStats1DayModel::getInstance()->getModel()
            ->where('date', '=', $curentDate)
            ->select()->toArray();
        $parseUserList = [];
        foreach ($res as $item) {
            ParseUserActionCommon::getInstance()->parseDataNew($item, $userbehavior, $parseUserList);
        }

        $charge_user = $parseUserList['user']['charge_user'] ?? [];
        $agentcharge_user = $parseUserList['user']['agentcharge_user'] ?? [];
        $active_user = $parseUserList['user']['user_all'] ?? [];
        $charge_all_user = array_unique(array_merge($charge_user, $agentcharge_user));
        $userList['active'] = $active_user;
        $userList['charge'] = $charge_all_user;
        $tomorrowDate = date('Y-m-d', strtotime("+1days", strtotime($curentDate)));
        $registerList =  BiUserStats1DayModel::getInstance()->getModel()
            ->where("date", "=", $curentDate)
            ->field("uid")
            ->where("register_time", ">=", $curentDate . " 00:00:00")
            ->where("register_time", "<", $tomorrowDate . " 00:00:00")
            ->select()->toArray();
        $regiser_user = array_column($registerList, "uid");

        $userList['register'] = $regiser_user;
        $userList['charge_add'] = array_values(array_intersect($regiser_user, $charge_all_user));

        return $userList;
    }



}

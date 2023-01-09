<?php

namespace app\admin\script;

use app\admin\model\BiAsaRoomPromotionModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiUserStats1DayModel;
use app\common\ParseUserStateDataCommmon;
use app\common\RabbitMQCommand;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;


class AsaPromoteCommand extends Command
{

    const  UPDATE_TABLE_NAME = 'bi_asa_room_promotion'; //数据表
    const COMMAND_NAME = "AsaPromoteCommand";
    protected $url = "http://182.92.189.66:180/room_query";


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        $asaroompromotionModel = BiAsaRoomPromotionModel::getInstance()->getModel();
        $sourceList = $asaroompromotionModel->field('id,begin_time,end_time,room_id,type')
            ->where("status", "=", 0)
            ->select()->toArray();
        try {
            foreach ($sourceList as $items) {
                $begin_time = $items['begin_time'];
                $end_time = $items['end_time'];
                $room_id = $items['room_id'];
                $type = $items['type'];
                $data = $this->handlerRes($begin_time, $end_time, $room_id, $type); //单条处理逻辑
                $asaroompromotionModel->where('id', '=', $items['id'])->update($data);
            }
        } catch (\Throwable $e) {
            Log::info(self::COMMAND_NAME.$e->getMessage());
        }


    }


    public function handlerRes($begin_time, $end_time, $room_id, $type='')
    {
        $returnRes = [];
        $date = date("Y-m-d", strtotime($begin_time));
        $runSql="";
        $uidsList=[];
        if ($type == 'ios_channel') {

          /*  $runSql = "
            select  uid from bi_user_stats_1day  A  INNER JOIN  bi_channel_appstore B where
            A.promote_channel = 0
            and  date='{$date}'
            and A.register_time>='{$begin_time}' and A.register_time <= '{$end_time}'  AND B.user_id = A.uid
            and A.register_channel = 'appStore'";*/

            $ioswhere=[
                ["A.promote_channel","=",0],
                ["A.date","=",$date],
                ["A.register_time",">=",$begin_time],
                ["A.register_time","<=",$end_time],
                ["A.register_channel","=",'appStore'],
            ];

            $uidsList = BiUserStats1DayModel::getInstance()->getModel()
                ->alias('A')->JOIN('bi_channel_appstore B','B.user_id = A.uid')
                ->where($ioswhere)
                ->select()->toArray();
        }

        if ($type == 'huawei_channel') {
      /*      $runSql = "
			select  uid from bi_user_stats_1day  A  INNER JOIN  bi_channel_huawei B where
            A.promote_channel = 0
            and  date='{$date}'
            and A.register_time>='{$begin_time}' and A.register_time <= '{$end_time}'  AND B.user_id = A.uid
            and A.register_channel = 'HuaWei' and A.source='ccp' ";*/

            $hwwhere=[
                ["A.promote_channel","=",0],
                ["A.date","=",$date],
                ["A.register_time",">=",$begin_time],
                ["A.register_time","<=",$end_time],
                ["A.register_channel","=",'HuaWei'],
                ["A.source","=",'ccp'],
            ];

            $uidsList = BiUserStats1DayModel::getInstance()->getModel()
                ->alias('A')->JOIN('bi_channel_huawei B','B.user_id = A.uid')
                ->where($hwwhere)
                ->select()->toArray();
        }

        //$uidsList = Db::connect('adminback')->query($runSql);
        $regUsers = array_column($uidsList, "uid");

        $returnRes['register_user_number'] = count($regUsers); //注册用户数量
        $returnRes['register_users'] = join(",", $regUsers); //注册人员列表

        $enter_users = ParseUserStateDataCommmon::getInstance()->getEnterRoomUsers($begin_time, $end_time, $room_id);

        //注册并进厅用户
        $regEnterroomUsers = array_intersect($enter_users, $regUsers);

        if (empty($regEnterroomUsers)) {
            return [];
        }


        $returnRes['enter_user_number'] = count($regEnterroomUsers); //引流进厅用户数量
        $returnRes['enter_users'] = join(",", $regEnterroomUsers); //引流注册人员列表

        //当天充值金额
        $current_charge_info = BiDaysUserChargeModel::getInstance()->getModel()
            ->field("uid,amount")
            ->where('uid', 'in', $regEnterroomUsers)
            ->where('date', '=', date('Y-m-d', strtotime($begin_time)))
            ->select()->toArray();


        $user_charge_amount = array_sum(array_column($current_charge_info, 'amount')) ?: 0;
        $user_charge_number = count(array_unique(array_column($current_charge_info, 'uid'))) ?: 0;
        $charge_users = join(",", array_unique(array_column($current_charge_info, 'uid'))) ?: 0;


        //累计充值金额
        $sum_charge_info = BiDaysUserChargeModel::getInstance()->getModel()
            ->field("uid,amount")
            ->where('uid', 'in', $regEnterroomUsers)
            ->select()->toArray();

        $user_charge_amount_sum = array_sum(array_column($sum_charge_info, "amount"));
        $user_charge_number_sum = count(array_unique(array_column($sum_charge_info, 'uid'))) ?: 0;


        $returnRes['user_charge_number'] = $user_charge_number ?: 0;
        $returnRes['charge_users'] = $charge_users ?: '';
        $returnRes['user_charge_amount'] = $this->divedFunc($user_charge_amount, 10) ?: 0;
        $returnRes['user_charge_number_sum'] = $user_charge_number_sum ?: 0;
        $returnRes['user_charge_amount_sum'] = $this->divedFunc($user_charge_amount_sum, 10) ?: 0;

        return $returnRes;

    }


    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }


}

<?php

namespace app\admin\script;

use app\admin\model\AnchorCpPromotionModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiDaysUserSendgiftModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberSocityModel;
use app\admin\model\UserLastInfoModel;
use app\common\RabbitMQCommand;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;


class AnchorBindUserSendGiftCommand extends Command
{

    const COMMAND_NAME = "AnchorBindUserSendGiftCommand";

    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        try {
            $pageSize = 1000;
            $page = 1;
            while (true) {

                $sourceList = AnchorCpPromotionModel::getInstance()->getModel()
                    ->page($page, $pageSize)
                    ->where("status", "=", 1)
                    ->where("anchor_id", ">", 0)
                    ->where("user_id", ">", 0)
                    ->order("id desc")
                    ->select()->toArray();

                if (empty($sourceList)) {
                    break;
                }

                foreach ($sourceList as $promoteItem) {
                    $anchor_id = $promoteItem['anchor_id'] ?? 0;
                    $user_id = $promoteItem['user_id'] ?? 0;
                    $promoteid = $promoteItem['id'] ?? 0;
                    $promoteData = []; //需要更新的数据
                    echo $anchor_id . PHP_EOL;

                    $lastLoginTime = UserLastInfoModel::getInstance()->getModel($user_id)
                        ->where("user_id", "=", $user_id)->value("update_time");
                    $promoteData['last_login_time'] = date('Y-m-d H:i:s', $lastLoginTime);

                    //获取用户的基本信息
                    if (empty($promoteItem['register_time'])) {
                        $memberRes = MemberModel::getInstance()->getModel($user_id)->field('id,register_time')->where("id", "=", $user_id)->find();
                        if (empty($memberRes)) {
                            continue;
                        }
                        $promoteData['register_time'] = strtotime($memberRes['register_time']);
                    }

                    //用户注册的日期开始

                    $chargeAmounts = BiDaysUserChargeModel::getInstance()->getModel()
                        ->where("uid", "=", $user_id)
                        ->sum("amount");


                    $promoteData['charge_sum'] = $chargeAmounts;

                    //累计刷礼物的消费
                    $sendGiftAmounts = BiDaysUserSendgiftModel::getInstance()->getModel()
                        ->where("uid", "=",$user_id)
                        ->where("touid", "=",$anchor_id)
                        ->sum("consume_amount");



                    $promoteData['direct_consume_sum'] = $sendGiftAmounts ?: 0;

                    if (empty($promoteItem['guild_id'])) {
                        $promoteData['guild_id'] = MemberSocityModel::getInstance()->getModel()->where('user_id', '=', $anchor_id)
                            ->where('status', '=', 1)->value('guild_id') ?: 0;
                    }
                   AnchorCpPromotionModel::getInstance()->getModel()->where("id", $promoteid)->update($promoteData);
                }
                $page++;
            }

        } catch (\Throwable $e) {
            Log::info(self::COMMAND_NAME.":error:".$e->getMessage());
        }


    }

    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }


}

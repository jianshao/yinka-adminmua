<?php

namespace app\admin\script;
use app\admin\model\BiUserStats1DayModel;
use app\admin\model\BiUserStats5MinsModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;


class SolveCodeLoseCommand extends Command
{
    const COMMAND_NAME = 'SolveCodeLoseCommand'; //解决invitcode丢失问题

    protected function configure()
    {
        // 指令配置
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME)
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d', strtotime("-1 days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d'));
    }


    protected function execute(Input $input, Output $output)
    {
        try {
            $start_time = $input->getArgument('start_time');
            $end_time = $input->getArgument('end_time');
            $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($start_time, $end_time, true);
            $invitcodes =  MarketChannelModel::getInstance()->getModel()->column("invitcode");
            $memberModels = MemberModel::getInstance()->getallModel();
            foreach ($dateList as $dateNode) {
                $startdateNode = $dateNode;
                $enddateNode = date('Y-m-d', strtotime("+1days", strtotime($dateNode)));

                /*$command = "SELECT uid from bi_user_stats_1day where uid in (SELECT id from zb_member where invitcode in
(SELECT invitcode from zb_market_channel) and register_time >= '{$startdateNode}' and register_time < '{$enddateNode}') and promote_channel = 0";
                $res = Db::query($command);*/

                $existsinvitcodeuids=[];

                foreach($memberModels as $memberModel){
                    $data = $memberModel->getModel()->where([
                        ["register_time",">=",$startdateNode],
                        ["register_time","<",$enddateNode],
                        ['invitcode',"in",$invitcodes]
                    ])->column("id");
                    $existsinvitcodeuids = array_merge($existsinvitcodeuids,$data);
                }

                $res = BiUserStats1DayModel::getInstance()->getModel()->field("uid")->where([
                    ['promote_channel','=',0],
                    ["uid","in",$existsinvitcodeuids],
                ])->select()->toArray();

                if ($res) {
                    foreach ($res as $items) {
                        $uid = $items['uid'];
                        $invitcode = MemberModel::getInstance()->getModel($uid)->where("id", $uid)->value("invitcode");
                        $promote_channel = MarketChannelModel::getInstance()->getModel()->where("invitcode", $invitcode)->value("id");
                        if ($promote_channel > 0) {
                            BiUserStats1DayModel::getInstance()->getModel()->where("uid", $uid)
                                ->where("promote_channel", 0)
                                ->update(["promote_channel" => $promote_channel]);

                            BiUserStats5MinsModel::getInstance()->getModel()->where("uid", $uid)
                                ->where("promote_channel", 0)
                                ->update(["promote_channel" => $promote_channel]);
                        }
                    }

                }
            }

        } catch (\Throwable $e) {
            echo $e->getMessage().$e->getLine();
            Log::info(self::COMMAND_NAME . ":error" . $e->getMessage());
        }

    }


}

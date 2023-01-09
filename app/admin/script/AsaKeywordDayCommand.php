<?php

namespace app\admin\script;

use app\admin\model\BiAasByKeywordModel;
use app\admin\model\BiAsaUserModel;
use app\admin\model\BiUserKeepDayModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;


class AsaKeywordDayCommand extends Command
{
    const  UPDATE_TABLE_NAME = 'bi_asa_by_keyword'; //数据表
    const COMMAND_NAME = "AsaKeywordDayCommand";

    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d', strtotime("-1days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d'))
            ->setDescription(self::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        $begin_date = $input->getArgument("start_time");
        $end_date = $input->getArgument("end_time");
        $this->_asacommon($begin_date, $end_date, 0, 'appstore');
        $this->_asacommon($begin_date, $end_date, 0, 'huawei');
    }


    private function _asacommon($begin_date, $end_date, $keywordid = 0, $asaType = 'appstore')
    {

        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($begin_date, $end_date);

        foreach ($dateList as $node) {
            echo $node . PHP_EOL;

            $keywordids = BiAsaUserModel::getInstance()->getModel()
                ->field('iad_keyword_id')
                ->where('date', '=', $node)
                ->where("source", "=", $asaType)
                ->where('iad_keyword_id', '>', 0)
                ->distinct(true)
                ->select()->toArray();


            $register_keep_uids_temp = BiUserKeepDayModel::getInstance()->getModel()->where('date', '=', $node)->where('type', '=', "register")
                ->column('keep_2', 'date');

            $charge_keep_uids_temp = BiUserKeepDayModel::getInstance()->getModel()->where('date', '=', $node)->where('type', 'in', "charge")
                ->column('keep_2', 'date');

            $keywordList = array_column($keywordids, "iad_keyword_id");


            foreach ($keywordList as $keywordid) {


                $register_user = [];
                $register_keep_count = 0;
                $charge_keep_count = 0;
                $register_total = 0;
                $charge_keep_arr = [];
                $register_keep_arr = [];

                $uids = BiAsaUserModel::getInstance()->getModel()
                    ->where('date', '=', $node)
                    ->where('source', '=', $asaType)
                    ->where('iad_keyword_id', '=', $keywordid)
                    ->select()->toArray();

                if ($uids) {
                    $register_user = array_column($uids, "uid");
                    $register_total = count($register_user);
                }


                if (isset($register_keep_uids_temp[$node]) && $register_keep_uids_temp[$node]) {
                    $register_keep_uids_arr = explode(",", $register_keep_uids_temp[$node]);
                    $register_keep_arr = array_intersect($register_user, $register_keep_uids_arr);
                    $register_keep_count = count($register_keep_arr);
                }

                if (isset($charge_keep_uids_temp[$node]) && $charge_keep_uids_temp[$node]) {
                    $charge_keep_uids_arr = explode(",", $charge_keep_uids_temp[$node]);
                    $charge_keep_arr = array_intersect($register_user, $charge_keep_uids_arr);
                    $charge_keep_count = count($charge_keep_arr);
                }

                /*
                 CREATE TABLE `bi_asa_by_keyword` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `date` varchar(50) NOT NULL DEFAULT '' COMMENT '日期',
                    `keyword_id` varchar(50) NOT NULL DEFAULT '' COMMENT '关键词id',
                  `register_uid_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册总人数',
                  `register_keep2_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册总人数',
                  `charge_keep2_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册总人数',
                    `register_uids` longtext COMMENT '注册总人数',
                  `register_keep2_uids` longtext COMMENT '注册总人数',
                  `charge_keep2_uids` longtext COMMENT '注册总人数',
                      PRIMARY KEY (`id`) USING BTREE,
                  UNIQUE KEY `date_keyword_id` (`date`,`keyword_id`) USING BTREE
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COMMENT='asa分关键词统计'*/

                $insertRes = [
                    'date' => $node,
                    "keyword_id" => $keywordid,
                    "register_uid_number" => $register_total, //注册总人数
                    'register_keep2_number' => $register_keep_count, //注册次留总人数
                    'charge_keep2_number' => $charge_keep_count, //充值次留总人数
                    'register_uids' => join(",", $register_user), //注册用户列表
                    'register_keep2_uids' => join(",", $register_keep_arr), //注册留存列表
                    'charge_keep2_uids' => join(",", $charge_keep_arr), //充值留存用户列表
                    'asatype' => $asaType, //asa的类型
                ];
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(
                    BiAasByKeywordModel::getInstance()->getModel(),
                    [$insertRes],
                    ["date", "keyword_id", "id"]
                );
            }


        }

    }


}

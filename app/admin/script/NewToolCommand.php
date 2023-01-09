<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\common\ApiUrlConfig;
use app\admin\common\ParseUserState;
use app\admin\model\ActiveModel;
use app\admin\model\AdminUserModel;
use app\admin\model\AnchorCpPromotionModel;
use app\admin\model\AssetLogModel;
use app\admin\model\BiAasByKeywordModel;
use app\admin\model\BiAnchorCpSendGiftModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiUserKeepDayModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\model\BlackDataModel;
use app\admin\model\BlackLogModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\CheckImMessageModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberSocityModel;
use app\admin\model\MenuModel;
use app\admin\model\PackModel;
use app\admin\model\RoomCloseModel;
use app\admin\model\RoomHideModel;
use app\admin\model\UserAssetLogModel;
use app\admin\model\UserLastInfoModel;
use app\admin\model\UserWithdrawalModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ApiService;
use app\admin\service\ConfigService;
use app\admin\service\ElasticsearchService;
use app\admin\service\MarketChannelService;
use app\admin\service\WithdrawalService;
use app\common\Ip2Region;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use Elasticsearch\ClientBuilder;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class NewToolCommand extends Command
{

    const COMMAND_NAME = "NewToolCommand";

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        $this->userLoginDetail();
    }


    public function vipEveryDay()
    {

        //日期  开通vip人数  开通svip人数   开通vip金额  开通svip总额   vip日活  svip日活

        /**
         * CREATE TABLE `temp_vip_daily` (
         * `date` varchar(40) NOT NULL default ''  COMMENT '日期',
         * `vip` int(11) NOT NULL DEFAULT '0' COMMENT 'vip人数',
         * `svip` int(11) NOT NULL DEFAULT '0' COMMENT 'svip人数',
         * `vip_rmb` varchar(20) NOT NULL DEFAULT '0' COMMENT 'vip rmb',
         * `svip_rmb` varchar(20) NOT NULL DEFAULT '0' COMMENT 'vip rmb',
         * `vip_active` int(11) NOT NULL DEFAULT '0' COMMENT 'vip日活',
         * `svip_active` int(11) NOT NULL DEFAULT '0'  COMMENT 'svip日活',
         * KEY `date` (`date`)
         * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='vip基本数据'
         */

        /**
         * select
         * date as '日期',
         * vip as '开通VIP人数',
         * svip as '开通SVIP人数',
         * vip_rmb as 'vip充值',
         * svip_rmb as 'svip充值',
         * vip_active as 'vip 日活',
         * svip_active as 'svip日活',
         * active as '平台日活'
         * from  temp_vip_daily
         */

        $dateNodes = ParseUserStateDataCommmon::getInstance()->getTimeNode('2022-05-10', '2022-05-11');

        foreach ($dateNodes as $node) {
            $vipUidList = $svipUidList = $vip_active = $svip_active = [];
            $vip_rmb = 0;
            $svip_rmb = 0;
            echo $node . PHP_EOL;
            $chargeDetail = ChargedetailModel::getInstance()->getModel()
                ->where("addtime", ">=", $node . " 00:00:00")
                ->where("addtime", "<", date('Y-m-d 00:00:00', strtotime($node . " +1days")))
                ->where("status", "in", [1, 2])
                ->where("type", "in", [2, 3])
                ->select()->toArray();
            foreach ($chargeDetail as $chargeDetailItem) {
                if ($chargeDetailItem['type'] == 2) {
                    $vip_rmb += $chargeDetailItem['rmb'];
                    $vipUidList[] = $chargeDetailItem['uid'];
                }

                if ($chargeDetailItem['type'] == 3) {
                    $svip_rmb += $chargeDetailItem['rmb'];
                    $svipUidList[] = $chargeDetailItem['uid'];
                }
            }

            $membervipEs = ElasticsearchService::getInstance()->index("zb_member");
            $membervipEs->fields(['id', 'vip_exp', 'svip_exp']);
            $membervipEs->range("vip_exp", ['gte' => strtotime($node)]);
            $membervipEs->page(1, 20000);
            $res = $membervipEs->select();
            $vip_uids = array_column($res['data'], "id");
            $source = BiUserKeepDayModel::getInstance()->where('type', "=", "active")
                ->where("date", "=", $node)->value("source");
            if ($vip_uids) {
                $vip_active = ParseUserState::getInstance()->strIntersect(join(",", array_unique($vip_uids)), $source);
            }


            $membersvipEs = ElasticsearchService::getInstance()->index("zb_member");
            $membersvipEs->fields(['id', 'vip_exp', 'svip_exp']);
            $membersvipEs->range("svip_exp", ['gte' => strtotime($node)]);
            $membersvipEs->page(1, 20000);
            $res = $membersvipEs->select();
            $svip_uids = array_column($res['data'], "id");
            if ($svip_uids) {
                $svip_active = ParseUserState::getInstance()->strIntersect(join(",", array_unique($svip_uids)), $source);
            }

            $data = [
                "date" => $node,
                "vip" => count(array_unique($vipUidList)),
                "svip" => count(array_unique($svipUidList)),
                'vip_rmb' => $vip_rmb,
                'svip_rmb' => $svip_rmb,
                'active' => count(explode(",", $source)),
                'vip_active' => count($vip_active),
                'svip_active' => count($svip_active)
            ];


            Db::table("temp_vip_daily")->insert($data);

        }


    }


    /**
     * 封禁用户
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userblackData()
    {

        /**
         *
         * select
         * user_id as '用户id',
         * FROM_UNIXTIME(blacks_time) as '封禁时间',
         * reason as '封禁原因',
         * lv_dengji as '用户等级',
         * case sex when 1  then  '男' when 2 then '女' when 3 then '保密' else '' end as '用户性别',
         * case shiming when 1 then '已实名' else '' end as '是否实名',
         * deviceid  as '最后登录的deviceid',
         * case when time=-1 then '永封' when end_time>0 then CONCAT('到期时间:',FROM_UNIXTIME(end_time),time)  end  as '性质'
         * from temp_black_data
         *
         *
         */

        $start_time = strtotime('2022-08-01');
        $end_time = strtotime('2022-08-08');
        $res = BlackDataModel::getInstance()->getModel()->where("user_id", ">", 0)
            ->where("blacks_time", ">=", $start_time)
            ->where("blacks_time", "<", $end_time)
            ->where("status", "=", 1)->select()->toArray();

        Db::table("temp_black_data")->insertAll($res);

        $blackData = Db::table("temp_black_data")->select()->toArray();

        foreach ($blackData as $key => $item) {
            echo $key . PHP_EOL;
            $update = [];
            $user_id = $item['user_id'];
            $update['deviceid'] = UserLastInfoModel::getInstance()->getModel($user_id)->where("user_id", $user_id)->value("deviceid");
            $memberInfo = MemberModel::getInstance()->getModel($user_id)->where("id", $user_id)->field("id,sex,lv_dengji,attestation")->find();
            $update['sex'] = $memberInfo['sex'] ?? '';
            $update['lv_dengji'] = $memberInfo['lv_dengji'] ?? '';
            if ($memberInfo['attestation'] == 1) {
                $update['shiming'] = 1;
            }

            Db::table("temp_black_data")->where("user_id", $user_id)->update($update);
        }

        dd("complete success");
    }


    public function userLoginDetail()
    {
        //脚本需求
        //1.6月和7月，登录过平台的用户，设备和版本的情况 表头：用户ID  device  version  IP归属省份
        /*
                 CREATE TABLE `temp_user_last_login` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户',
                `login_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '注册渠道',
                `channel` varchar(50) NOT NULL DEFAULT '',
                `device` varchar(50) NOT NULL DEFAULT '',
                `platform` varchar(50) NOT NULL DEFAULT '',
                `version` varchar(50) NOT NULL DEFAULT '',
                `province` varchar(50) NOT NULL DEFAULT '',
                `date`  varchar(50) NOT NULL DEFAULT '',
                PRIMARY KEY (`id`),
                UNIQUE KEY `uid` (`uid`)
                ) ENGINE=InnoDB   DEFAULT CHARSET=utf8mb4
         * */

        $dateNode = ParseUserStateDataCommmon::getInstance()->getTimeNode('2022-06-01', '2022-08-01');
        foreach ($dateNode as $node) {
            echo $node . PHP_EOL;
            $data = BiUserStats1DayModel::getInstance()->getModel()->field("uid")->where('date', '=', $node)->select()->toArray();
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul($data, "temp_user_last_login", ['uid', "id"]);
        }

        Db::table("temp_user_last_login")->chunk(5000, function ($data) {
            // login_ip,channel,device,platform,version,deviceid,user_id
            foreach ($data as $item) {
                echo $item['id'] . PHP_EOL;
                $insert = [];
                $uid = $item['uid'];
                $lastinfo = UserLastInfoModel::getInstance()->getModel($uid)->where("user_id", $uid)->find();
                $insert['uid'] = $uid;
                $insert['login_ip'] = $lastinfo['login_ip'] ?? '';
                $insert['channel'] = $lastinfo['channel'] ?? '';
                $insert['device'] = $lastinfo['device'] ?? '';
                $insert['platform'] = $lastinfo['platform'] ?? '';
                $insert['platform'] = $lastinfo['platform'] ?? '';
                $insert['version'] = $lastinfo['version'] ?? '';
                $insert['deviceid'] = $lastinfo['deviceid'] ?? '';
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul([$insert], "temp_user_last_login", ["uid", "id"]);
            }
        });

    }



    //今年来参与打地鼠的游戏的男女比例
    public function gopherUserList()
    {
        /**
         * CREATE TABLE `temp_activity` (
        `date` varchar(40) NOT NULL DEFAULT '' COMMENT '日期',
        `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
        `sex` tinyint(4) NOT NULL DEFAULT '0',
        KEY `date` (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8m
         */
        $dateNodes = ParseUserStateDataCommmon::getInstance()->getTimeNode('2022-01-01', '2022-08-01');
        foreach ($dateNodes as $node) {
            echo $node['id'] . $node['date'] . PHP_EOL;
            $insertData = [];
            $res = BiUserStats1DayModel::getInstance()->getModel()->where('date', $node)->select()->toArray();
            foreach ($res as $item_uid) {
                $params = json_decode($item_uid['json_data'], true);
                $activity = $params['activity'];
                foreach ($activity as $item) {
                    if (array_key_exists("gopher", $item)) {
                        $insertData[] = ['uid' => $node['uid'], "date" => $node['date']];
                        break;
                    }
                }
            }
            Db::table("temp_activity")->insertAll($insertData);
        }
    }


}

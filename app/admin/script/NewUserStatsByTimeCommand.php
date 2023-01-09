<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\model\BiUserStats1DayModel;
use app\admin\model\BiUserStats5MinsModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MemberWithdrawalModel;
use app\admin\model\SyncDataConfModel;
use app\admin\model\UserAssetLogModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class NewUserStatsByTimeCommand extends Command
{
    const SYNCID = 25;
    const COMMAND_NAME = "NewUserStatsByTimeCommand";

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        $this->dealById();
    }

    public function dealById()
    {
        //前闭后开

        $start_timestamp = $this->getBaseDataSyncList();
        $end_timestamp = strtotime("-5minutes");

//        $start_timestamp = $this->getBaseDataSyncList();
//        $end_timestamp = strtotime("+2hours", $start_timestamp);

        if ($start_timestamp >= $end_timestamp) {
            Log::info(SELF::COMMAND_NAME . "start_timestamp and end_timestamp setting fail");
        }

        $b_m = date('Y-m', $start_timestamp);
        $e_m = date('Y-m', $end_timestamp);
        //以下的逻辑 开始日期与截止日期是跨月的 则设置截止日期是月初第一天
        if ($b_m != $e_m) { //跨月判断
            $paramdate = date('Y-m-01 00:00:00', strtotime($b_m . "+1months"));
            $end_timestamp = strtotime($paramdate);
        }

        $redis = RedisCommon::getInstance()->getRedis(['select' => 8]);
        $commandLockName = SELF::COMMAND_NAME . ":Lock";
        //防止定时任务重复调用
        if (!$redis->set($commandLockName, 1, ["nx", "ex" => 3600])) {
            Log::info(SELF::COMMAND_NAME . ":lock exists");
            return;
        }

        try {
            $this->serviceHandler($start_timestamp, $end_timestamp);
        } catch (\Throwable $e) {
            Log::error(SELF::COMMAND_NAME . ":error" . $e->getMessage() . "getLine:".$e->getLine() . $e->getFile());
        } finally {
            $redis->del($commandLockName);
        }
    }

    public function serviceHandler($start_node, $end_node)
    {
        $behaviorMin = new AnslysisUserBehavior(); //创建解析容器
        $behaviorDay = new AnslysisUserBehavior();
        //$assetLogRecord = []; //记录预处理的zb_user_asset_log的ID

        $this->calLogin($start_node, $end_node, $behaviorMin, $behaviorDay);
        $this->calUserAssetLog($start_node, $end_node, $behaviorMin, $behaviorDay);
        $this->calWithdraw($start_node, $end_node, $behaviorMin, $behaviorDay);
        $this->calVip($start_node, $end_node, $behaviorMin, $behaviorDay);

        //合并数据
        $this->mergeHandler($behaviorMin, $behaviorDay);
        //更新数据
        $arrDataByMin = $behaviorMin->objectToArray(); //5分钟的数据
        $arrdataByDay = $behaviorDay->objectToArray(); //1天的数据
        $arrDataByMinRepeat = [];
        $arrdataByDayRepeat = [];

        foreach ($arrDataByMin as $repeatkey => $repeatitem) {
            foreach ($repeatitem as $key => $item) {
                if (array_filter($item)) {
                    $arrDataByMinRepeat[$repeatkey][$key] = $item;
                }
            }
        }

        foreach ($arrdataByDay as $repeatkey => $repeatitem) {
            foreach ($repeatitem as $key => $item) {
                if (array_filter($item)) {
                    $arrdataByDayRepeat[$repeatkey][$key] = $item;
                }
            }
        }
        //最终入库的数据
        $completeData = $this->updateHandleData($arrDataByMinRepeat, $arrdataByDayRepeat);
        // 启动事务
        Db::startTrans();
        try {
            $isok = SyncDataConfModel::getInstance()->getModel()->where("id", SELF::SYNCID)->update(["sync_id" => $end_node]);
            $userstats5minsmodel = BiUserStats5MinsModel::getInstance()->getModel();
            $userstats1daymodel = BiUserStats1DayModel::getInstance()->getModel();
            $m_ok = ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($userstats5minsmodel, $completeData['mintue'], ["uid", "interval_time", "date", "id"]);
            $d_ok = ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($userstats1daymodel, $completeData['day'], ["uid", "date", "id"]);
            if (false === $d_ok || false === $isok || false === $m_ok) {
                throw new Exception(SELF::COMMAND_NAME . ":operation data error");
            }
            Db::commit();
        } catch (\Exception $e) {
            Log::error(SELF::COMMAND_NAME . ":error" . $e->getMessage() . "getLine:".$e->getLine() . $e->getFile());
            Db::rollback();
        }

    }

    /**数据的合并
     * @param $dataByMin
     * @param $dataByDay
     */
    public function mergeHandler(&$dataByMin, &$dataByDay)
    {
        foreach ($dataByMin->dataMap as $key_min => $item_min) {
            $params = ParseUserStateDataCommmon::getInstance()->identifySplit($key_min);
            $uid = $params[1];
            $interval_time = $params[0];
            $res = $this->haveHistoryDataMin($uid, $interval_time);
            if ($res) {
                $data = json_decode($res['json_data'], true);
                $dataByMin->mergeObject($data, $key_min);
            }
        }

        foreach ($dataByDay->dataMap as $key_day => $item_day) {
            $params = ParseUserStateDataCommmon::getInstance()->identifySplit($key_day);
            $uid = $params[1];
            $date = $params[0];
            $res = $this->haveHistoryDataDay($uid, $date);
            if ($res) {
                $data = json_decode($res['json_data'], true);
                $dataByDay->mergeObject($data, $key_day);
            }
        }
    }

    public function calLogin($start_node, $end_node, &$behaviorMin, &$behaviorDay)
    {
        echo "calLogin::runing:{$start_node} -- {$end_node}" . PHP_EOL;
        $dataList = $this->dealUserLoginById($start_node, $end_node);
        if ($dataList) {
            foreach ($dataList as $item) {
                $uid = $item['uid'];
                $interval_time = ParseUserStateDataCommmon::getInstance()->getIntervalTime(date('Y-m-d H:i:s', $item['login_time']));
                $identifyByMin = ParseUserStateDataCommmon::getInstance()->identifyMerge($interval_time, $uid);
                $identifyByDay = ParseUserStateDataCommmon::getInstance()->identifyMerge(date('Y-m-d', strtotime($interval_time)), $uid);
                $behaviorMin->analyLogin($item, $identifyByMin);
                $behaviorDay->analyLogin($item, $identifyByDay);
                if (empty($uid)) {
                    Log::error(SELF::COMMAND_NAME . "-calLogin:uid:为空  primary-id:{$item['id']}  {$identifyByMin} -- {$identifyByDay}   time:" . date('Y-m-d H:i:s'));
                }
            }
        }
    }

    public function calWithdraw($start_node, $end_node, &$behaviorMin, &$behaviorDay)
    {
        echo "calWithdraw::runing:{$start_node}--{$end_node}" . PHP_EOL;
        $dataList = $this->dealWithdrawById($start_node, $end_node);
        if ($dataList) {
            foreach ($dataList as $item) {
                $uid = $item['uid'];
                $interval_time = ParseUserStateDataCommmon::getInstance()->getIntervalTime(date('Y-m-d H:i:s', $item['created_time']));
                $identifyByMin = ParseUserStateDataCommmon::getInstance()->identifyMerge($interval_time, $uid);
                $identifyByDay = ParseUserStateDataCommmon::getInstance()->identifyMerge(date('Y-m-d', strtotime($interval_time)), $uid);
                $behaviorMin->analyWithdraw($item, $identifyByMin);
                $behaviorDay->analyWithdraw($item, $identifyByDay);
                if (empty($uid)) {
                    Log::error(SELF::COMMAND_NAME . "-calWithdraw:uid:为空  primary-id:{$item['id']}   {$identifyByMin} -- {$identifyByDay}  time:" . date('Y-m-d H:i:s'));
                }
            }
        }
    }

    public function calVip($start_node, $end_node, &$behaviorMin, &$behaviorDay)
    {
        echo "calVip:runing:{$start_node} -- {$end_node}" . PHP_EOL;
        $res = $this->dealVipChargeData($start_node, $end_node);

        if ($res) {
            foreach ($res as $item) {
                $uid = $item['uid'];
                $interval_time = ParseUserStateDataCommmon::getInstance()->getIntervalTime(date('Y-m-d H:i:s', $item['finish_time']));
                $identifyByMin = ParseUserStateDataCommmon::getInstance()->identifyMerge($interval_time, $uid);
                $identifyByDay = ParseUserStateDataCommmon::getInstance()->identifyMerge(date('Y-m-d', strtotime($interval_time)), $uid);
                $behaviorMin->analyCharge($item, $identifyByMin);
                $behaviorDay->analyCharge($item, $identifyByDay);
                if (empty($uid)) {
                    Log::error(SELF::COMMAND_NAME . "-calVip:uid:为空  primary-id:{$item['id']}  {$identifyByMin} -- {$identifyByDay}  time:" . date('Y-m-d H:i:s'));
                }
            }
        }
    }

    public function calUserAssetLog($start_node, $end_node, &$behaviorMin, &$behaviorDay)
    {
        echo "calUserAssetLog:runing:{$start_node} -- {$end_node}" . date('Y-m-d H:i:s', $end_node) . PHP_EOL;
        $res = $this->dealUserAssetLog($start_node, $end_node);
        if ($res) {
            foreach ($res as $item) {
                $uid = $item['uid'];
                $interval_time = ParseUserStateDataCommmon::getInstance()->getIntervalTime(date('Y-m-d H:i:s', $item['success_time']));
                $identifyByMin = ParseUserStateDataCommmon::getInstance()->identifyMerge($interval_time, $uid);
                $identifyByDay = ParseUserStateDataCommmon::getInstance()->identifyMerge(date('Y-m-d', strtotime($interval_time)), $uid);
                $event_id = $item['event_id'];
                $asset_id = $item['asset_id'];
                $type = $item['type'];

                if (empty($uid)) {
                    Log::error(SELF::COMMAND_NAME . "-calUserAssetLog:uid:为空  primary-id:{$item['id']} {$identifyByMin} -- {$identifyByDay}  time:" . date('Y-m-d H:i:s'));
                }

                //$assetLogRecord[] = ["log_id" => $item['id'], 'success_time' => $item['success_time'], "uid" => $uid]; //记录预处理的id值

                if ($event_id == 10014 && $asset_id == 'bean') {
                    $behaviorMin->analyAgentCharge($item, $identifyByMin);
                    $behaviorDay->analyAgentCharge($item, $identifyByDay);
                }

                if ($event_id == 10001 && $type == 4) {
                    $chargeinfo = ParseUserStateDataCommmon::getInstance()->dealChargeData($item['ext_1']);
                    $behaviorMin->analyCharge($chargeinfo, $identifyByMin);
                    $behaviorDay->analyCharge($chargeinfo, $identifyByDay);
                    $firstcharge = ParseUserStateDataCommmon::getInstance()->dealFirstChargeData($uid);
                    if ($firstcharge) {
                        $behaviorMin->analyFirstcharge($firstcharge, $identifyByMin);
                        $behaviorDay->analyFirstcharge($firstcharge, $identifyByDay);
                    }
                }

                if ($event_id == 10002 && in_array($type, [3, 4])) {
                    $behaviorMin->analySendUserGift($item, $identifyByMin);
                    $behaviorDay->analySendUserGift($item, $identifyByDay);
                    $new_identifybyMin = ParseUserStateDataCommmon::getInstance()->identifyMerge($interval_time, $item['touid']);
                    $new_identifyByDay = ParseUserStateDataCommmon::getInstance()->identifyMerge(date('Y-m-d', strtotime($interval_time)), $item['touid']);
                    $behaviorMin->analyReceiveGift($item, $new_identifybyMin);
                    $behaviorDay->analyReceiveGift($item, $new_identifyByDay);
                    if (empty($item['touid'])) {
                        Log::error(SELF::COMMAND_NAME . "-calUserAssetLog:event_id:10002 uid:为空  primary-id:{$item['touid']} {$new_identifybyMin} -- {$new_identifyByDay}  time:" . date('Y-m-d H:i:s'));
                    }

                }
                if ($event_id == 10010) {
                    $item['count'] = (int) $item['ext_2'];
                    $behaviorMin->analySendRedPackage($item, $identifyByMin);
                    $behaviorDay->analySendRedPackage($item, $identifyByDay);
                }
                if ($event_id == 10012) {
                    $item['count'] = $item['ext_2'];
                    $behaviorMin->analyReceiveRedPackage($item, $identifyByMin);
                    $behaviorDay->analyReceiveRedPackage($item, $identifyByDay);
                }
                if ($event_id == 10013) {
                    $item['count'] = $item['ext_2'];
                    $behaviorMin->analyReturnRedPackage($item, $identifyByMin);
                    $behaviorDay->analyReturnRedPackage($item, $identifyByDay);
                }

                if ($event_id == 10009) {
                    $behaviorMin->analyActivity($item, $identifyByMin);
                    $behaviorDay->analyActivity($item, $identifyByDay);
                }

                if ($asset_id == 'diamond' && !in_array($event_id, [10015, 10016, 10017]) && in_array($type, [5])) {
                    $behaviorMin->analyDiamond($item, $identifyByMin);
                    $behaviorDay->analyDiamond($item, $identifyByDay);
                }
            }
        }

    }

    public function updateHandleData($dataByMin, $dataByDay)
    {
        $identify = array_keys($dataByMin);
        $uids = [];
        foreach ($identify as $item) {
            $parsekey = explode("#", $item);
            if (isset($parsekey[1])) {
                $uids[] = $parsekey[1];
            }
        }
        //参数true 读取主库的信息
        $userinfo = ParseUserStateDataCommmon::getInstance()->getUserBaseInfo($uids, false);
        Log::info("newuserstatsbytimecommand:getuserinfo:" . json_encode($userinfo));
        $insertDataByMin = [];
        $insertDataByDate = [];

        foreach ($dataByMin as $key => $item) {
            $params = ParseUserStateDataCommmon::getInstance()->identifySplit($key);
            $uid = $params[1] ?? 0;
            $interval_time = $params[0] ?? '';
            if (empty($uid) || empty($interval_time)) {
                continue;
            }

            $item['register'] = $userinfo[$uid] ?? [];
            $insertDataByMin[] = [
                "uid" => $uid,
                "date" => date('Y-m-d', strtotime($interval_time)),
                "interval_time" => $interval_time,
                "register_channel" => $userinfo[$uid]['register_channel'] ?? '',
                "register_time" => $userinfo[$uid]['register_time'] ?? '',
                "source" => $userinfo[$uid]['source'] ?? '',
                "promote_channel" => $userinfo[$uid]['promote_channel'] ?? '',
                "json_data" => json_encode($item),
            ];
        }

        foreach ($dataByDay as $key => $item) {
            $params = ParseUserStateDataCommmon::getInstance()->identifySplit($key);
            $uid = $params[1] ?? 0;
            $interval_time = $params[0] ?? '';
            if (empty($uid) || empty($interval_time)) {
                continue;
            }
            $item['register'] = $userinfo[$uid] ?? [];
            $insertDataByDate[] = [
                "uid" => $uid,
                "date" => date('Y-m-d', strtotime($interval_time)),
                "register_channel" => $userinfo[$uid]['register_channel'] ?? '',
                "register_time" => $userinfo[$uid]['register_time'] ?? '',
                "source" => $userinfo[$uid]['source'] ?? '',
                "promote_channel" => $userinfo[$uid]['promote_channel'] ?? '',
                "json_data" => json_encode($item),
            ];
        }
        return ["mintue" => $insertDataByMin, "day" => $insertDataByDate];
    }

    ///读取5分钟的数据
    public function haveHistoryDataMin($uid, $interval_time)
    {
        return BiUserStats5MinsModel::getInstance()->getModel()
            ->where('uid', $uid)
            ->where('interval_time', $interval_time)
            ->find();
    }

    //读取一天的数据
    public function haveHistoryDataDay($uid, $date)
    {
        return BiUserStats1DayModel::getInstance()->getModel()
            ->where('uid', $uid)
            ->where('date', $date)
            ->find();
    }

    //获取同步的配置数据
    public function getBaseDataSyncList(): int
    {
        /* return Db::connect(self::DATABASECONFIG)
        ->table(self::TABLENAME_SYNCDATA)->where('id', 25)->value("sync_id");*/

        return SyncDataConfModel::getInstance()->getModel()->where('id', SELF::SYNCID)->value("sync_id");
    }

    /*
     * 登陆数据
     */
    public function dealUserLoginById($start_node, $end_node)
    {
        //$start_node $end_node 为时间戳
        //因为要兼容数据所以我要先计算出所有的用户uid
        $searchwhere = [['ctime',">=",$start_node],['ctime','<',$end_node]];
        $uidsList = LogindetailModel::getInstance()->getWhereAllData($searchwhere,"user_id");
        $uids = array_column($uidsList,"user_id");
        $res = [];
        $models = LogindetailModel::getInstance()->getModels($uids);
        foreach ($models as $model) {
            $data = $model->getModel()->field("id,user_id as uid,channel,device_id as deviceId,mobile_version,idfa,ctime as  login_time")
                ->where("user_id","in",$model->getList())
                ->where('ctime', '>=', $start_node)
                ->where('ctime', '<', $end_node)
                ->select()
                ->toArray();
            $res = array_merge($res, $data);
        }
        return $res;
    }

    public function dealUserAssetLog($start_node, $end_node)
    {
        $start_date = date('Y-m-d H:i:s', $start_node);
        $end_date = date('Y-m-d H:i:s', $end_node);
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start_date, $end_date);
        //$models = UserAssetLogModel::getInstance($instance)->getallModel();
        //因为要兼容数据所以我要先计算出所有的用户uid
        $searchwhere=[["success_time",">=",$start_node],["success_time","<",$end_node]];
        $uidsList = UserAssetLogModel::getInstance($instance)->getWhereAllData($searchwhere,"uid");
        $uids = array_column($uidsList,"uid");
        $res = [];
        $models = UserAssetLogModel::getInstance($instance)->getModels($uids);
        foreach ($models as $model) {
            $data = $model->getModel()
                ->where("uid","in",$model->getList())
                ->where('success_time', '>=', $start_node)
                ->where("success_time", "<", $end_node)
                ->field('*,abs(change_amount) as change_amount,success_time,ext_1 as gift_id, ext_2, ext_3 as count,change_amount as change_amount_real')
                ->select()->toArray();
            $res = array_merge($res, $data);
        }
        return $res;
    }

    /*
     * VIP充值数据
     */
    public function dealVipChargeData($start_node, $end_node)
    {
        return ChargedetailModel::getInstance()->getModel()
            ->where('finish_time', '>=', $start_node)
            ->where('finish_time', '<', $end_node)
            ->where('status', 2)
            ->where('type', 'in', [2, 3])
            ->field('id,uid,channel,type,(rmb * 10) as amount,1 as count,finish_time') //单位:豆
            ->select()
            ->toArray();
    }

    /*
     * 兑换数据
     */
    public function dealWithdrawById($start_node, $end_node)
    {
        return MemberWithdrawalModel::getInstance()->getModel()
            ->where('created_time', '>=', $start_node)
            ->where('created_time', '<', $end_node)
            ->field('id, uid, status, money as amount,created_time')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

}

<?php

namespace app\admin\script;

use app\admin\model\BiMessageOnmicModel;
use app\admin\model\BiUserOnlineMicModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\SyncDataConfModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;


//统计每天的日常数据量 日活 新增 充值人数 充值总金额
class UserRoomOnMicCommand extends Command
{
    const  COMMAND_NAME = 'UserRoomOnMicCommand';
    const  SYNCID = 29;


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }


    protected function execute(Input $input, Output $output)
    {
        try {
            $start_timestamp = $this->getBaseDataSyncList();
            $end_timestamp = strtotime("-5minutes");
            if ($start_timestamp >= $end_timestamp) {
                Log::error(SELF::COMMAND_NAME . "start_timestamp and end_timestamp setting fail");
            }
            //获取同步信息里面的
            $b_m = date('Y-m', $start_timestamp);
            $e_m = date('Y-m', $end_timestamp);
            //以下的逻辑 开始日期与截止日期是跨月的 则设置截止日期是月初第一天
            if ($b_m != $e_m) { //跨月判断
                $paramdate = date('Y-m-01 00:00:00', strtotime($b_m . "+1months"));
                $end_timestamp = strtotime($paramdate);
            }
            $result = $this->serviceHandler($start_timestamp, $end_timestamp);
            Sharding::getInstance()->getConnectModel('bi', '')->transaction(function () use ($result, $end_timestamp) {
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiUserOnlineMicModel::getInstance()->getModel(), $result, ["user_id", "id"]);
                SyncDataConfModel::getInstance()->getModel()->where('id',SELF::SYNCID)->update(["sync_id" => $end_timestamp]);
            });
        } catch (\Throwable $e) {
            Log::error(self::COMMAND_NAME . ":error" . $e->getMessage());
        }
    }


    //获取同步的配置数据
    public function getBaseDataSyncList(): int
    {
        return SyncDataConfModel::getInstance()->getModel()->where('id', SELF::SYNCID)->value("sync_id");
    }


    public function serviceHandler($start_timestamp, $end_timestamp)
    {
        $where = [];
        $where[] = ['ctime', '>=', $start_timestamp];
        $where[] = ['ctime', '<', $end_timestamp];
        $where[] = ['type', '=', 'user_leave_mic'];
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName(date('Y-m-d H:i:s', $start_timestamp), date('Y-m-d H:i:s', $end_timestamp));
        $messageLeaveMicList = BiMessageOnmicModel::getInstance($instance)->getModel()->where($where)->select()->toArray();
        foreach ($messageLeaveMicList as $params) {
            if (isset($params['uid']) && empty($params['uid'])) {
                continue;
            }
            $room_id = $params['room_id'] ?? 0;
            $uid = $params['uid'] ?? 0;
            $roomRes = LanguageroomModel::getInstance()->getModel($room_id)->where('id', $room_id)->find();
            $is_owner = ($uid == $roomRes['user_id'] ? 1 : 0);
            $duration = $params['duration'] ?? 0;
            $logTime = $params['ctime'] ?? 0;
            //如果在麦时长是跨天的 把每天的节点都计算出来
            $getdateNodeList = $this->getdateNode($logTime - $duration, $duration);
            foreach ($getdateNodeList as $nodeItem) {
                $insertdata[] = [
                    "date" => $nodeItem['date'],
                    "uid" => $uid,
                    "micid" => $params['micid'] ?? 0,
                    "room_id" => $params['room_id'] ?? 0,
                    "duration" => $nodeItem['duration'],
                    "guild_id" => $roomRes['guild_id'] ?: 0,
                    "is_owner" => $is_owner,
                    "logTime" => $params['ctime'] ?? 0,
                ];
            }
        }
        return $insertdata;

    }


    /**
     * @param $startnode
     * @param $dur
     * @return array
     */
    public function getdateNode($startnode, $dur)
    {
        $returnRes = [];
        $start_node = $startnode;
        while (true) {
            $limit = strtotime(date('Y-m-d 00:00:00', strtotime("+1days", $start_node))) - $start_node;
            if (($dur - $limit) > 0) {
                $returnRes[] = [
                    "date" => date('Y-m-d', $start_node),
                    "duration" => $limit,
                ];
                $dur = $dur - $limit;
                $start_node = strtotime("+1days", strtotime(date('Ymd', $start_node)));

            } else {
                $returnRes[] = [
                    "date" => date('Y-m-d', $start_node),
                    "duration" => $dur,
                ];
                break;
            }
        }
        return $returnRes;
    }


}

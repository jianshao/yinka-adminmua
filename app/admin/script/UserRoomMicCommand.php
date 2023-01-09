<?php

namespace app\admin\script;

use app\admin\model\BiRoomActionModel;
use app\admin\model\BiUserOnlineMicModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\SyncDataConfModel;
use app\common\ParseUserStateByUniqkey;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;


//统计每天的日常数据量 日活 新增 充值人数 充值总金额
class UserRoomMicCommand extends Command
{


    const  UPDATE_TABLE_NAME_TARGET = 'bi_user_online_mic'; //目标数据表
    const  UPDATE_TABLE_NAME_SOURCE = 'bi_room_action'; //消息队列写入目标数据表
    const  SYNC_CONF_TABLE = 'sync_data_conf';
    const  COMMAND_NAME = 'UserRoomMicCommand';
    const  MAXLIMIT = 3000;


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }


    protected function execute(Input $input, Output $output)
    {
        //获取同步信息里面的
        $redis = RedisCommon::getInstance()->getRedis(['select' => 8]);
        $commandLockName = SELF::COMMAND_NAME . ":Lock";
        //防止定时任务重复调用
        if (!$redis->set($commandLockName, 1, ["nx", "ex" => 120])) {
            Log::info(SELF::COMMAND_NAME . ":lock exists");
            return;
        }
        $sync_table = SyncDataConfModel::getInstance()->getModel()->where("deal_func", 'user_online_mic')->find();
        $where = [];
        $where[] = ['id', '>', $sync_table['sync_id']];
        $where[] = ['type', '=', 'user_leave_mic'];
        $page = 1;
        $res = $this->getDataSource($where, $page);;
        while ($res) {
            $insertdata = [];
            $max_id = max(array_column($res, "id"));
            foreach ($res as $item) {

                if (isset($item['user_id']) && empty($item['user_id'])) {
                    continue;
                }

                $params = json_decode($item['content'], true);

                if (empty($params)) {
                    continue;
                }

                $room_id = $params['roomId'] ?? 0;
                $uid = $params['userId'] ?? 0;
                $roomRes = LanguageroomModel::getInstance()->getModel($room_id)->where('id', $room_id)->find();
                if (empty($roomRes)) {
                    continue;
                }

                $is_owner = 0;

                if ($uid == $roomRes['user_id']) {
                    $is_owner = 1; //房主
                }

                $duration = $params['duration'] ?? 0;
                $logTime = $params['logTime'] ?? 0;
                //如果在麦时长是跨天的 把每天的节点都计算出来
                $getdateNodeList = $this->getdateNode($logTime - $duration,$duration);
                foreach($getdateNodeList as $nodeItem){
                    $insertdata[] = [
                        "date" => $nodeItem['date'],
                        "uid" => $uid,
                        "micid" => $params['micId'] ?? 0,
                        "room_id" => $params['roomId'] ?? 0,
                        "duration" => $nodeItem['duration'],
                        "guild_id" => $roomRes['guild_id'] ?: 0,
                        "is_owner" => $is_owner,
                        "logTime" => $params['logTime'] ?? 0,
                    ];
                }

            }
            //bi_user_online_mic

            // 启动事务
            Db::startTrans();
            try {
                if ($insertdata) {
                   // $this->insertOrUpdateMul($insertdata, self::UPDATE_TABLE_NAME_TARGET, ["user_id", "id"]);
                    ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiUserOnlineMicModel::getInstance()->getModel(),$insertdata, ["user_id", "id"]);
                }
                //更新配置文件
                SyncDataConfModel::getInstance()->getModel()->where('deal_func', 'user_online_mic')->update(["sync_id" => $max_id]);
                Db::commit();
            } catch (\Exception $e) {
                Db::error(self::COMMAND_NAME . ":exception" . $e->getMessage());
                Db::rollback();
            }

            $page++;
            $res = $this->getDataSource($where, $page);
        }

        $redis->del($commandLockName);
    }


    public function getDataSource($where, $page)
    {
        return BiRoomActionModel::getInstance()->getModel()
            ->where($where)
            ->page($page, self::MAXLIMIT)
            ->select()
            ->toArray();
    }





    function getdateNode($startnode,$dur)
    {
        $returnRes = [];
        $start_node = $startnode;
        while (true) {
            $limit = strtotime(date('Y-m-d 00:00:00',strtotime("+1days",$start_node))) - $start_node;
            if(($dur-$limit) > 0) {
                $returnRes[] = [
                    "date" => date('Y-m-d', $start_node),
                    "duration" => $limit,
                ];

                $dur = $dur-$limit;
                $start_node = strtotime("+1days", strtotime(date('Ymd', $start_node)));

            }else{
                $returnRes[] = [
                    "date" => date('Y-m-d',$start_node),
                    "duration" => $dur,
                ];
                break;
            }
        }

        return  $returnRes;
    }



}

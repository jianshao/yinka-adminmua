<?php
/**
 * 同步脚本
 */

namespace app\admin\script;
use app\admin\model\SyncDataConfModel;
use app\admin\model\UserAssetLogModel;
use app\admin\service\ElasticsearchService;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class UserAssetLogInesByTimeCommand extends Command
{
    const SYNCID = 28;
    const ESINDEX = 'es_zb_user_asset_log';
    const COMMAND_NAME = "UserAssetLogInesByTimeCommand";

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        //前闭后开
        $start_timestamp = $this->getBaseDataSyncList();
        $end_timestamp = strtotime("-5minutes");

       // $start_timestamp = $this->getBaseDataSyncList();
      //  $end_timestamp = strtotime("+1hours", $start_timestamp);

        if ($start_timestamp >= $end_timestamp) {
            Log::error(SELF::COMMAND_NAME . "start_timestamp and end_timestamp setting fail");
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
            $res =  $this->getUserAssetLog($start_timestamp, $end_timestamp);
            if(empty($res)){
                SyncDataConfModel::getInstance()->getModel()->where("id",SELF::SYNCID)->update(['sync_id'=>$end_timestamp]);
            }else{
                $bulkdata = $this->bulk($res);
                if(isset($bulkdata['errors']) && $bulkdata['errors'] == false){
                    SyncDataConfModel::getInstance()->getModel()->where("id",SELF::SYNCID)->update(['sync_id'=>$end_timestamp]);
                }
            }

        } catch (\Throwable $e) {
            Log::error(SELF::COMMAND_NAME . ":error" . $e->getMessage() . "getLine:".$e->getLine() . $e->getFile());
        } finally {
            $redis->del($commandLockName);
        }
    }


    /**数据写入es中
     * @param $dataByMin
     * @param $dataByDay
     */
    private function bulk($data)
    {
            $body = [];
            foreach ($data as $item) {
                $body[] = ['index' => ['_index' => self::ESINDEX, '_id' => md5(md5(json_encode($item))) . '-' . $item['id']]];
                $body[] = $item;
            }


        return ElasticsearchService::getInstance()->bulkData( self::ESINDEX,$body);
    }



    //获取同步的配置数据
    private function getBaseDataSyncList()
    {
        return SyncDataConfModel::getInstance()->getModel()->where('id', SELF::SYNCID)->value("sync_id");
    }


    /**
     * 获取5分钟的内的assetlog数据
     * @param $start_node
     * @param $end_node
     * @return array
     */
    private function getUserAssetLog($start_node, $end_node)
    {
        $start_date = date('Y-m-d H:i:s', $start_node);
        $end_date = date('Y-m-d H:i:s', $end_node);
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start_date, $end_date);
        //$models = UserAssetLogModel::getInstance($instance)->getallModel();
        //因为要兼容数据所以我要先计算出所有的用户uid
        $searchwhere=[["success_time",">=",$start_node],["success_time","<",$end_node]];
        $uids = UserAssetLogModel::getInstance($instance)->getuids($searchwhere);
        $userassetLogModels = UserAssetLogModel::getInstance($instance)->getModels($uids);
        $res = [];
        foreach($userassetLogModels as $userassetLogModel){
            $data = $userassetLogModel->getModel()
                ->where("uid","in",$userassetLogModel->getList())
                ->where('success_time', '>=', $start_node)
                ->where("success_time", "<", $end_node)
                ->select()->toArray();
            $res = array_merge($res, $data);
        }
        return $res;
    }



}

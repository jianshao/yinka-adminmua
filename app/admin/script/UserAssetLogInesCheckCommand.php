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

class UserAssetLogInesCheckCommand extends Command
{
    const ESINDEX = 'es_zb_user_asset_log';
    const COMMAND_NAME = "UserAssetLogInesCheckCommand";

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {

        $config = [
            //["mark"=>"2月份数量对比","start"=>'2022-02-01',"end"=>'2022-03-01'],
            //["mark"=>"3月份数量对比","start"=>'2022-03-01',"end"=>'2022-04-01'],
            //["mark"=>"4月份数量对比","start"=>'2022-04-01',"end"=>'2022-05-01'],
            //["mark"=>"5月份数量对比","start"=>'2022-05-01',"end"=>'2022-06-01'],
            //["mark"=>"6月份数量对比","start"=>'2022-06-01',"end"=>'2022-07-01'],
            ["mark"=>"7月份数量对比","start"=>'2022-07-25',"end"=>'2022-08-01'],
            //["mark"=>"8月份数量对比","start"=>'2022-08-01',"end"=>'2022-08-11'],
        ];
        try {
            foreach ($config as $key=>$conf) {
                $table_count = $this->getUserAssetLog(strtotime($conf['start']),strtotime($conf['end']));
                $config[$key]['table_count'] = $table_count;
                $config[$key]['es_count'] = $this->getEscount($conf['start'],$conf['end']);

            }
            dump($config);
        }catch (\Throwable $e){
            dump($e->getMessage().'getline'.$e->getLine().$e->getFile());
        }
    }


    private function getUserAssetLog($start_node, $end_node)
    {
        $start_date = date('Y-m-d H:i:s', $start_node);
        $end_date = date('Y-m-d H:i:s', $end_node);
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start_date, $end_date);
        //$models = UserAssetLogModel::getInstance($instance)->getallModel();
        //因为要兼容数据所以我要先计算出所有的用户uid
        $searchwhere=[["success_time",">=",$start_node],["success_time","<",$end_node]];
        $userassetLogModels = UserAssetLogModel::getInstance($instance)->getAllModels();
        $total_count = 0;
        foreach($userassetLogModels as $userassetLogModel){
            $total_count += $userassetLogModel->getModel()
                ->where($searchwhere)
                ->count();
        }
        return $total_count;
    }



    public function getEscount($start,$end){

        $data = ["query"=>["range"=>[
            "success_time"=>["from"=>strtotime($start),"to"=>strtotime($end),"include_lower"=>true,"include_upper"=>false]
        ]]];

        $host = "http://". config("config.es_config")[0]."/".SELF::ESINDEX."/_count/";

        $result =  curlData($host,json_encode($data),"POST");

        $parseData = json_decode($result,true);

        return $parseData['count'];

    }




}

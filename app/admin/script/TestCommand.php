<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\common\ApiUrlConfig;
use app\admin\model\ActiveModel;
use app\admin\model\AdminUserModel;
use app\admin\model\AnchorCpPromotionModel;
use app\admin\model\AssetLogModel;
use app\admin\model\BiAasByKeywordModel;
use app\admin\model\BiAnchorCpSendGiftModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiUserKeepDayModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\model\BlackLogModel;
use app\admin\model\CheckImMessageModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberSocityModel;
use app\admin\model\MenuModel;
use app\admin\model\PackModel;
use app\admin\model\PromoteCallbackModel;
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

class TestCommand extends Command
{

    const COMMAND_NAME = "TestCommand";

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME);
    }


    public function callback($data,$callback){
        $res = $callback($data);
        dd($res);
    }

    public function execute(Input $input, Output $output)
    {

        $redis = RedisCommon::getInstance()->getRedis();
        $key = "web_user_withdrawal_record_existence_id:88eb5073b66047ec2f90acac86f0d19f";
        $redis->set($key,1,['nx','ex'=>30]);

        dd("cache sucess");



        $date = '20220801';

        $res =  PromoteCallbackModel::getInstance()->getModel()->alias("c")->LEFTJOIN("zb_promote_report r", "r.oaid = c.oaid")
            ->field("c.user_id,c.oaid,r.aid,DATE_FORMAT(str_date,'%Y-%m-%d') as date")
            ->where("c.factory_type", "Oppo")
            ->where("c.str_date", date("Ymd", strtotime($date)))
            ->where("c.event_type", 2)->fetchsql(true)
            ->select();

        dd($res);

        $this->treeResult = [];
        $total_list = MarketChannelModel::getInstance()->getModel()->order('pid asc')->select()->toArray();
        $showdata = $this->setTree($total_list);

        dd(count($showdata));
    }

    public function setTree($data,$pid = 0 ){
        $result = [];
          if(empty($result)){
              echo "=====".PHP_EOL;
          }
          foreach($data as $item){
              if($item['pid'] == $pid){
                  $temp = $item;
                  $temp['child'] = $this->setTree($data,$item['id']);
                  $result[]=$temp;
              }
          }

          return $result;
    }


    public function getRootTree($list)
    {
        $tree_list = [];
        if ($list) {
            $root_id = min(array_column($list, 'pid'));
            $tree_list = $this->list2Tree($list, $root_id);
        }
        return $tree_list;
    }


    public function list2Tree($list, $root = 0, $id = 'id', $pid = 'pid', $child = '_child')
    {
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$id]] = &$list[$key];
            }

            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent = &$refer[$parentId];
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
    }



    /**数据写入es中
     * @param $dataByMin
     * @param $dataByDay
     */
    private function bulk($data)
    {
        $callbackRes = $this->callfunc($data);
        return ElasticsearchService::getInstance()->bulk(SELF::ESINDEX,$callbackRes,"_id");
    }




    //对数据进行_id
    private function callfunc($res){
        return  array_map(function($item){
            $item['_id'] = md5(md5(json_encode($item)))."_".$item['id'];
            return $item;
        },$res);
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

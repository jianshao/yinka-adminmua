<?php
/*
 * 应用市场分包统计 register_channel source
 */

namespace app\admin\script;

use app\admin\common\ParseUserState;
use app\admin\model\BiChannelSourceDataModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiUserKeepDayModel;
use app\admin\model\BiUserStats1DayModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

class NewUserByRegChannelAndSourceCommand extends Command
{
    protected $date;
    protected $time;

    protected function configure()
    {
        $this->setName('NewUserByRegChannelAndSourceCommand')
            ->setDescription('NewUserByRegChannelAndSourceCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", date('Y-m-d'))
            ->addArgument('end', Argument::OPTIONAL, "end", date('Y-m-d',strtotime("+1days")));

        $this->date = date("Y-m-d");
        $this->time = date("Y-m-d H:i:s");
    }


    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        try{
            //如果要使用group_concat
            ParseUserStateDataCommmon::getInstance()->setGroupConcatLength();
            $start = $input->getArgument('start');
            $end = $input->getArgument('end');

            $currenthours = date('H');
            if($currenthours>= "00" and $currenthours <= "02"){
                $start = date("Y-m-d",strtotime("-1days"));
            }

            $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($start,$end); //不包括end的日期

            foreach($dateList as $date) {
                $start_date = $date;
                $end_date = date("Y-m-d", strtotime("{$date} + 1days"));
                $registerUserListInfo = $this->getRegisterUser($start_date, $end_date);
                $activeUserListInfo = $this->getActiveUser($start_date, $end_date);
                $registerUserList = array_column($registerUserListInfo,NULL,"mark");
                $activeUserList = array_column($activeUserListInfo,NULL,"mark");
                $insertData = [];
                foreach($registerUserList as $key=>$item){
                    $insertData[$key]['riq'] = strtotime($date);
                    $insertData[$key]['type'] = 2;
                    $insertData[$key]['today_reg_mebs'] = $item['uids'];// 日期注册用户 分包源分渠道
                    $insertData[$key]['source'] = $item['source'];//包源
                    $insertData[$key]['channel'] = $item['register_channel'];//渠道
                    $insertData[$key]['rih'] =  $this->splitMarkGetCount($activeUserList[$item['register_channel']."-".$item['source']]['uids'] ?? '');
                    $insertData[$key]['xinz'] = $this->splitMarkGetCount($registerUserList[$item['register_channel']."-".$item['source']]['uids'] ?? '');
                }
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiChannelSourceDataModel::getInstance()->getModel(),$insertData,['riq',"source","id","channel","type"]);
            }
            $this->keepHistory();

        }catch(\Throwable $e){
              Log::error($e->getMessage().":getLine:".$e->getLine().$e->getFile());
        }

    }


    private function keepHistory(){
         $res =  BiChannelSourceDataModel::getInstance()->getModel()->order("id desc")->select()->toArray();
         $map = [];
         foreach($res as $node){
             $update = [];
             $today_reg_mebs = explode(",",$node['today_reg_mebs']);
             $riq_date = date('Y-m-d',$node['riq']);
             if(!isset($map[$node['riq']]['active'])){
                 $where=[];
                 $where[] = ['date','=',date('Y-m-d',$node['riq'])];
                 $where[] = ["room_id","=",0];
                 $where[] = ["type","=",'active'];
                 $userkeepInfo = BiUserKeepDayModel::getInstance()->getModel()->where('date',date('Y-m-d',$node['riq']))->find();
                 $map[$node['riq']]['active']  = $userkeepInfo;
             }

             $keepuserRate = ParseUserState::getInstance()->strIntersect($node['today_reg_mebs'],$map[$node['riq']]['active']['keep_2'] ?? '');
             $update['cirlc'] = count($keepuserRate);  //次日留存

             $keepuserRate = ParseUserState::getInstance()->strIntersect($node['today_reg_mebs'],$map[$node['riq']]['active']['keep_3'] ?? '');
             $update['sanrlc'] = count($keepuserRate);  //3日留存


             $keepuserRate = ParseUserState::getInstance()->strIntersect($node['today_reg_mebs'],$map[$node['riq']]['active']['keep_7'] ?? '');
             $update['qirlc'] = count($keepuserRate);  //7日留存

             $condition = [];
             $condition[] =['register_channel','=',$node['channel']];
             $condition[] =['source','=',$node['source']];
             $condition[] =['promote_channel','=',0];
             $condition[] = ['date',"=",date('Y-m-d',$node['riq'])];
             $chargeList =  BiDaysUserChargeModel::getInstance()->getChargeSumAndNumber($condition);
             $czzje = $this->divedFunc(array_sum(array_column($chargeList,"amount")),10);
             $update['czzje'] = $czzje;
             $czrs = count((array_unique(array_column($chargeList,"uid"))));
             $update['czrs'] = $czrs;

             //新增用户三日内总付费
             if($today_reg_mebs){
                 $start = $riq_date;
                 $end = date('Y-m-d',strtotime($start."+3days"));
                 if(strtotime($end) > time()){ //只有当前时间大于要统计的节点才计算
                     $update['pay_retention_sum_3'] = $this->divedFunc(BiDaysUserChargeModel::getInstance()->getchargesum($today_reg_mebs,$start,$end),10);
                 }
             }



             if($today_reg_mebs){
                 $start = $riq_date;
                 $end = date('Y-m-d',strtotime($start."+7days"));
                 if(strtotime($end) > time()){ //只有当前时间大于要统计的节点才计算
                     $update['pay_retention_sum_7'] = $this->divedFunc(BiDaysUserChargeModel::getInstance()->getchargesum($today_reg_mebs,$start,$end),10);
                 }
             }


             if($today_reg_mebs){
                 $start = $riq_date;
                 $end = date('Y-m-d',strtotime($start."+15days"));
                 if(strtotime($end) > time()){ //只有当前时间大于要统计的节点才计算
                     $update['pay_retention_sum_15'] = $this->divedFunc(BiDaysUserChargeModel::getInstance()->getchargesum($today_reg_mebs,$start,$end),10);
                 }
             }


             if($today_reg_mebs){
                 $start = $riq_date;
                 $end = date('Y-m-d',strtotime($start."+30days"));
                 if(strtotime($end) > time()){ //只有当前时间大于要统计的节点才计算
                     $update['pay_retention_sum_30'] = $this->divedFunc(BiDaysUserChargeModel::getInstance()->getchargesum($today_reg_mebs,$start,$end),10);
                 }
             }


             //新增的用户当日的充值量 因为值已经固定不需要调整
             if((time()-$node['riq']) < 3600*24*2 && $today_reg_mebs){
                 $condition = [];
                 $start = $riq_date;
                 $end = date('Y-m-d',strtotime($start."+1days"));
                 $condition[] = ['uid',"in",$today_reg_mebs];
                 $condition[] = ['date',">=",$start];
                 $condition[] = ['date',"<",$end];
                 $result = BiDaysUserChargeModel::getInstance()->getChargeSumAndNumber($condition);

                 $todayregister_moneysum = array_sum(array_column($result,"amount"));
                 $todayregister_chargeuser = array_unique(array_column($result,"uid"));
                 $update['nczzje'] = $this->divedFunc($todayregister_moneysum,10); //今日注册用户今日充值多少
                 $update['nczrs'] = count($todayregister_chargeuser); //今日注册的用户有多少充值
             }
              //新增充值率 =  新增充值人数 / 新增人数
             if($node['xinz'] > 0 ){
                 $update['nczl'] = round($node['nczrs']/ $node['xinz']*100,2) *  100;
             }

             $pay_retention_sum =  BiDaysUserChargeModel::getInstance()->getchargesum($today_reg_mebs,$riq_date);

             //充值率 = 充值人数/日活人数
             if($node['rih'] > 0 ){
                 $update['czl'] = round($czrs/$node['rih']*100,2) * 100;
             }


             //注册的用户截止到今日的总充值
             $update['pay_retention_sum'] = $this->divedFunc($pay_retention_sum,10);

             //ARPU值
             if ($node['rih'] > 0) {
                 $update['arpu'] = round($czzje / $node['rih'] * 100, 2);
             }

             //ARPPU值
             if ($czrs > 0) {
                 $update['arppu'] = round( $czzje / $czrs * 100, 2);
             }

             BiChannelSourceDataModel::getInstance()->getModel()->where("id",$node['id'])->update($update);
         }
    }




    /**
     *每日新注册数据
     */
    private  function getRegisterUser($start, $end)
    {
        $res = BiUserStats1DayModel::getInstance()->getModel()->field("register_channel,source,GROUP_CONCAT(distinct uid) as uids,CONCAT_WS('-',register_channel,source) as mark")
            ->where('date',">=",$start)
            ->where('date',"<",$end)
            ->where('register_time','>=',$start." 00:00:00")
            ->where('register_time','<',$end." 00:00:00")
            ->where('promote_channel', 0)
            ->group('register_channel,source')
            ->select()
            ->toArray();
        return $res;
    }

    /**
     * 每日活跃用户
     */
    private  function getActiveUser($start, $end)
    {
        $res = BiUserStats1DayModel::getInstance()->getModel()->field("register_channel,source,GROUP_CONCAT(distinct uid) as uids,CONCAT_WS('-',register_channel,source) as mark")
            ->where('date',">=",$start)
            ->where('date',"<",$end)
            ->where('promote_channel', 0)
            ->group('register_channel,source')
            ->select()
            ->toArray();
        return $res;
    }


    //分割返回数量
    private function splitMarkGetCount($params,$split=",") :int
    {
         if(empty(trim($params,$split))){
             return 0;
         }
         return count(explode($split,$params));
    }


    //相除
    private function divedFunc($param1, $param2, $decimal = 2)
    {
       if($param2 ==0 || empty($param2)){
           return 0 ;
       }
        return round($param1 / $param2, $decimal);
    }


}

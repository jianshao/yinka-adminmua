<?php
/**
 * 同步脚本
 */

namespace app\admin\script;
use app\admin\model\AssetLogModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;\

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);
//1v1结算 新的跑量算法
class PormoeUserPointCommand extends Command
{

    const COMMAND_NAME = "TestCommand";
    public $chargeList = [];
    public $loginListByuid = [];
    public $marketList = [];
    public $chargeListByuid = [];
    public $start_time = '';
    public $end_time = '';
    public $stop_time = '';

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME);
    }


    public function init(){
        $where=[];
        $where[] = ["register_time",">=",$this->start_time];
        $where[] = ["register_time","<",$this->end_time];
        $where[] = ["date",">=",$this->start_time];
        $where[] = ["date","<=",$this->stop_time];
        $where[] = ["promote_channel",">",0];
        $where[] = ["promote_channel","<",800000];
        $this->chargeList   =  Db::name("bi_days_user_charge")->field("uid,promote_channel,amount,register_time,date")->where($where)->select()->toArray();
        $condition=[];
        $condition[] = ["user_id","in",array_unique(array_column($this->chargeList,"uid"))];
        $condition[] = ["ctime",">=",strtotime($this->start_time)];
        $condition[] = ["ctime",">=",strtotime($this->start_time)];
        $loginList = Db::name("zb_login_detail_new")->field("user_id,ctime")->where($condition)->select()->toArray();
        $this->marketList = Db::name("zb_market_channel")->column("id,channel_name,one_level","id");

        foreach($this->chargeList as $item){
            $this->chargeListByuid[$item['uid']][]=$item;
        }

        foreach($loginList as $itemLogin){
            $this->loginListByuid[$itemLogin['user_id']][]= $itemLogin;
        }
    }



    public function execute(Input $input, Output $output)
    {
        $this->start_time = '2022-04-01';
        $this->end_time = '2022-05-01';
        $this->stop_time = '2022-05-15';
        /*

        //规则1.注册后首次消费10元 并且24小时后没有没有登录日志   标记 10元无效用户    status = -1
        //规则2.1 注册后24小内送礼消费200豆以上 标记2分用户  一级渠道下最多300个用户标记  statsu = 2
        //规则2.2 次日充值100元以上  标记 8分用户   一级渠道下最多600个  status = 8
        //规则2.3 7日内累计统计1000元以上 标记 11分用户切10-14天内有登录记录   一级渠道下最多1100  status=11
        */


        try{

            $this->init();
            $returnData = [];
            foreach($this->chargeList  as $chargeItems){
                if(isset($returnData[$chargeItems['uid']])){
                    $returnData[$chargeItems['uid']]['money'] += $chargeItems['amount']/10;
                }else{
                    $returnData[$chargeItems['uid']]['uid'] = $chargeItems['uid'];
                    $returnData[$chargeItems['uid']]['register_time'] = $chargeItems['uid'];
                    $returnData[$chargeItems['uid']]['money'] = $chargeItems['amount']/10;
                    $returnData[$chargeItems['uid']]['channel_name'] = $this->marketList[$this->marketList[$chargeItems['promote_channel']]['one_level']]['channel_name'];
                    $returnData[$chargeItems['uid']]['promote_channel'] = $chargeItems['promote_channel'];
                    $returnData[$chargeItems['uid']]['point'] = $this->getRuleFour($chargeItems) ;
                }
            }



            Db::name("temp_user_1v1")->insertAll($returnData);


        }catch (\Throwable $e){
            echo $e->getFile().$e->getLine().$e->getMessage();
        }


    }

    //
    public function getRuleFour($info){
        //7日内累计统计1000元以上 标记 11分用户切10-14天内有登录记录
        $register_time = $info['register_time'];
        $register_date = date('Y-m-d',strtotime($register_time));
        $end7Date = date("Y-m-d",strtotime($register_time." +7days"));
        $end1Date = date("Y-m-d",strtotime($register_time." +1days"));
        $end10Date = date("Y-m-d",strtotime($register_time." +10days"));
        $end14Date = date("Y-m-d",strtotime($register_time." +14days"));
        $uid = $info['uid'];
        $sum_money_7days = 0;
        $sum_money_1days = 0;
        $sum_money_current_days = 0;
        //判断7日内的充值情况
        $chargeinfo = $this->chargeListByuid[$uid];
        foreach($chargeinfo as $chargedetailItem){
            echo $chargedetailItem['date'] ?? 'xingbaixin';
            if($chargedetailItem['date'] < $end7Date){
                $sum_money_7days += ($chargedetailItem['amount']/10);
            }

            if($chargedetailItem['date'] == $end1Date){
                $sum_money_1days = ($chargedetailItem['amount']/10);
            }

            if($chargedetailItem['date'] == $register_date){
                $sum_money_current_days = ($chargedetailItem['amount']/10);
            }
        }


        if($sum_money_7days >= 1000){  //7日内充值累计1000元 且在10天-14内有记录 则返回11分
            $loginlist = $this->loginListByuid[$uid];
            foreach($loginlist as $loginlistItem){
                if($loginlistItem['ctime'] >= strtotime($end10Date)  && $loginlistItem['ctime'] < strtotime($end14Date)){
                    return 11;
                }
            }
        }



        if($sum_money_1days > 100){  //第二日的充值大于等于100元 则返回8分
            return 8;
        }


        $beanamount = Db::name("bi_days_user_sendgift")
            ->where("date",">=",$register_date)
            ->where("date","<=",$end1Date)
            ->where("uid","=",$uid)
            ->sum("consume_amount");

        if($beanamount >= 200){
            return 2;
        }


        if($sum_money_current_days==10){ //当日消费10元切在24小时内无登录则返回-1
            $invaluser = -1;
            $loginlistbyuid = $this->loginListByuid[$uid];
            foreach($loginlistbyuid as $loginlistItem){
                if($loginlistItem['ctime'] >= strtotime($register_date."+1days")){
                    $invaluser = 0;
                }
            }
            return $invaluser;
        }

        return 0;
    }



}

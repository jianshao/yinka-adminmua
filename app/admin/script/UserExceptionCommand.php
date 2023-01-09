<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\model\BlackDataModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MemberModel;
use app\common\Ip2Region;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class UserExceptionCommand extends Command
{
    const COMMAND_NAME = "UserExceptionCommand";
    const EXCEPTIONKEYDEVICEID = "exception:user:deviceid";
    const EXCEPTIONKEYIMEI = "exception:user:imei";
    const EXCEPTIONKEYIDFA = "exception:user:idfa";
    const EXCEPTIONKEYAll =  "exception:user";
    const SAVETABLENAME = 'temp_user_exception';

    const BEGINDATE = '2022-06-01';
    const ENDDATE = '2022-07-01';

    const BLACKUSER_BEGINDATE = '2022-05-01';
    const BLACKUSER_ENDDATE = '2022-07-01';

    protected  $redis = NULL;

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        $this->redis =  RedisCommon::getInstance()->getRedis(['select' => 8]);
        $this->getuserid();
        $this->handlerRes();
    }

    public function handlerRes(){
        //这里取并集
        $this->redis->SUNIONSTORE(SELF::EXCEPTIONKEYAll,SELF::EXCEPTIONKEYIDFA,SELF::EXCEPTIONKEYIMEI,SELF::EXCEPTIONKEYDEVICEID);
        $uids =  $this->redis->sMembers(self::EXCEPTIONKEYAll);
        Db::execute("truncate table ".SELF::SAVETABLENAME);
        $models = MemberModel::getInstance()->getModels($uids);
        foreach($models as $model){
            $userinfo = $model->getModel()->field("id,register_time,register_ip,deviceid,invitcode")
                ->where("id","in",$model->getList())->select()->toArray();
            Db::name(SELF::SAVETABLENAME)->insertAll($userinfo);
        }
    }



    public function getuserid()
    {
        $start_timestamp = strtotime(SELF::BLACKUSER_BEGINDATE);
        $end_timestamp = strtotime(SELF::BLACKUSER_ENDDATE);

        //封禁的用户
        $blackuids = BlackDataModel::getInstance()->getModel()->where(
            [
                ["blacks_time",">=",$start_timestamp],
                ["blacks_time","<",$end_timestamp],
                ["user_id",">",0],
                ["time","=",-1],
            ]
        )->column("user_id");

        //封禁用户中含充值用户
        $blackuser = BiDaysUserChargeModel::getInstance()
            ->getModel()->where("uid","in",$blackuids)
            ->distinct(true)
            ->column("uid");

        $logindetailModels = LogindetailModel::getInstance()->getModels($blackuser);
        foreach($logindetailModels as $logindetailmodel){
            $searchSource = $logindetailmodel->getModel()->where("user_id","in",$blackuser)
                ->field("login_ip")
                ->distinct(true)
                ->select()->toArray();
            $this->runExecute($searchSource);
        }

        /*
        $querySql = <<<st
        select DISTINCT login_ip from zb_login_detail where user_id in (
        select distinct uid from bi_days_user_charge where uid in (2107274) and register_ip!= ''
        )
        st;*/

    }



    public function runExecute($searchSource){
        foreach($searchSource as $source_item){
            if(empty($source_item['login_ip'])){
                continue;
            }
            $where=[];
            $where = [
                ["register_ip", "=", $source_item['login_ip']]
            ];
            $this->handlerDeviceidList($where);
            $this->handlerImeiList($where);
            $this->handlerIdfaList($where);
        }
    }


    public function handlerDeviceidList($where, $ipsearch = true)
    {
        dump($where);
        $res = $this->getModelData($where);
        if ($res) {
            $ipsearch = !$ipsearch;
            foreach ($res as $item) {
                if ($this->redis->sIsMember(self::EXCEPTIONKEYDEVICEID,$item['id'])) {
                    continue;
                } else {
                    $this->redis->sAdd(self::EXCEPTIONKEYDEVICEID,$item['id']);
                    Log::info("user_exception_command:uid=".$item['id'] . "where=".json_encode($where));
                }
                if ($ipsearch) {
                    if (empty(trim($item['register_ip']))) {
                        continue;
                    }
                    $condition = [
                        ["register_ip", "=", trim($item['register_ip'])],
                    ];
                } else {
                    if (empty($item['deviceid'])) {
                        continue;
                    }
                    $condition = [
                        ["deviceid", "=", $item['deviceid']]
                    ];
                }
                $this->handlerDeviceidList($condition, $ipsearch);
            }
        }
    }


    /**
     * @param $where
     * @param bool $ipsearch
     *  ##根据 这三个维度 来分别获取用户  然后用户合并起来
    idfa!='' and idfa != '00000000-0000-0000-0000-000000000000'
    imei!='' and imei != 'unknown' '0000000'
    deviceid != ''
     */

    public function handlerImeiList($where, $ipsearch = true)
    {
        dump($where);
        $res = $this->getModelData($where);
        if ($res) {
            $ipsearch = !$ipsearch;
            foreach ($res as $item) {
                if ($this->redis->sIsMember(self::EXCEPTIONKEYIMEI,$item['id'])) {
                    continue;
                } else {
                    $this->redis->sAdd(self::EXCEPTIONKEYIMEI,$item['id']);
                    Log::info("user_exception_command:uid=".$item['id'] . "where=".json_encode($where));
                }
                if ($ipsearch) {
                    if (empty(trim($item['register_ip']))) {
                        continue;
                    }
                    $condition = [
                        ["register_ip", "=", trim($item['register_ip'])],
                    ];
                } else {
                    if (empty($item['imei']) || strlen($item['imei']) < 20) {
                        continue;
                    }
                    $condition = [
                        ["imei", "=", $item['imei']]
                    ];
                }
                $this->handlerImeiList($condition, $ipsearch);
            }
        }
    }



    public function handlerIdfaList($where, $ipsearch = true)
    {
        dump($where);
        $res = $this->getModelData($where);
        if ($res) {
            $ipsearch = !$ipsearch;
            foreach ($res as $item) {
                if ($this->redis->sIsMember(self::EXCEPTIONKEYIDFA,$item['id'])) {
                    continue;
                } else {
                    $this->redis->sAdd(self::EXCEPTIONKEYIDFA,$item['id']);
                    Log::info("user_exception_command:uid=".$item['id'] . "where=".json_encode($where));
                }
                if ($ipsearch) {
                    if (empty(trim($item['register_ip']))) {
                        continue;
                    }
                    $condition = [
                        ["register_ip", "=", trim($item['register_ip'])],
                    ];
                } else {
                    if (empty($item['idfa']) || $item['idfa'] == 'unknown' || $item['idfa'] == '00000000-0000-0000-0000-000000000000' || strlen($item['idfa']) < 20) {
                        continue;
                    }
                    $condition = [
                        ["idfa", "=", $item['idfa']]
                    ];
                }
                $this->handlerIdfaList($condition, $ipsearch);
            }
        }
    }



    public function getModelData($where)
    {
        return BiDaysUserChargeModel::getInstance()->getModel()->where($where)
            ->where('promote_channel', ">", 0)
            ->where("date",">=",self::BEGINDATE)
            ->where("date","<",self::ENDDATE)
            ->field('uid as id,register_ip,deviceid,idfa,imei')
            ->select()->toArray();
    }

}

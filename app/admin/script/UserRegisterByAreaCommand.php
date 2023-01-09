<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\model\BiRegisterUserProvinceModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MemberModel;
use app\common\Ip2Region;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class UserRegisterByAreaCommand extends Command
{
    const COMMAND_NAME = "UserRegisterByAreaCommand";

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d', strtotime("-1days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d'))
            ->setDescription(SELF::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        $begin_date = $input->getArgument("start_time");
        $end_date = $input->getArgument("end_time");
        $ipmodel = new Ip2Region(app()->getRootPath() . "/public/admin/ip2region.db");
        $this->registerUserIp($ipmodel, $begin_date, $end_date);
        //$this->loginUserIp($ipmodel, $begin_date, $end_date);
    }

    //注册用户
    public function registerUserIp($ipmodel, $begin_date, $end_date)
    {
        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($begin_date, $end_date, false);
        foreach ($dateList as $nodes) {
            $res = [];
            $models = MemberModel::getInstance()->getallModel();
            foreach ($models as $model) {
                $getMemberList = $model->getModel()->field("register_ip,register_time,sex")
                    ->where("register_time", ">=", $nodes . " 00:00:00")
                    ->where("register_time", "<", date('Y-m-d', strtotime("+1days", strtotime($nodes))) . " 00:00:00")
                    ->select()
                    ->toArray();
                $res = array_merge($res, $getMemberList);
            }

            $insertRes = [];
            foreach ($res as $item) {
                if ($item['register_ip']) {
                    $searchipRes = $ipmodel->memorySearch($item['register_ip']);
                    $params = explode("|", $searchipRes['region'] ?? '');

                    if (isset($params[0]) && $params[0] == '中国' && isset($params[2]) && !empty($params[2])) {
                        $province = $params[2];
                    } else {
                        $province = '其他';
                    }

                    if (isset($insertRes[$province])) {
                        $insertRes[$province]['people_number'] += 1;
                        if ($item['sex'] == 1) { //男
                            $insertRes[$province]['man_number'] += 1;
                        } elseif ($item['sex'] == 2) { //女
                            $insertRes[$province]['woman_number'] += 1;
                        }
                    } else {
                        $insertRes[$province]['people_number'] = 1;
                        $insertRes[$province]['man_number'] = 0;
                        $insertRes[$province]['woman_number'] = 0;
                        $insertRes[$province]['date'] = date('Y-m-d', strtotime($item['register_time']));
                        $insertRes[$province]['type'] = 0; //注册
                        $insertRes[$province]['province'] = $province;
                        if ($item['sex'] == 1) {
                            $insertRes[$province]['man_number'] += 1;
                        } elseif ($item['sex'] == 2) {
                            $insertRes[$province]['woman_number'] += 1;
                        }
                    }

                }
            }
            try {
                //ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul(array_values($insertRes), "bi_register_user_province", ["id", "date", "province"]);
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiRegisterUserProvinceModel::getInstance()->getModel(), array_values($insertRes), ["id", "date", "province","type"]);
            } catch (\Throwable$e) {
                Log::error(self::COMMAND_NAME . ":error" . $e->getMessage());
            }

        }
    }

    //登陆用户ip
    public function loginUserIp($ipmodel, $begin_date, $end_date)
    {
        $dateList = ParseUserStateDataCommmon::getInstance()->getTimeNode($begin_date, $end_date, false);
        $logindetailModels = LogindetailModel::getInstance()->getallModel();
        foreach ($dateList as $nodes) {
            echo $nodes;
            $begin_time = strtotime($nodes);
            $end_time = strtotime("+1days", strtotime($nodes));
       /*     $buildSql = "select user_id,login_ip from (select user_id,login_ip from zb_login_detail where ctime>=$begin_time and ctime<$end_time order by id desc ) T group by user_id ";
            $res = Db::query($buildSql);*/
            $res=[];
            foreach($logindetailModels as $logindetailmodel){
                $buildsql = $logindetailmodel->getModel()
                    ->field("user_id,login_ip")
                    ->where('ctime','>=',$begin_time)
                    ->where('ctime','<',$end_time)
                    ->order("id desc")
                    ->buildSql();
               $data =  Db::query("select user_id,login_ip from $buildsql A group by user_id ");
               $res = array_merge($res,$data);
            }
            $uids = array_column($res, "user_id");
            $memberInfo = [];
            $memberModels = MemberModel::getInstance()->getModels($uids);
            foreach($memberModels as $membermodel){
                $memberdata = $membermodel->getModel()->field("sex,id")->where("id","in",$membermodel->getList())
                    ->select()->toArray();
                $memberInfo = array_merge($memberInfo,$memberdata);
            }

            $memberInfoByID = array_column($memberInfo, null, "id");
            $insertRes = [];
            foreach ($res as $item) {
                if ($item['login_ip']) {
                    $searchipRes = $ipmodel->memorySearch($item['login_ip']);
                    $params = explode("|", $searchipRes['region'] ?? '');

                    if (isset($params[0]) && $params[0] == '中国' && isset($params[2]) && !empty($params[2])) {
                        $province = $params[2];
                    } else {
                        $province = '其他';
                    }

                    $sex = $memberInfoByID[$item['user_id']]['sex'] ?? 0;

                    if (isset($insertRes[$province])) {
                        $insertRes[$province]['people_number'] += 1;
                        if ($sex == 1) { //男
                            $insertRes[$province]['man_number'] += 1;
                        } elseif ($sex == 2) { //女
                            $insertRes[$province]['woman_number'] += 1;
                        }
                    } else {
                        $insertRes[$province]['people_number'] = 1;
                        $insertRes[$province]['man_number'] = 0;
                        $insertRes[$province]['woman_number'] = 0;
                        $insertRes[$province]['date'] = $nodes;
                        $insertRes[$province]['type'] = 1; //登陆
                        $insertRes[$province]['province'] = $province;
                        if ($sex == 1) {
                            $insertRes[$province]['man_number'] += 1;
                        } elseif ($sex == 2) {
                            $insertRes[$province]['woman_number'] += 1;
                        }
                    }
                }
            }
            try {

                ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiRegisterUserProvinceModel::getInstance()->getModel(),
                    array_values($insertRes),["id", "date", "province","type"]);
            } catch (\Throwable $e) {
                Log::error(self::COMMAND_NAME . ":error" . $e->getMessage());
            }

        }
    }

}
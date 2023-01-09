<?php

namespace app\common;

use app\admin\model\BiUserStats1DayModel;
use app\admin\script\analysis\AnslysisConst;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;


/**
 * User: baixin
 * Date: 2021/9/11
 * Time: 下午4:37
 */
class ParseUserStateByUniqkey
{


    public static $instance = NULL;
    public static function getInstance()
    {

        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


//根据唯一的key来合并数据
    public function parseData($where, $uniqkey, &$returnData = [], &$obj = [])
    {
        $limit = 1000;
        $page = 1;
        $res = BiUserStats1DayModel::getInstance()->getModel()->where($where)->field("*")->page($page, $limit)->select();
        while (!$res->isEmpty()) {
            $this->calDayData($res, $returnData, $obj, $uniqkey);
            $page++;
            $res = BiUserStats1DayModel::getInstance()->getModel()->where($where)->field("*")->page($page, $limit)->select();

        }
        return $returnData[$uniqkey] ?? [];
    }




    //根据唯一的key来合并数据
    public function parseMulData($where, $uniqkey, &$returnData = [], &$obj = [])
    {
        $limit = 1000;
        $page = 1;
        $res = BiUserStats1DayModel::getInstance()->getModel()->alias("us")
            ->join('bi_channel_huawei hw','hw.user_id = us.uid')
            ->where($where)
            ->field("*")
            ->page($page, $limit)
            ->select();

        while (!$res->isEmpty()) {
            $this->calDayData($res, $returnData, $obj, $uniqkey);
            $page++;

            $res = BiUserStats1DayModel::getInstance()->getModel()->alias("us")
                ->join('bi_channel_huawei hw','hw.user_id = us.uid')
                ->where($where)
                ->field("*")
                ->page($page, $limit)
                ->select();

        }
        return $returnData[$uniqkey] ?? [];
    }



    //根据唯一的key来合并数据
    public function parseAppstoreData($where, $uniqkey, &$returnData = [], &$obj = [])
    {
        $limit = 1000;
        $page = 1;
        $res = BiUserStats1DayModel::getInstance()->getModel()->alias("us")
            ->join('bi_channel_appstore ios','ios.user_id = us.uid')
            ->where($where)
            ->field("*")
            ->page($page, $limit)
            ->select();

        while (!$res->isEmpty()) {
            $this->calDayData($res, $returnData, $obj, $uniqkey);
            $page++;

            $res = BiUserStats1DayModel::getInstance()->getModel()->alias("us")
                ->join('bi_channel_appstore ios','ios.user_id = us.uid')
                ->where($where)
                ->field("*")
                ->page($page, $limit)
                ->select();

        }
        return $returnData[$uniqkey] ?? [];
    }


    //根据唯一的key来合并数据
    public function parseOppoData($where, $uniqkey, &$returnData = [], &$obj = [])
    {
        $limit = 1000;
        $page = 1;
        $res = BiUserStats1DayModel::getInstance()->getModel()->alias("us")
            ->join('bi_channel_oppo p','p.user_id = us.uid')
            ->where($where)
            ->field("*")
            ->page($page, $limit)
            ->select();

        while (!$res->isEmpty()) {
            $this->calDayData($res, $returnData, $obj, $uniqkey);
            $page++;
            $res = BiUserStats1DayModel::getInstance()->getModel()->alias("us")
                ->join('bi_channel_oppo p','p.user_id = us.uid')
                ->where($where)
                ->field("*")
                ->page($page, $limit)
                ->select();

        }
        return $returnData[$uniqkey] ?? [];
    }




    protected function calDayData($res, &$data, &$obj, $stats_column)
    {
        foreach ($res as $mins5_data) {
            $uid = $mins5_data['uid'];
            $user_json = json_decode($mins5_data['json_data'], true);

            if (!isset($data[$stats_column]['active_users'])) {
                $data[$stats_column]['active_users'] = [];
            }

            if (!in_array($uid, $data[$stats_column]['active_users'])) {
                array_push($data[$stats_column]['active_users'], $uid);
            }

            foreach ($user_json as $json_type => $json) {
                if (!empty($json)) {
                    if (array_key_exists($json_type, AnslysisConst::CLASS_MAP)) {
                        $class = AnslysisConst::CLASS_MAP[$json_type];

                        $user_obj = new $class($stats_column);
                        $user_obj->fromJson($json);

                        if (!isset($obj[$stats_column][$json_type])) {
                            $obj[$stats_column][$json_type] = $user_obj;
                        } else {
                            $obj[$stats_column][$json_type] = $obj[$stats_column][$json_type]->merge($user_obj);
                        }

                        $data[$stats_column][$json_type]['data'] = $obj[$stats_column][$json_type]->toJson();
                    } elseif (!array_key_exists($json_type, AnslysisConst::CLASS_MAP)) {
                        $data[$stats_column][$json_type]['data'] = $json;
                    }

                    if (!isset($data[$stats_column][$json_type]['users'])) {
                        $data[$stats_column][$json_type]['users'][] = $uid;
                    }
                    if (!in_array($uid, $data[$stats_column][$json_type]['users'])) {
                        array_push($data[$stats_column][$json_type]['users'], $uid);
                    }
                }
            }
        }
    }


//直冲充值总金额
    public function getChargeSum($data, $json_key = 'charge'): int
    {
        $amount = 0;
        if (array_key_exists($json_key, $data)) {
            foreach ($data[$json_key]['data'] as $channelCharge) {
                foreach ($channelCharge as $charge_data) {
                    $amount += $charge_data['amount'];
                }
            }
        }
        return $amount / 10;
    }


//vip 充值
    public function getChargeVipSum($data, $json_key)
    {
        $amount = 0;
        if (array_key_exists($json_key, $data)) {
            foreach ($data[$json_key]['data'] as $key => $channelCharge) {
                if ($key == 2) { //vip
                    foreach ($channelCharge as $charge_data) {
                        $amount += $charge_data['amount'];
                    }
                }

            }
        }
        return $amount / 10;
    }


    //svip 充值
    public function getChargeSvipSum($data, $json_key)
    {
        $amount = 0;
        if (array_key_exists($json_key, $data)) {
            foreach ($data[$json_key]['data'] as $key => $channelCharge) {
                if ($key == 3) { //svip
                    foreach ($channelCharge as $charge_data) {
                        $amount += $charge_data['amount'];
                    }
                }

            }
        }
        return $amount / 10;
    }




//代充总金额
    public function getAgentChargeSum($data, $json_key = 'agentcharge'): int
    {
        $amount = 0;
        if (array_key_exists($json_key, $data)) {
            foreach ($data[$json_key]['data'] as $charge_data) {
                $amount += $charge_data['amount'];
            }
        }

        return $amount / 10;
    }


//获取充值用户列表 直冲或者代充
    public function getChargeUsers($data, $json_key): array
    {
        $users = [];
        if (array_key_exists($json_key, $data)) {
            $users = $data[$json_key]['users'];
        }

        return $users;
    }


//获取充值用户数量 直冲或者代充
    public function getChargeCount($data, $json_key): int
    {
        $count = 0;
        if (array_key_exists($json_key, $data)) {
            $count = count($data[$json_key]['users']);
        }
        return $count;
    }


//获取注册用户的数量
    public function getRegiserUserCount($data, $json_key): int
    {
        $count = 0;
        if (array_key_exists($json_key, $data)) {
            $count = count($data[$json_key]['users']);
        }
        return $count;
    }


//更新数据
    public function insertOrUpdateMul($data, $table, $unique = [])
    {
        $getfield = (Db::getFields($table));
        $updateFields = array_diff(array_keys($getfield), $unique);
        $exceptUniq = join(",", $updateFields);
        return Db::table($table)->duplicate($exceptUniq)->insertAll($data);
    }

    public function getArrayKeyValue(array $data, $key): array
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }
        return [];
    }


    public function getDiffDays($end, $start): int
    {
        return (strtotime($end) - strtotime($start)) / 24 / 60 / 60;
    }


    //活动数据
   public  function getActivitySum($data, $searchkey = 'box2'): array
    {
        $returnData = [];
        if (array_key_exists('activity',$data)) {
            foreach ($data['activity'] as $items) {
                foreach ($items as $key=>$item) {
                    if($key == $searchkey){
                        foreach($item as $mapid=>$mapitem){
                            $consumevalue = 0;
                            $rewardvalue = 0;
                            $consumeList = $mapitem['consume'] ?? [];
                            $rewardList = $mapitem['reward'] ?? [];
                            foreach($consumeList as $consumeitem){
                                $consumevalue += ($consumeitem['value'] ?? 0);
                            }

                            foreach($rewardList as $rewarditem){
                                $rewardvalue += ($rewarditem['value'] ?? 0);
                            }
                            if(!isset($returnData[$mapid]['reward'])){
                                $returnData[$mapid]['reward'] = 0;
                            }
                            if(!isset($returnData[$mapid]['consume'])){
                                $returnData[$mapid]['consume'] = 0;
                            }

                            $returnData[$mapid]['reward'] += $rewardvalue;
                            $returnData[$mapid]['consume'] += $consumevalue;
                        }

                    }
                }
            }
        }
        return $returnData;
    }






    //根据的key来合并数据
    public function parseCustomData($tableName,$where, $uniqkey, &$returnData = [], &$obj = [])
    {
        $limit = 1000;
        $page = 1;
        $res = Db::table($tableName)->where($where)->field("*")->page($page, $limit)->select();
        while (!$res->isEmpty()) {
            $this->calDayData($res, $returnData, $obj, $uniqkey);
            $page++;
            $res = Db::table($tableName)->where($where)->field("*")->page($page, $limit)->select();
        }

        return $returnData[$uniqkey] ?? [];
    }


    //更新数据
    public function insertOrUpdate($data, $table, $unique = [])
    {
        $getfield = (Db::getFields($table));
        $updateFields = array_diff(array_keys($getfield), $unique);
        $exceptUniq = join(",", $updateFields);
        return Db::table($table)->duplicate($exceptUniq)->insert($data);
    }


    //更新数据
    public function insertOrUpdateModel($model,$data,$unique = [])
    {
        $getfield = $model->getTableFields();
        $updateFields = array_diff($getfield, $unique);
        $exceptUniq = join(",", $updateFields);
        return $model->duplicate($exceptUniq)->insertAll($data);
    }




    //根据唯一的key来合并数据
    public function parsePromoteData($database, $uniqkey, &$returnData = [], &$obj = [])
    {
        $limit = 1000;
        $page = 1;
        $res = $database->page($page, $limit)->select();
        while (!$res->isEmpty()) {
            $this->calDayData($res, $returnData, $obj, $uniqkey);
            $page++;
            $res = $database->page($page, $limit)->select();
        }
        return $returnData[$uniqkey] ?? [];
    }



}





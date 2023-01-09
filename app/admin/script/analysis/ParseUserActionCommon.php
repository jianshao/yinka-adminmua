<?php

namespace app\admin\script\analysis;

use think\facade\Db;

class ParseUserActionCommon
{

    protected static $instance;
    const DATA_TABLE_NAME = "bi_user_stats_1day";

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    //合并数据

    /**
     * @param $where
     * @param string $mark
     * @param array $returnData
     * @return array
     */
    public function parseData($where, $limit = 1000, $mark = "", &$returnData = [])
    {
        $page = 1;
        $res = Db::table(SELF::DATA_TABLE_NAME)->where($where)->page($page, $limit)->select()->toArray();
        $userBehaviorInit = new UserBehavior();
        $userList = [];
        $markList = ["charge", "agentcharge", "sendGift", "login", "activity"];
        foreach ($markList as $mark_item) {
            $userList[$mark_item . "_user"] = [];
        }
        $userList['user_all'] = [];
        while ($res) {
            foreach ($res as $items) {
                $parseUserJsonData = json_decode($items['json_data'], true);

                foreach ($markList as $mark_value) {
                    if (array_key_exists($mark_value, $parseUserJsonData)) {
                        if (!in_array($items['uid'], $userList[$mark_value . "_user"])) {
                            $userList[$mark_value . "_user"][] = $items['uid']; //充值用户
                        }
                    }
                }


                if (!in_array($items['uid'], $userList['user_all'])) {
                    $userList["user_all"][] = $items['uid']; //充值用户
                }


                $userBehavior = new UserBehavior();
                $userBehavior->fromJson($parseUserJsonData);
                $userBehaviorInit->mergePart($userBehavior, $mark);
            }
            $page++;
            $res = Db::table(SELF::DATA_TABLE_NAME)->where($where)->page($page, $limit)->select()->toArray();
        }
        $toJsonRes = $userBehaviorInit->toJson();
        $returnData['data'] = $toJsonRes;
        $returnData['user'] = $userList;
        return $returnData;
    }


    /**
     * @param $where
     * @param string $mark
     * @param array $returnData
     * @return array
     */
    public function parseDataNew($items, &$userBehaviorInit, &$returnData = [])
    {
        $markList = ["charge", "agentcharge", "sendGift", "login", "activity"];
        foreach ($markList as $mark_item) {
            if (!isset($returnData['user'][$mark_item . "_user"])) {
                $returnData['user'][$mark_item . "_user"] = [];
            }
        }

        if (!isset($returnData['user']['user_all'])) {
            $returnData['user']['user_all'] = [];
        }


        $parseUserJsonData = json_decode($items['json_data'], true);

        foreach ($markList as $mark_value) {
            if (array_key_exists($mark_value, $parseUserJsonData)) {
                if (!in_array($items['uid'], $returnData['user'][$mark_value . "_user"])) {
                    $returnData['user'][$mark_value . "_user"][] = $items['uid']; //充值用户
                }
            }
        }

        if (!in_array($items['uid'], $returnData['user']['user_all'])) {
            $returnData['user']["user_all"][] = $items['uid']; //充值用户
        }

        $userBehavior = new UserBehavior();
        $userBehavior->fromJson($parseUserJsonData);
        $userBehaviorInit->mergePart($userBehavior);
    }


    //直冲充值总金额
    public function getChargeSum($data): int
    {
        $amount = 0;
        if (array_key_exists("charge", $data)) {
            foreach ($data['charge'] as $channelCharge) {
                foreach ($channelCharge as $charge_data) {
                    $amount += $charge_data['amount'];
                }
            }
        }
        return $amount / 10;
    }


//vip 充值
    public function getChargeVipSum($data)
    {
        $amount = 0;
        if (array_key_exists("charge", $data)) {
            foreach ($data['charge'] as $key => $channelCharge) {
                if ($key == 2) { //vip
                    foreach ($channelCharge as $charge_data) {
                        $amount += $charge_data['amount'];
                    }
                }

            }
        }
        return $amount / 10;
    }


//代充总金额
    public function getAgentChargeSum($data): int
    {
        $amount = 0;
        if (array_key_exists("agentcharge", $data)) {
            foreach ($data["agentcharge"] as $charge_data) {
                $amount += $charge_data['amount'];
            }
        }

        return $amount / 10;
    }


//更新数据
    public function insertOrUpdateMul($data, $table, $unique = [])
    {
        $getfield = (Db::getFields($table));
        $updateFields = array_diff(array_keys($getfield), $unique);
        $exceptUniq = join(",", $updateFields);
        return Db::table($table)->duplicate($exceptUniq)->insertAll($data);
    }


    public function getDiffDays($end, $start): int
    {
        return (strtotime($end) - strtotime($start)) / 24 / 60 / 60;
    }


    //活动数据
    public function getActivitySum($data, $searchkey = 'box2'): array
    {
        $returnData = [];
        if (array_key_exists('activity', $data)) {
            foreach ($data['activity'] as $items) {
                foreach ($items as $key => $item) {
                    if ($key == $searchkey) {
                        foreach ($item as $mapid => $mapitem) {
                            $consumevalue = 0;
                            $rewardvalue = 0;
                            $consumeList = $mapitem['consume'] ?? [];
                            $rewardList = $mapitem['reward'] ?? [];
                            foreach ($consumeList as $consumeitem) {
                                $consumevalue += ($consumeitem['value'] ?? 0);
                            }

                            foreach ($rewardList as $rewarditem) {
                                $rewardvalue += ($rewarditem['value'] ?? 0);
                            }
                            if (!isset($returnData[$mapid]['reward'])) {
                                $returnData[$mapid]['reward'] = 0;
                            }
                            if (!isset($returnData[$mapid]['consume'])) {
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


}
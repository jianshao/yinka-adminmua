<?php

namespace app\admin\script\analysis;

use app\common\ParseUserStateDataCommmon;

class UserChargeEveryday
{
    public static $instance = NULL;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    // $result = "大json" 这是一天合并的数据
    public function parseCharge($result)
    {
        $returnData = [];
        foreach ($result as $key => $item) {
            $params = ParseUserStateDataCommmon::getInstance()->identifySplit($key);
            $uid = $params[1] ?? 0; //用户的id
            $date = $params[0] ?? ''; //日期
            if (empty($uid) || empty($date)) {
                continue;
            }

            $chargeList = $item['charge'] ?? [];
            $agentchargeList = $item['agentcharge'] ?? [];

            if ($chargeList) {
                $this->handleCharge($chargeList, $uid, $date, $params, $returnData);
            }

            if ($agentchargeList) {
                $this->handleAgentCharge($agentchargeList, $uid, $date, $params, $returnData);
            }
        }

        return $returnData;
    }


    public function handleCharge($listItem, $uid, $date, $params, &$returnData)
    {
        //register_ip,idfa,imei,deviceid,register_channel
        //$type 代表直冲的用户
        $register_channel = $params[2] ?? ''; //注册渠道
        $register_ip = $params[3] ?? ''; //register
        $idfa = $params[4] ?? ''; //idfa
        $deviceId = $params[5] ?? ''; //deviceId
        $imei = $params[6] ?? ''; //imei
        $promote_channel = $params[7] ?? ''; //promote_channel
        $register_time = $params[8] ?? ''; //register_time
        $type = 1;
        foreach ($listItem as $key => $items) {
            foreach ($items as $item) {
                $returnData[] =
                    [
                        "amount" => $item['amount'],
                        "uid" => $uid,
                        "type" => $type,
                        "date" => $date,
                        "register_channel" => $params[2] ?? '', //注册渠道,
                        "register_ip" => $params[3] ?? '',
                        "idfa" => $params[4] ?? '',
                        "imei" => $params[6] ?? '',
                        "deviceid" => $params[5] ?? '',
                        "promote_channel" => $params[7] ?? '',
                        "register_time" => $params[8] ?? '',
                    ];
            }
        }
    }


    public function handleAgentCharge($listItem, $uid, $date, $params, &$returnData)
    {
        //$type 代表代充的用户
        $type = 2;
        foreach ($listItem as $key => $item) {
            $returnData[] =
                [
                    "amount" => $item['amount'],
                    "uid" => $uid,
                    "type" => $type,
                    "date" => $date,
                    "register_channel" => $params[2] ?? '', //注册渠道,
                    "register_ip" => $params[3] ?? '',
                    "idfa" => $params[4] ?? '',
                    "imei" => $params[6] ?? '',
                    "deviceid" => $params[5] ?? '',
                    "promote_channel" => $params[7] ?? '',
                    "register_time" => $params[8] ?? '',
                ];
        }
    }


    // $result = "大json" 这是一天合并的数据
    public function parseChargeNew($result, &$returnData)
    {
        $uid = $result['uid'] ?? 0; //用户的id
        $date = $result['date'] ?? ''; //日期

        if (empty($uid) || empty($date)) {
            return;
        }

        $this->handleChargeNew($result, $uid, $date, $returnData);

        $this->handleAgentChargeNew($result, $uid, $date, $returnData);

        return $returnData;
    }


    public function handleChargeNew($listItem, $uid, $date, &$returnData)
    {
        $type = 1;
        $bigData = json_decode($listItem['json_data'], true);
        $chargeList = $bigData['charge'] ?? [];
        if (empty($chargeList)) return;
        $merge = [];
        foreach ($chargeList as $key => $items) {
                if ($items) {
                    $merge = array_merge(array_column($items, "amount"), $merge);
            }
        }

        $amounts = array_sum($merge);

        $returnData[] =
            [
                "amount" => $amounts,
                "uid" => $uid,
                "type" => $type,
                "date" => $date,
                "register_channel" => $listItem['register_channel'] ?? '',
                "register_ip" => $bigData['register']['register_ip'] ?? '',
                "idfa" => $bigData['register']['idfa'] ?? '',
                "imei" => $bigData['register']['imei'] ?? '',
                "deviceid" => $bigData['register']['deviceId'] ?? '',
                "promote_channel" => $listItem['promote_channel'] ?? '',
                "register_time" => $listItem['register_time'] ?? '',
                "source" => $listItem['source'] ?? '',
            ];
    }


    public function handleAgentChargeNew($listItem, $uid, $date, &$returnData)
    {
        //$type 代表代充的用户
        $type = 2;
        $bigData = json_decode($listItem['json_data'], true);
        $chargeList = $bigData['agentcharge'] ?? [];
        if (empty($chargeList)) return;
        $amounts = array_sum(array_column($chargeList,"amount"));

        $returnData[] =
            [
                "amount" => $amounts,
                "uid" => $uid,
                "type" => $type,
                "date" => $date,
                "register_channel" => $listItem['register_channel'] ?? '',
                "register_ip" => $bigData['register']['register_ip'] ?? '',
                "idfa" => $bigData['register']['idfa'] ?? '',
                "imei" => $bigData['register']['imei'] ?? '',
                "deviceid" => $bigData['register']['deviceId'] ?? '',
                "promote_channel" => $listItem['promote_channel'] ?? '',
                "register_time" => $listItem['register_time'] ?? '',
                "source" => $listItem['source'] ?? '',
            ];
    }


}






<?php

namespace app\admin\script;

use app\admin\script\analysis\UserBehavior;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;

//用户行为解析
class AnslysisUserBehavior
{
    public $dataMap = [];
    public $userBehavior = null;


    const ASSET_TYPE_MAP = [
        1 => 'prop:',
        2 => 'bank:',
        3 => 'gift:',
        4 => 'user:',
        6 => 'user:',
        7 => 'user:',
        8 => 'ore:',
    ];


    const DIAMOND_TYPE_MAP = [
        10003 => 'receivegift',
        10004 => 'exchange',
        10014 => 'agentpay',
        10016 => 'withdraw',
        10020 => 'operator',
    ];

    const WITHDRAW_TYPE_MAP = [
        0 => 'apply',
        1 => 'dealing',
        2 => 'fail',
        3 => 'success',
        4 => 'refuse',
        5 => 'cancel',
    ];


    public function __construct()
    {
        $this->getGift();
    }

    public function analyRegister($data, $identifier)
    {
        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }
        $obj->registerMap->add($data);
        $this->dataMap[$identifier] = $obj;
    }


    public function analyLogin($data, $identifier)
    {
        list($uid, $channel, $deviceId, $mobile_version) = [$data['uid'], $data['channel'], $data['deviceId'], $data['mobile_version']];
        $up_mobile_version = strtoupper($mobile_version);
        $pos = strpos($up_mobile_version, 'IPHONE');
        //判断手机系统类型
        $os = $pos !== false ? 'ios' : 'android';
        $version = $pos !== false ? 'v2.5' : '3.0.1';

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $obj->loginMap->addOs($os, $version, 1);
        $obj->loginMap->addChannel($channel, $version, 1);
        $obj->loginMap->addDeviceId($deviceId, $version, 1);
        $obj->loginMap->addDevice($mobile_version, $version, 1);
        $this->dataMap[$identifier] = $obj;
    }


    public function analyFirstcharge($data, $identifier)
    {
        list($uid, $firstChargeTime) = [$data['uid'], $data['addtime']];

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $obj->firstchargeMap->add($uid, $firstChargeTime);
        $this->dataMap[$identifier] = $obj;
    }


    public function analyWithdraw($data, $identifier)
    {
        list($uid, $status, $amount) = [(string)$data['uid'], (string)$data['status'], (int)$data['amount']];
        $type = self::WITHDRAW_TYPE_MAP[$status];
        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $obj->withdrawMap->add($type, 1, $amount);
        $this->dataMap[$identifier] = $obj;
    }


    public function analyCharge($data, $identifier)
    {
        list($uid, $type, $channel, $amount, $count) = [
            (string)$data['uid'],
            (string)$data['type'],
            (string)$data['channel'],
            (int)$data['amount'],
            (int)$data['count'],
        ];

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $obj->chargeMap->add($type, $channel, $count, $amount);
        $this->dataMap[$identifier] = $obj;
    }


    public function analyAgentCharge($data, $identifier)
    {
        list($uid, $touid, $amount) = [
            (string)$data['uid'],
            (string)$data['touid'],
            (int)$data['change_amount'],
        ];

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $obj->agentchargeMap->add($touid, 1, $amount);
        $this->dataMap[$identifier] = $obj;
    }


    /*
     * 送礼
     */
    public function analySendUserGift($data, $identifier)
    {
        list($uid, $room_id, $gift_id, $real_gift_id, $count, $consume_amount, $output_amount, $type) = [
            (string)$data['uid'],
            (string)$data['room_id'],
            (string)$data['gift_id'],
            (string)$data['ext_2'],
            (int)$data['count'],
            (int)$data['change_amount'],
            (int)$data['ext_4'],
            (int)$data['type'],
        ];

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $obj->sendGiftMap->addRoom($room_id, $gift_id, $real_gift_id, $count, $type, $consume_amount, $output_amount);
        $this->dataMap[$identifier] = $obj;
    }

    /*
     * 收礼
     */
    public function analyReceiveGift($data, $identifier)
    {
        list($uid, $room_id, $gift_id, $real_gift_id, $count, $consume_amount, $output_amount, $type) = [
            (string)$data['uid'],
            (string)$data['room_id'],
            (string)$data['gift_id'],
            (string)$data['ext_2'],
            (int)$data['count'],
            (int)$data['change_amount'],
            (int)$data['ext_4'],
            (int)$data['type'],
        ];


        if (!isset($this->dataMap[$identifier])) {
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }
        $obj->receiveGiftMap->addRoom($room_id, $gift_id, $real_gift_id, $count, $type, $consume_amount, $output_amount);
        $this->dataMap[$identifier] = $obj;
    }


    public function analySendRedPackage($data, $identifier)
    {
        list($uid, $room_id, $count, $change_amount) =
            [(string)$data['uid'], (string)$data['room_id'], (int)$data['count'], (int)$data['change_amount']];

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }
        $obj->sendRedMap->addRoom($room_id, $count, $change_amount);
        $this->dataMap[$identifier] = $obj;

    }


    public function analyReceiveRedPackage($data, $identifier)
    {
        list($uid, $room_id, $count, $change_amount) =
            [(string)$data['uid'], (string)$data['room_id'], (int)$data['count'], (int)$data['change_amount']];

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $obj->receiveRedMap->addRoom($room_id, $count, $change_amount);
        $this->dataMap[$identifier] = $obj;
    }


    public function analyReturnRedPackage($data, $identifier)
    {
        list($uid, $room_id, $count, $change_amount) =
            [(string)$data['uid'], (string)$data['room_id'], (int)$data['count'], (int)$data['change_amount']];

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $obj->returnRedMap->addRoom($room_id, $count, $change_amount);
        $this->dataMap[$identifier] = $obj;
    }


    public function analyDiamond($data, $identifier)
    {
        list($uid, $touid, $event_id, $change_amount, $change_before, $change_after) = [
            (string)$data['uid'],
            (string)$data['touid'],
            (string)$data['event_id'],
            (int)$data['change_amount'],
            (int)$data['change_before'],
            (int)$data['change_after'],
        ];

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $type = self::DIAMOND_TYPE_MAP[$event_id];
        if ($event_id == 10014 || $event_id == 10004) {
            $obj->diamondMap->addSpend($type, 1, $change_amount);
        } elseif ($event_id == 10003) {
            $obj->diamondMap->addIncome($type, 1, $change_amount);
        } elseif ($event_id == 10020) {
            $change_before < $change_after ? $obj->diamondMap->addIncome($type, 1, $change_amount) : $obj->diamondMap->addSpend($type, 1, $change_amount);
        }
        //$this->userBehavior->diamondMap = $obj->diamondMap;
        $this->dataMap[$identifier] = $obj;


        if ($event_id == 10014) {
            $params = ParseUserStateDataCommmon::getInstance()->identifySplit($identifier);
            $new_identifier = ParseUserStateDataCommmon::getInstance()->identifyMerge($params[0], $touid);
            if (!isset($this->dataMap[$new_identifier])) {
                $this->userBehavior = new UserBehavior();
                $obj = new UserBehavior();
            } else {
                $obj = $this->dataMap[$new_identifier];
            }
            $obj->diamondMap->addIncome($type, 1, $change_amount);
            $this->dataMap[$new_identifier] = $obj;
        }
    }


    public function analyActivity($data, $identifier)
    {
        list($uid, $room_id, $asset_id, $activity, $sub, $ext_3, $change_amount, $type) = [
            (int)$data['uid'],
            (string)$data['room_id'],
            (string)$data['asset_id'],
            (string)$data['ext_1'],
            (string)$data['ext_2'],
            (int)$data['ext_3'],
            (int)$data['change_amount_real'],
            (int)$data['type'],
        ];

        $asset_id = self::ASSET_TYPE_MAP[$type] . $asset_id;

        if (!isset($this->dataMap[$identifier])) {
            $this->userBehavior = new UserBehavior();
            $obj = new UserBehavior();
        } else {
            $obj = $this->dataMap[$identifier];
        }

        $sub = empty($sub) ? $activity : $sub;

        if ($change_amount > 0) { //产出
            if ($type == 3) {
                $price = $this->giftList[$data['asset_id']] ?? 0 ;
                $value = $price * (int)$change_amount;
                $obj->activityMap->addReward($room_id, $activity, $sub, $asset_id, $value, $change_amount);
            } else {
                $obj->activityMap->addReward($room_id, $activity, $sub, $asset_id, $change_amount, $ext_3);
            }

        } elseif ($change_amount < 0) { //消耗
            $value = abs($change_amount);
            $obj->activityMap->addCount($room_id, $activity, $sub, $ext_3);
            $obj->activityMap->addConsume($room_id, $activity, $sub, $asset_id, $value, $ext_3);
        }

        $this->dataMap[$identifier] = $obj;
    }

    public function objectToArray()
    {
        $res = [];
        foreach ($this->dataMap as $identifier => $items) {
            $res[$identifier] = $items->toJson();
        }
        return $res;
    }


    //merge对象 $data为数据表里面的大json数据
    public function mergeObject($data, $identifier)
    {
        $newobj = new UserBehavior();
        $newobj->fromJson($data);
        $this->dataMap[$identifier]->merge($newobj);
    }


    public function getGift()
    {
        $res = [];
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $data = json_decode($redis->get('gift_conf'), true);
        foreach ($data as $k => $v) {
            $gift['id'] = $v['giftId'];
            $gift['gift_name'] = $v['name'];
            $gift['gift_coin'] = (int)$v['price']['count'];
            $res[$k] = $gift;
        }
        $this->giftList = array_column($res, 'gift_coin', 'id');
    }


}
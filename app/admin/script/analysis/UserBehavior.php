<?php

namespace app\admin\script\analysis;

use app\admin\script\analysis\activity\UserRoomActivityDataByRegChannel;
use app\admin\script\analysis\agentpay\UserAgentPayDataByRegChannel;
use app\admin\script\analysis\charge\ChargeChannelData;
use app\admin\script\analysis\charge\UserChargeDataByRegChannel;
use app\admin\script\analysis\diamond\UserDiamondDataByRegChannel;
use app\admin\script\analysis\firstcharge\FirstchargeData;
use app\admin\script\analysis\gift\UserRoomSendGiftDataByRegChannel;
use app\admin\script\analysis\login\UserLoginDataByRegChannel;
use app\admin\script\analysis\redpackage\UserRedPackageDataByRegChannel;
use app\admin\script\analysis\register\RegisterData;
use app\admin\script\analysis\withdraw\UserWithdrawDataByRegChannel;


//用户行为解析
class UserBehavior
{
    public $loginMap = NULL;
    public $registerMap = NULL;
    public $chargeMap = NULL;
    public $agentchargeMap = NULL;
    public $firstchargeMap = NULL;
    public $sendGiftMap = NULL;
    public $receiveGiftMap = NULL;
    public $activityMap = NULL;
    public $sendRedMap = NULL;
    public $receiveRedMap = NULL;
    public $returnRedMap = NULL;
    public $diamondMap = NULL;
    public $withdrawMap = NULL;

    public function __construct()
    {
        $this->registerMap = new RegisterData('');
        $this->firstchargeMap = new FirstchargeData('');
        $this->loginMap = new UserLoginDataByRegChannel('');
        $this->chargeMap = new UserChargeDataByRegChannel('');
        $this->agentchargeMap = new UserAgentPayDataByRegChannel('');
        $this->sendGiftMap = new UserRoomSendGiftDataByRegChannel('');
        $this->receiveGiftMap = new UserRoomSendGiftDataByRegChannel('');
        $this->activityMap = new UserRoomActivityDataByRegChannel('');
        $this->sendRedMap = new UserRedPackageDataByRegChannel('');
        $this->receiveRedMap = new UserRedPackageDataByRegChannel('');
        $this->returnRedMap = new UserRedPackageDataByRegChannel('');
        $this->diamondMap = new UserDiamondDataByRegChannel('');
        $this->withdrawMap = new UserWithdrawDataByRegChannel('');
    }

    public function add($type, $channel, $count, $amount)
    {
        if (array_key_exists($type, $this->chargeMap)) {
            $this->chargeMap[$type]->add($channel, $amount, $count);
        } else {
            $chargeData = new ChargeChannelData($type);
            $chargeData->add($channel, $amount, $count);
            $this->chargeMap[$type] = $chargeData;
        }
        return $this;
    }


    //数组到对象
    public function fromJson(array $data)
    {
        foreach ($data as $key => $item) {

            if ($key == 'register') {
                $this->registerMap->fromJson($item);
            }

            if ($key == 'firstcharge') {
                $this->firstchargeMap->fromJson($item);
            }

            if ($key == 'login') {
                $this->loginMap->fromJson($item);
            }

            if ($key == 'charge') {
                $this->chargeMap->fromJson($item);
            }

            if ($key == 'agentcharge') {
                $this->agentchargeMap->fromJson($item);
            }

            if ($key == 'sendGift') {
                $this->sendGiftMap->fromJson($item);
            }

            if ($key == 'receiveGift') {
                $this->receiveGiftMap->fromJson($item);
            }

            if ($key == 'activity') {
                $this->activityMap->fromJson($item);
            }

            if ($key == 'sendRed') {
                $this->sendRedMap->fromJson($item);
            }

            if ($key == 'receiveRed') {
                $this->receiveRedMap->fromJson($item);
            }

            if ($key == 'returnRed') {
                $this->returnRedMap->fromJson($item);
            }

            if ($key == 'diamond') {
                $this->diamondMap->fromJson($item);
            }

            if ($key == 'withdraw') {
                $this->withdrawMap->fromJson($item);
            }
        }
    }


    //对象到数组
    public function toJson()
    {
        $res = [];
        if (isset($this->registerMap)) {
            $res['register'] = $this->registerMap->toJson();
        }
        if (isset($this->firstchargeMap)) {
            $res['firstcharge'] = $this->firstchargeMap->toJson();
        }

        if (isset($this->loginMap)) {
            $res['login'] = $this->loginMap->toJson();
        }

        if (isset($this->chargeMap)) {
            $res['charge'] = $this->chargeMap->toJson();
        }

        if (isset($this->agentchargeMap)) {
            $res['agentcharge'] = $this->agentchargeMap->toJson();
        }

        if (isset($this->sendGiftMap)) {
            $res['sendGift'] = $this->sendGiftMap->toJson();
        }

        if (isset($this->receiveGiftMap)) {
            $res['receiveGift'] = $this->receiveGiftMap->toJson();
        }

        if (isset($this->activityMap)) {
            $res['activity'] = $this->activityMap->toJson();
        }

        if (isset($this->sendRedMap)) {
            $res['sendRed'] = $this->sendRedMap->toJson();
        }

        if (isset($this->receiveRedMap)) {
            $res['receiveRed'] = $this->receiveRedMap->toJson();
        }

        if (isset($this->returnRedMap)) {
            $res['returnRed'] = $this->returnRedMap->toJson();
        }

        if (isset($this->diamondMap)) {
            $res['diamond'] = $this->diamondMap->toJson();
        }

        if (isset($this->withdrawMap)) {
            $res['withdraw'] = $this->withdrawMap->toJson();
        }
        return $res;
    }


    public function merge($other)
    {
        if (!empty($other)) {
            $this->registerMap->merge($other->registerMap);
            $this->firstchargeMap->merge($other->firstchargeMap);
            $this->loginMap->merge($other->loginMap);
            $this->chargeMap->merge($other->chargeMap);
            $this->agentchargeMap->merge($other->agentchargeMap);
            $this->sendGiftMap->merge($other->sendGiftMap);
            $this->receiveGiftMap->merge($other->receiveGiftMap);
            $this->activityMap->merge($other->activityMap);
            $this->sendRedMap->merge($other->sendRedMap);
            $this->returnRedMap->merge($other->returnRedMap);
            $this->diamondMap->merge($other->diamondMap);
            $this->withdrawMap->merge($other->withdrawMap);
        }
        return $this;
    }

    //部分数据合并
    public function mergePart($other, $mark = "")
    {
        if (!empty($other)) {
            switch (strtolower($mark)){
                case "charge":
                    $this->chargeMap->merge($other->chargeMap);
                    break;
                case "agentcharge":
                    $this->agentchargeMap->merge($other->agentchargeMap);
                    break;
                case "sendgift":
                    $this->sendGiftMap->merge($other->sendGiftMap);
                    break;
                case "activity":
                    $this->activityMap->merge($other->activityMap);
                    break;
                case "login":
                    $this->loginMap->merge($other->loginMap);
                    break;
                default :
                    $this->registerMap->merge($other->registerMap);
                    $this->firstchargeMap->merge($other->firstchargeMap);
                    $this->loginMap->merge($other->loginMap);
                    $this->chargeMap->merge($other->chargeMap);
                    $this->agentchargeMap->merge($other->agentchargeMap);
                    $this->sendGiftMap->merge($other->sendGiftMap);
                    $this->receiveGiftMap->merge($other->receiveGiftMap);
                    $this->activityMap->merge($other->activityMap);
                    $this->sendRedMap->merge($other->sendRedMap);
                    $this->returnRedMap->merge($other->returnRedMap);
                    $this->diamondMap->merge($other->diamondMap);
                    $this->withdrawMap->merge($other->withdrawMap);
                    break;
            }
        }

        return $this;
    }


}
<?php
namespace app\admin\script\analysis\charge;

ini_set('memory_limit', -1);

class UserChargeData
{
    public $uid = 0;
    public $chargeMap = [];

    public function __construct($uid)
    {
        $this->uid = $uid;
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
    public function fromJson($jsonObj)
    {
        $this->chargeMap = $this->decodechargeMap($jsonObj);
        return $this;
    }

    public function decodechargeMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $type => $chargeDataJson) {
            $chargeData = new ChargeChannelData($type);
            $chargeData->fromJson($chargeDataJson);
            $ret[$type] = $chargeData;
        }
        return $ret;
    }
    //对象到数组
    public function toJson()
    {
        $charge = [];
        foreach ($this->chargeMap as $type => $chargeData) {
            $charge[$type] = $chargeData->toJson();
        }

        return $charge;
    }

    public function encodechargeMap(&$chargeDataMap)
    {
        $ret = [];
        if (!empty($chargeDataMap)) {
            foreach ($chargeDataMap as $type => $chargeData) {
                $ret[$type] = $chargeData->toJson();
            }
        }
        return $ret;
    }

    public function merge($other)
    {
        if (!empty($other->chargeMap)) {
            foreach ($other->chargeMap as $type => $chargeData) {
                if (!array_key_exists($type, $this->chargeMap)) {
                    $newchargeData = new ChargeChannelData($type);
                    $newchargeData->merge($chargeData);
                    $this->chargeMap[$type] = $newchargeData;
                } else {
                    $this->chargeMap[$type]->merge($chargeData);
                }
            }
        }
        return $this;
    }
}
<?php
namespace app\admin\script\analysis\withdraw;

class UserWithdrawData
{
    public $uid = 0;
    public $withdrawMap = [];

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function add($type, $count, $amount)
    {
        if (array_key_exists($type, $this->withdrawMap)) {
            $this->withdrawMap[$type]->add($amount, $count);
        } else {
            $withdrawData = new WithdrawData($type);
            $withdrawData->add($amount, $count);
            $this->withdrawMap[$type] = $withdrawData;
        }
        return $this;
    }

    public function fromJson($jsonObj)
    {
        $this->withdrawMap = $this->decodeWithdrawMap($jsonObj);
        return $this;
    }

    public function decodeWithdrawMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $type => $withdrawDataJson) {
            $withdrawData = new WithdrawData($type);
            $withdrawData->fromJson($withdrawDataJson);
            $ret[$type] = $withdrawData;
        }
        return $ret;
    }

    public function toJson()
    {
        $withdraw = [];
        foreach ($this->withdrawMap as $type => $withdrawData) {
            $withdraw[$type] = $withdrawData->toJson();
        }

        return $withdraw;
    }

    public function encodeWithdrawMap(&$withdrawDataMap)
    {
        $ret = [];
        if (!empty($withdrawDataMap)) {
            foreach ($withdrawDataMap as $type => $withdrawData) {
                $ret[$type] = $withdrawData->toJson();
            }
        }
        return $ret;
    }

    public function merge($other)
    {
        if (!empty($other->withdrawMap)) {
            foreach ($other->withdrawMap as $type => $withdrawData) {
                if (!array_key_exists($type, $this->withdrawMap)) {
                    $newWithdrawData = new WithdrawData($type);
                    $newWithdrawData->merge($withdrawData);
                    $this->withdrawMap[$type] = $newWithdrawData;
                } else {
                    $this->withdrawMap[$type]->merge($withdrawData);
                }
            }
        }
        return $this;
    }
}
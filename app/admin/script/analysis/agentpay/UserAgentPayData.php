<?php
namespace app\admin\script\analysis\agentpay;

ini_set('memory_limit', -1);

class UserAgentPayData
{
    public $uid = 0;
    public $payMap = [];

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function add($type, $count, $amount)
    {
        if (array_key_exists($type, $this->payMap)) {
            $this->payMap[$type]->add($amount, $count);
        } else {
            $payData = new AgentPayData($type);
            $payData->add($amount, $count);
            $this->payMap[$type] = $payData;
        }
        return $this;
    }

    public function fromJson($jsonObj)
    {
        $this->payMap = $this->decodePayMap($jsonObj);
        return $this;
    }

    public function decodePayMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $type => $payDataJson) {
            $payData = new AgentPayData($type);
            $payData->fromJson($payDataJson);
            $ret[$type] = $payData;
        }
        return $ret;
    }

    public function toJson()
    {
        $pay = [];
        foreach ($this->payMap as $type => $payData) {
            $pay[$type] = $payData->toJson();
        }

        return $pay;
    }

    public function encodePayMap(&$payDataMap)
    {
        $ret = [];
        if (!empty($payDataMap)) {
            foreach ($payDataMap as $type => $payData) {
                $ret[$type] = $payData->toJson();
            }
        }
        return $ret;
    }

    public function merge($other)
    {
        if (!empty($other->payMap)) {
            foreach ($other->payMap as $type => $payData) {
                if (!array_key_exists($type, $this->payMap)) {
                    $newPayData = new AgentPayData($type);
                    $newPayData->merge($payData);
                    $this->payMap[$type] = $newPayData;
                } else {
                    $this->payMap[$type]->merge($payData);
                }
            }
        }
        return $this;
    }
}
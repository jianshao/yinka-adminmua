<?php
namespace app\admin\script\analysis\agentpay;

ini_set('memory_limit', -1);

class UserAgentPayDataByRegChannel
{
    public $reg_channel = '';
    public $payMap = [];

    public function __construct($reg_channel)
    {
        $this->reg_channel = $reg_channel;
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
        $res = [];
        foreach ($this->payMap as $type => $payData) {
            $res[$type] = $payData->toJson();
        }

        return $res;
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
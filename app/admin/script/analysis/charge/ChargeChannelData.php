<?php
namespace app\admin\script\analysis\charge;

ini_set('memory_limit', -1);

class ChargeChannelData
{
    public $type = null;
    public $channelDataMap = [];

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function add($channel, $amount, $count)
    {
        if (array_key_exists($channel, $this->channelDataMap)) {
            $this->channelDataMap[$channel]->add($amount, $count);
        } else {
            $channelData = new ChargeData($channel);
            $channelData->add($amount, $count);
            $this->channelDataMap[$channel] = $channelData;
        }
    }

    public function toJson()
    {
        return $this->encodeLoginDataMap($this->channelDataMap);
    }

    public function encodeLoginDataMap(&$chargedataMap)
    {
        $ret = [];
        foreach ($chargedataMap as $channel => $channelData) {
            $ret[$channel] = $channelData->toJson();
        }
        return $ret;
    }

    public function fromJson($jsonObj)
    {
        return $this->channelDataMap = $this->decodeLoginDataMap($jsonObj);
    }

    public function decodeLoginDataMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $channel => $channelDataJson) {
            $channelData = new ChargeData($channel);
            $channelData->fromJson($channelDataJson);
            $ret[$channel] = $channelData;
        }
        return $ret;
    }

    public function merge($other)
    {
        foreach ($other->channelDataMap as $channel => $channelData) {
            if (!array_key_exists($channel, $this->channelDataMap)) {
                $newChargeData = new ChargeData($channel);
                $newChargeData->merge($channelData);
                $this->channelDataMap[$channel] = $newChargeData;
            } else {
                $this->channelDataMap[$channel]->merge($channelData);
            }
        }
        return $this;
    }
}
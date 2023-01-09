<?php
namespace app\admin\script\analysis\activity;

ini_set('memory_limit', -1);

class UserRoomActivityDataByRegChannel
{
    public $reg_channel = 0;

    public $roomActivityDataMap = [];

    public function __construct($reg_channel)
    {
        $this->reg_channel = $reg_channel;
    }

    public function add($roomId, $activity, $sub, $assetId, $amount, $ext_3)
    {
        if (array_key_exists($roomId, $this->roomActivityDataMap)) {
            //活动
            $roomData = $this->roomActivityDataMap[$roomId];
            $roomData->addConsume($activity, $assetId, $amount, $sub, $ext_3);
        } else {
            $roomActivityData = new RoomActivityData($roomId);
            $roomActivityData->addConsume($activity, $assetId, $amount, $sub, $ext_3);
            $this->roomActivityDataMap[$roomId] = $roomActivityData;
        }

        return $this;
    }

    public function addCount($roomId, $activity, $sub, $amount)
    {
        if (array_key_exists($roomId, $this->roomActivityDataMap)) {
            //活动
            $roomData = $this->roomActivityDataMap[$roomId];
            $roomData->addCount($activity, $sub, $amount);
        } else {
            $roomActivityData = new RoomActivityData($roomId);
            $roomActivityData->addCount($activity, $sub, $amount);
            $this->roomActivityDataMap[$roomId] = $roomActivityData;
        }

        return $this;
    }

    public function addConsume($roomId, $activity, $sub, $assetId, $amount, $ext_3)
    {
        if (array_key_exists($roomId, $this->roomActivityDataMap)) {
            //活动
            $roomData = $this->roomActivityDataMap[$roomId];
            $roomData->addConsume($activity, $assetId, $amount, $sub, $ext_3);
        } else {
            $roomActivityData = new RoomActivityData($roomId);
            $roomActivityData->addConsume($activity, $assetId, $amount, $sub, $ext_3);
            $this->roomActivityDataMap[$roomId] = $roomActivityData;
        }

        return $this;
    }

    public function addReward($roomId, $activity, $sub, $assetId, $amount, $ext_3)
    {
        if (array_key_exists($roomId, $this->roomActivityDataMap)) {
            //活动
            $roomData = $this->roomActivityDataMap[$roomId];
            $roomData->addReward($activity, $assetId, $amount, $sub, $ext_3);
        } else {
            $roomActivityData = new RoomActivityData($roomId);
            $roomActivityData->addReward($activity, $assetId, $amount, $sub, $ext_3);
            $this->roomActivityDataMap[$roomId] = $roomActivityData;
        }

        return $this;
    }

    public function fromJson($jsonObj)
    {
        return $this->roomActivityDataMap = $this->decodeRoomActivityDataMap($jsonObj);
    }

    public function decodeRoomActivityDataMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $roomId => $roomActivityDataJson) {
            $roomActivityData = new RoomActivityData($roomId);
            $roomActivityData->fromJson($roomActivityDataJson);
            $ret[$roomId] = $roomActivityData;
        }
        return $ret;
    }

    public function toJson()
    {
        return $this->encodeRoomActivityDataMap($this->roomActivityDataMap);
    }

    public function encodeRoomActivityDataMap(&$roomActivityDataMap)
    {
        $ret = [];
        foreach ($roomActivityDataMap as $roomId => $roomActivityData) {
            $ret[$roomId] = $roomActivityData->toJson();
        }
        return $ret;
    }

    public function merge($other)
    {
        foreach ($other->roomActivityDataMap as $roomId => $roomData) {
            if (!array_key_exists($roomId, $this->roomActivityDataMap)) {
                $newRoomData = new RoomActivityData($roomId);
                $newRoomData->merge($roomData);
                $this->roomActivityDataMap[$roomId] = $newRoomData;
            } else {
                $this->roomActivityDataMap[$roomId]->merge($roomData);
            }
        }
        return $this;
    }
}
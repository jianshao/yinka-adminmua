<?php
namespace app\admin\script\analysis\activity;

ini_set('memory_limit', -1);

class RoomActivityData
{
    public $roomId = 0;
    public $roomActivityDataMap = [];

    public function __construct($roomId)
    {
        $this->roomId = $roomId;
    }

    public function addCount($activity, $sub, $count)
    {
        if (array_key_exists($activity, $this->roomActivityDataMap)) {
            $this->roomActivityDataMap[$activity]->addCount($sub, $count);
        } else {
            $activityData = new ActivityData($activity);
            $activityData->addCount($sub, $count);
            $this->roomActivityDataMap[$activity] = $activityData;
        }

        return $this;
    }

    public function addConsume($activity, $assetId, $amount, $sub, $ext_3)
    {
        if (array_key_exists($activity, $this->roomActivityDataMap)) {
            $this->roomActivityDataMap[$activity]->addConsume($sub, $assetId, $amount, $ext_3);
        } else {
            $activityData = new ActivityData($activity);
            $activityData->addConsume($sub, $assetId, $amount, $ext_3);
            $this->roomActivityDataMap[$activity] = $activityData;
        }

        return $this;
    }

    public function addReward($activity, $assetId, $amount, $sub, $ext_3)
    {
        if (array_key_exists($activity, $this->roomActivityDataMap)) {
            $this->roomActivityDataMap[$activity]->addReward($sub, $assetId, $amount, $ext_3);
        } else {
            $activityData = new ActivityData($activity);
            $activityData->addReward($sub, $assetId, $amount, $ext_3);
            $this->roomActivityDataMap[$activity] = $activityData;
        }

        return $this;
    }

    public function encodeActivityDataMap(&$activityDataMap)
    {
        $ret = [];
        foreach ($activityDataMap as $activity => $activityData) {
            $ret[$activity] = $activityData->toJson();
        }
        return $ret;
    }

    public function fromJson($jsonObj)
    {
        return $this->roomActivityDataMap = $this->decodeActivityDataMap($jsonObj);
    }

    public function decodeActivityDataMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $activity => $activityDataJson) {
            $activityData = new ActivityData($activity);
            $activityData->fromJson($activityDataJson);
            $ret[$activity] = $activityData;
        }
        return $ret;
    }

    public function toJson()
    {
        $bag = [];
        foreach ($this->roomActivityDataMap as $activity => $activityData) {
            $bag[$activity] = $activityData->toJson();
        }
        return $this->encodeActivityDataMap($this->roomActivityDataMap);
    }

    public function merge($other)
    {
        foreach ($other->roomActivityDataMap as $roomId => $activityData) {
            if (!array_key_exists($roomId, $this->roomActivityDataMap)) {
                $newActivityRoomData = new ActivityData($roomId);
                $newActivityRoomData->merge($activityData);
                $this->roomActivityDataMap[$roomId] = $newActivityRoomData;
            } else {
                $this->roomActivityDataMap[$roomId]->merge($activityData);
            }
        }
        return $this;
    }
}
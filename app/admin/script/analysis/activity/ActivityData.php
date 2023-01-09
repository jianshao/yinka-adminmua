<?php
namespace app\admin\script\analysis\activity;

ini_set('memory_limit', -1);

class ActivityData
{
    public $activity = null;
    public $subActivityDataMap = [];

    public function __construct($activity)
    {
        $this->activity = $activity;
    }

    public function addCount($sub, $count)
    {
        if (array_key_exists($sub, $this->subActivityDataMap)) {
            $this->subActivityDataMap[$sub]->addCount($count);
        } else {
            $subActivity = new SubActivityData($sub);
            $subActivity->addCount($count);
            $this->subActivityDataMap[$sub] = $subActivity;
        }

        return $this;
    }

    public function addConsume($sub, $assetId, $value, $count)
    {
        if (array_key_exists($sub, $this->subActivityDataMap)) {
            $this->subActivityDataMap[$sub]->addConsume($assetId, $value, $count);
        } else {
            $subActivity = new SubActivityData($sub);
            $subActivity->addConsume($assetId, $value, $count);
            $this->subActivityDataMap[$sub] = $subActivity;
        }

        return $this;
    }

    public function addReward($sub, $assetId, $value, $count)
    {
        if (array_key_exists($sub, $this->subActivityDataMap)) {
            $this->subActivityDataMap[$sub]->addReward($assetId, $value, $count);
        } else {
            $subActivity = new SubActivityData($sub);
            $subActivity->addReward($assetId, $value, $count);
            $this->subActivityDataMap[$sub] = $subActivity;
        }

        return $this;
    }

    public function toJson()
    {
        $subActivityDataMap = [];
        foreach ($this->subActivityDataMap as $sub => $subActivity) {
            $subActivityDataMap[$sub] = $subActivity->toJson();
        }
        return $subActivityDataMap;
    }

    public function fromJson($jsonObj)
    {
        foreach ($jsonObj as $sub => $subActivityJson) {
            $subActivity = new SubActivityData($sub);
            $subActivity->fromJson($subActivityJson);
            $this->subActivityDataMap[$sub] = $subActivity;
        }
        return $this;
    }

    public function merge($other)
    {
        foreach ($other->subActivityDataMap as $roomId => $subActivityData) {
            if (!array_key_exists($roomId, $this->subActivityDataMap)) {
                $newSubActivityRoomData = new SubActivityData($roomId);
                $newSubActivityRoomData->merge($subActivityData);
                $this->subActivityDataMap[$roomId] = $newSubActivityRoomData;
            } else {
                $this->subActivityDataMap[$roomId]->merge($subActivityData);
            }
        }
        return $this;
    }
}
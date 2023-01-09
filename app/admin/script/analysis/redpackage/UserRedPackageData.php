<?php
namespace app\admin\script\analysis\redpackage;

class UserRedPackageData
{
    public $uid = 0;
    public $roomDataMap = [];

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function addRoom($roomId, $count, $amount)
    {
        if (array_key_exists($roomId, $this->roomDataMap)) {
            $this->roomDataMap[$roomId]->add($amount, $count);
        } else {
            $redData = new RedData($roomId);
            $redData->add($amount, $count);
            $this->roomDataMap[$roomId] = $redData;
        }
        return $this;
    }

    public function fromJson($jsonObj)
    {
        $this->roomDataMap = $this->decodeRedPackageMap($jsonObj);
        return $this;
    }

    public function decodeRedPackageMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $type => $redDataJson) {
            $redData = new RedData($type);
            $redData->fromJson($redDataJson);
            $ret[$type] = $redData;
        }
        return $ret;
    }

    public function toJson()
    {
        return $this->encodeRedPackageMap($this->roomDataMap);
    }

    public function encodeRedPackageMap(&$roomDataMap)
    {
        $ret = [];
        foreach ($roomDataMap as $type => $redData) {
            $ret[$type] = $redData->toJson();
        }
        return $ret;
    }

    public function merge($other)
    {
        if (!empty($other->roomDataMap)) {
            foreach ($other->roomDataMap as $roomId => $roomData) {
                if (!array_key_exists($roomId, $this->roomDataMap)) {
                    $newRoomData = new RedData($roomId);
                    $newRoomData->merge($roomData);
                    $this->roomDataMap[$roomId] = $newRoomData;
                } else {
                    $this->roomDataMap[$roomId]->merge($roomData);
                }
            }
        }
        return $this;
    }
}
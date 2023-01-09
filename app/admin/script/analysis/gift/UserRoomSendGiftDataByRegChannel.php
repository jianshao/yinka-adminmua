<?php
namespace app\admin\script\analysis\gift;

ini_set('memory_limit', -1);

class UserRoomSendGiftDataByRegChannel
{
    public $reg_channel = '';

    public $roomSendGiftDataMap = [];

    public function __construct($reg_channel)
    {
        $this->reg_channel = $reg_channel;
    }

    public function addRoom($roomId, $giftId, $realGiftId, $count, $type, $consume_amount, $output_amount)
    {
        if (array_key_exists($roomId, $this->roomSendGiftDataMap)) {
            //背包送礼
            $roomData = $this->roomSendGiftDataMap[$roomId];
            if ($type == 3) {
                $roomData->addBag($giftId, $output_amount, $count);
                $roomData->addBagReal($giftId, $realGiftId, $output_amount, $count);
            } else {
                $roomData->addPanel($giftId, $consume_amount, $count);
                $roomData->addPanelReal($giftId, $realGiftId, $output_amount, $count);
            }
        } else {
            $roomSendGiftData = new RoomSendGiftData($roomId);
            if ($type == 3) {
                $roomSendGiftData->addBag($giftId, $output_amount, $count);
                $roomSendGiftData->addBagReal($giftId, $realGiftId, $output_amount, $count);
            } else {
                $roomSendGiftData->addPanel($giftId, $consume_amount, $count);
                $roomSendGiftData->addPanelReal($giftId, $realGiftId, $output_amount, $count);
            }
            $this->roomSendGiftDataMap[$roomId] = $roomSendGiftData;
        }

        return $this;
    }

    public function fromJson($jsonObj)
    {
        return $this->roomSendGiftDataMap = $this->decodeRoomSendGiftDataMap($jsonObj);
    }

    public function decodeRoomSendGiftDataMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $roomId => $roomSendGiftDataJson) {
            $roomSendGiftData = new RoomSendGiftData($roomId);
            $roomSendGiftData->fromJson($roomSendGiftDataJson);
            $ret[$roomId] = $roomSendGiftData;
        }
        return $ret;
    }

    public function toJson()
    {
        return $this->encodeRoomSendGiftDataMap($this->roomSendGiftDataMap);
    }

    public function encodeRoomSendGiftDataMap(&$roomSendGiftDataMap)
    {
        $ret = [];
        foreach ($roomSendGiftDataMap as $roomId => $roomSendGiftData) {
            $ret[$roomId] = $roomSendGiftData->toJson();
        }
        return $ret;
    }

    public function merge($other)
    {
        if (!empty($other->roomSendGiftDataMap)) {
            foreach ($other->roomSendGiftDataMap as $roomId => &$roomSendGiftData) {
                if (!array_key_exists($roomId, $this->roomSendGiftDataMap)) {
                    $newRoomSendGiftData = new RoomSendGiftData($roomId);
                    $newRoomSendGiftData->merge($roomSendGiftData);
                    $this->roomSendGiftDataMap[$roomId] = $newRoomSendGiftData;
                } else {
                    $this->roomSendGiftDataMap[$roomId]->merge($roomSendGiftData);
                }
            }
        }

        return $this;
    }
}
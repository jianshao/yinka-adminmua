<?php
namespace app\admin\script\analysis\gift;

ini_set('memory_limit', -1);

class RoomSendGiftData
{
    public $roomId = 0;
    public $bagSendGiftDataMap = [];
    public $panelSendGiftDataMap = [];

    public function __construct($roomId)
    {
        $this->roomId = $roomId;
    }

    public function addBag($giftId, $amount, $count)
    {
        if (array_key_exists($giftId, $this->bagSendGiftDataMap)) {
            $this->bagSendGiftDataMap[$giftId]->add($amount, $count);
        } else {
            $sendGiftData = new SendGiftData($giftId);
            $sendGiftData->add($amount, $count);
            $this->bagSendGiftDataMap[$giftId] = $sendGiftData;
        }
    }

    public function addBagReal($giftId, $realGiftId, $amount, $count)
    {
        if (array_key_exists($giftId, $this->bagSendGiftDataMap)) {
            $this->bagSendGiftDataMap[$giftId]->addReal($realGiftId, $amount, $count);
        } else {
            $sendGiftData = new SendGiftData($giftId);
            $sendGiftData->addReal($realGiftId, $amount, $count);
            $this->bagSendGiftDataMap[$giftId] = $sendGiftData;
        }
    }

    public function addPanel($giftId, $amount, $count)
    {
        if (array_key_exists($giftId, $this->panelSendGiftDataMap)) {
            $this->panelSendGiftDataMap[$giftId]->add($amount, $count);
        } else {
            $sendGiftData = new SendGiftData($giftId);
            $sendGiftData->add($amount, $count);
            $this->panelSendGiftDataMap[$giftId] = $sendGiftData;
        }
    }

    public function addPanelReal($giftId, $realGiftId, $amount, $count)
    {
        if (array_key_exists($giftId, $this->panelSendGiftDataMap)) {
            $this->panelSendGiftDataMap[$giftId]->addReal($realGiftId, $amount, $count);
        } else {
            $sendGiftData = new SendGiftData($giftId);
            $sendGiftData->addReal($realGiftId, $amount, $count);
            $this->panelSendGiftDataMap[$giftId] = $sendGiftData;
        }
    }

    public function decodeSendGiftDataMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $giftId => $sendGiftDataJson) {
            $sendGiftData = new SendGiftData($giftId);
            $sendGiftData->fromJson($sendGiftDataJson);
            $ret[$giftId] = $sendGiftData;
        }
        return $ret;
    }

    public function encodeSendGiftDataMap(&$sendGiftDataMap)
    {
        $ret = [];
        foreach ($sendGiftDataMap as $giftId => $sendGiftData) {
            $ret[$giftId] = $sendGiftData->toJson();
        }
        return $ret;
    }

    public function fromJson($jsonObj)
    {
        if (array_key_exists('bag', $jsonObj)) {
            $this->bagSendGiftDataMap = $this->decodeSendGiftDataMap($jsonObj['bag']);
        }
        if (array_key_exists('panel', $jsonObj)) {
            $this->panelSendGiftDataMap = $this->decodeSendGiftDataMap($jsonObj['panel']);
        }
        return $this;
    }

    public function toJson()
    {
        return [
            'bag' => $this->encodeSendGiftDataMap($this->bagSendGiftDataMap),
            'panel' => $this->encodeSendGiftDataMap($this->panelSendGiftDataMap),
        ];
    }

    public function merge($other)
    {
        foreach ($other->bagSendGiftDataMap as $giftId => &$sendGiftData) {
            if (!array_key_exists($giftId, $this->bagSendGiftDataMap)) {
                $newSendGiftData = new SendGiftData($giftId);
                $newSendGiftData->merge($sendGiftData);
                $this->bagSendGiftDataMap[$giftId] = $newSendGiftData;
            } else {
                $this->bagSendGiftDataMap[$giftId]->merge($sendGiftData);
            }
        }

        foreach ($other->panelSendGiftDataMap as $giftId => &$sendGiftData) {
            if (!array_key_exists($giftId, $this->panelSendGiftDataMap)) {
                $newSendGiftData = new SendGiftData($giftId);
                $newSendGiftData->merge($sendGiftData);
                $this->panelSendGiftDataMap[$giftId] = $newSendGiftData;
            } else {
                $this->panelSendGiftDataMap[$giftId]->merge($sendGiftData);
            }
        }

        return $this;
    }
}
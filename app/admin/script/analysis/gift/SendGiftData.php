<?php
namespace app\admin\script\analysis\gift;

class SendGiftData
{
    public $giftData = null;
    public $realGiftDataMap = [];

    public function __construct($giftId)
    {
        $this->giftData = new GiftData($giftId);
    }

    public function getGiftId()
    {
        return $this->giftData->giftId;
    }

    public function add($amount, $count)
    {
        $this->giftData->add($amount, $count);
    }

    public function addReal($giftId, $amount, $count)
    {
        if (array_key_exists($giftId, $this->realGiftDataMap)) {
            $this->realGiftDataMap[$giftId]->add($amount, $count);
        } else {
            $giftData = new GiftData($giftId);
            $giftData->add($amount, $count);
            $this->realGiftDataMap[$giftId] = $giftData;
        }
        return $this;
    }

    public function toJson()
    {
        $jsonObj = $this->giftData->toJson();
        $realGiftDataMap = [];
        foreach ($this->realGiftDataMap as $giftId => $giftData) {
            $realGiftDataMap[$giftId] = $giftData->toJson();
        }
        $jsonObj['real'] = $realGiftDataMap;
        return $jsonObj;
    }

    public function fromJson($jsonObj)
    {
        $this->giftData->fromJson($jsonObj);
        if (array_key_exists('real', $jsonObj)) {
            foreach ($jsonObj['real'] as $giftId => $giftDataJson) {
                $giftData = new GiftData($giftId);
                $giftData->fromJson($giftDataJson);
                $this->realGiftDataMap[$giftId] = $giftData;
            }
        }
        return $this;
    }

    public function merge($other)
    {
        $this->giftData->merge($other->giftData);
        foreach ($other->realGiftDataMap as $giftId => $giftData) {
            if (!array_key_exists($giftId, $this->realGiftDataMap)) {
                $newGiftData = new GiftData($giftId);
                $newGiftData->merge($giftData);
                $this->realGiftDataMap[$giftId] = $newGiftData;
            } else {
                $this->realGiftDataMap[$giftId]->merge($giftData);
            }
        }
        return $this;
    }
}
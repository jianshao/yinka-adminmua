<?php
namespace app\admin\script\analysis\activity;

use app\admin\script\analysis\activity\CalculateData;

ini_set('memory_limit', -1);

class SubActivityData
{
    public $sub = null;
    public $count = 0;
    public $consumeMap = [];
    public $rewardMap = [];

    public function __construct($sub)
    {
        $this->sub = $sub;
    }

    public function getGiftId()
    {
        return $this->sub->giftId;
    }

    public function addCount($count)
    {
        $this->count += $count;

        return $this;
    }

    public function addConsume($assetId, $value, $count)
    {
        if (array_key_exists($assetId, $this->consumeMap)) {
            $this->consumeMap[$assetId]->add($value, $count);
        } else {
            $calculateData = new CalculateData($assetId);
            $calculateData->add($value, $count);
            $this->consumeMap[$assetId] = $calculateData;
        }

        return $this;
    }

    public function addReward($assetId, $value, $count)
    {
        if (array_key_exists($assetId, $this->rewardMap)) {
            $this->rewardMap[$assetId]->add($value, $count);
        } else {
            $calculateData = new CalculateData($assetId);
            $calculateData->add($value, $count);
            $this->rewardMap[$assetId] = $calculateData;
        }

        return $this;
    }

    public function toJson()
    {
        $consumeDataMap = [];
        foreach ($this->consumeMap as $assetId => $calculateData) {
            $consumeDataMap[$assetId] = $calculateData->toJson();
        }

        $rewardDataMap = [];
        foreach ($this->rewardMap as $assetId => $calculateData) {
            $rewardDataMap[$assetId] = $calculateData->toJson();
        }

        $jsonObj['count'] = $this->count;
        $jsonObj['consume'] = $consumeDataMap;
        $jsonObj['reward'] = $rewardDataMap;
        return $jsonObj;
    }

    public function fromJson($jsonObj)
    {
        // echo "SubActivityData@fromJson::>>>>>" . PHP_EOL;

        $this->count = $jsonObj['count'];
        if (array_key_exists('consume', $jsonObj)) {
            foreach ($jsonObj['consume'] as $assetId => $calculateData) {
                $newcalculateData = new CalculateData($assetId);
                $newcalculateData->fromJson($calculateData);
                $this->consumeMap[$assetId] = $newcalculateData;
            }
        }

        if (array_key_exists('reward', $jsonObj)) {
            foreach ($jsonObj['reward'] as $assetId => $calculateData) {
                $newcalculateData = new CalculateData($assetId);
                $newcalculateData->fromJson($calculateData);
                $this->rewardMap[$assetId] = $newcalculateData;
            }
        }
        // echo "SubActivityData@fromJson::<<<<<" . PHP_EOL;

        return $this;
    }

    public function merge($other)
    {
        $this->count += $other->count;
        foreach ($other->consumeMap as $assetId => $consumeData) {
            if (!array_key_exists($assetId, $this->consumeMap)) {
                $newConsumeData = new CalculateData($assetId);
                $newConsumeData->merge($consumeData);
                $this->consumeMap[$assetId] = $newConsumeData;
            } else {
                $this->consumeMap[$assetId]->merge($consumeData);
            }
        }

        foreach ($other->rewardMap as $assetId => $rewardData) {
            if (!array_key_exists($assetId, $this->rewardMap)) {
                $newRewardData = new CalculateData($assetId);
                $newRewardData->merge($rewardData);
                $this->rewardMap[$assetId] = $newRewardData;
            } else {
                $this->rewardMap[$assetId]->merge($rewardData);
            }
        }
        return $this;
    }

}
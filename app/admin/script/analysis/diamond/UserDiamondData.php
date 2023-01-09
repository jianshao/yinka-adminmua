<?php
namespace app\admin\script\analysis\diamond;

ini_set('memory_limit', -1);

class UserDiamondData
{
    public $uid = 0;
    public $incomeMap = [];
    public $spendMap = [];

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function addIncome($type, $count, $amount)
    {
        if (array_key_exists($type, $this->incomeMap)) {
            $this->incomeMap[$type]->add($amount, $count);
        } else {
            $diamondData = new DiamondData($type);
            $diamondData->add($amount, $count);
            $this->incomeMap[$type] = $diamondData;
        }

        return $this;
    }

    public function addSpend($type, $count, $amount)
    {
        if (array_key_exists($type, $this->spendMap)) {
            $this->spendMap[$type]->add($amount, $count);
        } else {
            $diamondData = new DiamondData($type);
            $diamondData->add($amount, $count);
            $this->spendMap[$type] = $diamondData;
        }

        return $this;
    }

    public function fromJson($jsonObj)
    {
        if (array_key_exists('income', $jsonObj)) {
            $this->incomeMap = $this->decodeDiamondMap($jsonObj['income']);
        }

        if (array_key_exists('spend', $jsonObj)) {
            $this->spendMap = $this->decodeDiamondMap($jsonObj['spend']);
        }
        return $this;
    }

    public function decodeDiamondMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $type => $diamondDataJson) {
            $diamondData = new DiamondData($type);
            $diamondData->fromJson($diamondDataJson);
            $ret[$type] = $diamondData;
        }
        return $ret;
    }

    public function toJson()
    {
        return [
            'income' => $this->encodeDiamondMap($this->incomeMap),
            'spend' => $this->encodeDiamondMap($this->spendMap),
        ];
    }

    public function encodeDiamondMap(&$diamondDataMap)
    {
        $ret = [];
        if (!empty($diamondDataMap)) {
            foreach ($diamondDataMap as $type => $diamondData) {
                $ret[$type] = $diamondData->toJson();
            }
        }
        return $ret;
    }

    public function merge($other)
    {
        if (!empty($other->incomeMap)) {
            foreach ($other->incomeMap as $type => $diamondData) {
                if (!array_key_exists($type, $this->incomeMap)) {
                    // echo $type . PHP_EOL;
                    $newDiamondData = new DiamondData($type);
                    $newDiamondData->merge($diamondData);
                    $this->incomeMap[$type] = $newDiamondData;
                } else {
                    $this->incomeMap[$type]->merge($diamondData);
                }
            }
        }
        if (!empty($other->spendMap)) {
            foreach ($other->spendMap as $type => $diamondData) {
                if (!array_key_exists($type, $this->spendMap)) {
                    $newDiamondData = new DiamondData($type);
                    $newDiamondData->merge($diamondData);
                    $this->spendMap[$type] = $newDiamondData;
                } else {
                    $this->spendMap[$type]->merge($diamondData);
                }
            }
        }

        return $this;
    }
}
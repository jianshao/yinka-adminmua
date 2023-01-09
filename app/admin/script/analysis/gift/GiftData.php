<?php
namespace app\admin\script\analysis\gift;

class GiftData
{
    public $giftId = 0;
    public $amount = 0;
    public $count = 0;

    public function __construct($giftId)
    {
        $this->giftId = $giftId;
    }

    public function toJson()
    {
        return [
            'amount' => $this->amount,
            'count' => $this->count,
        ];
    }

    public function fromJson($jsonObj)
    {
        $this->amount = $jsonObj['amount'];
        $this->count = $jsonObj['count'];
        return $this;
    }

    public function add($amount, $count)
    {
        $this->amount += $amount;
        $this->count += $count;
        return $this;
    }

    public function merge($others)
    {
        $this->amount += $others->amount;
        $this->count += $others->count;

        return $this;
    }
}
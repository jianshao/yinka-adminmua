<?php
namespace app\admin\script\analysis\charge;

class ChargeData
{
    public $channel = '';
    public $amount = 0;
    public $count = 0;

    public function __construct($channel)
    {
        $this->channel = $channel;
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

    public function merge($other)
    {
        $this->amount += $other->amount;
        $this->count += $other->count;
        return $this;
    }
}
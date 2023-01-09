<?php
namespace app\admin\script\analysis\activity;

class CalculateData
{
    public $assetId = null;
    public $value = 0;
    public $count = 0;

    public function __construct($assetId)
    {
        $this->assetId = $assetId;
    }

    public function toJson()
    {
        return [
            'count' => $this->count,
            'value' => $this->value,
        ];
    }

    public function fromJson($jsonObj)
    {
        $this->count = $jsonObj['count'];
        $this->value = $jsonObj['value'];
        return $this;
    }

    public function add($value, $count)
    {
        $this->count += $count;
        $this->value += $value;
        return $this;
    }

    public function merge($other)
    {
        $this->count += $other->count;
        $this->value += $other->value;
        return $this;
    }
}

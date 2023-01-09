<?php
namespace app\admin\script\analysis\firstcharge;

class FirstchargeData
{
    public $uid = 0;
    public $firstChargeTime = 0;
    public $dataMap=[];

    public function __construct($uid)
    {
        $this->uid = $uid;
    }


    public function add($uid,$chargetime)
    {
        $this->dataMap['uid'] = $uid;
        $this->dataMap['firstChargeTime'] = $chargetime;
    }


    public function fromJson($jsonObj)
    {
         $this->dataMap['uid'] = $jsonObj['uid'];
         $this->dataMap['firstChargeTime'] = $jsonObj['firstChargeTime'];
         return $this;
    }


    public function toJson()
    {
        return $this->dataMap;
    }


    public function merge($other)
    {
        if (!empty($other->dataMap)) {
           $this->dataMap = $other->dataMap;
        }
        return $this;
    }
}
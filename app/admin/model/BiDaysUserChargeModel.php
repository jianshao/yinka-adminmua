<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiDaysUserChargeModel extends ModelDao

{
    protected $table = 'bi_days_user_charge';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiDaysUserChargeModel();
        }
        return self::$instance;
    }



    public function getChargeSumAndNumber($condition){
       return  $this->getModel()->where($condition)->select()->toArray();
    }



    public function getchargesum($uids,$start,$end=''){

        if(empty($uids)){
            return 0;
        }

        if(empty($end)){
            $end = date('Y-m-d',strtotime("+1days"));
        }

        return  $this->getModel()
            ->where('uid','in',$uids)
            ->where("date",">=",$start)
            ->where("date","<",$end)
            ->sum("amount");
    }


}


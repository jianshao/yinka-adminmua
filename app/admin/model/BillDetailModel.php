<?php
//对账明细数据

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BillDetailModel extends ModelDao
{
    protected $table = 'zb_bill_detail';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BillDetailModel();
        }
        return self::$instance;
    }
}

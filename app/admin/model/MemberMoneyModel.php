<?php

// !!!
namespace app\admin\model;

use app\core\mysql\ModelDao;

class MemberMoneyModel extends ModelDao
{
    protected $table = 'yyht_money';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberMoneyModel();
        }
        return self::$instance;
    }
}

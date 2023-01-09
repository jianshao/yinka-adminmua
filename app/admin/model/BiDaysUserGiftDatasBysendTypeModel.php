<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiDaysUserGiftDatasBysendTypeModel extends ModelDao
{
    protected $table = 'bi_days_user_gift_datas_bysend_type';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

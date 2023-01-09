<?php
/**
 * User: li
 * Date: 2019
 * 金币
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiHwDailyDayModel extends ModelDao
{

    protected $table = 'bi_hw_daily_day';
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
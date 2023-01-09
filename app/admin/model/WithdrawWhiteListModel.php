<?php
/**
 * @author ly
 * 用户举报表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class WithdrawWhiteListModel extends ModelDao
{
    protected $table = 'zb_withdraw_white_list';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new WithdrawWhiteListModel();
        }
        return self::$instance;
    }

}
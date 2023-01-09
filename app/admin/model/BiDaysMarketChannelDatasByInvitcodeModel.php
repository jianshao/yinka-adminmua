<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiDaysMarketChannelDatasByInvitcodeModel extends ModelDao
{
    protected $table = 'bi_days_market_channel_data_by_invitcode';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiDaysMarketChannelDatasByInvitcodeModel();
        }
        return self::$instance;
    }

}
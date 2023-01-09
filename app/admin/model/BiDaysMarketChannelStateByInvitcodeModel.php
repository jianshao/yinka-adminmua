<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiDaysMarketChannelStateByInvitcodeModel extends ModelDao
{
    protected $table = 'bi_days_market_channel_stats_by_invitcode';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BiDaysMarketChannelStateByInvitcodeModel();
        }
        return self::$instance;
    }

}

<?php
namespace app\admin\script\analysis;

use think\facade\Db;

class CalculateCommon
{
    protected const CALCULATE_COLUMN = [
        1 => 'promote_channel',
        2 => 'register_channel',
    ];

    protected const TABLE_5MINS = 'bi_user_stats_5mins'; //5分钟统计表
    protected const TABLE_1DAY = 'bi_user_stats_1day'; //1日统计表

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //注册
    protected function dealReg()
    {

    }

    /*
     *获取注册用户
     */
    public static function getRegUsers2($start, $end, $promote_channel = 0)
    {
        $query = Db::table(self::TABLE_5MINS)
            ->field('distinct(uid) as uid')
            ->where('register_time', '>=', $start)
            ->where('register_time', '<', $end);

        if ($promote_channel != 0) {
            $query = $query->where('promote_channel', $promote_channel);
        }

        return $query->select()->toArray();
    }

    /*
     *获取活跃用户
     */
    public static function getActiveUsers($start, $end, $promote_channel = 0)
    {
        $query = Db::table(self::TABLE_5MINS)
            ->field('distinct(uid) as uid')
            ->where('date', '>=', $start)
            ->where('date', '<', $end);

        if ($promote_channel != 0) {
            $query = $query->where('promote_channel', $promote_channel);
        }

        return $query->select()->column('uid');
    }

    /*
     *充值
     */
    public static function getCharge($start, $end, array $users, $is_count = false)
    {
        $query = Db::table('zb_chargedetail')
            ->where('addtime', '>=', $start)
            ->where('addtime', '<=', $end)
            ->where('status', 'in', [1, 2])
            ->where('uid', 'in', $users);

        if ($is_count) {
            $res = $query->field('distinct(uid) as uid')->select()->toArray();
        } else {
            $res = $query->sum('rmb');
        }

        return $res;
    }

    //登陆
    public static function dealLogin()
    {

    }

    //充值
    public static function dealPay()
    {

    }

    //首冲
    public static function dealFirstPay()
    {

    }

    //代理充值
    public static function dealAgentPay()
    {

    }

    //送礼
    public static function dealSendGift()
    {

    }

    //收礼数据
    public static function dealRecGift()
    {

    }

    //发红包
    public static function dealSendPackage()
    {

    }

    //领红包
    public static function dealGetRedPackage()
    {

    }

    //返还红包
    public static function dealReturnRedPackage()
    {

    }

    // 活动
    public static function dealActivity()
    {

    }

    //钻石
    public static function dealDiamond()
    {

    }

    //兑换用户
    public static function dealWithdraw()
    {

    }
}

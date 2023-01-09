<?php
/**
 * 常量及变量配置
 * */
namespace app\admin\common;

class CommonConst
{
    const _10001_充值 = 10001;
    const _10002_送礼 = 10002;
    const _10003_收礼 = 10003;
    const _10004_钻石兑换 = 10004;
    const _10005_商城购买 = 10005;
    const _10006_任务奖励 = 10006;
    const _10007_爵位变化 = 10007;
    const _10008_VIP变化 = 10008;
    const _10009_活动 = 10009;
    const _10010_红包 = 10010;
    const _10011_等级特权 = 10011;
    const _10012_抢红包 = 10012;
    const _10013_返还红包 = 10013;
    const _10014_工会代充 = 10014;
    const _10015_提现预扣除 = 10015;
    const _10016_提现成功 = 10016;
    const _10017_提现拒绝 = 10017;
    const _10020_运营调整 = 10020;
    const _10021_打开背包礼物 = 10021;
    const _10022_淘金活动过期 = 10022;
    const _10023_道具action = 10023; //道具action
    const _10024_商城赠送 = 10024; //商城赠送
    const _10025_商城接收 = 10025; //商城接收
    const _10026_礼物action = 10026; //礼物action
    const _10027_音豆兑换金币 = 10027; //音豆兑换金币

    const EVENTS_MAP = [
        self::_10001_充值 => '充值',
        self::_10002_送礼 => '送礼',
        self::_10003_收礼 => '收礼',
        self::_10004_钻石兑换 => '钻石兑换',
        self::_10005_商城购买 => '商城购买',
        self::_10006_任务奖励 => '任务奖励',
        self::_10007_爵位变化 => '爵位变化',
        self::_10008_VIP变化 => 'VIP变化',
        self::_10009_活动 => '活动',
        self::_10010_红包 => '红包',
        self::_10011_等级特权 => '等级特权',
        self::_10012_抢红包 => '抢红包',
        self::_10013_返还红包 => '返还红包',
        self::_10014_工会代充 => '工会代充',
        self::_10015_提现预扣除 => '提现预扣除',
        self::_10016_提现成功 => '提现成功',
        self::_10017_提现拒绝 => '提现拒绝',
        self::_10020_运营调整 => '运营调整',
        self::_10021_打开背包礼物 => '打开背包礼物',
        self::_10022_淘金活动过期 => '淘金活动过期',
        self::_10023_道具action => '道具action',
        self::_10024_商城赠送 => '商城赠送',
        self::_10025_商城接收 => '商城接收',
        self::_10026_礼物action => '礼物action',
        self::_10027_音豆兑换金币 => '音豆兑换金币',
    ];

    const DUKE = [
        '1' => '游侠',
        '2' => '骑士',
        '3' => '伯爵',
        '4' => '公爵',
        '5' => '国王',
    ];

    const GOPHER_MAP = [
        1 => '4倍地鼠',
        2 => '8倍地鼠',
        3 => '16倍地鼠',
        4 => '32倍地鼠',
        99 => '4倍地鼠王',
        'king' => '地鼠王',
    ];

    const MISSION_MAP = [
        'activeBoxDay' => ['desc' => '日活跃度任务'],
        'activeBoxWeek' => ['desc' => '周活跃度任务'],
        'daily' => ['desc' => '每日任务'],
        'newer' => ['desc' => '新手任务'],
        'weekCheckin' => ['desc' => '签到'],
    ];

    const AVTIVITY_MAP = [
        'box' => ['desc' => '砸蛋'],
        'coin_lottery' => ['desc' => '金币抽奖'],
        'coin_lottey' => ['desc' => '金币抽奖'],
        'fuxing' => ['desc' => '福星'],
        'return_user' => ['desc' => ''],
        'silver' => ['desc' => ''],
        'sweetJourney' => ['desc' => '淘金之旅', 'list' => [1 => '提莫斯宝箱', 2 => '宙斯宝箱', 3 => '盖亚宝箱']],
        'box2' => ['desc' => '新砸蛋', 'list' => [1 => '提莫斯宝箱', 2 => '宙斯宝箱', 3 => '盖亚宝箱']],
        'duobao3' => ['desc' => '三人夺宝'],
        '1' => ['desc' => ''],
        '2' => ['desc' => ''],
        '520_love' => ['desc' => '520活动'],
        'luck_star' => ['desc' => '辛运之星'],
        'taojin' => ['desc' => '淘金之旅'],
    ];

    const SUB_AVTIVITY_MAP = [
        'box' => '砸蛋',
        'coin_lottery' => '金币抽奖',
        'coin_lottey' => '金币抽奖',
        'fuxing' => '福星',
        'return_user' => '',
        'silver' => '',
        'sweetJourney' => '',
        'box2' => '新砸蛋',
        'duobao3' => '三人夺宝',
        '1' => '',
        '2' => '',
        '520_love' => '520活动',
        'luck_star' => '辛运之星',
        'taojin' => '淘金',
    ];

    public static $mall_map = [
        'bean' => ['desc' => '背包商城', 'list' => []],
        'coin' => ['desc' => '金币商城', 'list' => []],
        'game' => ['desc' => '游戏商城', 'list' => []],
        'ore' => ['desc' => '淘金商城', 'list' => []],
    ];

    public static $event_column_map = [
        self::_10001_充值 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],

        self::_10002_送礼 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '数量', 'ext_4' => '豆', 'ext_5' => ''],
        self::_10003_收礼 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '数量', 'ext_4' => '豆', 'ext_5' => ''],

        self::_10004_钻石兑换 => ['ext_1' => '钻石', 'ext_2' => '豆', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10005_商城购买 => ['ext_1' => [], 'ext_2' => '商品ID', 'ext_3' => '数量', 'ext_4' => '购买类型', 'ext_5' => ''],
        self::_10006_任务奖励 => ['ext_1' => self::MISSION_MAP, 'ext_2' => '任务ID', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10007_爵位变化 => ['ext_1' => '爵位前', 'ext_2' => '爵位后', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],

        self::_10008_VIP变化 => ['ext_1' => 'VIP前', 'ext_2' => 'VIP后', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],

        self::_10009_活动 => ['ext_1' => self::AVTIVITY_MAP, 'ext_2' => '子活动ID', 'ext_3' => '次数', 'ext_4' => '', 'ext_5' => ''],

        self::_10010_红包 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10011_等级特权 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10012_抢红包 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10013_返还红包 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],

        self::_10014_工会代充 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10015_提现预扣除 => ['ext_1' => ['desc' => '订单号', 'list' => []], 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10016_提现成功 => ['ext_1' => ['desc' => '订单号', 'list' => []], 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10017_提现拒绝 => ['ext_1' => ['desc' => '订单号', 'list' => []], 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10020_运营调整 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10021_打开背包礼物 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10022_淘金活动过期 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10023_道具action => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10024_商城赠送 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10025_商城接收 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10026_礼物action => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
        self::_10027_音豆兑换金币 => ['ext_1' => '', 'ext_2' => '', 'ext_3' => '', 'ext_4' => '', 'ext_5' => ''],
    ];

    const TYPE_MAP = [
        1 => '道具',
        2 => '银行',
        3 => '礼物',
        4 => '豆',
        5 => '钻石',
        6 => '金币',
        7 => '体力',
        8 => '铁矿石',
        9 => '',
    ];

    const GAME_ASSET_MAP = [
        'gold' => '金矿石',
        'silver' => '银矿石',
        'iron' => '铁矿石',
        'fossil' => '化石',
        'bean' => '豆',
        'prop' => '道具',
        'gift' => '礼物',
    ];

    const ASSET_MAP = [
        'user:bean' => '音豆',
        'user:diamond' => '钻石',
        'user:coin' => '金币',
        'prop:道具id' => '道具资产',
        'gift:礼物id' => '礼物资产',
        'ore:iron' => '铁矿石',
        'ore:silver' => '银矿石',
        'ore:gold' => '金矿石',
        'ore:fossil' => '化石',
        'bank:game:score' => '积分',
        'bank:chip:silver' => '银碎片',
        'bank:chip:gold' => '金碎片',
        'user:vip' => 'vip特权',
        'user:svip' => 'svip特权',
        'user:vip_month' => '月vip特权',
        'user:svip_month' => '月svip特权',
    ];

    const USER_ASSET_MAP = [
        'user:bean' => '豆',
        'user:diamond' => '钻石',
        'user:coin' => '金币',
        // 'prop:道具id' => '道具资产',
        // 'gift:礼物id' => '礼物资产',
        'ore:iron' => '铁矿石',
        'ore:silver' => '银矿石',
        'ore:gold' => '金矿石',
        'ore:fossil' => '化石',
        'bank:game:score' => '积分',
        'bank:chip:silver' => '银碎片',
        'bank:chip:gold' => '金碎片',
        'user:vip' => 'vip特权',
        'user:svip' => 'svip特权',
        'user:vip_month' => '月vip特权',
        'user:svip_month' => '月svip特权',
    ];

    const ASSET_TYPE_MAP = [
        1 => 'prop:',
        2 => 'bank:',
        3 => 'gift:',
        4 => 'user:',
        6 => 'user:',
        7 => 'user:',
        8 => 'ore:',
    ];

    const MALL_ASSET_MAP = [
        'avatar' => '头像框',
        'bubble' => '气泡',
        'mount' => '坐骑',
        'circle' => '麦位光圈',
        'simple' => '普通道具',
        'gift' => '礼物',
        'asset' => '资产',
    ];

    public static $game_map = [
        'gopher' => '打地鼠',
        'box2' => '砸蛋',
        'box' => '旧砸蛋',
        'turntable' => '转盘',
        'taojin' => '淘金',
    ];

    public static $BUY = 'buy';
    public static $VIP = 'vip';
    public static $SVIP = 'svip';
    public static $GOLD_BOX = 'goldBox';
    public static $SILVER_BOX = 'silverBox';
    public static $FIRST_PAY = 'firstPay';
    public static $DUKE = 'duke';
    public static $ACTIVITY = 'activity';
    public static $LEVEL = 'level';

    public static $buy_type = [
        'buy' => '购买',
        'vip' => 'VIP',
        'svip' => 'SVIP',
        'goldBox' => '金宝箱',
        'silverBox' => '银宝箱',
        'firstPay' => '首充',
        'duke' => '爵位',
        'activity' => '活动',
        'level' => '爵位等级',
    ];
}

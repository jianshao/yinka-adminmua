<?php

namespace constant;

class UserAssetConstant
{
    // 资产类型
    const PROP_TYPE = 1;
    const BANK_TYPE = 2;
    const GIFT_TYPE = 3;
    const BEAN_TYPE = 4;
    const DIAMOND_TYPE = 5;
    const COIN_TYPE = 6;
    const ENERGY_TYPE = 7;
    const ORE_TYPE = 8;

    // 资产类型
    const BEAN = 'bean';
    const DIAMOND = 'diamond';
    const COIN = 'coin';
    const ACTIVE_DEGREE = 'active_degree';
    const VIP = 'vip';
    const SVIP = 'svip';
    const VIP_MONTH = 'vip_month';
    const SVIP_MONTH = 'svip_month';
    const TAOJIN_ENERGY = 'energy';
    const TAOJIN_ORE_IRON = 'iron';
    const TAOJIN_ORE_SILVER = 'silver';
    const TAOJIN_ORE_GOLD = 'gold';
    const TAOJIN_ORE_FOSSIL = 'fossil';

    const ASSET_MAP = [
        self::BEAN => 'M豆',
        self::DIAMOND => '钻石',
        self::COIN => '金币',
        self::ACTIVE_DEGREE => '活跃度',
        self::VIP => 'VIP',
        self::SVIP => 'SVIP',
        self::VIP_MONTH => 'vip_month',
        self::SVIP_MONTH => 'svip_month',
        self::TAOJIN_ENERGY => '体力',
        self::TAOJIN_ORE_IRON => '铁矿',
        self::TAOJIN_ORE_SILVER => '银矿',
        self::TAOJIN_ORE_GOLD => '金矿',
        self::TAOJIN_ORE_FOSSIL => '化石',
    ];

    const TYPE_MAP = [
        self::PROP_TYPE => '道具',
        self::BANK_TYPE => '银行',
        self::GIFT_TYPE => '礼物',
        self::BEAN_TYPE => 'M豆',
        self::DIAMOND_TYPE => '钻石',
        self::COIN_TYPE => '金币类型',
        self::ENERGY_TYPE => '体力',
        self::ORE_TYPE => '矿石',
    ];

    //eventId
    const CHARGE_EVENTID = 10001;
    const SEND_GIFT_EVENTID = 10002;
    const RECEIVE_GIFT_EVENTID = 10003;
    const DIAMOND_EXCHANGE_EVENTID = 10004;
    const BUY_EVENTID = 10005;
    const TASK_EVENTID = 10006;
    const DUKE_EVENTID = 10007;
    const VIP_EVENTID = 10008;
    const ACTIVITY_EVENTID = 10009;
    const REDPACKETS_EVENTID = 10010;
    const PRIVILEGE_REWARD_EVENTID = 10011;
    const REDPACKETS_GRAB_EVENTID = 10012;
    const REDPACKETS_RETURN_EVENTID = 10013;

    const BI_EVENT_MAP = [
        self::CHARGE_EVENTID => '充值',
        self::SEND_GIFT_EVENTID => '送礼',
        self::RECEIVE_GIFT_EVENTID => '收礼',
        self::DIAMOND_EXCHANGE_EVENTID => '钻石兑换',
        self::BUY_EVENTID => '商城购买',
        self::TASK_EVENTID => '任务中心',
        self::DUKE_EVENTID => '爵位',
        self::VIP_EVENTID => 'VIP',
        self::ACTIVITY_EVENTID => '活动',
        self::REDPACKETS_EVENTID => '发红包',
        self::PRIVILEGE_REWARD_EVENTID => '等级特权',
        self::REDPACKETS_GRAB_EVENTID => '抢红包',
        self::REDPACKETS_RETURN_EVENTID => '返还红包',
    ];
}
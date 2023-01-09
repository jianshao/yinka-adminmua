<?php

namespace app\admin\common;

use app\admin\common\CommonConst;
use app\common\RedisCommon;

class CommonConfig
{

    protected static $instance;
    public $gift_map = [];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getGift()
    {
        $gift_conf = $this->getRedisConf('gift_conf');
        $gift_names = array_column($gift_conf, 'name', 'giftId');
        foreach ($gift_names as $gift_id => $gift_name) {
            $gift_map[$gift_id]['desc'] = $gift_name;
            $gift_map[$gift_id]['list'][$gift_id] = $gift_name;
        }
        return $gift_map;
    }

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $goods_conf = $this->getRedisConf('goods_conf');
        $goods_list = [];
        foreach ($goods_conf as $key => $good) {
            $goods_list[$good['goodsId']] = $good['name'];
        }
        $gifts = $this->getGift();
        CommonConst::$event_column_map[CommonConst::_10002_送礼]['ext_1'] = $gifts;
        CommonConst::$event_column_map[CommonConst::_10002_送礼]['ext_1'] = $gifts;
        CommonConst::$event_column_map[CommonConst::_10003_收礼]['ext_1'] = $gifts;
        CommonConst::$event_column_map[CommonConst::_10003_收礼]['ext_2'] = $gifts;

        CommonConst::$mall_map['bean']['list'] = $goods_list;
        CommonConst::$mall_map['coin']['list'] = $goods_list;
        CommonConst::$mall_map['game']['list'] = $goods_list;
        CommonConst::$mall_map['ore']['list'] = $goods_list;
        CommonConst::$event_column_map[CommonConst::_10005_商城购买]['ext_1'] = CommonConst::$mall_map;
    }

    public function getRedisConf($key)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $json = $redis->get($key);

        return json_decode($json, true, 512);
    }

    public function getCommonConfig()
    {
        return CommonConst::$event_column_map;
    }
}
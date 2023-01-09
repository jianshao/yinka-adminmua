<?php

namespace app\admin\service;

use app\admin\common\RedisKeysConst;
use app\common\RedisCommon;

class HandleRedisService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //清除用户缓存信息
    public function delUserCache($userId)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->del(sprintf(RedisKeysConst::USER_INFO_CACHE, (int) $userId));
    }
}

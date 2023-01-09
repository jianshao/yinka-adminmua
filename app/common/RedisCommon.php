<?php

namespace app\common;

use think\App;

/**
 * redis类
 */
class RedisCommon
{
    protected static $instance;
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RedisCommon();
        }
        return self::$instance;
    }

    //获取redis实例
    public function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.redis');
        $param['host'] = $redis_result['host'];
        $param['port'] = $redis_result['port'];
        $param['password'] = $redis_result['password'];
        $param['select'] = 0;
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $param[$k] = $v;
            }
        }

        $this->handler = new \Redis;
        $this->handler->connect($param['host'], $param['port'], 0);
        if ('' != $param['password']) {
            $this->handler->auth($param['password']);
        }

        if (0 != $param['select']) {
            $this->handler->select($param['select']);
        }
        return $this->handler;
    }

}

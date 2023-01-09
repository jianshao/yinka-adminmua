<?php

namespace app\admin\service;

use app\admin\model\ConfigModel;
use app\common\RedisCommon;
use think\facade\Log;

class GopherConfService
{
    protected static $instance;
    protected $conf_key = 'gopher_conf';
    protected $gopher_conf = [];
    protected $poolsMap = [];

    public function __construct()
    {
        $this->gopher_conf = $this->getConfObj();

        $pools = $this->checkObjKey($this->gopher_conf, 'pools');

        $this->poolsMap = array_column($pools, null, 'poolId');
    }

    public function checkObjKey(object $conf, $key)
    {
        return isset($conf->$key) ? $conf->$key : [];
    }

    public function checkArrKey(array $conf, $key)
    {
        return isset($conf[$key]) ? $conf[$key] : [];
    }

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function getConfObj()
    {
        $this->gopher_conf = ConfigModel::getInstance()->getModel()->where('name', $this->conf_key)->value('json');
        return json_decode($this->gopher_conf);
    }

    public function saveConf($json)
    {
        return ConfigModel::getInstance()->getModel()->where('name', $this->conf_key)->update(['json' => $json]);
    }

    public function getConf()
    {
        return $this->gopher_conf;
    }

    public function setBaseInfo($instance, $data)
    {
        $instance->getConf()->kingReward = $data['kingReward'];
        $instance->getConf()->kingRate = $data['kingRate'];
        $instance->getConf()->poolRate = $data['poolRate'];
        $instance->getConf()->isOpen = $data['isOpen'];

        return $instance->saveConf(json_encode($instance->getConf()));
    }

    /*
     *公屏值
     */
    public function getKingReward()
    {
        return $this->gopher_conf->kingReward;
    }

    /*
     *公屏值
     */
    public function setKingReward($value)
    {
        $this->gopher_conf->kingReward = $value;
        return $this;
    }

    /*
     *飘瓶值
     */
    public function getKingRate()
    {
        return $this->gopher_conf->kingRate;
    }

    /*
     *飘瓶值
     */
    public function setKingRate($value)
    {
        $this->gopher_conf->kingRate = $value;
        return $this;
    }

    /*
     *死亡率
     */
    public function getPoolRate()
    {
        return $this->gopher_conf->poolRate;
    }

    /*
     *死亡率
     */
    public function setPoolRate($value)
    {
        $this->gopher_conf->poolRate = $value;
        return $this;
    }

    /*
     *死亡率
     */
    public function isOpen()
    {
        return isset($this->gopher_conf->isOpen) ? $this->gopher_conf->isOpen : 0;
    }

    /*
     *死亡率
     */
    public function setIsOpen($value)
    {
        $this->gopher_conf->isOpen = $value;
        return $this;
    }

    /*
     *获取奖池
     */
    public function getPoolsMap()
    {
        return $this->poolsMap;
    }

    /*
     *获取奖池列表
     */
    public function getPoolsList()
    {
        $poolList = [];
        foreach ($this->poolsMap as $poolId => $pool) {
            $items = array_column($pool->items, null, 0);
            $poolList[$poolId]['poolId'] = $poolId;
            $poolList[$poolId]['win'] = $items[1][1];
            $poolList[$poolId]['unwin'] = $items[0][1];
        }
        return array_values($poolList);
    }

    /*
     *获取奖池
     */
    public function getPools($poolId)
    {
        return $this->poolsMap[$poolId];
    }

    /*
     *配置奖池
     */
    public function setPools(array $data)
    {
        $poolId = $data['poolId'];
        $poolInfo = $this->poolsMap[$poolId];

        $items = $poolInfo->items;
        $items_map = array_column($items, null, 0);

        $items_map[0][1] = $data['unwin'];
        $items_map[1][1] = $data['win'];
        $poolInfo->items = array_values($items_map);

        return $this->saveConf(json_encode($this->getConf()));
    }

    public function refreshPool($poolId, $operatorId)
    {
        $socket_url = config('config.game_api_url') . 'iapi/refreshGopherPool';
        $data = ['poolId' => $poolId, 'operatorId' => $operatorId];
        $res = curlData($socket_url, json_encode($data), 'POST', 'json');

        Log::info('refreshPool:请求地址:{url},参数====>{data},返回值===>{res}', ['url' => $socket_url, 'data' => json_encode($data), 'res' => $res]);
        return json_decode($res, true);
    }

    public function getPoolInfo($poolId)
    {
        $socket_url = config('config.game_api_url') . 'iapi/getGopherPool';
        $data = ['poolId' => $poolId];
        $res = curlData($socket_url, json_encode($data), 'POST', 'json');

        Log::info('refreshPool:请求地址:{url},参数====>{data},返回值===>{res}', ['url' => $socket_url, 'data' => json_encode($data), 'res' => $res]);
        return json_decode($res, true);
    }

    public function clearCache($operatorId)
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', $this->conf_key)->value('json');
        $data = ['conf' => $json, 'operatorId' => $operatorId];

        return $this->validation($data);
    }

    public function validation($data)
    {
        $socket_url = config('config.game_api_url') . 'iapi/setGopherConf';
        $res = curlData($socket_url, json_encode($data), 'POST', 'json');

        Log::info('validation:请求地址:{url},参数====>{data},返回值===>{res}', ['url' => $socket_url, 'data' => json_encode($data), 'res' => $res]);
        return json_decode($res, true);
    }

    public function getPoolNum()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->hget('gopher_info', 'kingValue');
    }
}

<?php

namespace app\admin\service;

use app\admin\common\GameApiUrlConfig;
use app\admin\model\ConfigModel;
use app\common\RedisCommon;
use think\facade\Log;

class DigTreasureConfService
{
    protected static $instance;
    protected $conf_key = 'wabao_conf';
    protected $poolsMap = [];
    protected $isOpen;
    protected $config;

    public function __construct()
    {
        $this->config = $this->getConfObj();

        $pools = $this->checkObjKey($this->config, 'pools');
        $this->isOpen = isset($this->config->isOpen) ? 1 : 0;
        $this->poolsMap = array_column($pools, null, 'poolId');
    }

    public function checkObjKey(object $conf, $key)
    {
        return isset($conf->$key) ? $conf->$key : [];
    }

    public function isOpen()
    {
        return $this->isOpen;
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
        $this->config = ConfigModel::getInstance()->getModel()->where('name', $this->conf_key)->value('json');
        return json_decode($this->config);
    }

    public function saveConf($json)
    {
        return ConfigModel::getInstance()->getModel()->where('name', $this->conf_key)->update(['json' => $json]);
    }

    public function getConf()
    {
        return $this->config;
    }

    public function getGameObj()
    {
        $res = [];
        $conf = $this->getConf();
        foreach ((array) $conf as $key => $obj) {
            if (in_array($key, ['single'])) {
                $res[$key] = $obj;
            }
        }
        return $res;
    }

    public function setBaseInfo($data)
    {
        $gameId = $data['gameId'];

        $this->getConf()->$gameId->name = $data['name'];
        $this->getConf()->$gameId->knockScore = (int) $data['knockScore'];
        $this->getConf()->$gameId->resetScore = (int) $data['resetScore'];
        $this->getConf()->$gameId->ledGiftValue = (int) $data['ledGiftValue'];
        $this->getConf()->$gameId->publicGiftValue = (int) $data['publicGiftValue'];
        return $this->saveConf(json_encode($this->getConf()));
    }

    /*
     *死亡率
     */
    public function getPoolRate()
    {
        return $this->config->poolRate;
    }

    /*
     *死亡率
     */
    public function setPoolRate($value)
    {
        $this->config->poolRate = $value;
        return $this;
    }

    public function getPoolsList()
    {
        return $this->config;
    }
    /*
     *获取奖池
     */
    public function getPoolsMap()
    {
        return $this->poolsMap;
    }

    /*
     *获取奖池
     */
    public function getPools($gameId)
    {
        return $this->config->$gameId->pools;
    }

    /*
     *配置奖池
     */
    public function setPools(array $data)
    {
        $gameId = $data['gameId'];
        $pools = $this->getPools($gameId);

        $poolId = $data['poolId'];
        $name = $data['name'];
        $poolType = $data['poolType'];
        $condition = $data['condition'];
        $items = $data['items'];

        $pools_map = array_column($pools, null, 'poolId');
        $poolInfo = $pools_map[$poolId];

        $poolInfo->items = $items;
        $poolInfo->name = $name;
        $poolInfo->poolType = $poolType;
        $poolInfo->condition = $condition;
        return $this->saveConf(json_encode($this->getConf()));
    }

    /*
     *配置奖池
     */
    public function addPools(array $data)
    {
        $gameId = $data['gameId'];
        $poolId = $data['poolId'];
        $name = $data['name'];
        $poolType = $data['poolType'];
        $condition = $data['condition'];
        $items = $data['items'];

        $pools = $this->getPools($gameId);
        $pools_map = array_column($pools, null, 'poolId');

        if (empty($pools)) {
            $newPoolId = 1;
        } else {
            $newPoolId = max(array_keys($pools_map)) + 1;
        }

        $newPool = (object) [];
        $newPool->poolId = $newPoolId;
        $newPool->name = $name;
        $newPool->poolType = $poolType;
        $newPool->items = $items;
        $newPool->condition = $condition;
        $pools[] = $newPool;

        $this->config->$gameId->pools = $pools;
        return $this->saveConf(json_encode($this->getConf()));
    }

    /*
     *奖池删除
     */
    public function delPools(array $data)
    {
        $gameId = $data['gameId'];
        $poolId = $data['poolId'];
        $name = $data['name'];
        $poolType = $data['poolType'];
        $condition = $data['condition'];
        $items = $data['items'];

        $pools = $this->getPools($gameId);
        $pools_map = array_column($pools, null, 'poolId');
        if (array_key_exists($poolId, $pools_map)) {
            unset($pools_map[$poolId]);
        }
        $this->config->$gameId->pools = array_values($pools_map);
        return $this->saveConf(json_encode($this->getConf()));
    }

    public function refreshPool($gameId, $poolId, $operatorId)
    {
        $socket_url = config('config.game_api_url') . GameApiUrlConfig::$wabao_refershpool;
        $data = ['mode' => $gameId, 'poolId' => (int) $poolId, 'operatorId' => $operatorId];
        $res = curlData($socket_url, json_encode($data), 'POST', 'json');

        Log::info('refreshPool:请求地址:{url},参数====>{data},返回值===>{res}', ['url' => $socket_url, 'data' => json_encode($data), 'res' => $res]);
        return json_decode($res, true);
    }

    public function getPoolInfo($gameId, $poolId)
    {
        $socket_url = config('config.game_api_url') . GameApiUrlConfig::$wabao_getpool;
        $data = ['mode' => $gameId, 'poolId' => (int) $poolId];
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

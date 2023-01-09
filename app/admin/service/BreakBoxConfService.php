<?php

namespace app\admin\service;

use app\admin\model\Box3UserSpecialGiftModel;
use app\admin\model\ConfigModel;
use app\common\RedisCommon;

class BreakBoxConfService
{
    protected static $instance;
    protected $boxConfKey = 'break_box_conf';
    protected $boxConf = [];
    protected $boxesMap = [];
    protected $boxPoolsMap = [];
    protected $boxRulesMap = [];
    protected $boxRatesMap = [];
    protected $boxPoolsRateMap = [];

    public function __construct()
    {
        $this->boxConf = $this->getConf();

        $boxes = $this->checkObjKey($this->boxConf, 'boxes');
        $this->boxesMap = array_column($boxes, null, 'boxId');
        $boxPools = array_column($boxes, 'pools', 'boxId');

        // dump($boxPools);die;
        foreach ($boxPools as $boxId => $pools) {
            foreach ($pools as $pool) {
                $this->boxPoolsMap[$boxId][$pool->poolId] = $pool;
            }
        }
        $this->boxRulesMap = array_column($boxes, 'poolRule', 'boxId');
        $this->boxRatesMap = array_column($boxes, 'rateControl', 'boxId');

        foreach ($this->boxRatesMap as $boxId => $poolsRates) {
            foreach ($poolsRates as $rate) {
                $this->boxPoolsRateMap[$boxId][$rate->blackPoolId] = $rate;
            }
        }
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

    protected function getConf()
    {
        $this->boxConf = ConfigModel::getInstance()->getModel()->where('name', $this->boxConfKey)->value('json');
        return json_decode($this->boxConf);
    }

    public function saveConf($json)
    {
        $res = ConfigModel::getInstance()->getModel()->where('name', $this->boxConfKey)->update(['json' => $json]);
        return $res;
    }

    public function getBoxConf()
    {
        return $this->boxConf;
    }

    public function getBoxList()
    {
        return $this->boxesMap;
    }

    public function getBox($boxId)
    {
        return $this->checkArrKey($this->boxesMap, $boxId);
    }

    public function setBox($box, $data)
    {
        $box->boxName = $data['boxName'];
        $box->price = $data['price'];
        $box->inLuckRankGiftValue = $data['inLuckRankGiftValue'];
        $box->fullFlutterGiftValue = $data['fullFlutterGiftValue'];
        $box->fullPublicGiftValue = $data['fullPublicGiftValue'];
        $box->profitsBaolv = $data['profitsBaolv'];
        $box->isOpen = $data['isOpen'];

        $this->boxesMap[$box->boxId] = $box;
        return $box;
    }

    /*
     *公屏值
     */
    public function getPublicValue()
    {
        return $this->boxConf->fullPublicGiftValue;
    }

    /*
     *公屏值
     */
    public function setPublicValue($value)
    {
        $this->boxConf->fullPublicGiftValue = $value;
        return;
    }

    /*
     *飘瓶值
     */
    public function getFlutterValue()
    {
        return $this->boxConf->fullFlutterGiftValue;
    }

    /*
     *飘瓶值
     */
    public function setFlutterValue($value)
    {
        $this->boxConf->fullFlutterGiftValue = $value;
        return;
    }

    /*
     *获取奖池
     */
    public function getPoolsMap()
    {
        return $this->boxPoolsMap;
    }

    /*
     *获取奖池
     */
    public function getPools($boxId)
    {
        return $this->boxesMap[$boxId]->pools;
    }

    /*
     *获取奖池map
     */
    public function getPoolsIds($pools)
    {
        $poolIds = [];
        foreach ($pools as $pool) {
            $poolIds[$pool->poolId] = $pool->poolId;
        }
        return $poolIds;
    }

    /*
     *配置奖池
     */
    public function setPools($boxId, $pools)
    {
        $this->boxesMap[$boxId] = $pools;

        foreach ($this->boxConf->boxes as $box) {
            if ($box->boxId === $boxId) {
                $box->pools = $pools;
            }
        }
        return $this->boxConf;
    }

    /*
     *获取奖池规则
     */
    public function getRulesMap()
    {
        return $this->boxRulesMap;
    }

    /*
     *获取奖池规则
     */
    public function getRules($boxId)
    {
        return $this->boxesMap[$boxId]->poolRule;
    }

    /*
     *配置奖池规则
     */
    public function setRules($boxId, $poolRules, $rules)
    {
        if (!in_array($rules, $poolRules)) {
            $poolRules[] = $rules;
        }

        $this->boxRulesMap[$boxId] = $poolRules;

        foreach ($this->boxConf->boxes as $box) {
            if ($box->boxId === $boxId) {
                $box->poolRule = $poolRules;
            }
        }

        return $this;
    }

    /*
     *配置奖池规则
     */
    public function delRules($boxId, $poolRules, $rules)
    {
        if (in_array($rules, $poolRules)) {
            $index = array_search($rules, $poolRules);
            unset($poolRules[$index]);
            $poolRules = array_values($poolRules);
        }

        $this->boxRulesMap[$boxId] = $poolRules;

        foreach ($this->boxConf->boxes as $box) {
            if ($box->boxId === $boxId) {
                $box->poolRule = $poolRules;
            }
        }

        return $this;
    }

    /*
     *获取爆率规则
     */
    public function getRatesMap()
    {
        return $this->boxRatesMap;
    }

    /*
     *获取爆率规则
     */
    public function getRates($boxId)
    {
        $rateControl = [];
        if (array_key_exists($boxId, $this->boxesMap)) {
            $rateControl = $this->boxesMap[$boxId]->rateControl;
        }
        return $rateControl;
    }

    /*
     *配置爆率规则
     */
    public function addRates($boxId, $poolRate, $rules)
    {
        $newRule = (object) [];
        $newRule->boxRange = $rules['boxRange'];
        $newRule->whiteBaolv = $rules['whiteBaolv'];
        $newRule->whiteHopeBaolv = $rules['whiteHopeBaolv'];
        $newRule->blackBaolv = $rules['blackBaolv'];
        $newRule->blackPoolId = $rules['blackPoolId'];
        $newRule->whitePoolId = $rules['whitePoolId'];

        $poolRate[] = $newRule;

        foreach ($this->boxConf->boxes as $box) {
            if ($box->boxId === $boxId) {
                $box->rateControl = $poolRate;
            }
        }
        return $this;
    }

    /*
     *配置爆率规则
     */
    public function setRates($boxId, $poolRate, $rules)
    {
        //修改
        $poolRate->boxRange = $rules['boxRange'];
        $poolRate->whiteBaolv = $rules['whiteBaolv'];
        $poolRate->whiteHopeBaolv = $rules['whiteHopeBaolv'];
        $poolRate->blackBaolv = $rules['blackBaolv'];
        $poolRate->blackPoolId = $rules['blackPoolId'];
        $poolRate->whitePoolId = $rules['whitePoolId'];
        return $this;
    }

    /*
     *配置爆率规则
     */
    public function delRates($boxId, $key, $poolRates)
    {
        unset($poolRates[$key - 1]);
        $poolRates = array_values($poolRates);
        $this->boxRatesMap[$boxId] = $poolRates;
        foreach ($this->boxConf->boxes as $box) {
            if ($box->boxId === $boxId) {
                $box->rateControl = $poolRates;
            }
        }

        return $this;
    }

    /*
     *配置爆率规则
     */
    public function delRatesOld($boxId, $poolRates, $rules)
    {
        if (in_array($rules, $poolRates)) {
            $index = array_search($rules, $poolRates);
            unset($poolRates[$index]);
            $poolRates = array_values($poolRates);
        }

        $this->boxRatesMap[$boxId] = $poolRates;

        foreach ($this->boxConf->boxes as $box) {
            if ($box->boxId === $boxId) {
                $box->rateControl = $poolRates;
            }
        }

        return $this;
    }

    public function refreshAllPool($array)
    {
        if (array_key_exists('poolId', $array)) {
            $src = $this->refreshPoolApi($array['boxId'], $array['poolId']);
        } else {
            $src = $this->refreshAllPoolApi($array['boxId']);
        }
        if ($src['code'] == 200) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => $src['desc']]);die;
        }
    }

    public function refreshPoolApi($id, $poolId)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/box/refreshPool';
        $data = ['boxId' => $id, 'poolId' => $poolId];
        $res = curlData($socket_url, $data);
        return json_decode($res, true);
    }

    public function refreshAllPoolApi($id)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/box/refreshAllPool';
        $data = ['boxId' => $id];
        $res = curlData($socket_url, $data);
        return json_decode($res, true);
    }

    public function clearCacheBoxConf()
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', $this->boxConfKey)->value('json');
        $src = $this->validation($json);
        if ($src['code'] == 200) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => $src['desc']]);die;
        }
    }

    public function validation($json)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/box/setBoxConf';
        $data = ['conf' => $json];
        $res = curlData($socket_url, $data, 'POST', 'form-data');
        return json_decode($res, true);
    }

    public function addUserSpecialGift($array, $username)
    {
        $user_id = $array['user_id'];
        $gift_id = $array['gift_id'];

        $data = [
            'user_id' => $user_id,
            'box_id' => $array['box_id'],
            'gift_id' => $gift_id,
            'created' => time(),
            'create_user' => $username,
        ];
        return Box3UserSpecialGiftModel::getInstance()->getModel()->insert($data);
    }

    public function cancelUserSpecialGift($array)
    {
        return Box3UserSpecialGiftModel::getInstance()->getModel()->where('id', $array['id'])->save(['state' => 4]);
    }

    public static function getBoxData($day_datas)
    {
        $data = $gold = $silver = [];

        $gold_output_amount = 0;
        $gold_consumption = 0;
        $silver_output_amount = 0;
        $silver_consumption = 0;

        if (!empty($day_datas)) {
            foreach ($day_datas as $item) {
                $box_data['date'] = $item['date'];
                $box_data['uid'] = $item['uid'];
                $box_data['gold_consume_amount'] = 0;
                $box_data['gold_output_amount'] = 0;
                $box_data['silver_consume_amount'] = 0;
                $box_data['silver_output_amount'] = 0;
                $box_data['gold_explodeRate'] = 0;
                $box_data['silver_explodeRate'] = 0;

                $json_data = json_decode($item['json_data'], true);
                if (isset($json_data['activity'])) {
                    $activity_rooms = array_values($json_data['activity']);
                    foreach ($activity_rooms as $room_data) {
                        $silver_consume = $silver_output = $gold_consume = $gold_output = 0;

                        if (array_key_exists('box', $room_data)) {
                            $box2_data = $room_data['box'];
                            if (array_key_exists(2, $box2_data) && isset($box2_data[2]['consume']['user:bean'])) {
                                $silver_consume = $box2_data[2]['consume']['user:bean']['value'];

                                foreach ($box2_data[2]['reward'] as $asset => $reward) {
                                    if (strpos($asset, 'gift') !== false) {
                                        $silver_output += $reward['value'];
                                    }
                                }
                            }

                            if (array_key_exists(1, $box2_data) && isset($box2_data[1]['consume']['user:bean'])) {
                                $gold_consume = $box2_data[1]['consume']['user:bean']['value'];

                                foreach ($box2_data[1]['reward'] as $asset => $reward) {
                                    if (strpos($asset, 'gift') !== false) {
                                        $gold_output += $reward['value'];
                                    }
                                }
                            }

                            $box_data['silver_consume_amount'] += $silver_consume;
                            $silver_consumption += $silver_consume;

                            $box_data['silver_output_amount'] += $silver_output;
                            $silver_output_amount += $silver_output;

                            $box_data['gold_consume_amount'] += $gold_consume;
                            $gold_consumption += $gold_consume;

                            $box_data['gold_output_amount'] += $gold_output;
                            $gold_output_amount += $gold_output;
                        }
                    }
                }

                if ($box_data['gold_consume_amount'] > 0) {
                    $box_data['gold_explodeRate'] = round($box_data['gold_output_amount'] * 100 / $box_data['gold_consume_amount'], 2);
                }
                if ($box_data['silver_consume_amount'] > 0) {
                    $box_data['silver_explodeRate'] = round($box_data['silver_output_amount'] * 100 / $box_data['silver_consume_amount'], 2);
                }
                $data[] = $box_data;
            }
        }

        if ($data) {
            $gold = array_column($data, null, 'gold_consume_amount');
            krsort($gold);
            $silver = array_column($data, null, 'silver_consume_amount');
            krsort($silver);
        }

        $redis = RedisCommon::getInstance()->getRedis();

        $gold_profit_pool_amount = $redis->hget('box_profits_pool', 1);
        $silver_profit_pool_amount = $redis->hget('box_profits_pool', 2);

        $gold_explodeRate = 0.01;
        if ($gold_consumption) {
            $gold_explodeRate += round($gold_output_amount / $gold_consumption, 4);
        }
        $silver_explodeRate = 0.01;
        if ($silver_consumption) {
            $silver_explodeRate += round($silver_output_amount / $silver_consumption, 4);
        }

        return [
            'gold' => [
                'gold_output_amount' => $gold_output_amount,
                'gold_consumption' => $gold_consumption,
                'gold_profit_pool_amount' => $gold_profit_pool_amount,
                'gold_explodeRate' => $gold_explodeRate,
                'data' => $gold,
            ],
            'silver' => [
                'silver_output_amount' => $silver_output_amount,
                'silver_consumption' => $silver_consumption,
                'silver_profit_pool_amount' => $silver_profit_pool_amount,
                'silver_explodeRate' => $silver_explodeRate,
                'data' => $silver,
            ],
            'data' => $data,
        ];
    }

    public function setBoxConf($array)
    {
        $res = $this->saveConf($array['boxJson']);
        $this->clearCacheBoxConf();
    }

    public function boxPoolsShow()
    {
        return json_encode($this->boxConf);
    }
}

<?php

namespace app\admin\service;

use app\admin\model\ConfigModel;
use think\facade\Log;

class BoxConfService
{
    protected static $instance;
    protected static $box = 'box2_conf';
    protected static $boxType = ['newer' => '新手池', 'daily' => '日池'];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BoxConfService();
        }
        return self::$instance;
    }

    public function jackpotTheRemaining($array)
    {
        $src = $this->getRunningBoxApi($array['boxId'], $array['poolId']);
        $data = [];
        if ($src['code'] == 200) {
            $url = config('config.APP_URL_image');
            $pool = $src['data']['pools'];
            foreach ($pool as $k => $v) {
                if ($v['poolId'] == $array['poolId']) {
                    $info = $v;
                }
            }
            foreach ($info['gifts'] as $k => $v) {
                $data[] = [
                    'image' => $url . $this->getGiftVal($v[0], 'image'),
                    'name' => $this->getGiftVal($v[0], 'name'),
                    'giftId' => $v[0],
                    'TheNumberOf' => $v[1],
                ];
            }
        }
        return $data;
    }

    public function getRunningBoxApi($id, $poolId)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/box2/getRunningBox';
        $data = ['boxId' => $id, 'poolId' => $poolId];
        Log::info('BoxConfService@getRunningBoxApi:params:{res}', ['res' => json_encode($data)]);
        $res = curlData($socket_url, $data);
        Log::info('BoxConfService@getRunningBoxApi:curl:{res}', ['res' => $res]);
        return json_decode($res, true);
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
        $socket_url = config('config.app_api_url') . 'api/inner/box2/refreshPool';
        $data = ['boxId' => $id, 'poolId' => $poolId];
        Log::info('BoxConfService@refreshPoolApi:params:{res}', ['res' => json_encode($data)]);
        $res = curlData($socket_url, $data);
        Log::info('BoxConfService@refreshPoolApi:curl:{res}', ['res' => $res]);
        return json_decode($res, true);
    }

    public function refreshAllPoolApi($id)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/box2/refreshAllPool';
        $data = ['boxId' => $id];
        Log::info('BoxConfService@refreshAllPoolApi:params:{res}', ['res' => json_encode($data)]);
        $res = curlData($socket_url, $data);
        Log::info('BoxConfService@refreshAllPoolApi:curl:{res}', ['res' => $res]);
        return json_decode($res, true);
    }

    public function clearCacheBoxConf()
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', self::$box)->value('json');
        $src = $this->validation($json);
        if ($src['code'] == 200) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => $src['desc']]);die;
        }
    }

    public function boxSwitch($array)
    {
        $box = $this->getConf(self::$box);
        $box['isOpen'] = (int) $array['status'];
        $box2_conf = json_encode($box);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$box)->save(['json' => $box2_conf]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function saveBoxForm($array)
    {
        if (!$array['goodsId'] || !$array['fullPublicGiftValue'] || !$array['fullFlutterGiftValue']) {
            echo json_encode(['code' => 500, 'msg' => '必填参数不可为空']);die;
        }
        $box = $this->getConf(self::$box);
        $box['goodsId'] = (int) $array['goodsId'];
        $box['fullPublicGiftValue'] = (int) $array['fullPublicGiftValue'];
        $box['fullFlutterGiftValue'] = (int) $array['fullFlutterGiftValue'];
        $box['special']['maxPoolValue'] = (int) $array['maxPoolValue'];
        $box['special']['maxProgress'] = (int) $array['maxProgress'];
        $box['special']['giftValue'] = (int) $array['giftValue'];

        if (isset($array['gifts']) && !empty($array['gifts'])) {
            foreach ($array['gifts'] as $_ => &$gift_id) {
                $gift_id = (int) $gift_id;
            }
        }
        $box['special']['gifts'] = (array) $array['gifts'];

        $data = json_encode($box);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$box)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function savePool($data)
    {
        if (array_key_exists('type', $data)) {
            if ($data['type'] == 'delete') {
                $data = $this->delBoxPool($data);
            } else {
                $data = $this->saveBoxPool($data);
            }
        }

        $is = ConfigModel::getInstance()->getModel()->where('name', self::$box)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '配置未改动']);die;
        }
    }

    public function getPoolGift($data)
    {
        $box = $this->getConf(self::$box);
        foreach ($box['boxes'] as $k => $v) {
            if ($v['boxId'] == $data['id']) {
                if (array_key_exists('pools', $v)) {
                    $array = $v['pools'];
                }
            }
        }
        if (array_key_exists('poolId', $data)) {
            foreach ($array as $k => $v) {
                if ($v['poolId'] == $data['poolId']) {
                    $gifts = $v['gifts'];
                }
            }
        } else {
            if (array_key_exists('gifts', $array[0])) {
                foreach ($array as $k => $v) {
                    if ($v['poolId'] == 1) {
                        $gifts = $v['gifts'];
                    }
                }
            } else {
                $gifts = [];
            }
        }

        if (array_key_exists('type', $data)) {
            $data = $this->spellWereGift($gifts, 1);
        } else {
            $data = $this->spellWereGift($gifts);
        }
        echo json_encode(['code' => 200, 'msg' => '操作成功', 'data' => $data]);die;
    }

    public function getGiftConf($key)
    {
        return json_decode(ConfigModel::getInstance()->getModel()->where('name', $key)->value('json'), 1);
    }

    public function getCondition($data)
    {
        $box = $this->getConf(self::$box);
        foreach ($box['boxes'] as $k => $v) {
            if ($v['boxId'] == $data['id']) {
                if (array_key_exists('pools', $v)) {
                    $array = $v['pools'];
                }
            }
        }

        foreach ($array as $k => $v) {
            if ($v['poolId'] == $data['poolId']) {
                $condition = $v['condition'];
            }
        }
        if (array_key_exists('type', $data)) {
            $data = $this->spellWere($condition, 1);
        } else {
            $data = $this->spellWere($condition);
        }
        echo json_encode(['code' => 200, 'msg' => '操作成功', 'data' => $data]);die;
    }

    public function addBoxPool($data)
    {
        $data = $this->saveBoxPool($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$box)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function validation($josn)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/box2/setConf';
        $data = ['conf' => $josn];
        Log::info('BoxConfService@validation:params:{res}', ['res' => json_encode($data)]);
        $res = curlData($socket_url, $data, 'POST', 'form-data');
        Log::info('BoxConfService@validation:curl:{res}', ['res' => $res]);
        return json_decode($res, true);
    }

    //宝箱奖池
    public function boxPool($data)
    {
        $boxId = $data['boxId'];
        $box = $this->getConf(self::$box);
        foreach ($box['boxes'] as $k => $v) {
            if ($v['boxId'] == $boxId) {
                $box = $v;
                $boxName = $v['name'];
                if (array_key_exists('pools', $v)) {
                    $count = count($v['pools']) + 1;
                    $pool = $v['pools'];
                } else {
                    $count = 1;
                    $pool = [];
                }
            }
        }
        $rs = $burstRate = [];
        if (!empty($pool)) {
            $rs = $this->asseMbly($pool);
            $burstRate = $this->burstRate($pool, $box);
        }
        $last_names = array_column($burstRate, 'sort');
        array_multisort($last_names, SORT_ASC, $burstRate);
        return ['data' => $burstRate, 'boxName' => $boxName, 'count' => $count];
    }

    public function burstRate($pool, $box)
    {
        $pool = $this->asseMbly($pool);
        foreach ($pool as $k => $v) {
            $pool[$k]['price'] = $box['price'];
            $output = $consume = 0;
            foreach ($v['gift'] as $kk => $vv) {
                $output += $this->getGiftPrice($vv[0]) * $vv[1];
                $consume += $box['price'] * $vv[1];
            }
            $pool[$k]['output'] = $output;
            $pool[$k]['consume'] = $consume;
            unset($pool[$k]['gift']);
            $pool[$k]['burstRate'] = $pool[$k]['output'] > 0 && $pool[$k]['consume'] > 0 ? $pool[$k]['output'] / $pool[$k]['consume'] : 0;
        }
        return $pool;
    }

    public function getGiftPrice($id)
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json');
        $array = json_decode($json, true);
        foreach ($array as $k => $v) {
            if ($v['giftId'] == $id) {
                return $v['price']['count'];
            }
        }
    }

    //编辑
    public function saveBox($data)
    {
        $data = $this->dealWithData($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$box)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function getBoxGift($data)
    {
        $id = $data['id'];
        $box = $this->getConf(self::$box);
        foreach ($box['boxes'] as $k => $v) {
            if ($v['boxId'] == $id) {
                echo json_encode(['code' => 200, 'msg' => '获取宝箱特殊礼物']);
            }
        }
    }

    //增
    public function addBox($data)
    {
        $box = $this->getConf(self::$box);
        if (in_array($data['boxId'], array_column($box['boxes'], 'boxId'))) {
            echo json_encode(['code' => 500, 'msg' => '宝箱id已存在']);die;
        }
        $data = $this->dealWithData($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$box)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }
    //查
    public function getBoxConf()
    {
        $box = $this->getConf(self::$box);
        $pool_value = $this->getPoolValue();

        $data = [];
        if (!empty($box)) {
            foreach ($box['boxes'] as $k => $v) {
                $data[] = [
                    'boxId' => $v['boxId'],
                    'name' => $v['name'],
                    'price' => $v['price'],
                    'inJinliGiftValue' => $v['inJinliGiftValue'],
                    // 'gifts' => $this->getGift($v['special']['gifts']),
                    // 'maxProgress' => $v['special']['maxProgress'],
                    // 'maxPoolValue' => $v['special']['maxPoolValue'],
                    // 'giftValue' => $v['special']['giftValue'],
                    'specialGiftWeight' => $v['specialGiftWeight'] ?? 0,
                ];
            }
        }
        return ['data' => $data, 'box' => $box, 'pool_value' => $pool_value];
    }

    public function getPoolValue()
    {
        $socket_url = config('config.app_api_url') . 'api/inner/box2/getBoxSpecialPool';
        $data = [];
        Log::info('BoxConfService@getPoolValue:params:{res}', ['res' => json_encode($data)]);
        $res = curlData($socket_url, $data);
        Log::info('BoxConfService@getPoolValue:curl:{res}', ['res' => $res]);

        if ($res) {
            $res = json_decode($res, true);
            $pool_value = $res['data']['poolValue'];
        } else {
            $pool_value = 0;
        }

        return $pool_value;
    }
    //转
    public function intGiftId($array)
    {
        foreach ($array as $k => $v) {
            $data[] = (int) $v;
        }
        return $data;
    }
    //取
    public function getGift($giftId)
    {
        $gift = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json');
        $gift = json_decode($gift, true);
        $data = [];
        foreach ($gift as $k => $v) {
            if (in_array($v['giftId'], $giftId)) {
                $data[] = $v['name'];
            }
        }
        return implode("、", $data);
    }
    //取配置
    public function getConf($name)
    {
        $conf = ConfigModel::getInstance()->getModel()->where('name', $name)->value('json');
        return json_decode($conf, true);
    }
    //拼
    public function assemblyBoxConf($array)
    {
        $data = [
            'count' => [
                'default' => [1, 10, 66],
                'custom' => [5, 200],
            ],
            'goodsId' => 46,
            'isOpen' => 0,
            'priceAssetId' => 'bank:game:score',
            'fullPublicGiftValue' => 500,
            'fullFlutterGiftValue' => 1000,
            'boxes' => [$array],
        ];
        return $data;
    }

    public function asseMbly($array)
    {
        foreach ($array as $k => $v) {
            $data[$k] = [
                'poolId' => $v['poolId'],
                'type' => self::$boxType[$v['type']],
                'typeVal' => $v['type'],
                'sort' => $v['sort'],
                'where1' => '/',
                'where2' => '/',
                'where3' => '/',
                'gift' => $v['gifts'],
            ];
            if (count($v['condition']) >= 3) {
                $data[$k]['where3'] = $this->asseMblyCondition($v['condition'][2]);
            }
            if (count($v['condition']) >= 2) {
                $data[$k]['where2'] = $this->asseMblyCondition($v['condition'][1]);
            }
            if (count($v['condition']) >= 1) {
                $data[$k]['where1'] = $this->asseMblyCondition($v['condition'][0]);
            }
        }
        return $data;
    }

    public function spellWere($array, $type = '')
    {
        if ($type) {
            $data[] = ['consume0' => '开始(豆)', 'consume1' => '结束(豆)', 'baolv0' => '开始(%)', 'baolv1' => '结束(%)'];
        } else {
            $data = [];
        }
        foreach ($array as $k => $v) {
            if (array_key_exists('consume', $v)) {
                $data1[$k]['consume0'] = $v['consume'][0];
                $data1[$k]['consume1'] = $v['consume'][1];
            }
            if (array_key_exists('baolv', $v)) {
                $data1[$k]['baolv0'] = $v['baolv'][0];
                $data1[$k]['baolv1'] = $v['baolv'][1];
            } else {
                $data1[$k]['baolv0'] = 0;
                $data1[$k]['baolv1'] = 0;
            }
        }
        return array_merge($data, $data1);
    }

    public function spellWereGift($array, $type = '')
    {
        $gift = $this->getGiftConf('gift_conf');
        foreach ($gift as $k => $v) {
            $giftData[$v['giftId']] = [
                'name' => $v['name'],
                'price' => $v['price']['count'],
            ];
        }
        if ($type) {
            $data[] = ['gifts0' => 'id', 'gifts1' => '数量', 'name' => '名称', 'price' => '价值'];
        } else {
            $data = [];
        }

        foreach ($array as $k => $v) {
            $data[] = [
                'gifts0' => $v[0],
                'gifts1' => $v[1],
                'name' => $giftData[$v[0]]['name'],
                'price' => $giftData[$v[0]]['price'],
            ];
        }
        return $data;
    }

    public function asseMblyCondition($array)
    {
        if (count($array) == 1) {
            return implode('-', $array['consume']) . '豆';
        } elseif (count($array) == 2) {
            return implode('-', $array['consume']) . '豆、' . implode('-', $array['baolv']) . '%';
        }
    }

    public function dealWithData($array)
    {
        $box = $this->getConf(self::$box);
        $value = [
            'boxId' => (int) $array['boxId'],
            'name' => $array['name'],
            'price' => (int) $array['price'],
            'inJinliGiftValue' => (int) $array['inJinliGiftValue'],
            'specialGiftWeight' => (int) $array['specialGiftWeight'] ?? 0,
        ];

        if (!$box) {
            $box = $this->assemblyBoxConf($value);
        } else {
            if (in_array($value['boxId'], array_column($box['boxes'], 'boxId'))) {
                foreach ($box['boxes'] as $k => $v) {
                    if ($v['boxId'] == $value['boxId']) {
                        $count = $k;
                        if (array_key_exists('pools', $v)) {
                            $value['pools'] = $v['pools'];
                        } else {
                            $value['pools'] = [];
                        }
                    }
                }
                $box['boxes'][$count] = $value;
            } else {
                $box['boxes'][count($box['boxes'])] = $value;
            }
        }
        return json_encode($box);
    }

    public function delBoxPool($array)
    {
        $boxData = $this->getConf(self::$box);
        foreach ($boxData['boxes'] as $k => $v) {
            if ($v['boxId'] == $array['boxId']) {
                $box = $v;
            }
        }
        if (array_key_exists('pools', $box) && !empty($box)) {
            $boxPools = $box['pools'];
            foreach ($boxPools as $k => $v) {
                if ($v['poolId'] == $array['poolId']) {
                    unset($boxPools[$k]);
                    $box['pools'] = $boxPools;
                }
            }
        }
        foreach ($boxData['boxes'] as $k => $v) {
            if ($v['boxId'] == $array['boxId']) {
                $boxData['boxes'][$k] = $box;
            }
        }
        return json_encode($boxData);
    }

    public function saveBoxPool($array)
    {
        if ((int) $array['sort'] <= 0) {
            echo json_encode(['code' => 500, 'msg' => '排序错误']);die;
        }
        $boxPools = [];
        $boxData = $this->getConf(self::$box);
        foreach ($boxData['boxes'] as $k => $v) {
            if ($v['boxId'] == $array['boxId']) {
                $box = $v;
            }
        }
        if (array_key_exists('pools', $box) && !empty($box)) {
            $boxPools = $box['pools'];
            $poolIdColumn = array_column($boxPools, 'poolId');
        }
        if (array_key_exists('poolId', $array)) {
            $poolId = $array['poolId'];
        } else {
            if (array_key_exists('pools', $box)) {
                $sort = array_column($boxPools, 'sort');
                if (in_array($array['sort'], $sort)) {
                    echo json_encode(['code' => 500, 'msg' => '奖池排序以存在']);die;
                }
                $poolId = count($boxPools) + 1;
            } else {
                $poolId = 1;
            }
        }

        $value = [
            'poolId' => (int) $poolId,
            'type' => $array['type'],
            'sort' => (int) $array['sort'],
            'condition' => $this->poolCondition($array),
            'gifts' => $this->poolGift($array),
        ];

        if ($poolId == 1 && count($boxPools) <= 1) {
            $box['pools'] = [$value];
        } else {
            if (in_array($value['poolId'], $poolIdColumn)) {
                foreach ($poolIdColumn as $k => $v) {
                    if ($v == $value['poolId']) {
                        $boxPools[$k] = $value;
                    }
                }
            } else {
                $boxPools[count($poolIdColumn)] = $value;
            }

            $box['pools'] = $boxPools;
        }

        foreach ($boxData['boxes'] as $k => $v) {
            if ($v['boxId'] == $array['boxId']) {
                $boxData['boxes'][$k] = $box;
            }
        }
        return json_encode($boxData);
    }

    public function getBox($id)
    {
        $box = $this->getConf(self::$box);
        foreach ($box['boxes'] as $k => $v) {
            if ($v['boxId'] == $id) {
                return $v;
            }
        }
    }

    public function poolGift($array)
    {
        foreach ($array['giftsKey'] as $k => $v) {
            if ($v == null) {
                echo json_encode(['code' => 500, 'msg' => '礼物不可为空']);die;
            }
            $data[$k][0] = (int) $v;
        }
        foreach ($array['giftsVal'] as $k => $v) {
            if ($v == null) {
                echo json_encode(['code' => 500, 'msg' => '礼物不可为空']);die;
            }
            $data[$k][1] = (int) $v;
        }
        return $data;
    }

    public function poolCondition($array)
    {
        foreach ($array['consumeKey'] as $k => $v) {
            if (empty($v) && $v != 0) {
                echo json_encode(['code' => 500, 'msg' => '条件不可为空']);die;
            }
            $data[$k]['consume'][0] = (int) $v;
        }
        foreach ($array['consumeVal'] as $k => $v) {
            if (empty($v) && $v != 0) {
                echo json_encode(['code' => 500, 'msg' => '条件不可为空']);die;
            }
            $data[$k]['consume'][1] = (int) $v;
        }

        foreach ($array['baolvVal'] as $k => $v) {
            if ($v != null && $v != 0) {
                $data[$k]['baolv'][1] = (real) $v;
            } else {
                unset($data[$k]['baolv']);
            }
        }

        foreach ($array['baolvKey'] as $k => $v) {
            if (array_key_exists('baolv', $data[$k])) {
                if ($v != null) {
                    $data[$k]['baolv'][0] = (real) $v;
                    asort($data[$k]['baolv']);
                }
            }
        }
        return $data;
    }

    public function getGiftVal($giftId, $keys)
    {
        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftId) {
                return $v[$keys];
            }
        }
    }
}
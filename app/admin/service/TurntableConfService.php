<?php

namespace app\admin\service;

use app\admin\model\ConfigModel;

class TurntableConfService
{
    protected static $instance;
    protected static $turntable = 'turntable_conf';
    protected static $turntableType = ['newer' => '新手池', 'daily' => '日池'];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TurntableConfService();
        }
        return self::$instance;
    }

    public function jackpotTurntableTheRemaining($array)
    {
        $src = $this->getRunningBoxApi($array['turntableId'], $array['poolId']);
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
        $socket_url = config('config.app_api_url') . 'api/inner/turntable/getRunningBox';
        $data = ['turntableId' => $id, 'poolId' => $poolId];
        $res = curlData($socket_url, $data);
        return json_decode($res, true);
    }

    public function refreshAllTurntablePool($array)
    {
        if (array_key_exists('poolId', $array)) {
            $src = $this->refreshPoolApi($array['turntableId'], $array['poolId']);
        } else {
            $src = $this->refreshAllPoolApi($array['turntableId']);
        }
        if ($src['code'] == 200) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => $src['desc']]);die;
        }
    }

    public function refreshPoolApi($id, $poolId)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/turntable/refreshPool';
        $data = ['turntableId' => $id, 'poolId' => $poolId];
        $res = curlData($socket_url, $data);
        return json_decode($res, true);
    }

    public function refreshAllPoolApi($id)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/turntable/refreshAllPool';
        $data = ['turntableId' => $id];
        $res = curlData($socket_url, $data);
        return json_decode($res, true);
    }

    public function clearCacheTurntableConf()
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', self::$turntable)->value('json');
        $src = $this->validation($json);
        if ($src['code'] == 200) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => $src['desc']]);die;
        }
    }

    public function turntableSwitch($array)
    {
        $turntable = $this->getConf(self::$turntable);
        $turntable['isOpen'] = (int) $array['status'];
        $turntable_conf = json_encode($turntable);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$turntable)->save(['json' => $turntable_conf]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function saveTurntableForm($array)
    {
        if (!$array['fullPublicGiftValue'] || !$array['fullFlutterGiftValue'] || !$array['inJinliGiftValue']) {
            echo json_encode(['code' => 500, 'msg' => '必填参数不可为空']);die;
        }
        $turntable = $this->getConf(self::$turntable);
        $turntable['fullPublicGiftValue'] = (int) $array['fullPublicGiftValue'];
        $turntable['fullFlutterGiftValue'] = (int) $array['fullFlutterGiftValue'];
        $turntable['inJinliGiftValue'] = (int) $array['inJinliGiftValue'];
        $data = json_encode($turntable);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$turntable)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function saveTurntablePool($data)
    {
        if (array_key_exists('type', $data)) {
            if ($data['type'] == 'delete') {
                $res = $this->delBoxPool($data);
            } else {
                $res = $this->saveBoxPool($data);
            }
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$turntable)->save(['json' => $res]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '配置未改动']);die;
        }
    }

    public function getTurntablePoolGift($data)
    {
        $turntable = $this->getConf(self::$turntable);
        foreach ($turntable['turntables'] as $k => $v) {
            if ($v['turntableId'] == $data['id']) {
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

    public function getTurntableCondition($data)
    {
        $turntable = $this->getConf(self::$turntable);
        foreach ($turntable['turntables'] as $k => $v) {
            if ($v['turntableId'] == $data['id']) {
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

    public function addTurntablePool($data)
    {
        $data = $this->saveBoxPool($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$turntable)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function validation($json)
    {
        $socket_url = config('config.app_api_url') . 'api/inner/turntable/setConf';
        $data = ['conf' => $json];
        $res = curlData($socket_url, $data, 'POST', 'form-data');
        return json_decode($res, true);
    }

    //转盘奖池
    public function turntablePool($data)
    {
        $turntableId = $data['turntableId'];
        $turntable = $this->getConf(self::$turntable);
        foreach ($turntable['turntables'] as $k => $v) {
            if ($v['turntableId'] == $turntableId) {
                $turntable = $v;
                $turntableName = $v['name'];
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
            $burstRate = $this->burstRate($pool, $turntable);
        }
        $last_names = array_column($burstRate, 'sort');
        array_multisort($last_names, SORT_ASC, $burstRate);
        return ['data' => $burstRate, 'turntableName' => $turntableName, 'count' => $count];
    }

    public function burstRate($pool, $turntable)
    {
        $pool = $this->asseMbly($pool);
        foreach ($pool as $k => $v) {
            $pool[$k]['price'] = $turntable['price'];
            $output = $consume = 0;
            foreach ($v['gift'] as $kk => $vv) {
                $output += $this->getGiftPrice($vv[0]) * $vv[1];
                $consume += $turntable['price'] * $vv[1];
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
    public function saveTurntable($data)
    {
        $data = $this->dealWithData($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$turntable)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function getTurntableGift($data)
    {
        $id = $data['id'];
        $turntable = $this->getConf(self::$turntable);
        foreach ($turntable['turntables'] as $k => $v) {
            if ($v['turntableId'] == $id) {
                echo json_encode(['code' => 200, 'msg' => '获取转盘特殊礼物', 'data' => $v['special']['gifts']]);
            }
        }
    }

    //增
    public function addTurntable($data)
    {
        $turntable = $this->getConf(self::$turntable);
        if (in_array($data['turntableId'], array_column($turntable['turntables'], 'turntableId'))) {
            echo json_encode(['code' => 500, 'msg' => '转盘id已存在']);die;
        }
        $data = $this->dealWithData($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$turntable)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }
    //查
    public function getTurntableConf()
    {
        $turntable = $this->getConf(self::$turntable);
        $data = [];
        if (!empty($turntable)) {
            foreach ($turntable['turntables'] as $k => $v) {
                $data[] = [
                    'turntableId' => $v['turntableId'],
                    'name' => $v['name'],
                    'price' => $v['price'],
                ];
            }
        }
        if (!array_key_exists('inJinliGiftValue', $turntable)) {
            $turntable['inJinliGiftValue'] = 0;
        }

        return ['data' => $data, 'turntable' => $turntable];
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
            'turntables' => [$array],
        ];
        return $data;
    }

    public function asseMbly($array)
    {
        foreach ($array as $k => $v) {
            $data[$k] = [
                'poolId' => $v['poolId'],
                'type' => self::$turntableType[$v['type']],
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
                'key' => $k + 1,
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
        $turntable = $this->getConf(self::$turntable);
        $value = [
            'turntableId' => (int) $array['turntableId'],
            'name' => $array['name'],
            'price' => (int) $array['price'],
        ];

        if (!$turntable) {
            $turntable = $this->assemblyBoxConf($value);
        } else {
            if (in_array($value['turntableId'], array_column($turntable['turntables'], 'turntableId'))) {
                foreach ($turntable['turntables'] as $k => $v) {
                    if ($v['turntableId'] == $value['turntableId']) {
                        $count = $k;
                        if (array_key_exists('pools', $v)) {
                            $value['pools'] = $v['pools'];
                        } else {
                            $value['pools'] = [];
                        }
                    }
                }
                $turntable['turntables'][$count] = $value;
            } else {
                $turntable['turntables'][count($turntable['turntables'])] = $value;
            }
        }
        return json_encode($turntable);
    }

    public function delBoxPool($array)
    {
        $turntableData = $this->getConf(self::$turntable);
        foreach ($turntableData['turntables'] as $k => $v) {
            if ($v['turntableId'] == $array['turntableId']) {
                $turntable = $v;
            }
        }
        if (array_key_exists('pools', $turntable) && !empty($turntable)) {
            $turntablePools = $turntable['pools'];
            foreach ($turntablePools as $k => $v) {
                if ($v['poolId'] == $array['poolId']) {
                    unset($turntablePools[$k]);
                    $turntable['pools'] = $turntablePools;
                }
            }
        }
        foreach ($turntableData['turntables'] as $k => $v) {
            if ($v['turntableId'] == $array['turntableId']) {
                $turntableData['turntables'][$k] = $turntable;
            }
        }
        return json_encode($turntableData);
    }

    public function saveBoxPool($array)
    {
        if ((int) $array['sort'] <= 0) {
            echo json_encode(['code' => 500, 'msg' => '排序错误']);die;
        }
        $turntablePools = [];
        $turntableData = $this->getConf(self::$turntable);
        foreach ($turntableData['turntables'] as $k => $v) {
            if ($v['turntableId'] == $array['turntableId']) {
                $turntable = $v;
            }
        }
        if (array_key_exists('pools', $turntable) && !empty($turntable)) {
            $turntablePools = $turntable['pools'];
            $poolIdColumn = array_column($turntablePools, 'poolId');
        }

        $poolId = 1;
        if (array_key_exists('poolId', $array)) {
            $poolId = $array['poolId'];
        } else {
            if (array_key_exists('pools', $turntable)) {
                $sort = array_column($turntablePools, 'sort');
                if (in_array($array['sort'], $sort)) {
                    echo json_encode(['code' => 500, 'msg' => '奖池排序以存在']);die;
                }
                $poolIds = array_column($turntablePools, 'poolId');
                if (!empty($poolIds)) {
                    $poolId = max($poolIds) + 1;
                }
            }
        }

        $value = [
            'poolId' => (int) $poolId,
            'type' => $array['type'],
            'sort' => (int) $array['sort'],
            'condition' => $this->poolCondition($array),
            'gifts' => $this->poolGift($array),
        ];
        if ($poolId == 1 && count($turntablePools) <= 1) {
            $turntable['pools'] = [$value];
        } else {
            if (in_array($value['poolId'], $poolIdColumn)) {
                foreach ($poolIdColumn as $k => $v) {
                    if ($v == $value['poolId']) {
                        $turntablePools[$k] = $value;
                    }
                }
            } else {
                $turntablePools[count($poolIdColumn)] = $value;
            }

            $turntable['pools'] = array_values($turntablePools);
        }
        foreach ($turntableData['turntables'] as $k => $v) {
            if ($v['turntableId'] == $array['turntableId']) {
                $turntableData['turntables'][$k] = $turntable;
            }
        }
        return json_encode($turntableData);
    }

    public function getBox($id)
    {
        $turntable = $this->getConf(self::$turntable);
        foreach ($turntable['turntables'] as $k => $v) {
            if ($v['turntableId'] == $id) {
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
                echo json_encode(['code' => 500, 'msg' => '数量不可为空']);die;
            }
            $data[$k][1] = (int) $v;
        }

        if (count($array['Key']) != count(array_unique($array['Key']))) {
            echo json_encode(['code' => 500, 'msg' => '奖励排序不可重复']);die;
        }
        foreach ($array['Key'] as $k => $v) {
            if ($v == null) {
                echo json_encode(['code' => 500, 'msg' => '排序不可为空']);die;
            }
            $data[$k][2] = (int) $v;
        }
        $this->sortArrByField($data, 2);
        return $data;
    }

    public function sortArrByField(&$array, $field, $desc = false)
    {
        $fieldArr = array();
        foreach ($array as $k => $v) {
            $fieldArr[$k] = $v[$field];
        }
        $sort = $desc == false ? SORT_ASC : SORT_DESC;
        array_multisort($fieldArr, $sort, $array);
        foreach ($array as $k => $v) {
            unset($array[$k][2]);
        }
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
                $data[$k]['baolv'][1] = (int) $v;
            } else {
                unset($data[$k]['baolv']);
            }
        }

        foreach ($array['baolvKey'] as $k => $v) {
            if (array_key_exists('baolv', $data[$k])) {
                if ($v != null) {
                    $data[$k]['baolv'][0] = (int) $v;
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

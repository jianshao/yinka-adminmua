<?php

namespace app\admin\service;

use app\admin\common\CommonConst;
use app\admin\model\ActivityTimesModel;
use app\admin\model\ConfigModel;
use app\admin\model\EmoticonModel;
use app\admin\model\GiftGameModel;
use app\admin\model\GiftModel;
use app\admin\model\SiteconfigModel;
use app\admin\script\analysis\GiftsCommon;
use app\common\RedisCommon;
use Exception;
use think\facade\Log;
use Throwable;

class ConfigService
{
    protected static $instance;
    protected static $jin = 'ore:gold';
    protected static $yin = 'ore:silver';
    protected static $tie = 'ore:iron';
    protected static $hua = 'ore:fossil';
    protected static $dou = 'user:bean';
    protected static $currency = ['金矿石' => 'ore:gold', '银矿石' => 'ore:silver', '铁矿石' => 'ore:iron', '化石' => 'ore:fossil', '豆' => 'user:bean'];

    public static $BUY = 'buy';
    public static $VIP = 'vip';
    public static $SVIP = 'svip';
    public static $GOLD_BOX = 'goldBox';
    public static $SILVER_BOX = 'silverBox';
    public static $FIRST_PAY = 'firstPay';
    public static $DUKE = 'duke';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ConfigService();
        }
        return self::$instance;
    }

    public function savePublicScreen($array)
    {
        try {
            $lucky_bag_public_screen_value = (int) $array['lucky_bag_public_screen_value'];
            $lucky_bag_marquee_value = (int) $array['lucky_bag_marquee_value'];
            if ($lucky_bag_public_screen_value <= 0 || $lucky_bag_marquee_value <= 0) {
                echo json_encode(['code' => 500, 'msg' => '必填参数不可小于等于0']);die;
            }
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $redis->hSet('public_screen_conf', 'lucky_bag_public_screen_value', (int) $array['lucky_bag_public_screen_value']);
            $redis->hSet('public_screen_conf', 'lucky_bag_marquee_value', (int) $array['lucky_bag_marquee_value']);
            $this->register();
            // $gift = ConfigService::getInstance()->JsonEscape('gift_conf');
            // foreach ($gift as $k => $v) {
            //     foreach ($array['giftId'] as $kk => $vv) {
            //         if ($vv == $v['giftId']) {
            //             if ((int) $array['randValues0'][$kk] <= 0 || (int) $array['randValues1'][$kk] <= 0) {
            //                 echo json_encode(['code' => 500, 'msg' => '必填参数不可小于等于0']);die;
            //             }
            //             $gift[$k]['gainContents'][0]['randValues'][0] = (int) $array['randValues0'][$kk];
            //             $gift[$k]['gainContents'][0]['randValues'][1] = (int) $array['randValues1'][$kk];
            //         }
            //     }
            // }
            // $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($gift)]);
            echo json_encode(['code' => 200, 'msg' => '编辑成功']);die;
        } catch (Throwable $th) {
            echo json_encode(['code' => 500, 'msg' => '编辑失败']);die;
        }
    }

    public function PublicScreen()
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $lucky_bag_marquee_value = $redis->hGet('public_screen_conf', 'lucky_bag_marquee_value');
        $lucky_bag_public_screen_value = $redis->hGet('public_screen_conf', 'lucky_bag_public_screen_value');
        $gift = ConfigService::getInstance()->JsonEscape('gift_conf');
        $giftList = $info = [];
        foreach ($gift as $k => $v) {
            if (array_key_exists('type', $v) && $v['type'] == 'luckyBag') {
                $giftList[] = $v['type'] == 'luckyBag' ? $v : '';
            }
        }

        foreach ($giftList as $k => $v) {
            $info[] = [
                'giftId' => $v['giftId'],
                'name' => $v['name'],
                'assetId' => $v['gainContents'][0]['assetId'],
                'randValues0' => $v['gainContents'][0]['randValues'][0],
                'randValues1' => $v['gainContents'][0]['randValues'][1],
            ];
        }
        return ['lucky_bag_marquee_value' => $lucky_bag_marquee_value, 'lucky_bag_public_screen_value' => $lucky_bag_public_screen_value, 'info' => $info];
    }

    //表情
    public function EmoticonConfJson()
    {
        $data = EmoticonModel::getInstance()->getModel()->field('id,face_name name,face_image image,is_vip vipLevel,type,is_lock isLock,animation,game_image gameImages')->select()->toArray();
        foreach ($data as $k => $v) {
            if ($v['gameImages']) {
                $data[$k]['gameImages'] = explode(";", $v['gameImages']);
            } else {
                $data[$k]['gameImages'] = [];
            }
        }
        return json_encode($data);
    }

    //表情面板
    public function EmoticonPanelsConfJson()
    {
        return json_encode([
            [
                'name' => 'normal',
                'icon' => '',
                'mold' => (int) 1,
                'emoticons' => array_column(EmoticonModel::getInstance()->getModel()->where([['is_vip', '=', 0]])->order('is_sort desc')->field('id')->select()->toArray(), 'id'),
            ],
            [
                'name' => 'special',
                'icon' => '',
                'mold' => (int) 2,
                'emoticons' => array_column(EmoticonModel::getInstance()->getModel()->where([['is_vip', '<>', 0]])->order('is_sort desc')->field('id')->select()->toArray(), 'id'),
            ],
        ]);
    }

    //礼物奖池比例
    public function gameProportion($id)
    {
        $Json = SiteconfigModel::getInstance()->getModel()->field('game_proportion')->select();
        if ($Json) {
            $Json = $Json->toArray();
        } else {
            $Json = [];
        }
        $int = json_decode($Json[0]['game_proportion'], true);
        return [
            'id' => $id,
            'id2' => $int['id'],
        ];
    }

    //礼物面板编辑
    public function giftWallStatusSave($id)
    {
        if ($id == 0) {
            $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->save(['gift_wall_status' => 1]);
        } else {
            $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->save(['gift_wall_status' => 0]);
        }
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);die;
        }
    }

    //礼物列表
    public function giftConf($page, $giftId, $giftType)
    {
        $pagenum = 20;
        $count = 0;
        $vipData = [
            1 => 'vip',
            2 => 'svip',
        ];

        $duke = [];
        foreach (ConfigService::getInstance()->JsonEscape('duke_conf')['levels'] as $k => $v) {
            $duke[] = [
                'duke_id' => $v['level'],
                'duke_name' => $v['name'],
            ];
            $dukeData[$v['level']] = $v['name'];
        }

        $list = [];
        $total_gifts = $this->JsonEscape('gift_conf');

        if (!empty($giftType)) {
            $list = [];
            foreach ($total_gifts as $_ => &$gift) {
                if (!isset($gift['gift_type'])) {
                    $gift['gift_type'] = 1;
                }
                if ($gift['gift_type'] == $giftType) {
                    $list[] = $gift;
                }
            }

            $data = $this->_page_array(20, $page, 1, $list);
        } else if (!empty($giftId)) {
            $list = [];
            $gift_map = array_column($total_gifts, null, 'giftId');
            $gift_name_map = array_column($total_gifts, null, 'name');
            if (!is_numeric($giftId)) {
                //根据关键词找到所有的礼物id
                $findGiftList = $this->findGiftByName($giftId);
                $list = $findGiftList;
            } else {
                $list[0] = isset($gift_map[$giftId]) ? $gift_map[$giftId] : [];
            }
            $data = $this->_page_array(20, $page, 1, $list);
        } else {
            $data = $this->page_array(20, $page, 1, 'gift_conf');
        }

        $list = [];
        if ($data) {
            try {
                $list = $data['list'];
                $count = $data['count'];
                foreach ($list as $k => $v) {
                    $image = arrayStringVal($v, 'image');
                    $list[$k]['image'] = config('config.APP_URL_image') . $image;

                    $animation = arrayStringVal($v, 'animation');
                    $giftAnimation = arrayStringVal($v, 'giftAnimation');
                    $tags = arrayStringVal($v, 'tags');
                    $giftMp4Animation = arrayStringVal($v, 'giftMp4Animation');
                    $list[$k]['mp4Rate'] = arrayFloatVal($v, 'mp4Rate', 0.5);

                    $list[$k]['animation'] = config('config.APP_URL_image') . $animation;
                    $list[$k]['giftAnimation'] = config('config.APP_URL_image') . $giftAnimation;
                    $list[$k]['gift_animation'] = $giftAnimation;
                    $list[$k]['tags'] = config('config.APP_URL_image') . $tags;
                    $list[$k]['gift_mp4'] = config('config.APP_URL_image') . $giftMp4Animation;

                    $list[$k]['count'] = (int) $v['price']['count'];
                    $list[$k]['gift_type'] = isset($v['gift_type']) ? $v['gift_type'] : 1;
                    $list[$k]['duke_name'] = isset($dukeData[$v['dukeLevel']]) ? $dukeData[$v['dukeLevel']] : '/';
                    $list[$k]['vip_name'] = isset($vipData[$v['vipLevel']]) ? $vipData[$v['vipLevel']] : '/';

                    $list[$k]['is_all_mic'] = isset($v['clientParams']) ? $v['clientParams']['sendAllMic'] : 0;

                    // "clientParams" => [
                    //     "sendAllMic" => (int) $array['is_all_mic'],
                    // ],

                    unset($list[$k]['price']);
                }
            } catch (Exception $e) {
                $list = [];
            }
        }
        $page_array = [];
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $pagenum);
        return [
            'page' => $page_array,
            'list' => $list,
            'duke' => $duke,
        ];
    }

    //礼物图片添加
    public function giftConfImg($array)
    {
        $id = $array['id'];
        unset($array['master_url']);unset($array['token']);unset($array['id']);
        $data = ConfigService::getInstance()->JsonEscape('gift_conf');
        foreach ($data as $k => $v) {
            if ($v['giftId'] == $id) {
                $gift = $v;
            }
        }

        foreach ($array as $k => $v) {
            $gift[$k] = $v;
        }

        foreach ($data as $k => $v) {
            if ($v['giftId'] == $id) {
                $data[$k] = $gift;
            }
        }

        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    //删除礼物
    public function giftConfDel($id)
    {
        $type = 'gift_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);
        foreach ($data as $k => $v) {
            if ($v['giftId'] == $id) {
                unset($data[$k]);
            }
        }
        $data = json_encode($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', $type)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }
    //礼物编辑
    public function giftConfSave($data)
    {
        if (empty($data['intro'])) {
            echo json_encode(['code' => 500, 'msg' => '介绍不可为空']);die;
        }
        if ($data['count'] < 0) {
            echo json_encode(['code' => 500, 'msg' => '价值不可为负数']);die;
        }
        if (empty($data['name'])) {
            echo json_encode(['code' => 500, 'msg' => '名称不可为空']);die;
        }
        if (empty($data['classification'])) {
            echo json_encode(['code' => 500, 'msg' => '描述不可为空']);die;
        }

        $data['giftId'] = (int) $data['giftId'];
        $data['charm'] = (int) $data['charm'];
        $data['count'] = (int) $data['count'];
        $data['class_type'] = (int) $data['class_type'];
        $data['gift_type'] = (int) $data['gift_type'];
        $data['dukeLevel'] = (int) $data['dukeLevel'];
        $data['vipLevel'] = (int) $data['vipLevel'];
        $data['mp4Rate'] = arrayFloatVal($data, 'mp4Rate', 0.5);

        $data['clientParams'] = [
            "sendAllMic" => (int) $data['is_all_mic'],
        ];

        $arr = ConfigService::getInstance()->JsonEscape('gift_conf');

        foreach ($arr as $k => $v) {
            if ($v['giftId'] == $data['giftId']) {
                $gift = $v;
            }
        }

        foreach ($gift as $k => $v) {
            if (isset($data[$k])) {
                $gift[$k] = $data[$k];
            }
            if (isset($data['count'])) {
                $gift['price']['count'] = (int) $data['count'];
            }
            $gift['class_type'] = $data['class_type'];
            $gift['gift_type'] = $data['gift_type'];
            $gift['mp4Rate'] = $data['mp4Rate'];
            $gift['clientParams'] = $data['clientParams'];
        }

        foreach ($arr as $k => $v) {
            if ($v['giftId'] == $data['giftId']) {
                $arr[$k] = $gift;
            }
        }

        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($arr)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    //商品详情
    public function goodsConfDetails($id)
    {
        $goods = 'goods_conf';
        $list = ConfigService::getInstance()->JsonEscape($goods, 'goodsId', $id);
        $data = [];
        if (array_key_exists('priceList', $list) && !empty($list['priceList'])) {
            foreach ($list['priceList'] as $k => $v) {
                $data[$k]['number'] = (int) $v['count'];
                $data[$k]['price'] = (int) $v['price']['count'];
                $data[$k]['assetId'] = explode(':', $v['price']['assetId'])[1];
            }
        } else if (count($data) < 1) {
            $data[0]['number'] = 0;
            $data[0]['price'] = 0;
            $data[0]['assetId'] = 'coin';
        }
        $page_array = [];
        return [
            'page_array' => $page_array,
            'id' => $id,
            'list' => $data,
        ];
    }

    //商品详情编辑
    public function goodsDetailsSave($number, $price, $type, $id)
    {
        $goodsList = [];
        foreach ($number as $k => $v) {
            $goodsList[$k]['count'] = (int) $v;
            $goodsList[$k]['price']['count'] = (int) $price[$k];
            if ($type[$k] == 'iron' || $type[$k] == 'fossil' || $type[$k] == 'silver' || $type[$k] == 'gold') {
                $goodsList[$k]['price']['assetId'] = 'ore:' . $type[$k];
            } else {
                $goodsList[$k]['price']['assetId'] = 'user:' . $type[$k];
            }
        }
        $type = 'goods_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);
        foreach ($data as $k => $v) {
            if ($v['goodsId'] == $id) {
                $data[$k]['priceList'] = $goodsList;
            }
        }
        if ($data) {
            $data = array_merge($data);
            $data = json_encode($data);
            $is = ConfigModel::getInstance()->getModel()->where('name', $type)->save(['json' => $data]);
            if ($is) {
                echo json_encode(['code' => 200, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 500, 'msg' => '修改失败']);
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '数据不存在']);
        }
    }

    //礼物配置添加
    public function giftConfAdd($array)
    {
        $GiftId = $this->bigId('gift_conf', 'giftId');
        $list = [[
            'giftId' => (int) $GiftId,
            'name' => $array['name'],
            'image' => arrayStringVal($array, 'image'),
            'class_type' => (int) $array['class_type'],
            'animation' => arrayStringVal($array, 'animation'),
            'giftAnimation' => arrayStringVal($array, 'giftAnimation'),
            'charm' => (int) $array['charm'],
            'intro' => '打赏后主播将获得' . $array['charm'] . '魅力值',
            'classification' => $array['classification'],
            'tags' => arrayStringVal($array, 'tags'),
            'giftMp4Animation' => arrayStringVal($array, 'giftMp4Animation'),
            'createTime' => (int) time(),
            'unit' => $array['unit'],
            'price' => [
                "assetId" => "user:bean",
                "count" => (int) $array['count'],
            ],
            'vipLevel' => 0,
            'dukeLevel' => 0,
            'updateTime' => 0,
            'mp4Rate' => arrayFloatVal($array, 'mp4Rate'),
            "clientParams" => [
                "sendAllMic" => (int) $array['is_all_mic'],
            ],
        ]];

        if ($array['class_type'] == 3) {
            $list[0]['type'] = 'luckyBag';
            $list[0]['functions'] = ['open'];
            $list[0]['gainContents'] = [
                [
                    'type' => 'SingleRandomContent',
                    'assetId' => 'user:bean',
                    'randValues' => [0, 0],
                ],
            ];
        }
        $data = ConfigService::getInstance()->JsonEscape('gift_conf');

        if ($data) {
            $data = array_merge($data, $list);
        } else {
            $data = $list;
        }

        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    //商城商品展示
    public function mallconfAreas($id, $currency)
    {
        $data = ConfigService::getInstance()->JsonEscape('mall_conf');
        $list = [];
        $shelves_desc = [];
        if (count($data[$id]) > 0) {
            foreach ($data[$id]['areas'] as $k => $v) {
                if ($v['type'] == $currency && count($v['shelves']) > 0) {
                    foreach ($v['shelves'] as $kk => $vv) {
                        $list[$kk]['type'] = $v['type'];
                        $list[$kk]['displayName'] = $v['displayName'];
                        $list[$kk]['displayTypeName'] = $vv['displayName'];
                        $list[$kk]['goodsIds'] = $vv['goodsIds'];
                        $key = $kk;
                    }

                    $shelves_desc = array_column($v['shelves'], 'displayName');
                }
            }
        }

        $goods = ConfigService::getInstance()->JsonEscape('goods_conf');
        $info = [];
        if (!empty($list) && $goods) {
            foreach ($list as $k => $v) {
                foreach ($v['goodsIds'] as $kk => $vv) {
                    $info[] = [
                        'name' => '',
                        'image' => '',
                        'goodsId' => $vv,
                        'type' => $v['type'],
                        'displayName' => $v['displayName'],
                        'displayTypeName' => $v['displayTypeName'],
                    ];
                }
            }

            foreach ($goods as $k => $v) {
                foreach ($info as $kk => $vv) {
                    if ($v['goodsId'] == $vv['goodsId']) {
                        if (isset($v['image'])) {
                            $image = config('config.APP_URL_image') . $v['image'];
                        }
                        $info[$kk]['image'] = $image;
                        $info[$kk]['name'] = $v['name'];
                    }
                }
            }
        }

        $goods_map = [];
        foreach ($goods as $_ => $goods_item) {
            $goods_map[$goods_item['type']][] = $goods_item;
        }

        return [
            'list' => $info,
            'goods' => $goods,
            'goods_map' => $goods_map,
            'goods_type' => array_unique(array_column($goods, 'type')),
            'shelves_desc' => $shelves_desc,
            'id' => $id,
            'currency' => $currency,
        ];
    }
    //删除商城展示
    public function delMallGoods($id, $currency, $goodsId, $displayTypeName)
    {
        $type = 'mall_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);
        foreach ($data[$id]['areas'] as $k => $v) {
            if ($v['type'] == $currency) {
                foreach ($v['shelves'] as $kk => $vv) {
                    if ($vv['displayName'] == $displayTypeName) {
                        unset($vv['goodsIds'][array_search($goodsId, $vv['goodsIds'])]);
                        $data[$id]['areas'][$k]['shelves'][$kk] = $vv;
                    }
                }
            }
        }

        $is = ConfigModel::getInstance()->getModel()->where('name', $type)->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }
    //商城商品添加
    public function mallAddGoods($params)
    {
        if (!isset($params['goodsId']) && $params['goodsId'] > 0) {
            echo json_encode(['code' => 500, 'msg' => '请选择商品']);die;
        }

        if (!isset($params['displayTypeName']) && !isset($params['currency']) && !isset($params['id'])) {
            echo json_encode(['code' => 500, 'msg' => '参数错误']);
        }

        $goodsId = $params['goodsId'];
        $displayTypeName = $params['displayTypeName'];
        $currency = $params['currency'];
        $id = $params['id'];
        $type = $params['type'];

        $data = ConfigService::getInstance()->JsonEscape('mall_conf');
        $areas = $data[$id]['areas'];
        $displayName = ConfigService::getInstance()->mall_shelves($areas, $currency); //获取货架信息
        if ($id == 'gashapon') {
            $shelvesGoods = ConfigService::getInstance()->goodsIdsGashapon($displayName, $type, (int) $goodsId); //获取货架商品
            $areasNew = ConfigService::getInstance()->mallGoodsGashapon($areas, $currency, $type, $shelvesGoods); //货架添加商品
        } else {
            $shelvesGoods = ConfigService::getInstance()->goodsIds($displayName, $displayTypeName, (int) $goodsId); //获取货架商品
            $areasNew = ConfigService::getInstance()->mallGoods($areas, $currency, $displayTypeName, $shelvesGoods); //货架添加商品
        }

        $data[$id]['areas'] = $areasNew;
        $is = ConfigModel::getInstance()->getModel()->where('name', 'mall_conf')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    //商品配置
    public function goodsConf($page)
    {
        $pagenum = 20;
        $count = 0;
        $list = [];

        $data = $this->page_array(20, $page, 1, 'goods_conf');
        $prop = ConfigService::getInstance()->JsonEscape('prop_conf');

        $props_map = [];
        foreach ($prop as $_ => $prop_item) {
            $type = $prop_item['type'];
            $props_map[$type][] = $prop_item;
        }

        $gift = ConfigService::getInstance()->JsonEscape('gift_conf');
        if ($data) {
            $list = $data['list'];
            $count = $data['count'];
            foreach ($list as $k => $v) {
                if ($v['image']) {
                    $list[$k]['image'] = config('config.APP_URL_image') . $v['image'];
                }
                if ($v['animation']) {
                    $list[$k]['animation'] = config('config.APP_URL_image') . $v['animation'];
                }
                if ($v['state'] == 1) {
                    $list[$k]['state_name'] = '上架';
                } else {
                    $list[$k]['state_name'] = '下架';
                }

                if ($v['unit'] == '天') {
                    $list[$k]['unit'] = '天';
                } elseif ($v['unit'] == '0') {
                    $list[$k]['unit'] = '个';
                } else {
                    $list[$k]['unit'] = '天';
                }
            }
        }
        $page_array = [];
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $pagenum);
        return [
            'page' => $page_array,
            'prop' => $prop,
            'props_map' => $props_map,
            'gift' => $gift,
            'list' => $list,
        ];
    }

    //商品添加
    public function goodsAdd($params)
    {
        if (!arrayKeyValue($params, 'type')) {
            echo json_encode(['code' => 500, 'msg' => '请选择类型']);die;
        }

        if (!arrayKeyValue($params, 'kindId')) {
            echo json_encode(['code' => 500, 'msg' => '请选择资产']);die;
        }
        $kindId = (int) $params['kindId'];
        $type = $params['type'];
        $state = (int) $params['state'];
        $buyType = $params['buyType'];
        $actions = isset($params['actions']) && !empty($params['actions']) && !empty($params['action'][0]) ? $params['actions'] : [];

        $priceList = [];
        for ($i = 0; $i < count($params['number']); $i++) {
            if ((int) $params['price'][$i] > 0 && (int) $params['number'][$i] > 0) {
                $price_item = [
                    "price" => [
                        "assetId" => $params['assets'][$i],
                        "count" => (int) $params['price'][$i],
                    ],
                    "count" => (int) $params['number'][$i],
                ];
                $priceList[] = $price_item;
            }
        }
        $id = $this->bigId('goods_conf', 'goodsId');
        if ($type == 'gift') {
            $gift = ConfigService::getInstance()->JsonEscape('gift_conf', 'giftId', $kindId);
            $list = [[
                'goodsId' => $id,
                'name' => $gift['name'],
                'desc' => $gift['name'],
                'image' => $gift['image'],
                'animation' => (int) $gift['animation'],
                'multiple' => 0, //图片比例
                'state' => $state,
                'color' => '',
                'unit' => $gift['unit'],
                'buyType' => $buyType,
                'type' => $type,
                'priceList' => $priceList,
                'actions' => $actions,
                'content' => [
                    'assetId' => 'gift:' . $kindId,
                    'count' => 1,
                ],
            ]];

        } else if ($type == 'asset') {
            // $prop = ConfigService::getInstance()->JsonEscape('prop_conf', 'kindId', $kindId);
            $list = [[
                'goodsId' => $id,
                'name' => '',
                'desc' => '',
                'image' => '',
                'animation' => '',
                'multiple' => 0,
                'state' => $state,
                'color' => '',
                'unit' => '',
                'buyType' => $buyType,
                'type' => $type,
                'priceList' => $priceList,
                'actions' => $actions,
                'content' => [
                    'assetId' => $kindId,
                    'count' => $params['kind_count'],
                ],
            ]];
        } else {
            //道具
            $prop = ConfigService::getInstance()->JsonEscape('prop_conf', 'kindId', $kindId);
            $goodsImage = $prop['image'];
            if ($prop['type'] == 'bubble') {
                $goodsImage = $prop['bubbleWordImage'];
            }
            $list = [[
                'goodsId' => $id,
                'name' => $prop['name'],
                'desc' => $prop['desc'],
                'image' => $goodsImage,
                'animation' => $prop['animation'],
                'multiple' => (float) $prop['multiple'],
                'state' => $state,
                'color' => arrayStringVal($prop, 'color'),
                'unit' => $prop['unit']['displayName'],
                'buyType' => $buyType,
                'type' => $type,
                'priceList' => $priceList,
                'actions' => $actions,
                'content' => [
                    'assetId' => 'prop:' . $kindId,
                    'count' => 1,
                ],
            ]];
        }

        if ($state == 0) {
            unset($list[0]['priceList']);
        }

        $data = ConfigService::getInstance()->JsonEscape('goods_conf');
        if ($data) {
            $data = array_merge($data, $list);
        } else {
            $data = $list;
        }

        $is = ConfigModel::getInstance()->getModel()->where('name', 'goods_conf')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //商品上下架切换
    public function goodsConfSave($params)
    {
        if (!arrayKeyValue($params, 'type')) {
            echo json_encode(['code' => 500, 'msg' => '请选择类型']);die;
        }
        if (!arrayKeyValue($params, 'goodsId')) {
            echo json_encode(['code' => 500, 'msg' => '参数错误']);die;
        }

        $goodsId = $params['goodsId'];
        $type = $params['type'];
        $buyType = $params['buyType'];
        $kindId = $params['kindId'];
        $state = (int) $params['state'];

        $actions = isset($params['actions']) && !empty($params['actions']) && !empty($params['actions'][0]) ? $params['actions'] : [];

        $priceList = [];
        for ($i = 0; $i < count($params['number']); $i++) {
            if ($params['price'][$i] > 0 && $params['number'][$i] > 0) {
                $price_item = [
                    "price" => [
                        "assetId" => $params['assets'][$i],
                        "count" => (int) $params['price'][$i],
                    ],
                    "count" => (int) $params['number'][$i],
                ];
                $priceList[] = $price_item;
            }
        }

        $data = ConfigService::getInstance()->JsonEscape('goods_conf');
        if ($data) {
            foreach ($data as $k => &$goods) {
                if ($goods['goodsId'] == $goodsId) {
                    if ($type == 'gift') {
                        $gift = ConfigService::getInstance()->JsonEscape('gift_conf', 'giftId', $kindId);
                        $goods['name'] = $gift['name'];
                        $goods['desc'] = $gift['name'];
                        $goods['image'] = $gift['image'];
                        $goods['animation'] = (int) $gift['animation'];
                        $goods['multiple'] = 0; //图片比;
                        $goods['color'] = '';
                        $goods['unit'] = $gift['unit'];

                        $goods['content'] = [
                            'assetId' => 'gift:' . $kindId,
                            'count' => 1,
                        ];
                    } else if ($type == 'asset') {
                        //资产
                        $goods['name'] = '';
                        $goods['desc'] = '';
                        $goods['image'] = '';
                        $goods['animation'] = '';
                        $goods['multiple'] = 0;
                        $goods['color'] = '';
                        $goods['unit'] = '';

                        $goods['content'] = [
                            'assetId' => $kindId,
                            'count' => (int) $params['kind_count'],
                        ];
                    } else {
                        //道具
                        $prop = ConfigService::getInstance()->JsonEscape('prop_conf', 'kindId', $kindId);
                        $goodsImage = $prop['image'];
                        if ($prop['type'] == 'bubble') {
                            $goodsImage = $prop['bubbleWordImage'];
                        }
                        $goods['name'] = $prop['name'];
                        $goods['desc'] = $prop['desc'];
                        $goods['image'] = $goodsImage;
                        $goods['animation'] = $prop['animation'];
                        $goods['multiple'] = (float) $prop['multiple'];
                        $goods['color'] = arrayStringVal($prop, 'color');
                        $goods['unit'] = $prop['unit']['displayName'];

                        $goods['content'] = [
                            'assetId' => 'prop:' . $kindId,
                            'count' => 1,
                        ];
                    }

                    $goods['type'] = $type;
                    $goods['buyType'] = $buyType;
                    $goods['state'] = $state;
                    $goods['actions'] = $actions;
                    $goods['priceList'] = $priceList;
                }
            }
            $is = ConfigModel::getInstance()->getModel()->where('name', 'goods_conf')->save(['json' => json_encode($data)]);
            if ($is) {
                echo json_encode(['code' => 200, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 500, 'msg' => '修改失败']);
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '数据不存在']);
        }
    }

    //删除商城礼物
    public function giftPanelsDel($id, $type1, $type2)
    {
        $data = ConfigService::getInstance()->JsonEscape('gift_panels');
        $gifts = [];
        if ($type2 != false) {
            foreach ($data[$type2] as $k => $v) {
                if ($v['name'] == $type1) {
                    $gifts = $v['gifts'];
                }
            }
            foreach ($gifts as $k => $v) {
                if ($v == $id) {
                    unset($gifts[$k]);
                }
            }
            foreach ($data[$type2] as $k => $v) {
                if ($v['name'] == $type1) {
                    $data[$type2][$k]['gifts'] = array_values($gifts);
                }
            }
        } else {
            unset($data[$type1][array_search($id, $data[$type1])]);
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_panels')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    //礼物商城礼物添加
    public function giftPanelsAdd($giftId, $type1, $type2)
    {
        if (!GiftsCommon::getInstance()->checkGiftIdExists($giftId)) {
            echo json_encode(['code' => 500, 'msg' => '礼物ID不存在']);die;
        }
        $gift[] = $giftId;
        $data = ConfigService::getInstance()->JsonEscape('gift_panels');
        $gifts = [];

        if ($type2 != false) {
            foreach ($data[$type2] as $k => $v) {
                if ($v['name'] == $type1) {
                    $gifts = $v['gifts'];
                }
            }
            $dataGiftPanels = array_merge($gifts, $gift);
            foreach ($data[$type2] as $k => $v) {
                if ($v['name'] == $type1) {
                    $data[$type2][$k]['gifts'] = $dataGiftPanels;
                }
            }
        } else {
            $data[$type1] = array_merge($data[$type1], [$giftId]);
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_panels')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    //礼物权重
    public function saveGiftWeight($weight, $giftid, $type1, $type2)
    {
        $data = ConfigService::getInstance()->JsonEscape('gift_panels');
        if ($type2 != false) {
            foreach ($data[$type2] as $k => $v) {
                if ($v['name'] == $type1) {
                    $gifts = $v['gifts'];
                }
            }
            foreach ($gifts as $k => $v) {
                if ($v == $giftid) {
                    unset($gifts[$k]);
                }
            }
            array_splice($gifts, $weight - 1, 0, $giftid);
            $gifts = array_merge($gifts);
            foreach ($data[$type2] as $k => $v) {
                if ($v['name'] == $type1) {
                    $data[$type2][$k]['gifts'] = $gifts;
                }
            }
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_panels')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    //礼物面板
    public function giftWallDetails($valType)
    {
        $type = 'gift_wall';
        $data = ConfigService::getInstance()->JsonEscape($type);
        $gift = 'gift_conf';
        $gift = ConfigService::getInstance()->JsonEscape($gift);
        $info = [];
        if ($data && $gift) {
            foreach ($data['walls'] as $k => $v) {
                if ($v['name'] == $valType) {
                    $gifts = $v['gifts'];
                }
            }
            foreach ($gift as $kk => $vv) {
                foreach ($gifts as $k => $v) {
                    if ($vv['giftId'] == $v) {
                        $info[$k]['giftId'] = $vv['giftId'];
                        $info[$k]['name'] = $vv['name'];
                        $info[$k]['image'] = config('config.APP_URL_image') . $vv['image'];
                    }
                }
            }
        }
        return [
            'info' => $info,
            'gift' => $gift,
            'type' => $valType,
        ];
    }
    //礼物添加
    public function giftWallAdd($giftId, $type)
    {
        if (!GiftsCommon::getInstance()->checkGiftIdExists($giftId)) {
            echo json_encode(['code' => 500, 'msg' => '礼物ID不存在']);die;
        }
        $gift[] = $giftId;
        $giftPanels = 'gift_wall';
        $data = ConfigService::getInstance()->JsonEscape($giftPanels);
        $gifts = [];
        foreach ($data['walls'] as $k => $v) {
            if ($v['name'] == $type) {
                $gifts = $v['gifts'];
            }
        }
        $dataGiftPanels = array_merge($gifts, $gift);
        foreach ($data['walls'] as $k => $v) {
            if ($v['name'] == $type) {
                $data['walls'][$k]['gifts'] = $dataGiftPanels;
            }
        }
        $data = json_encode($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', $giftPanels)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }
    //删除商城礼物
    public function giftWallDel($id, $type)
    {
        $giftPanels = 'gift_wall';
        $data = ConfigService::getInstance()->JsonEscape($giftPanels);
        $gifts = [];
        foreach ($data['walls'] as $k => $v) {
            if ($v['name'] == $type) {
                $gifts = $v['gifts'];
            }
        }

        foreach ($gifts as $k => $v) {
            if ($v == $id) {
                unset($gifts[$k]);
            }

        }

        foreach ($data['walls'] as $k => $v) {
            if ($v['name'] == $type) {
                $data['walls'][$k]['gifts'] = $gifts;
            }
        }
        $data = json_encode($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', $giftPanels)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
        }
    }
    //礼物商城第一层
    public function giftPanelsTheFirst()
    {
        $type = 'gift_panels';
        $data = array_keys(ConfigService::getInstance()->JsonEscape($type));
        foreach ($data as $k => $v) {
            if ($v == 'panels') {
                $src[$k]['name'] = '房间礼物面板';
                $src[$k]['key'] = $v;
            } elseif ($v == 'private_chat_panels') {
                $src[$k]['name'] = '私聊礼物面板';
                $src[$k]['key'] = $v;
            } elseif ($v == 'gameGifts') {
                $src[$k]['name'] = '活动礼物面板';
                $src[$k]['key'] = $v;
            } else {
                $src[$k]['name'] = $v;
                $src[$k]['key'] = $v;
            }
        }
        return $src;
    }
    //礼物商城类型面板
    public function giftPanels($id)
    {
        $data = ConfigService::getInstance()->JsonEscape('gift_panels');
        foreach ($data as $k => $v) {
            if ($k == $id) {
                $data = $v;
            }
        }
        foreach ($data as $k => $v) {
            $src[$k]['key'] = $v['name'];
            $src[$k]['name'] = $v['displayName'];
        }
        return $src;
    }
    //礼物面板分类添加
    public function giftClassificationAdd($displayName, $name, $type)
    {
        $info = ConfigService::getInstance()->JsonEscape('gift_panels');
        $data = $info[$type];
        $data1 = [
            'name' => $name,
            'displayName' => $displayName,
            'gifts' => [],
        ];
        array_push($data, $data1);
        $info[$type] = $data;
        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_panels')->save(['json' => json_encode($info)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    //礼物商城类型
    public function giftPanelsDetails($type1, $type2)
    {
        $data = ConfigService::getInstance()->JsonEscape('gift_panels');
        $gift = ConfigService::getInstance()->JsonEscape('gift_conf');
        $info = [];
        if ($type1 == 'gameGifts') {
            if ($gift && $data) {
                foreach ($data[$type1] as $k => $v) {
                    $gifts[] = $v;
                }
                foreach ($gift as $k => $v) {
                    if (in_array($v['giftId'], $gifts)) {
                        $info[$k]['giftId'] = $v['giftId'];
                        $info[$k]['name'] = $v['name'];
                        $info[$k]['weight'] = 0;
                        $info[$k]['image'] = config('config.APP_URL_image') . $v['image'];
                    }
                }
            }
        } else {
            if ($gift && $data) {
                foreach ($data[$type2] as $k => $v) {
                    if ($v['name'] == $type1) {
                        $gifts = $v['gifts'];
                    }
                }
                foreach ($gift as $kk => $vv) {
                    foreach ($gifts as $k => $v) {
                        if ($vv['giftId'] == $v) {
                            $info[$k]['giftId'] = $vv['giftId'];
                            $info[$k]['name'] = $vv['name'];
                            $info[$k]['image'] = config('config.APP_URL_image') . $vv['image'];
                            $info[$k]['weight'] = $k + 1;
                        }
                    }
                }
            }
        }
        $data = array_column($info, 'weight');

        if ($data) {
            array_multisort($data, SORT_ASC, $info);
        }
        return [
            'info' => $info,
            'gift' => $gift,
            'type1' => $type1,
            'type2' => $type2,
        ];
    }

    /********************************************* 装扮 ************************************/
    //编辑
    public function propConfSave($kindId,
        $image,
        $imageAndroid,
        $animation,
        $unit,
        $valType,
        $name,
        $desc,
        $showInBag,
        $multiple,
        $bubbleWordImage,
        $color,
        $removeFormBagWhenDied,

        $is_use,
        $use_name,
        $use_asset_nums,
        $use_asset_types,

        $is_breakup,
        $breakup_name,
        $breakup_asset_nums,
        $breakup_asset_types,

        $weight,
        $textColor
    ) {
        $type = 'prop_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);

        if ($is_use && (empty(array_filter($use_asset_types)) || empty(array_filter($use_asset_nums)))) {
            echo json_encode(['code' => 500, 'msg' => '请配置可使用资产']);die;
        }

        if ($is_breakup && (empty(array_filter($breakup_asset_types)) || empty(array_filter($breakup_asset_nums)))) {
            echo json_encode(['code' => 500, 'msg' => '请配置可分解资产']);die;
        }

        $actions = [];
        //是否可使用
        if ($is_use) {
            $use_action = $this->setActions('use', $use_name, $use_asset_types, $use_asset_nums);
            if ($use_action) {

                $actions[] = $use_action;
            }
        }
        //是否可分解
        if ($is_breakup) {
            $break_action = $this->setActions('breakup', $breakup_name, $breakup_asset_types, $breakup_asset_nums);
            if ($break_action) {
                $actions[] = $break_action;
            }
        }

        foreach ($data as $k => &$prop) {
            if ($kindId == $prop['kindId']) {
                $prop['type'] = $valType;
                $prop['name'] = $name;
                $prop['desc'] = $desc;
                if ($image) {
                    $prop['image'] = $image;
                }
                if ($imageAndroid) {
                    $prop['imageAndroid'] = $imageAndroid;
                }
                if ($animation) {
                    $prop['animation'] = $animation;
                }
                $prop['showInBag'] = $showInBag;
                $prop['multiple'] = (float) $multiple;
                $prop['bubbleWordImage'] = $bubbleWordImage;
                $prop['color'] = $color;
                $prop['weight'] = $weight;
                $prop['textColor'] = $textColor;
                $prop['removeFormBagWhenDied'] = $removeFormBagWhenDied;
                $prop['updateTime'] = (int) time();
                $prop['actions'] = $actions;

                if ($unit == 'day') {
                    $prop['unit']['type'] = 'day';
                    $prop['unit']['displayName'] = '天';
                } elseif ($unit == 'wearDay') {
                    $prop['unit']['type'] = 'wearDay';
                    $prop['unit']['displayName'] = '天';
                } elseif ($unit == 'count') {
                    $prop['unit']['type'] = 'count';
                    $prop['unit']['displayName'] = '个';
                } elseif ($unit == 'countMax1') {
                    $prop['unit']['type'] = 'countMax1';
                    $prop['unit']['displayName'] = '个';
                }
            }
        }

        $data = json_encode(array_values($data));
        $is = ConfigModel::getInstance()->getModel()->where('name', $type)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '编辑成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '编辑失败']);
        }
    }
    //删除
    public function propConfDel($id)
    {
        $giftPanels = 'prop_conf';
        $data = ConfigService::getInstance()->JsonEscape($giftPanels);
        foreach ($data as $k => $v) {
            if ($v['kindId'] == $id) {
                unset($data[$k]);
            }

        }
        $data = json_encode($data);
        $is = ConfigModel::getInstance()->getModel()->where('name', $giftPanels)->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
        }
    }

    public function setActions($type, $action_name, $asset_types, $asset_nums)
    {
        $count = count($asset_types);
        $action = [];
        if (!empty(array_filter($asset_types)) && !empty(array_filter($asset_nums))) {
            $action = [
                "type" => $type,
                "name" => $type,
                "displayName" => $action_name,
                "sendAssets" => [],
            ];
            for ($i = 0; $i < $count; $i++) {
                if (!empty($asset_types[$i])) {
                    $sendAssets = [
                        "assetId" => $asset_types[$i],
                        "count" => (int) $asset_nums[$i],
                    ];
                    $action['sendAssets'][] = $sendAssets;
                }
            }
        }
        return $action;
    }
    //添加
    public function propConfAdd(
        $unit,
        $valType,
        $name,
        $desc,
        $image,
        $imageAndroid,
        $animation,
        $showInBag,
        $multiple,
        $bubbleWordImage,
        $color,
        $removeFormBagWhenDied,

        $is_use,
        $use_name,
        $use_asset_nums,
        $use_asset_types,

        $is_breakup,
        $breakup_name,
        $breakup_asset_nums,
        $breakup_asset_types,

        $weight,
        $textColor
    ) {
        if ($is_use && empty(array_filter($use_asset_types)) && empty(array_filter($use_asset_nums))) {
            echo json_encode(['code' => 500, 'msg' => '请配置可使用资产']);die;
        }

        if ($is_breakup && empty(array_filter($breakup_asset_types)) && empty(array_filter($breakup_asset_nums))) {
            echo json_encode(['code' => 500, 'msg' => '请配置可分解资产']);die;
        }

        $actions = [];
        //是否可使用
        $use_action = $this->setActions('use', $use_name, $use_asset_types, $use_asset_nums);
        if ($is_use && $use_action) {
            $actions[] = $use_action;
        }
        //是否可分解
        $break_action = $this->setActions('breakup', $breakup_name, $breakup_asset_types, $breakup_asset_nums);
        if ($is_breakup && $break_action) {
            $actions[] = $break_action;
        }

        $list = [
            [
                'kindId' => $this->bigId('prop_conf', 'kindId'),
                'name' => $name,
                'desc' => $desc,
                'image' => $image,
                'imageAndroid' => $imageAndroid == true ? $imageAndroid : '',
                'bubbleWordImage' => $bubbleWordImage == true ? $bubbleWordImage : '',
                'animation' => $animation == true ? $animation : '',
                'color' => $color == true ? $color : '',
                'createTime' => (int) time(),
                'multiple' => (float) $multiple,
                'updateTime' => 0,
                'removeFormBagWhenDied' => $removeFormBagWhenDied,
                'showInBag' => $showInBag,
                'type' => $valType,
                'unit' => [
                    'type' => 'day',
                    'displayName' => '自然减天',
                ],
                'actions' => $actions,
                'weight' => $weight,
                'textColor' => $textColor,
            ],
        ];

        if ($unit == 'day') {
            $list[0]['unit']['type'] = 'day';
            $list[0]['unit']['displayName'] = '天';
        } elseif ($unit == 'wearDay') {
            $list[0]['unit']['type'] = 'wearDay';
            $list[0]['unit']['displayName'] = '天';
        } elseif ($unit == 'count') {
            $list[0]['unit']['type'] = 'count';
            $list[0]['unit']['displayName'] = '个';
        } elseif ($unit == 'countMax1') {
            $list[0]['unit']['type'] = 'countMax1';
            $list[0]['unit']['displayName'] = '个';
        }
        $data = ConfigService::getInstance()->JsonEscape('prop_conf');
        if (empty($data)) {
            $data = json_encode($list);
        } else {
            $data = array_merge($data, $list);
            $data = json_encode($data);
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'prop_conf')->save(['json' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }
    //列表
    public function propConf($page)
    {
        $master_page = !empty($page) ? $page : 1;
        $count = 0;
        $data = $this->page_array(20, $page, 1, 'prop_conf');
        $list = [];
        if (count($data) > 0) {
            $list = $data['list'];
            $count = $data['count'];
            foreach ($list as $k => $v) {
                $url = config('config.APP_URL_image');
                $list[$k]['image_url'] = isset($v['image']) ? $url . $v['image'] : '';

                $list[$k]['imageAndroid_url'] = isset($v['imageAndroid']) ? $url . $v['imageAndroid'] : '';

                $list[$k]['animation_url'] = isset($v['animation']) ? $url . $v['animation'] : '';

                $list[$k]['bubbleWordImage_url'] = isset($v['bubbleWordImage']) ? $url . $v['bubbleWordImage'] : '';

                if ($v['unit']['type'] && $v['unit']['displayName']) {
                    $list[$k]['displayName'] = $v['unit']['displayName'];
                    $list[$k]['displayType'] = $v['unit']['type'];
                }
                $list[$k]['createTime'] = date('Y-m-d', $v['createTime']);
                $list[$k]['weight'] = isset($v['weight']) ? $v['weight'] : 0;
                $list[$k]['textColor'] = isset($v['textColor']) ? $v['textColor'] : '';
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / 20);
        return ['page_array' => $page_array, 'list' => $list];
    }

    /**************************************~w~** 充值 **************************************/
    //充值面板
    public function chargemallConf($type, $key)
    {
        $data = $this->JsonEscape($type);
        $key = (empty($key)) ? 'ios' : $key;
        if ($key == 'ios') {
            $src['val'] = $data[$key];
            $src['type'] = 1;
        } elseif ($key == 'android') {
            $src['val'] = $data[$key];
            $src['type'] = 2;
        }
        return $src;
    }

    //充值配置
    public function chargeConf($type, $key)
    {
        $data = $this->JsonEscape($type);
        $image_url = config('config.APP_URL_image');
        foreach ($data['products'] as $k => $v) {
            if ($key == $v['productId']) {
                $src['val'] = $v['deliveryItems'];
                $src['type'] = 2;
            }
            if ($key == false) {
                $src['val'][$k] = [
                    'productId' => $v['productId'],
                    'price' => $v['price'],
                    'bean' => $v['bean'],
                    'present' => $v['present'],
                    'image' => $image_url . $v['image'],
                ];
                if (isset($v['status'])) {
                    $src['val'][$k]['status'] = $v['status'];
                } else {
                    $src['val'][$k]['status'] = '';
                }
                if (isset($v['appStoreProductId'])) {
                    $src['val'][$k]['appStoreProductId'] = $v['appStoreProductId'];
                } else {
                    $src['val'][$k]['appStoreProductId'] = '';
                }
                if (isset($v['chargeMsg'])) {
                    $src['val'][$k]['chargeMsg'] = $v['chargeMsg'];
                } else {
                    $src['val'][$k]['chargeMsg'] = '';
                }
                $src['type'] = 1;
            }
        }

        return $src;
    }

    /**************************** 宝箱配置管理 *****************************************/
    public function boxConf($type, $key)
    {
        $data = $this->JsonEscape($type);

        $image_url = config('config.APP_URL_image');
        $key = (empty($key)) ? 'counts' : $key;
        if ($key == 'counts') {
            $src['val'] = $data[$key];
            $src['type'] = 1;
        } elseif ($key == 'silver') {
            $src['val'] = $data['boxes'][0];
            foreach ($src['val']['gifts'] as $k => $v) {
                $src['val']['gifts'][$k]['name'] = $this->giftConfData($v['giftId']);
            }
            $src['type'] = 2;
        } elseif ($key == 'gold') {
            $src['val'] = $data['boxes'][1];
            foreach ($src['val']['gifts'] as $k => $v) {
                $src['val']['gifts'][$k]['name'] = $this->giftConfData($v['giftId']);
            }
            $src['type'] = 3;
        }
        return $src;
    }
    public function giftConfData($id)
    {
        $gift = $this->JsonEscape('gift_conf');
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $id) {
                return $v['name'];
            }
        }
    }
    //宝箱配置保存
    public function boxConfSave($type, $count, $giftId, $weight)
    {
        $data = $this->JsonEscape('box_conf');
        if ($type == 1) {
            foreach ($count as $k => $v) {
                $count[$k] = (int) $v;
            }
            $data['counts'] = $count;
        } elseif ($type == 2) {
            foreach ($giftId as $k => $v) {
                $gifts[$k]['giftId'] = (int) $v;
            }
            foreach ($weight as $k => $v) {
                $gifts[$k]['weight'] = (int) $v;
            }
            $data['boxes'][0]['gifts'] = $gifts;
        } elseif ($type == 3) {
            foreach ($giftId as $k => $v) {
                $gifts[$k]['giftId'] = (int) $v;
            }
            foreach ($weight as $k => $v) {
                $gifts[$k]['weight'] = (int) $v;
            }
            $data['boxes'][1]['gifts'] = $gifts;
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'box_conf')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '编辑成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '编辑失败']);
        }
    }

    /***************************** 等级 *************************************/
    public function levelConf($type, $key)
    {
        $data = $this->JsonEscape($type);
        $key = (empty($key)) ? 'privilege' : $key;
        $image_url = config('config.APP_URL_image');
        if ($key == 'privilege') {
            foreach ($data[$key] as $k => $v) {
                $src['type'] = 1;
                $src['val'][$k] = [
                    'level' => $v['level'],
                    'title' => $v['title'],
                    'image' => $image_url . $v['image'],
                    'previewImage' => $image_url . $v['previewImage'],
                    'content' => $v['content'],
                    'rewardMsg' => $v['rewardMsg'],
                ];
                if (array_key_exists('rewards', $v)) {
                    $src['val'][$k]['assetId'] = $v['rewards'][0]['assetId'];
                    $src['val'][$k]['count'] = $v['rewards'][0]['count'];
                } else {
                    $src['val'][$k]['assetId'] = '';
                    $src['val'][$k]['count'] = '';
                }
            }
        } elseif ($key == 'level') {
            $src['type'] = 2;
            $src['val'] = $data[$key];
        }
        return $src;
    }

    /****************************** 淘金详情 ************************************/
    public function taojinContent($type, $gameId, $classification)
    {
        $data = $this->JsonEscape($type);
        if ($classification == 'diceReward') {
            $rsc = $data['games'][$gameId - 1]['diceReward'];
            $info['jin'] = ConfigService::$jin;
            $info['yin'] = ConfigService::$yin;
            $info['hua'] = ConfigService::$hua;
            $info['tie'] = ConfigService::$tie;
            $info['dou'] = ConfigService::$dou;
            foreach ($rsc as $k => $v) {
                $list[$k] = [
                    'sid' => $v['id'],
                    'weight' => $v['weight'],
                    'count' => $v['reward']['count'],
                ];
                if ($v['reward']['assetId'] == ConfigService::$dou) {
                    $list[$k]['gift'] = '豆';
                } elseif ($v['reward']['assetId'] == ConfigService::$yin) {
                    $list[$k]['gift'] = '银';
                } elseif ($v['reward']['assetId'] == ConfigService::$tie) {
                    $list[$k]['gift'] = '铁';
                } elseif ($v['reward']['assetId'] == ConfigService::$jin) {
                    $list[$k]['gift'] = '金';
                } elseif ($v['reward']['assetId'] == ConfigService::$hua) {
                    $list[$k]['gift'] = '化石';
                }
            }
            $info['list'] = $list;
        } else {
            $info = $data['games'][$gameId - 1]['proportion'];
        }
        return $info;
    }

    public function gameConfImg($array)
    {
        $data = $this->JsonEscape('taojin_conf');
        foreach ($data['games'] as $k => $v) {
            if ($v['gameId'] == $array['id']) {
                if (array_key_exists('image', $array)) {
                    $data['games'][$k]['image'] = $array['image'];
                }
                if (array_key_exists('bgmap', $array)) {
                    $data['games'][$k]['bgmap'] = $array['bgmap'];
                }
                if (array_key_exists('cover', $array)) {
                    $data['games'][$k]['cover'] = $array['cover'];
                }
                if (array_key_exists('map', $array)) {
                    $data['games'][$k]['map'] = $array['map'];
                }
                if (array_key_exists('covermap', $array)) {
                    $data['games'][$k]['covermap'] = $array['covermap'];
                }
            }
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'taojin_conf')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //淘金配置
    public function taojinConf($type, $gameId)
    {
        $data = $this->JsonEscape($type);
        $image_url = config('config.APP_URL_image');
        foreach ($data['games'] as $k => $v) {
            $list[] = [
                'gameId' => $v['gameId'],
                'name' => $v['name'],
                'image' => $image_url . $v['image'],
                'energy' => $v['energy'],
                'status' => $v['status'],
                'bgmap' => $image_url . $v['bgmap'],
                'cover' => $image_url . $v['cover'],
                'map' => $image_url . $v['map'],
                'covermap' => $image_url . $v['covermap'],
            ];
        }
        $energyInfo = $data['energyInfo'];
        $time['start_time'] = isset($data['start_time']) ? date('Y-m-d H:i:s', $data['start_time']) : '';
        $time['end_time'] = isset($data['end_time']) ? date('Y-m-d H:i:s', $data['end_time']) : '';
        return ['list' => $list, 'energyInfo' => $energyInfo, 'time' => $time];
    }

    //商品配置
    public function goods($type)
    {
        $data = $this->JsonEscape($type);
        return array_column($data, 'name', 'goodsId');
    }

    //淘金配置编辑
    public function saveTaoJinForm($array)
    {
        $data = $this->JsonEscape('taojin_conf');
        $data['energyInfo']['rule'] = $array['rule'];
        $data['energyInfo']['commontoast'] = $array['commontoast'];
        $data['energyInfo']['lacktoast'] = $array['lacktoast'];
        $start_time = strtotime($array['start_time']);
        $end_time = strtotime($array['end_time']);
        $start = date("Y-m-d", $start_time);
        $now = date("Y-m-d H:i:s");

        if ($start_time && $end_time) {
            if ($start_time >= $end_time) {
                echo json_encode(['code' => 500, 'msg' => '结束日期不可大于开始日期']);die;
            }
            if ($array['end_time'] < $now) {
                echo json_encode(['code' => 500, 'msg' => '结束日期不能小于当前时间']);die;
            }
            //活动时间插入
            $activity = ActivityTimesModel::getInstance()->getModel()->where('end_time', '>=', $now)->where('type', 'taojin')->find();
            if (empty($activity)) {
                if ($array['start_time'] < $now) {
                    echo json_encode(['code' => 500, 'msg' => '开始日期不能小于当前时间']);die;
                }
                $data['start_time'] = $start_time;
                $res = ActivityTimesModel::getInstance()->getModel()->insert(['type' => 'taojin', 'date' => $start, 'start_time' => $array['start_time'], 'end_time' => $array['end_time']]);
            }

            if (!empty($activity)) {

                $data['start_time'] = strtotime($activity['start_time']);
                $activity = $activity->toArray();
                $res = ActivityTimesModel::getInstance()->getModel()->where('start_time', $activity['start_time'])->where('type', 'taojin')->update(['end_time' => $array['end_time']]);
            }
            $data['end_time'] = $end_time;

            if ($res) {
                ConfigModel::getInstance()->getModel()->where('name', 'taojin_conf')->save(['json' => json_encode($data)]);
                echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
            } else {
                echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
            }

        } else {
            echo json_encode(['code' => 500, 'msg' => '开始或结束日期不正确']);die;
        }
    }

    /**************************** 会员配置 ***************************/
    public function vipConf($type, $status)
    {
        $data = $this->JsonEscape($type);
        $status = ($status == 1) ? 1 : 0;
        $image_url = config('config.APP_URL_image');
        $data = $data['levels'][$status];
        foreach ($data['privilegeDesc'] as $k => $v) {
            $data['privilegeDesc'][$k]['pic'] = $image_url . $v['pic'];
            $data['privilegeDesc'][$k]['previewPic'] = $image_url . $v['previewPic'];
        }
        return $data;
    }

    /*********************** 任务 新手任务 金币抽奖配置 ******************************/

    //登录任务
    public function dailyConf($type, $key, $keyId)
    {
        $data = $this->JsonEscape($type);
        $image_url = config('config.APP_URL_image');
        $key = (empty($key)) ? 'daily' : $key;
        if ($key == 'daily') {
            foreach ($data[$key] as $k => $v) {
                $src['val'][] = [
                    'id' => $v['id'],
                    'name' => $v['name'],
                    'desc' => $v['desc'],
                    'count' => $v['count'],
                    'cycle' => $v['cycle'],
                    'inspectorsType' => $v['inspectors'][0]['type'],
                    'inspectorsDisplayName' => $v['inspectors'][0]['displayName'],
                ];
                $src['type'] = 1;
            }
        } elseif ($key == 'reward') {
            foreach ($data['daily'] as $k => $v) {
                if ($keyId == $v['id']) {
                    $info = $v['rewards'];
                }
            }
            foreach ($info as $k => $v) {
                $src['val'][] = [
                    'assetId' => $v['assetId'],
                    'count' => $v['count'],
                    'name' => $v['name'],
                    'img' => $image_url . $v['img'],
                ];
            }
            $src['type'] = 2;
        }

        return $src;
    }

    //活跃度奖励配置
    public function activeboxConf($type, $key, $id = 0)
    {
        $data = $this->JsonEscape($type);
        $image_url = config('config.APP_URL_image');
        $key = (empty($key)) ? 'activeinfo' : $key;

        if ($id == 0) {
            if ($key == 'activeinfo') {
                $src['val'] = $data[$key];
                $src['type'] = 1;
            } elseif ($key == 'activebox') {
                foreach ($data[$key] as $k => $v) {
                    $src['val'][$k] = [
                        'id' => $v['id'],
                        'name' => $v['name'],
                        'desc' => $v['desc'],
                        'count' => $v['count'],
                        'cycle' => $v['cycle'],
                    ];
                }
                $src['type'] = 2;
            }
        } else {
            foreach ($data[$key] as $k => $v) {
                if ($v['id'] == $id) {
                    $info = $data[$key][$k]['rewards'];
                }
            }
            if (isset($info[0]['randoms'])) {
                foreach ($info[0]['randoms'] as $k => $v) {
                    $src['val'][] = [
                        'weight' => $v['weight'],
                        'assetId' => $v['assetId'],
                        'count' => $v['count'],
                        'name' => $v['name'],
                        'img' => $image_url . $v['img'],
                    ];
                }
                $src['type'] = 3;
            } else {
                foreach ($info as $k => $v) {
                    $src['val'][] = [
                        'assetId' => $v['assetId'],
                        'count' => $v['count'],
                        'name' => $v['name'],
                        'img' => $image_url . $v['img'],
                    ];
                }
                $src['type'] = 4;
            }
        }

        return $src;
    }

    //活跃度奖励
    public function lotteryConf($type, $key)
    {
        $data = $this->JsonEscape($type);
        $image_url = config('config.APP_URL_image');
        $key = (empty($key)) ? 'rules' : $key;
        if ($key == 'rules') {
            $src['type'] = 1;
            $src['val'] = $data['coinLottery'][$key];
        } elseif ($key == 'priceList') {
            $src['type'] = 2;
            foreach ($data['coinLottery'][$key] as $k => $v) {
                $src['val'][] = [
                    'num' => (int) $v['num'],
                    'assetId' => $v['price']['assetId'],
                    'count' => (int) $v['price']['count'],
                ];
            }
        } elseif ($key == 'lotterys') {
            $src['type'] = 3;
            foreach ($data['coinLottery'][$key] as $k => $v) {
                $src['val'][] = [
                    'id' => $v['id'],
                    'weight' => $v['weight'],
                    'name' => $v['name'],
                    'img' => $image_url . $v['img'],
                    'assetId' => $v['reward']['assetId'],
                    'count' => $v['reward']['count'],
                ];
            }
        }

        return $src;
    }

    //任务
    public function weekcheckin($type)
    {
        $data = $this->JsonEscape($type);
        $image_url = config('config.APP_URL_image');
        foreach ($data['weekcheckin'] as $k => $v) {
            $src[] = [
                'id' => $v['id'],
                'name' => $v['name'],
                'desc' => $v['desc'],
                'count' => (int) $v['count'],
                'cycle' => $v['cycle'],
                'count' => $v['rewards'][0]['count'],
                'smallName' => $v['rewards'][0]['name'],
                'img' => $image_url . $v['rewards'][0]['img'],
            ];
        }
        return $src;
    }

    //新手任务
    public function newerConf($type)
    {
        $image_url = config('config.APP_URL_image');
        $data = $this->JsonEscape($type);
        foreach ($data['newer'] as $k => $v) {
            $rsc[] = [
                'id' => $v['id'],
                'name' => $v['name'],
                'desc' => $v['desc'],
                'number' => (int) $v['count'],
                'type' => $v['inspectors'][0]['type'],
                'assetId' => $v['rewards'][0]['assetId'],
                'count' => (int) $v['rewards'][0]['count'],
                'img' => $image_url . $v['rewards'][0]['img'],
            ];
        }
        return $rsc;
    }

    /******************* 爵位 ****************************/
    public function dukeConfig($type)
    {
        $data = $this->JsonEscape($type);
        $image_url = config('config.APP_URL_image');
        $data = $data['levels'];
        foreach ($data as $k => $v) {
            $list[] = [
                'level' => $v['level'],
                'name' => $v['name'],
                'picture' => $image_url . $v['picture'],
                'animation' => $image_url . $v['animation'],
                'value' => (int) $v['value'],
                'relegation' => (int) $v['relegation'],
            ];
        }
        return $list;
    }

    public function dukeDetailsConfig($type, $prop, $level)
    {
        $data = ConfigService::getInstance()->JsonEscape($type);
        $prop = ConfigService::getInstance()->JsonEscape($prop);
        $image_url = config('config.APP_URL_image');
        $data = $data['levels'];
        foreach ($data as $k => $v) {
            if ($v['level'] == $level) {
                $inf = $v;
            }
        }
        foreach ($inf['privilegeAssets'] as $k => $v) {
            $propId[] = explode(':', $v['assetId'])[1];
        }
        foreach ($prop as $k => $v) {
            if (in_array($v['kindId'], $propId)) {
                $dukeProp['prop'][] = [
                    'name' => $v['name'],
                    'image' => $image_url . $v['image'],
                ];
            }
        }
        foreach ($inf['privilegeDesc'] as $k => $v) {
            $dukeProp['privilege'][] = [
                'name' => $v['title'],
                'image' => $image_url . $v['pic'],
            ];
        }
        return $dukeProp;
    }

    /**** 商城 ****/
    //商城
    public function mallConf()
    {
        $type = 'mall_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);
        $list = [];
        if ($data) {
            foreach ($data as $k => $v) {
                $list[] = [
                    'type' => $k,
                    'name' => $v['areas'][0]['displayName'],
                ];
            }
        }
        return $list;
    }

    //商城添加
    public function mallConfAdd($name)
    {
        $type = 'mall_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);
        if ($name) {
            $data[$name] = ['areas' => []];
            $is = ConfigModel::getInstance()->getModel()->where('name', $type)->save(['json' => json_encode($data)]);
            if ($is) {
                echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
            } else {
                echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '参数必填']);die;
        }
    }
    //商城第二次列表
    public function mallconfDetails($id)
    {
        $type = 'mall_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);
        $list = [];
        if (count($data[$id]) > 0) {
            foreach ($data[$id]['areas'] as $k => $v) {
                $list[$k]['type'] = $v['type'];
                $list[$k]['displayName'] = $v['displayName'];
            }
        }
        return $list;
    }
    //商城第二次配置添加
    public function mallconfDetailsAdd($type, $displayName, $currency)
    {
        $key = 'mall_conf';
        if (!$type || !$displayName) {
            echo json_encode(['code' => 500, 'msg' => '参数必填']);die;
        }
        $data = ConfigService::getInstance()->JsonEscape($key);
        $find = [
            'type' => $type,
            'displayName' => $displayName,
            'shelves' => [],
        ];
        array_push($data[$currency]['areas'], $find);
        $is = ConfigModel::getInstance()->getModel()->where('name', $key)->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //货架
    public function mall_shelves($areas, $currency)
    {
        foreach ($areas as $k => $v) {
            if ($v['type'] == $currency) {
                $displayName = $v['shelves'];
            }
        }
        return $displayName;
    }

    /**** 商品配置 ****/
    public function goodsIds($shelvesGoods, $displayTypeName, $goodsId)
    {
        $shelves = [];
        if (!empty($shelvesGoods) && $goodsId > 0) {
            foreach ($shelvesGoods as $k => $v) {
                if ($v['displayName'] == $displayTypeName) {
                    $shelves = $v;
                }
            }
            if (empty($shelves)) {
                $shelvesGoods = [
                    'displayName' => $displayTypeName,
                    'goodsIds' => [$goodsId],
                ];
            } else {
                array_push($shelves['goodsIds'], $goodsId);
                $shelves['goodsIds'] = array_unique($shelves['goodsIds']);
                $shelvesGoods = $shelves;
            }
        } else {
            $shelvesGoods['displayName'] = $displayTypeName;
            $shelvesGoods['goodsIds'] = [$goodsId];
        }
        return $shelvesGoods;
    }

    //货品
    public function mallGoods($areas, $currency, $displayTypeName, $Goods)
    {
        $shelves = [];
        foreach ($areas as $k => $v) {
            if ($v['type'] == $currency) {
                $shelves = $v['shelves'];
            }
        }
        foreach ($shelves as $k => $v) {
            if ($v['displayName'] == $displayTypeName) {
                $shelves[$k] = $Goods;
            }
        }
        foreach ($areas as $k => $v) {
            if ($v['type'] == $currency) {
                $areas[$k]['shelves'] = $shelves;
            }
        }
        return $areas;
    }

    /**** 商品配置 ****/
    public function goodsIdsGashapon($shelvesGoods, $type, $goodsId)
    {
        $shelves = [];
        if (!empty($shelvesGoods)) {
            foreach ($shelvesGoods as $k => $v) {
                if ($v['type'] == $type) {
                    $shelves = $v;
                }
            }
            if (empty($shelves)) {
                $shelvesGoods = [
                    'type' => $type,
                    'goodsIds' => [$goodsId],
                    'displayName' => CommonConst::MALL_ASSET_MAP[$type],
                ];
            } else {
                array_push($shelves['goodsIds'], $goodsId);
                $shelves['goodsIds'] = array_unique($shelves['goodsIds']);
                $shelvesGoods = $shelves;
            }
        } else {
            $shelvesGoods['type'] = $type;
            $shelvesGoods['displayName'] = CommonConst::MALL_ASSET_MAP[$type];
            $shelvesGoods['goodsIds'] = [$goodsId];
        }
        return $shelvesGoods;
    }

    //货品
    public function mallGoodsGashapon($areas, $currency, $type, $Goods)
    {
        $shelves = [];
        foreach ($areas as $k => $v) {
            if ($v['type'] == $currency) {
                $shelves = $v['shelves'];
            }
        }

        foreach ($shelves as $k => $v) {
            if ($v['type'] == $type) {
                $shelves[$k] = $Goods;
            }
        }

        if (!in_array($type, array_column($shelves, 'type'))) {
            $shelves[] = $Goods;
        }

        foreach ($areas as $k => $v) {
            if ($v['type'] == $currency) {
                $areas[$k]['shelves'] = $shelves;
            }
        }
        return $areas;
    }

    // json转义
    public function JsonEscape($key, $keyId = '', $id = '')
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', $key)->value('json');
        if (!empty($json)) {
            $array = json_decode($json, true);
            if ($keyId && $id) {
                foreach ($array as $k => $v) {
                    if ($array[$k][$keyId] == $id) {
                        return $array[$k];
                    }
                }
            }
            return $array;
        } else {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $json = $redis->get($key);
            ConfigModel::getInstance()->getModel()->where('name', $key)->save(['json' => $json]);
            return $json;
        }
    }

    //获取配置最大id
    public function bigId($key, $keyId)
    {
        $array = $this->JsonEscape($key);
        if (is_array($array)) {
            $array = array_reverse($array);
            return (int) ($array[0][$keyId] + 1);
        } else {
            return 1;
        }

    }

    //配置缓存
    public function redisConfig($type)
    {
        $count = ConfigModel::getInstance()->getModel()->count();
        if ($count) {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $data = ConfigModel::getInstance()->getModel()->select();
            if ($type == 1) {
                if ($count) {
                    $data = $data->toArray();
                    foreach ($data as $k => $v) {
                        $redis->set($v['name'], $v['json']);
                    }
                    $this->register();
                    echo json_encode(['code' => 200, 'msg' => '缓存成功']);
                    die;
                }
            } elseif ($type == 2) {
                $data = $data->toArray();
                foreach ($data as $k => $v) {
                    ConfigModel::getInstance()->getModel()->where('name', $v['name'])->save(['json' => $redis->get($v['name'])]);
                }
                echo json_encode(['code' => 200, 'msg' => '缓存成功']);
                die;
            }
        }

    }

    /************************ 表情包 ****************************************/
    //表情包删除
    public function emoticonConfDel($id)
    {
        $giftPanels = 'emoticon_conf';
        $data = ConfigService::getInstance()->JsonEscape($giftPanels);
        foreach ($data as $k => $v) {
            if ($v['id'] == $id) {
                unset($data[$k]);
            }

        }
        $data = json_encode($data);
        if (ConfigModel::getInstance()->getModel()->where('name', $giftPanels)->save(['json' => $data])) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']); //php编译join
        }
    }
    //表情包面板详情
    public function emoticonPanelsConfDetails($name)
    {
        $type1 = 'emoticon_panels_conf';
        $type2 = 'emoticon_conf';
        $data = ConfigService::getInstance()->JsonEscape($type1);
        $emoticon = ConfigService::getInstance()->JsonEscape($type2);
        $list = [];
        foreach ($data as $k => $v) {
            if ($v['name'] == $name) {
                $list = $v['emoticons'];
            }
        }
        $info = [];
        if ($list) {
            foreach ($emoticon as $k => $v) {
                foreach ($list as $kk => $vv) {
                    if ($v['id'] == $vv) {
                        $info[$kk]['id'] = $v['id'];
                        $info[$kk]['name'] = $v['name'];
                        $info[$kk]['image'] = config('config.APP_URL_image') . $v['image'];
                        $info[$kk]['animation'] = config('config.APP_URL_image') . $v['animation'];
                    }
                }
            }
        }
        return $data = [
            'info' => $info,
            'emoticon' => $emoticon,
            'name' => $name,
        ];
    }
    //表情包面板添加
    public function emoticonPanelsConfAdd($gift, $type)
    {
        $giftPanels = 'emoticon_panels_conf';
        $data = ConfigService::getInstance()->JsonEscape($giftPanels);
        $gifts = [];
        foreach ($data as $k => $v) {
            if ($v['name'] == $type) {
                $gifts = $v['emoticons'];
            }
        }
        $dataGiftPanels = array_merge($gifts, $gift);
        foreach ($data as $k => $v) {
            if ($v['name'] == $type) {
                $data[$k]['emoticons'] = $dataGiftPanels;
            }
        }
        return ConfigModel::getInstance()->getModel()->where('name', $giftPanels)->save(['json' => json_encode($data)]);
    }
    //表情包面板
    public function emoticonPanelsConfDel($id, $type)
    {
        $giftPanels = 'emoticon_panels_conf';
        $data = ConfigService::getInstance()->JsonEscape($giftPanels);
        $gifts = [];
        foreach ($data as $k => $v) {
            if ($v['name'] == $type) {
                $gifts = $v['emoticons'];
            }
        }

        foreach ($gifts as $k => $v) {
            if ($v == $id) {
                unset($gifts[$k]);
            }

        }

        foreach ($data as $k => $v) {
            if ($v['name'] == $type) {
                $data[$k]['emoticons'] = $gifts;
            }
        }
        $data = json_encode($data);
        return ConfigModel::getInstance()->getModel()->where('name', $giftPanels)->save(['json' => $data]);
    }
    //表情包列表
    public function emoticonConf($page, $master_page)
    {
        $pagenum = 20;
        $count = 0;
        $list = [];
        $gift = 'emoticon_conf';
        $data = $this->page_array(20, $page, 1, $gift);
        if (count($data) > 0) {
            $list = $data['list'];
            if (is_array($list)) {
                $count = $data['count'];
                foreach ($list as $k => $v) {
                    if ($v['image']) {
                        $list[$k]['image'] = config('config.APP_URL_image') . $v['image'];
                    }
                    if ($v['animation']) {
                        $list[$k]['animation'] = config('config.APP_URL_image') . $v['animation'];
                    }
                }
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $src = [
            'page_array' => $page_array,
            'list' => $list,
        ];
        return $src;
    }
    //表情包添加
    public function emoticonConfAdd($name, $image, $vipLevel, $type, $isLock, $animation)
    {
        $keyId = 'id';
        $key = 'emoticon_conf';
        $propId = $this->bigId($key, $keyId);
        $list = [[
            'id' => (int) $propId,
            'name' => $name,
            'image' => $image,
            'vipLevel' => (int) $vipLevel,
            'type' => (int) $type,
            'isLock' => (int) $isLock,
            'animation' => $animation,
            'gameImages' => [],
        ]];
        $data = ConfigService::getInstance()->JsonEscape($key);
        $data = array_merge($data, $list);
        $data = json_encode($data);
        return ConfigModel::getInstance()->getModel()->where('name', $key)->save(['json' => $data]);
    }

    //表情包编辑
    public function emoticonConfSave($kindId, $image, $animation, $valType, $name, $vipLevel, $isLock)
    {
        $type = 'emoticon_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);
        foreach ($data as $k => $v) {
            if ($kindId == $v['id']) {
                $data[$k]['type'] = (int) $valType;
                $data[$k]['name'] = $name;
                $data[$k]['vipLevel'] = (int) $vipLevel;
                $data[$k]['isLock'] = (int) $isLock;
                if ($image) {
                    $data[$k]['image'] = $image;
                }
                if ($animation) {
                    $data[$k]['animation'] = $animation;
                }
            }
        }
        $data = json_encode($data);
        return ConfigModel::getInstance()->getModel()->where('name', $type)->save(['json' => $data]);
    }

    public function page_array($count, $page, $order, $key)
    {
        $array = $this->JsonEscape($key);
        return $this->_page_array($count, $page, $order, $array);
    }

    /**
     * 数组分页函数  核心函数  array_slice
     * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
     * $count   每页多少条数据
     * $page   当前第几页
     * $array   查询出来的所有数组
     * order 0 - 不变     1- 反序
     */
    public function _page_array($count, $page, $order, $array)
    {
        global $countpage; #定全局变量
        $page = (empty($page)) ? '1' : $page; #判断当前页面是否为空 如果为空就表示为第一页面
        $start = ($page - 1) * $count; #计算每次分页的开始位置
        if ($order == 1 && is_array($array)) {
            $array = array_reverse($array);
            $totals = count($array);
            $countpage = ceil($totals / $count); #计算总页面数
            $pagedata = array();
            $pagedata['list'] = array_slice($array, $start, $count);
            $pagedata['count'] = count($array);
            return $pagedata; #返回查询数据
        } else {
            return []; #返回查询数据
        }
    }

    /**************************************** 淘金 **********************************************/

    /**
     * @return mixed
     * 矿石礼物
     */
    public function Exchange()
    {
        $where[] = ['is_gameexchange', '<>', 0];
        $count = GiftModel::getInstance()->getModel()->where($where)->field('id')->count();
        $giftList = GiftModel::getInstance()->getModel()->where($where)->field('id,gift_name,is_gameexchange,giftgame_price')->select();
        $List = GiftModel::getInstance()->getModel()->where([['giftgame_price', '=', 0]])->field('id,gift_name')->select()->toArray();
        if ($giftList) {
            $giftList = $giftList->toArray();
            foreach ($giftList as $k => $v) {
                $giftList[$k]['type'] = $giftList[$k]['is_gameexchange'];
                if ($giftList[$k]['is_gameexchange'] == 1) {
                    $giftList[$k]['is_gameexchange'] = '化石';
                } elseif ($giftList[$k]['is_gameexchange'] == 2) {
                    $giftList[$k]['is_gameexchange'] = '金矿石';
                } elseif ($giftList[$k]['is_gameexchange'] == 3) {
                    $giftList[$k]['is_gameexchange'] = '银矿石';
                } elseif ($giftList[$k]['is_gameexchange'] == 4) {
                    $giftList[$k]['is_gameexchange'] = '铁矿石';
                }
            }
        } else {
            $giftList = [];
        }
        return [
            'count' => $count,
            'gift' => $List,
            'data' => $giftList,
        ];
    }

    //矿石兑换礼物编辑
    public function saveProportion($id, $data)
    {
        $Json = [];
        $Json = SiteconfigModel::getInstance()->getModel()->field('game_proportion')->select();
        if ($Json) {
            $Json = $Json->toArray();
        }
        $int = json_decode($Json[0]['game_proportion'], true);
        $int[$id] = $data;
        if ($data != 0 && $data != '') {
            $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->update(['game_proportion' => json_encode($int)]);
            if ($is) {
                echo json_encode(['code' => 200, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 500, 'msg' => '修改失败']);
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);
        }
    }
    //矿石礼物编辑
    public function saveExchange($giftgame_price, $giftname, $giftid, $is_gameexchange)
    {
        $where[] = ['gift_name', '=', $giftname];
        if ($giftid) {
            $data['giftgame_price'] = 0;
            $data['is_gameexchange'] = 0;
            $save_type = GiftModel::getInstance()->getModel()->where($where)->update($data);
            if ($save_type) {
                $add['is_gameexchange'] = $is_gameexchange;
                $add['giftgame_price'] = $giftgame_price;
                $is = GiftModel::getInstance()->getModel()->where('id', $giftid)->update($add);
                if ($is) {
                    echo json_encode(['code' => 200, 'msg' => '修改成功']);
                } else {
                    echo json_encode(['code' => 500, 'msg' => '修改失败']);
                }
            } else {
                echo json_encode(['code' => 500, 'msg' => '修改失败']);
            }
        } elseif ($giftgame_price) {
            $data['giftgame_price'] = $giftgame_price;
            $giftgame_price_type = GiftModel::getInstance()->getModel()->where($where)->update($data);
            if ($giftgame_price_type) {
                echo json_encode(['code' => 200, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 500, 'msg' => '修改失败']);
            }
        } elseif ($is_gameexchange) {
            $data['is_gameexchange'] = $is_gameexchange;
            $is_gameexchange_type = GiftModel::getInstance()->getModel()->where($where)->update($data);
            if ($is_gameexchange_type) {
                echo json_encode(['code' => 200, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 500, 'msg' => '修改失败']);
            }
        }
    }

    //矿石礼物添加
    public function addExchange()
    {
        $count = GiftModel::getInstance()->getModel()->where([['status', '=', 1], ['is_gameexchange', '<>', 0]])->field('id')->count();
        if ($count >= 4) {
            echo json_encode(['code' => 500, 'msg' => '修改失败:礼物位置不可大于4']);
        } else {
            $id = GiftModel::getInstance()->getModel()->where([['status', '=', 1], ['is_gameexchange', '=', 0]])->value('id');
            $is = GiftModel::getInstance()->getModel()->where('id', $id)->update(['is_gameexchange' => 1]);
            if ($is) {
                echo json_encode(['code' => 200, 'msg' => '修改成功']);
            } else {
                echo json_encode(['code' => 500, 'msg' => '修改失败']);
            }
        }
    }
    //删除矿石礼物
    public function delExchange($giftid)
    {
        $save['is_gameexchange'] = 0;
        $save['is_giftganme'] = 0;
        $save['giftgame_price'] = 0;
        $is = GiftModel::getInstance()->getModel()->where('id', $giftid)->update($save);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '刪除成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '刪除失败']);
        }
    }
    //飞行棋奖池管理
    public function gameList()
    {
        $list = GiftGameModel::getInstance()->getModel()->select();
        if ($list) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $Json = SiteconfigModel::getInstance()->getModel()->field('game_proportion')->select();
                if ($Json) {
                    $Json = $Json->toArray();
                } else {
                    $Json = [];
                }
                $int = json_decode($Json[0]['game_proportion'], true);
                $list[$k]['tili'] = $int[$v['id']];
            }
        } else {
            $list = [];
        }
        return $list;
    }
    //飞行棋奖池
    public function gameJson($id)
    {
        $jin = GiftModel::getInstance()->getModel()->where('gift_name', '金矿石')->value('id');
        $yin = GiftModel::getInstance()->getModel()->where('gift_name', '银矿石')->value('id');
        $tie = GiftModel::getInstance()->getModel()->where('gift_name', '铁矿石')->value('id');
        $hua = GiftModel::getInstance()->getModel()->where('gift_name', '化石')->value('id');
        $gameJson = SiteconfigModel::getInstance()->getModel()->field('game_json')->select()->toArray();
        $gameArray = json_decode($gameJson[0]['game_json'], true);
        $gameArray = $gameArray[$id];
        return [
            'data' => $gameArray,
            'id' => $id,
            'jin' => $jin,
            'yin' => $yin,
            'tie' => $tie,
            'hua' => $hua,
        ];
    }

    //飞行棋奖池编辑
    public function saveGame($gameid, $sid, $weight)
    {
        $data = $this->JsonEscape('taojin_conf');
        $diceReward = $data['games'][$gameid - 1]['diceReward'];
        foreach ($diceReward as $k => $v) {
            if ($k + 1 == $sid) {
                $diceReward[$k]['weight'] = (int) $weight;
            }
        }
        $data['games'][$gameid - 1]['diceReward'] = $diceReward;
        $is = ConfigModel::getInstance()->getModel()->where('name', 'taojin_conf')->save(['json' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '编辑成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '编辑失败']);die;
        }
    }
    //敏感词列表
    public function bannedList($array)
    {
        $banned = array_key_exists('banned', $array) ? $array['banned'] : false;
        if ($banned) {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            if ($redis->sIsMember('banned_cache_set', $banned)) {
                $this->register();
                return [$banned];
            } else {
                return [];
            }
        } else {
            return [];
        }
    }
    //敏感词添加
    public function addBanned($greet_message)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $greet_message = strtolower($greet_message);
        if (strpos($greet_message, '、') !== false) {
            $array = explode("、", $greet_message);
            foreach ($array as $k => $v) {
                $is = $redis->sAdd("banned_cache_set", str_replace(" ", '', $v));
            }
        } else {
            $is = $redis->sAdd("banned_cache_set", $greet_message);
        }
        $this->register();

        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //敏感词更新缓存
    public function clearBanned()
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $redis->del('banned_cache_set');
        $this->register();
//        $List = SiteconfigModel::getInstance()->getModel()->field('banned')->select()->toArray();
        //        if(!empty($List[0]['banned'])) {
        //            foreach (json_decode($List[0]['banned'], true) as $k => $v) {
        //                $redis->sAdd("banned_cache_set",$v);
        //            }
        //        }
        echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
    }

    //敏感词删除
    public function delBanned($banned)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $is = $redis->srem('banned_cache_set', $banned);
        $this->register();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);die;
        }
    }

    //图片控制
    public function cpRecommendImageList()
    {
        $list = SiteconfigModel::getInstance()->getModel()->field('cp_recommend_images')->select()->toArray();
        if (empty($list[0]['cp_recommend_images'])) {
            return [];
        } else {
            $list = json_decode($list[0]['cp_recommend_images'], true);
            foreach ($list as $k => $v) {
                foreach ($v as $kk => $vv) {
                    if ($k == 'boy') {
                        if ($kk == 0) {
                            $data[11]['url'] = $vv;
                            $data[11]['sex'] = '男';
                        } else {
                            $data[$kk * 100]['url'] = $vv;
                            $data[$kk * 100]['sex'] = '男';
                        }
                    } else {
                        if ($kk == 0) {
                            $data[12]['url'] = $vv;
                            $data[12]['sex'] = '女';
                        } else {
                            $data[$kk * 10]['url'] = $vv;
                            $data[$kk * 10]['sex'] = '女';
                        }
                    }
                }
            }
            return $data;
        }
    }
    //随机头像添加
    public function addcpRecommendImage($sex, $image)
    {
        $List = SiteconfigModel::getInstance()->getModel()->field('cp_recommend_images')->select()->toArray();
        if (empty($List[0]['cp_recommend_images'])) {
            if ($sex == 1) {
                $info = ["boy" => [config('config.APP_URL_image') . $image], "girl" => []];
            } else {
                $info = ["boy" => [], "girl" => [config('config.APP_URL_image') . $image]];
            }
            $data = json_encode($info);
        } else {
            $list = json_decode($List[0]['cp_recommend_images'], true);
            if ($sex == 1) {
                array_push($list['boy'], config('config.APP_URL_image') . $image);
            } else {
                array_push($list['girl'], config('config.APP_URL_image') . $image);
            }
            $data = json_encode($list);
        }
        $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->update(['cp_recommend_images' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }
    //头像
    public function delcpRecommendImage($image, $sex)
    {
        $List = SiteconfigModel::getInstance()->getModel()->field('cp_recommend_images')->select()->toArray();

        $list = json_decode($List[0]['cp_recommend_images'], true);
        if ($sex == '男') {
            $sex = 'boy';
        } else {
            $sex = 'girl';
        }
        $key = array_search($image, $list[$sex]);
        if (count($list) > 0) {
            if ($key !== false) {
                unset($list[$sex][$key]);
            }
        }

        $data = json_encode($list);
        if ($data == '[]') {
            $data = '';
        }
        $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->update(['cp_recommend_images' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);die;
        }
    }
    // 打招呼信息配置
    public function greetMessageList()
    {
        $list = SiteconfigModel::getInstance()->getModel()->field('greet_message')->select()->toArray();
        if (empty($list[0]['greet_message'])) {
            return [];
        } else {
            return json_decode($list[0]['greet_message']);
        }
    }
    //打招呼添加
    public function addGreetMessage($greet_message)
    {
        $List = SiteconfigModel::getInstance()->getModel()->field('greet_message')->select()->toArray();

        if (empty($List[0]['greet_message'])) {
            $data = json_encode([$greet_message]);
        } else {
            $list = json_decode($List[0]['greet_message']);
            array_push($list, $greet_message);
            $data = json_encode($list);
        }
        $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->update(['greet_message' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }
    //打招呼删除
    public function delGreetMessage($greet_message)
    {
        $List = SiteconfigModel::getInstance()->getModel()->field('greet_message')->select()->toArray();
        $list = json_decode($List[0]['greet_message']);
        $key = array_search($greet_message, $list);
        if (count($list) > 0) {
            unset($list[$key]);
        }
        if ($list == '[]') {
            $data = '';
        } else {
            $data = json_encode(array_merge($list));
        }
        $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->save(['greet_message' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);die;
        }
    }

    //戳一戳的动词配置
    public function pokeWordsList()
    {
        $list = SiteconfigModel::getInstance()->getModel()->field('poke_words')->select()->toArray();
        if (empty($list[0]['poke_words'])) {
            return [];
        } else {
            return json_decode($list[0]['poke_words']);
        }
    }
    //戳一戳添加
    public function addPokeWords($poke_words)
    {
        $data = [];
        $List = SiteconfigModel::getInstance()->getModel()->field('poke_words')->select()->toArray();
        if (!empty($List[0]['poke_words'])) {
            foreach (json_decode($List[0]['poke_words'], true) as $k => $v) {
                $data[$k] = $v;
            }
        }
        if (count($data) <= 0) {
            $data = [$poke_words];
        } else {
            array_push($data, $poke_words);
        }
        $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->update(['poke_words' => json_encode($data)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //戳一戳添加
    public function delPokeWords($poke_words)
    {
        $data = [];
        $List = SiteconfigModel::getInstance()->getModel()->field('poke_words')->select()->toArray();
        if (!empty($List[0]['poke_words'])) {
            foreach (json_decode($List[0]['poke_words'], true) as $k => $v) {
                $data[$k] = $v;
            }
        }
        $key = array_search($poke_words, $data);
        if (count($data) > 0) {
            if ($key !== false) {
                unset($data[$key]);
            }
        }
        $is = SiteconfigModel::getInstance()->getModel()->where('id', 1)->update(['poke_words' => json_encode(array_merge($data))]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);die;
        }
    }

    public function register()
    {
        $app_version = config('config.app_version');
        if (!empty($app_version) && $app_version == 'v2') {
            $channel = config('config.channel');
            $time = time();
            $data = json_encode(['time' => $time, 'sign' => $this->getApiSign($time), 'channel' => $channel]);
            $socket_url = config('config.app_api_url') . 'api/v2/init/register';
            $this->curlRegister($data, $socket_url);
        }
        return true;
    }

    public function changeMemberInfo($id, $status, $token, $operatorid)
    {
        $time = time();
        $data = [
            'time' => time(),
            'sign' => $this->getApiSign($time),
            'mdaId' => $id,
            'status' => $status,
            "token" => $token,
            "operatorId" => $operatorid,
        ];
        $data = json_encode($data);
        $socket_url = config('config.app_api_url') . 'api/v1/memberDetailAudit';
        $res = $this->curlRegister($data, $socket_url);
        return $res;
    }

    public function changeRoomInfo($id, $status, $token, $operatorid)
    {
        $time = time();
        $data = [
            'time' => time(),
            'sign' => $this->getApiSign($time),
            'id' => $id,
            'status' => $status,
            "token" => $token,
            "operatorId" => $operatorid,
        ];
        $data = json_encode($data);
        $socket_url = config('config.app_api_url') . 'api/inner/roomInfoAudit';
        $res = $this->curlRegister($data, $socket_url);
        return $res;
    }

    public function curlRegister($data, $socket_url)
    {
        try {
            $res = curlData($socket_url, $data, 'POST');
            Log::info(sprintf('curlRegister:data====>%s,url====>%s, res====>%s', $data, $socket_url, $res));
            return json_decode($res, true);
        } catch (\Throwable $e) {
            Log::error(sprintf('curlRegister:data====>%s,url====>%s', $data, $socket_url));
            Log::error($socket_url . ":" . $e->getMessage());
        }

    }

    public function getApiSign($time)
    {
        return md5(sprintf("%s%s", 'registerfanqie', $time));
    }

    public function findGiftByName($name)
    {
        $giftList = $this->JsonEscape('gift_conf');
        return array_filter($giftList, function ($item) use ($name) {
            if (mb_strpos($item['name'], $name) !== false) {
                return true;
            } else {
                return false;
            }
        });
    }
}
<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\CommonConst;
use app\admin\model\ConfigModel;
use app\admin\model\SiteconfigModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ConfigService;
use app\admin\service\RoomService;
use app\common\RedisCommon;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ConfigController extends AdminBaseController
{
    public function savePublicScreen()
    {
        echo ConfigService::getInstance()->savePublicScreen(Request::param());
    }

    public function PublicScreen()
    {
        $data = ConfigService::getInstance()->PublicScreen(Request::param());
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/config/bag');
    }

    //权重编辑
    public function saveGiftWeight()
    {
        echo ConfigService::getInstance()->saveGiftWeight(Request::param('weight'), (int)Request::param('giftid'), Request::param('type1'), Request::param('type2'));
    }

    /**
     * @return mixed
     * 初始化展示页面
     */
    public function ChuShiHua()
    {
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/config/ChuShiHua');
    }

    public function configShow()
    {
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/config/configShow');
    }

    /**
     * @return mixed
     * @name 登录任务
     */
    public function dailyConf()
    {
        $type = 'daily_conf';
        $key = Request::param('key');
        $keyId = Request::param('keyId');
        $list = ConfigService::getInstance()->dailyConf($type, $key, $keyId);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/task/dailyConf');
    }

    /**
     * @return mixed
     * @name 充值面板
     */
    public function chargemallConf()
    {
        $type = 'chargemall_conf';
        $key = Request::param('key');
        $list = ConfigService::getInstance()->chargemallConf($type, $key);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/charge/chargemallConf');
    }

    /**
     * @return mixed
     * @name 充值配置
     */
    public function chargeConf()
    {
        $type = 'charge_conf';
        $key = Request::param('key');
        $list = ConfigService::getInstance()->chargeConf($type, $key);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/charge/chargeConf');
    }

    /**
     * @return mixed
     * @name 宝箱配置
     */
    public function boxConf()
    {
        $type = 'box_conf';
        $key = Request::param('key');
        $list = ConfigService::getInstance()->boxConf($type, $key);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/box/boxConf');
    }

    //宝箱保存
    public function boxConfSave()
    {
        echo ConfigService::getInstance()->boxConfSave((int)Request::param('type'), Request::param('count'), Request::param('giftId'), Request::param('weight'));
    }

    /**
     * @return mixed
     * @name 活跃度奖励配置
     */
    public function activeboxConf()
    {
        $type = 'activebox_conf';
        $id = Request::param('id');
        $key = Request::param('key');
        $list = ConfigService::getInstance()->activeboxConf($type, $key, $id);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/task/activeboxConf');
    }

    /**
     * @return mixed
     * @name 等级奖励配置
     */
    public function levelConf()
    {
        $type = 'level_conf';
        $key = Request::param('key');
        $list = ConfigService::getInstance()->levelConf($type, $key);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/level/levelConf');
    }

    /**
     * @return mixed
     * @name 金币抽奖
     */
    public function lotteryConf()
    {
        $type = 'lottery_conf';
        $key = Request::param('key');
        $list = ConfigService::getInstance()->lotteryConf($type, $key);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/task/lotteryConf');
    }

    /**
     * @return mixed
     * @name 新手任务
     */
    public function newerConf()
    {
        $type = 'newer_conf';
        $list = ConfigService::getInstance()->newerConf($type);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/task/newerConf');
    }

    //淘金配置图片
    public function gameConfImg()
    {
        echo ConfigService::getInstance()->gameConfImg(Request::param());
    }

    /**
     * @return mixed
     * @name 淘金详情
     */
    public function taojinContent()
    {
        $type = 'taojin_conf';
        $gameId = Request::param('gameId');
        $classification = Request::param('classification');
        $list = ConfigService::getInstance()->taojinContent($type, $gameId, $classification);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        View::assign('id', $gameId);
        return View::fetch('siteconfig/taojin/taojinContent');
    }

    /**
     * @return mixed
     * @name 淘金配置
     */
    public function taojinConf()
    {
        $type = 'taojin_conf';
        $gameId = Request::param('gameId');
        $list = ConfigService::getInstance()->taojinConf($type, $gameId);

        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        View::assign('time', $list['time']);
        View::assign('gameId', $gameId);
        return View::fetch('siteconfig/taojin/taojinConf');
    }

    //淘金配置编辑
    public function saveTaoJinForm()
    {
        echo ConfigService::getInstance()->saveTaoJinForm(Request::param());
    }

    /**
     * @return mixed
     * @name 会员配置
     */
    public function vipConf()
    {
        $type = 'vip_conf';
        $status = Request::param('status');
        $list = ConfigService::getInstance()->vipConf($type, $status);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        View::assign('status', $status);
        return View::fetch('siteconfig/vip/vipConf');
    }

    /**
     * @return mixed
     * @任务列表配置
     */
    public function weekcheckin()
    {
        $type = 'weekcheckin_conf';
        $list = ConfigService::getInstance()->weekcheckin($type);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/task/weekcheckin');
    }

    /**
     * @return mixed
     * 爵位配置列表
     */
    public function dukeConfig()
    {
        $type = 'duke_conf';
        $list = ConfigService::getInstance()->dukeConfig($type);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/duke/dukeConfig');
    }

    public function dukeConfigAdd()
    {
        print_r(Request::param());
        die;
    }

    /**
     * @return mixed
     * 爵位详情配置列表
     */
    public function dukeDetailsConfig()
    {
        $level = Request::param('id');
        $type = 'duke_conf';
        $prop = 'prop_conf';
        $dukeProp = ConfigService::getInstance()->dukeDetailsConfig($type, $prop, $level);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $dukeProp);
        return View::fetch('siteconfig/duke/dukeDetailsConfig');
    }

    /**
     * @return mixed
     * @name 缓存配置
     */
    public function redisConfig()
    {
        $type = Request::param('type');
        ConfigService::getInstance()->redisConfig($type);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/mall/redisConfig');
    }

    /**
     * @return mixed
     * @表情包面板
     * @dongbozhao
     * @2021-01-08
     */
    public function emoticonPanelsConf()
    {
        $type = 'emoticon_panels_conf';
        $data = ConfigService::getInstance()->JsonEscape($type);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data);
        return View::fetch('siteconfig/emoticon/emoticonPanelsConf');
    }

    /**
     * @return mixed
     * @表情包面板详情
     * @dongbozhao
     * @2021-01-08
     */
    public function emoticonPanelsConfDetails()
    {
        $name = Request::param('id');
        $data = ConfigService::getInstance()->emoticonPanelsConfDetails($name);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['info']);
        View::assign('type', $data['name']);
        View::assign('emoticon', $data['emoticon']);
        return View::fetch('siteconfig/emoticon/emoticonPanelsConfDetails');
    }

    /**
     * @return mixed
     * @表情包面板添加
     * @dongbozhao
     * @2021-01-08
     */
    public function emoticonPanelsConfAdd()
    {
        $gift[] = (int)Request::param('emoticon');
        $type = Request::param('type');
        if (ConfigService::getInstance()->emoticonPanelsConfAdd($gift, $type)) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']); //php编译join
        }
    }

    /**
     * @return mixed
     * @表情包面板删除
     * @dongbozhao
     * @2021-01-08
     */
    public function emoticonPanelsConfDel()
    {
        $id = Request::param('id');
        $type = Request::param('type');
        if (ConfigService::getInstance()->emoticonPanelsConfDel($id, $type)) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']); //php编译join
        }
    }

    /**
     * @return mixed
     * @表情包配置
     * @dongbozhao
     * @2021-01-08
     */
    public function emoticonConf()
    {
        $page = $this->request->param('page');
        $master_page = $this->request->param('page', 1);
        $list = ConfigService::getInstance()->emoticonConf($page, $master_page);
        View::assign('page', $list['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list['list']);
        return View::fetch('siteconfig/emoticon/emoticonConf');
    }

    /**
     * @return mixed
     * @表情包配置添加
     * @dongbozhao
     * @2021-01-08
     */
    public function emoticonConfAdd()
    {
        if (ConfigService::getInstance()->emoticonConfAdd(Request::param('name'), Request::param('image'), Request::param('vipLevel'), Request::param('type'), Request::param('isLock'), Request::param('animation'))) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']); //php编译join
        }
    }

    /**
     * @return mixed
     * @表情包配置编辑
     * @dongbozhao
     * @2021-01-08
     */
    public function emoticonConfSave()
    {
        if (ConfigService::getInstance()->emoticonConfSave(
            Request::param('id'),
            Request::param('image'),
            Request::param('animation'),
            Request::param('type'),
            Request::param('name'),
            Request::param('vipLevel'),
            Request::param('isLock')
        )) {
            echo json_encode(['code' => 200, 'msg' => '编辑成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '编辑失败']); //php编译join
        }
    }

    /**
     * @return mixed
     * @表情包配置删除
     * @dongbozhao
     * @2021-01-08
     */
    public function emoticonConfDel()
    {
        echo ConfigService::getInstance()->emoticonConfDel(Request::param('id'));
    }

    /**
     * @return mixed
     * @装扮配置
     * @dongbozhao
     * @2021-01-08
     */
    public function propConf()
    {
        $data = ConfigService::getInstance()->propConf($this->request->param('page'));
        $asset_types = CommonConst::USER_ASSET_MAP;
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['list']);
        View::assign('asset_types', $asset_types);
        return View::fetch('siteconfig/attire/propConf');
    }

    /**
     * @return mixed
     * @装扮配置添加
     * @dongbozhao
     * @2021-01-08
     */
    public function propConfAdd()
    {
        echo ConfigService::getInstance()->propConfAdd(
            Request::param('unit'),
            Request::param('type'),
            Request::param('name'),
            Request::param('desc'),
            Request::param('image'),
            Request::param('imageAndroid'),
            Request::param('animation'),
            (int)Request::param('showInBag'),
            (int)Request::param('multiple'),
            Request::param('bubbleWordImage'),
            Request::param('color'),
            (int)Request::param('removeFormBagWhenDied'),

            (int)Request::param('is_use', 0),
            Request::param('use_name', ''),
            Request::param('use_asset_nums', []),
            Request::param('use_asset_types', []),

            (int)Request::param('is_breakup', 0),
            Request::param('breakup_name', ''),
            Request::param('breakup_asset_nums', []),
            Request::param('breakup_asset_types', []),
            (int)Request::param('weight', 0),
            Request::param('textColor', ''),
        );

    }

    /**
     * @return mixed
     * @装扮配置删除
     * @dongbozhao
     * @2021-01-08
     */
    public function propConfDel()
    {
        echo ConfigService::getInstance()->propConfDel(Request::param('id'));
    }

    /**
     * @return mixed
     * @装扮配置编辑
     * @dongbozhao
     * @2021-01-08
     */
    public function propConfSave()
    {
        echo ConfigService::getInstance()->propConfSave(
            Request::param('id'),
            Request::param('image'),
            Request::param('imageAndroid'),
            Request::param('animation'),
            Request::param('unit'),
            Request::param('type'),
            Request::param('name'),
            Request::param('desc'),
            (int)Request::param('showInBag'),
            Request::param('multiple'),
            Request::param('bubbleWordImage'),
            Request::param('color'),
            (int)Request::param('removeFormBagWhenDied'),
            //是否可使用
            (int)Request::param('is_use', 0),
            Request::param('use_name', ''),
            Request::param('use_asset_nums', []),
            Request::param('use_asset_types', []),
            //是否可分解
            (int)Request::param('is_breakup', 0),
            Request::param('breakup_name', ''),
            Request::param('breakup_asset_nums', []),
            Request::param('breakup_asset_types', []),
            (int)Request::param('weight', 0),
            Request::param('textColor', '')
        );
    }

    /**
     * @return mixed
     * @礼物面板
     * @dongbozhao
     * @2021-01-11
     */
    public function giftWall()
    {
        $type = 'gift_wall';
        $data = ConfigService::getInstance()->JsonEscape($type);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['walls']);
        return View::fetch('siteconfig/gift/giftWall');
    }

    /**
     * @return mixed
     * @礼物面板类型
     * @dongbozhao
     * @2021-01-11
     */
    public function giftWallDetails()
    {
        $data = ConfigService::getInstance()->giftWallDetails(Request::param('id'));
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['info']);
        View::assign('gift', $data['gift']);
        View::assign('type', $data['type']);
        return View::fetch('siteconfig/gift/giftWallDetails');
    }

    /**
     * @return mixed
     * @添加礼物面板礼物
     * @dongbozhao
     * @2021-01-11
     */
    public function giftWallAdd()
    {
        echo ConfigService::getInstance()->giftWallAdd(
            (int)Request::param('gift'),
            Request::param('type')
        );
    }

    /**
     * @return mixed
     * @删除商城礼物
     * @dongbozhao
     * @2021-01-11
     */
    public function giftWallDel()
    {
        echo ConfigService::getInstance()->giftWallDel(
            Request::param('id'),
            Request::param('type')
        );
    }

    //礼物商城第一层
    public function giftPanelsTheFirst()
    {
        $data = ConfigService::getInstance()->giftPanelsTheFirst();
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data);
        return View::fetch('siteconfig/gift/giftPanelsTheFirst');
    }

    /**
     * @return mixed
     * @礼物商城展示
     * @dongbozhao
     * @2021-01-11
     */
    public function giftPanels()
    {
        $id = Request::param('id');
        $data = ConfigService::getInstance()->giftPanels($id);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data);
        View::assign('id', $id);
        return View::fetch('siteconfig/gift/giftPanels');
    }

    public function giftClassificationAdd()
    {
        echo ConfigService::getInstance()->giftClassificationAdd(
            Request::param('name'),
            Request::param('displayName'),
            Request::param('type1')
        );
    }

    /**
     * @return mixed
     * @礼物商城类型
     * @dongbozhao
     * @2021-01-11
     */
    public function giftPanelsDetails()
    {
        $id = Request::param('id');
        $type1 = Request::param('type1');
        $data = ConfigService::getInstance()->giftPanelsDetails($id, $type1);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['info']);
        View::assign('gift', $data['gift']);
        View::assign('type1', $data['type1']);
        View::assign('type2', $data['type2']);
        return View::fetch('siteconfig/gift/giftPanelsDetails');
    }

    /**
     * @return mixed
     * @礼物商城礼物添加
     * @dongbozhao
     * @2021-01-11
     */
    public function giftPanelsAdd()
    {
        echo ConfigService::getInstance()->giftPanelsAdd(
            (int)Request::param('gift'),
            Request::param('type1'),
            Request::param('type2')
        );
    }

    /**
     * @return mixed
     * @删除商城礼物
     * @dongbozhao
     * @2021-01-11
     */
    public function giftPanelsDel()
    {
        echo ConfigService::getInstance()->giftPanelsDel(
            Request::param('id'),
            Request::param('type1'),
            Request::param('type2')
        );
    }

    /**
     * @return mixed
     * @礼物配置添加
     * @dongbozhao
     * @2021-01-08
     */
    public function giftConfAdd()
    {
        echo ConfigService::getInstance()->giftConfAdd(Request::param());
    }

    /**
     * @return mixed
     * @礼物配置
     * @dongbozhao
     * @2021-01-08
     */
    public function giftConf()
    {
        $page = Request::param('page', 1);
        $giftId = Request::param('giftId', '');
        $giftType = Request::param('giftType', 0);

        $data = ConfigService::getInstance()->giftConf($page, $giftId, $giftType);
        View::assign('page', $data['page']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['list']);
        View::assign('duke', $data['duke']);
        View::assign('giftId', $giftId);
        View::assign('giftType', $giftType);
        return View::fetch('siteconfig/gift/giftConf');
    }

    //删除礼物
    public function giftConfDel()
    {
        echo ConfigService::getInstance()->giftConfDel(Request::param('id'));
    }

    //礼物编辑
    public function giftConfSave()
    {
        echo ConfigService::getInstance()->giftConfSave(Request::param());
    }

    public function giftConfImg()
    {
        echo ConfigService::getInstance()->giftConfImg(Request::param());
    }

    /**
     * @return mixed
     * @商城配置第一层
     * @dongbozhao
     * @2021-01-08
     */
    public function mallConf()
    {
        $list = ConfigService::getInstance()->mallConf();
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        return View::fetch('siteconfig/mall/mallConf');
    }

    /*
     * @return mixed
     * @商城配置第一层添加
     * @dongbozhao
     * @2021-01-08
     */
    public function mallConfAdd()
    {
        echo ConfigService::getInstance()->mallConfAdd(Request::param('name'));
    }

    /**
     * @return mixed
     * @商城配置第二层
     * @dongbozhao
     * @2021-01-08
     */
    public function mallconfDetails()
    {
        $id = Request::param('id');
        $list = ConfigService::getInstance()->mallconfDetails($id);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list);
        View::assign('currency', $id);
        return View::fetch('siteconfig/mall/mallconfDetails');
    }

    /**
     * @return mixed
     * @商城配置第二层添加
     * @dongbozhao
     * @2021-01-08
     */
    public function mallconfDetailsAdd()
    {
        echo ConfigService::getInstance()->mallconfDetailsAdd(Request::param('type'), Request::param('displayName'), Request::param('currency'));
    }

    /**
     * @return mixed
     * @商城商品展示
     * @dongbozhao
     * @2021-01-08
     */
    public function mallconfAreas()
    {
        $data = ConfigService::getInstance()->mallconfAreas(Request::param('id'), Request::param('currency'));
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['list']);
        View::assign('goods', $data['goods']);
        View::assign('goods_map', $data['goods_map']);
        View::assign('shelves_desc', $data['shelves_desc']);
        View::assign('id', $data['id']);
        View::assign('currency', $data['currency']);
        return View::fetch('siteconfig/mall/mallconfAreas');
    }

    /**
     * @return mixed
     * @商城商品展示
     * @dongbozhao
     * @2021-01-08
     */
    public function delMallGoods()
    {
        echo ConfigService::getInstance()->delMallGoods(Request::param('id'), Request::param('currency'), Request::param('goodsId'), Request::param('displayTypeName'));
    }

    /**
     * @return mixed
     * @商城商品添加
     * @dongbozhao
     * @2021-01-08
     */
    public function mallAddGoods()
    {
        $params['goodsId'] = Request::param('goodsId', 0);
        $params['displayTypeName'] = Request::param('displayTypeName', '');
        $params['currency'] = Request::param('currency', '');
        $params['id'] = Request::param('id', '');
        $params['type'] = Request::param('type', '');
        echo ConfigService::getInstance()->mallAddGoods($params);
    }

    /**
     * @dongbozhao
     * @2021-01-04 19:00
     * @商品配置
     */
    public function goodsConf()
    {
        $data = ConfigService::getInstance()->goodsConf(Request::param('page', 1));
        View::assign('page', $data['page']);
        View::assign('prop', $data['prop']);
        View::assign('gift', $data['gift']);
        View::assign('props_map', $data['props_map']);
        View::assign('assets_map', CommonConst::USER_ASSET_MAP);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['list']);
        return View::fetch('siteconfig/attire/goodsConf');
    }

    /**
     * @return mixed
     * @商品上下架切换
     * @dongbozhao
     * @2021-01-05 11:30
     */
    public function goodsConfSave()
    {
        echo ConfigService::getInstance()->goodsConfSave(Request::param());
    }

    /**
     * @return mixed
     * @添加商品
     * @dongbozhao
     * @2021-01-06 14:00
     */
    public function goodsAdd()
    {
        echo ConfigService::getInstance()->goodsAdd(Request::param());
    }

    /**
     * @return mixed
     * @商品详情
     * @dongbozhao
     * @2021-01-06 18:00
     */
    public function goodsConfDetails()
    {
        $data = ConfigService::getInstance()->goodsConfDetails(Request::param('id'));
        View::assign('page', $data['page_array']);
        View::assign('goodsId', $data['id']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data['list']);
        View::assign('asset_map', CommonConst::USER_ASSET_MAP);
        return View::fetch('siteconfig/attire/goodsConfDetails');
    }

    /**
     * @name 商品详情编辑
     * @return mixed
     */
    public function goodsDetailsSave()
    {
        echo ConfigService::getInstance()->goodsDetailsSave(Request::param('number'), Request::param('price'), Request::param('type'), Request::param('goodsId'));
    }

    //礼物面板
    public function giftWallStatus()
    {
        $giftWallStatus = SiteconfigModel::getInstance()->getModel()->value('gift_wall_status');
        View::assign('data', $giftWallStatus);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/gift/giftWallStatus');
    }

    //礼物面板编辑
    public function giftWallStatusSave()
    {
        echo ConfigService::getInstance()->giftWallStatusSave(Request::param('id'));
    }

    //礼物奖池比例
    public function gameProportion()
    {
        $data = ConfigService::getInstance()->gameProportion(Request::param('id'));
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('data', $data['id2']);
        View::assign('gameid', $data['id']);
        return View::fetch('siteconfig/game/gameProportion');
    }

    //矿石兑换礼物编辑
    public function saveProportion()
    {
        echo ConfigService::getInstance()->saveProportion(Request::param('id'), Request::param('data'));
    }

    /**
     * @return mixed
     * 矿石礼物
     */
    public function Exchange()
    {
        $data = ConfigService::getInstance()->Exchange();
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('count', $data['count']);
        View::assign('gift', $data['gift']);
        View::assign('data', $data['data']);
        return View::fetch('siteconfig/Exchange');
    }

    //矿石礼物编辑
    public function saveExchange()
    {
        echo ConfigService::getInstance()->saveExchange(Request::param('giftgame_price'), Request::param('giftname'), Request::param('giftid'), Request::param('is_gameexchange'));
    }

    //矿石礼物添加
    public function addExchange()
    {
        echo ConfigService::getInstance()->addExchange();
    }

    //删除矿石礼物
    public function delExchange()
    {
        echo ConfigService::getInstance()->delExchange(Request::param('giftid'));
    }

    /**
     * @return mixed
     * 飞行棋奖池管理
     */
    public function gameList()
    {
        $list = ConfigService::getInstance()->gameList();
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('data', $list);
        return View::fetch('siteconfig/game/gamelist');
    }

    //飞行棋奖池
    public function gameJson()
    {
        $data = ConfigService::getInstance()->gameJson(Request::param('id'));
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('data', $data['data']);
        View::assign('id', $data['id']);
        View::assign('jin', $data['jin']);
        View::assign('yin', $data['yin']);
        View::assign('tie', $data['tie']);
        View::assign('hua', $data['hua']);
        return View::fetch('siteconfig/game/gameArray');
    }

    //飞行棋奖池编辑
    public function saveGame()
    {
        echo ConfigService::getInstance()->saveGame(Request::param('gameid'), Request::param('sid'), Request::param('weight'));
//        echo ConfigService::getInstance()->saveGame(Request::param('gameid'), Request::param('sid'), Request::param('weight'), Request::param('giftnum'), Request::param('giftid'));
    }

    public function clearGame()
    {
        $gameid = Request::param('gameid');
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $is = $redis->set('taojin_conf', ConfigModel::getInstance()->getModel()->where('name', 'taojin_conf')->value('json'));
        //通知客户端更新资源
        ConfigService::getInstance()->register();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    /**
     * @return mixed
     * 敏感词管理
     */
    public function bannedList()
    {
        $list = ConfigService::getInstance()->bannedList(Request::param());
        View::assign('data', $list);
        View::assign('banned', Request::param('banned'));
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/banned');
    }

    //敏感词添加
    public function addBanned()
    {
        echo ConfigService::getInstance()->addBanned(Request::param('banned'));
    }

    //敏感词删除
    public function delBanned()
    {
        echo ConfigService::getInstance()->delBanned(Request::param('banned'));
    }

    public function clearBanned()
    {
        echo ConfigService::getInstance()->clearBanned();
    }

    /**
     * @return mixed
     * 图片控制
     */
    public function cpRecommendImageList()
    {
        $data = ConfigService::getInstance()->cpRecommendImageList();
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/cprecommendimage');
    }

    //随机头像添加
    public function addcpRecommendImage()
    {
        echo ConfigService::getInstance()->addcpRecommendImage(Request::param('sex'), Request::param('image'));
    }

    //头像
    public function delcpRecommendImage()
    {
        echo ConfigService::getInstance()->delcpRecommendImage(Request::param('greet_message'), Request::param('sex'));
    }

    public function clearCacheCpRecommendImage()
    {
        $redis = $this->getRedis();
        $is = $redis->del("cp_recommend_image_list");
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    /**
     * @return mixed
     * 打招呼信息配置
     */
    public function greetMessageList()
    {
        $list = ConfigService::getInstance()->greetMessageList();
        View::assign('data', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/greetMessage');
    }

    //打招呼添加
    public function addGreetMessage()
    {
        echo ConfigService::getInstance()->addGreetMessage(Request::param('greet_message'));
    }

    //打招呼删除
    public function delGreetMessage()
    {
        echo ConfigService::getInstance()->delGreetMessage(Request::param('greet_message'));
    }

    public function clearCacheGreetMessage()
    {
        $redis = $this->getRedis();
        $is = $redis->del("greetmessage_cache");
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    /**
     * @return mixed
     * 戳一戳的动词配置
     */
    public function pokeWordsList()
    {
        $data = ConfigService::getInstance()->pokeWordsList();
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/pokeWordsList');
    }

    //戳一戳添加
    public function addPokeWords()
    {
        echo ConfigService::getInstance()->addPokeWords(Request::param('poke_words'));
    }

    //戳一戳添加
    public function delPokeWords()
    {
        echo ConfigService::getInstance()->delPokeWords(Request::param('poke_words'));
    }

    public function clearCachePokeWords()
    {
        $redis = $this->getRedis();
        $is = $redis->del("pokewords_cache");
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    //房间最高在线人数配置
    public function roomTopOnline()
    {
        $gifts = GiftsCommon::getInstance()->getGifts();
        $count = RoomService::getInstance()->roomTopOnline();
        View::assign('count', $count);
        View::assign('gifts', $gifts);
        View::assign('token', $this->request->param('token'));
        return View::fetch('config/room/roomTopOnline');
    }

    //房间照片墙配置保存
    public function roomTopOnlineSave()
    {
        $count = Request::param('count');
        if ($count <= 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误']);
            die;
        }
        echo RoomService::getInstance()->roomTopOnlineSave($count);
    }


    //悄悄话配置列表
    public function whisperList()
    {
        $cachekey = "speakwhisperconf";
        $cache = RedisCommon::getInstance()->getRedis();
        $data = $cache->get($cachekey);
        $list = json_decode($data, true);
        View::assign('data', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/config/qiaoqiaohua');
    }

    //悄悄话配置新增或者删除
    public function whisperHandle()
    {
       try{
           $action = $this->request->param('action', 'trim');
           $word = $this->request->param('word', 'trim');
           $cachekey = "speakwhisperconf";
           $cache = RedisCommon::getInstance()->getRedis();
           $data = $cache->get($cachekey);
           $dataList = json_decode($data, true);
           $dataList = $dataList ?: [];
           if ($action == 'del') {
               $index = array_search($word, $dataList);
               if ($index !== false) {
                   unset($dataList[$index]);
               }
               $cache->set($cachekey, json_encode(array_values($dataList)));
               Log::INFO("whisperhandle:del:".$word.":admin_id:".$this->token['id']??0);
           }
           if ($action == 'add') {
               $index = array_search($word, $dataList);
               if ($index === false) {
                   array_push($dataList, $word);
               }
               $cache->set($cachekey, json_encode(array_values($dataList)));
               Log::INFO("whisperhandle:add:".$word.":admin_id:".$this->token['id']??0);
           }
           echo json_encode(['code' => 200, 'msg' => '操作成功']);
           die;

       }catch (\Throwable $e){
           Log::error("whisperhandle:error".$e->getMessage().$e->getLine().$e->getFile());
       }
    }






}

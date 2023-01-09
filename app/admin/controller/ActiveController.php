<?php

namespace app\admin\controller;

ini_set('memory_limit', '1024M');
use app\admin\common\AdminBaseController;
use app\admin\model\ActiveModel;
use app\admin\model\AttireModel;
use app\admin\model\GiftModel;
use app\admin\model\GoldCoinModel;
use app\admin\model\GoldModel;
use app\admin\model\TaskModel;
use app\admin\model\TreasurePoolModel;
use app\admin\model\WeeksConfigModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ActivityService;
use app\admin\validate\Gift;
use app\common\RedisCommon;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ActiveController extends AdminBaseController
{
    public static $weeksType = ['万千宠爱' => 1, '君临天下' => 2, '荣誉星耀' => 3];

    /**
     * 周星活动
     */
    public function weeksActive()
    {
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('type', '周星');
        return View::fetch('active/command');
    }

    /**
     * 添加 - 万千宠爱奖励
     */
    public function addWeeksConfig()
    {
        $data = [
            'type' => self::$weeksType['万千宠爱'],
            'gift_avatar' => Request::param('gift_avatar'),
            'avatar_name' => Request::param('avatar_name'),
            'avatar_details' => Request::param('avatar_details'),
            'gift_url' => Request::param('gift_url'),
            'named_url' => Request::param('named_url'),
            'gift_name' => Request::param('gift_name'),
            'gift_details' => Request::param('gift_details'),
            'named_name' => Request::param('named_name'),
            'named_details' => Request::param('named_details'),
        ];
        $is = WeeksConfigModel::getInstance()->insertWeeksConfig($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //编辑 - 万千宠爱奖励
    public function saveWeeksConfig()
    {
        $where = ['id' => Request::param('gid')];
        $data = Request::param();
        unset($data['token']);
        unset($data['master_url']);
        unset($data['gid']);
        foreach ($data as $k => $v) {
            if (empty($v) && $v != 0) {
                unset($data[$k]);
            }
        }
        $is = WeeksConfigModel::getInstance()->saveWeeksConfig($where, $data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    /**
     * 周榜 - 万千宠爱奖励
     */
    public function weeksCharmConf()
    {
        $data = ActivityService::getInstance()->weeksCharmConf(Request::param('page'), $this->token['username']);
        View::assign('page', $data['page_array']);
        View::assign('data', $data['data']);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('active/weeks/charm');
    }

    /**
     * 周榜 - 君临天下奖励
     */
    public function weeksWealthConf()
    {
        $data = ActivityService::getInstance()->weeksWealthConf(Request::param('page'), $this->token['username']);
        View::assign('page', $data['page_array']);
        View::assign('data', $data['data']);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('active/weeks/wealth');
    }

    /**
     * 周榜 - 月榜配置
     */
    public function weeksMonthConf()
    {
        $data = ActivityService::getInstance()->weeksMonthConf(Request::param('page'), $this->token['username']);
        View::assign('page', $data['page_array']);
        View::assign('data', $data['data']);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('active/weeks/month');
    }

    /**
     * 周星礼物配置
     */
    public function weeksGiftConf()
    {
        $data = ActivityService::getInstance()->weeksGiftConf(Request::param('page'), $this->token['username']);
        View::assign('page', $data['page_array']);
        View::assign('data', $data['data']);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('active/weeks/gift');
    }

    /**
     * 用户召回
     */
    public function returnUserActivityConfig()
    {
        $data = ActivityService::getInstance()->returnUserActivityConfig();
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data);
        return View::fetch('active/return/index');
    }

    public function returnSave()
    {
        echo ActivityService::getInstance()->returnSave(Request::param('data'));
    }

    /**
     * 福星降临 瓜分番茄豆
     * dongbozhao
     * 2021-03-03
     * start_time :开始时间 （2021-02-26 00:00:00）
     * end_time:结束时间   （2021-02-28 23:59:59 ）
     * init_pool_value ：奖池初始值（3000000）是实际值的100倍
     * rate ：奖池累积比例 （1） 百分之1
     * pool_prefix ：luck_star_pool_（常量）
     * first_partition_time：第一次瓜分的时间
     * last_partition_time：最后一次瓜分的时间
     * partition_date：活动日期（[20210226,20210227,20210228]）
     * partition_rank_cache_prefix：partition_rank_cache_（常量）
     * partition_rank_prefix：luck_star_partition_rank_（常量）
     * real_rate：100（常量）
     */
    public function shavePoints()
    {
        $res = ActivityService::getInstance()->shavePoints();
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $res);
        return View::fetch('active/shavePoints/index');
    }
    public function shavePointsSave()
    {
        $keys = 'luck_star_config';
        $start_time = Request::param('start_time');
        $end_time = Request::param('end_time');
        $init_pool_value = Request::param('init_pool_value') * 100;
        $rate = Request::param('rate');
        $partition_time = Request::param('partition_time');
        $partition_rate = Request::param('partition_rate');

        if (!$start_time || !$end_time || !$init_pool_value || !$rate) {
            echo json_encode(['code' => 500, 'msg' => '参数不可为空']);die;
        }

        /*** partition_date ***/
        $partition_date = ActivityService::getInstance()->getDateFromRange($start_time, $end_time);
        foreach ($partition_date as $key => $date) {$partition_date[$key] = date('Ymd', strtotime($date));}
        $partition_date = '[' . implode(',', $partition_date) . ']';

        /*** partition_time ***/
        foreach ($partition_time as $k => $v) {
            if (!empty($v)) {
                $partition_time[$k] = date('Y-m-d 00:00', strtotime($v));
            } else {
                unset($partition_time[$k]);
            }
        }
        $first_partition_time = $partition_time[0];
        $last_partition_time = $partition_time[count($partition_time) - 1];
        $partition_time = '["' . implode('","', $partition_time) . '"]';
        $partition_rate = json_encode($partition_rate);
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->hSet($keys, 'start_time', $start_time);
        $redis->hSet($keys, 'first_partition_time', date('Y-m-d 00:00:00', strtotime($first_partition_time)));
        $redis->hSet($keys, 'last_partition_time', date('Y-m-d 00:00:00', strtotime($last_partition_time)));
        $redis->hSet($keys, 'end_time', $end_time);
        $redis->hSet($keys, 'init_pool_value', $init_pool_value);
        $redis->hSet($keys, 'rate', $rate);
        $redis->hSet($keys, 'partition_time', $partition_time);
        $redis->hSet($keys, 'partition_rate', $partition_rate);
        $redis->hSet($keys, 'partition_date', $partition_date);
        $redis->hSet($keys, 'pool_prefix', 'luck_star_pool_');
        $redis->hSet($keys, 'real_rate', 100);
        $redis->hSet($keys, 'partition_rank_prefix', 'luck_star_partition_rank_');
        $redis->hSet($keys, 'partition_rank_cache_prefix', 'partition_rank_cache_');
        echo json_encode(['code' => 200, 'msg' => '编辑成功']);die;
    }

    /**
     * 三人夺宝产出
     * dongbozhao
     */
    public function treasurePoolList()
    {
        $pagenum = 20;
        $type = Request::param('type', 1);
        $uid = Request::param('uid');
        $demo = Request::param('demo', $this->default_date);
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $pool_type = $type == 1 ? 'min' : ($type == 2 ? 'mid' : 'max');
        $res = ActivityService::getInstance()->getTreasurePooWinner($page, $pagenum, $pool_type, $uid, $demo);
        $page_array = [];
        $page_array['page'] = $master_page;
        $pageCount = $res['maxId'];
        $page_array['total_page'] = ceil($pageCount / $pagenum);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $res['data']);
        View::assign('uidZhuan', $res['uidZhuan']);
        View::assign('douZhuan', $res['douZhuan']);
        View::assign('uidTui', $res['uidTui']);
        View::assign('douTui', $res['douTui']);
        View::assign('coinGift', $res['coinGift']);
        View::assign('type', $type);
        View::assign('uid', $uid);
        View::assign('demo', $demo);
        return View::fetch('active/treasurePool/treasurePoolList');
    }

    /**
     * @return mixed
     * 三人夺宝列表
     * dongbozhao
     */
    public function treasurePool()
    {
        $data = TreasurePoolModel::getInstance()->getModel()->select()->toArray();
        foreach ($data as $k => $v) {
            if ($v['type'] == 1) {
                $data[$k]['type'] = '小奖池';
            } elseif ($v['type'] == 2) {
                $data[$k]['type'] = '中奖池';
            } else {
                $data[$k]['type'] = '大奖池';
            }
            if ($v['status'] == 1) {
                $data[$k]['status'] = '活动开启';
            } else {
                $data[$k]['status'] = '活动结束';
            }
            $data[$k]['createTime'] = date('Y-m-d H:i:s', $v['createTime']);
            $data[$k]['updateTime'] = date('Y-m-d H:i:s', $v['updateTime']);
        }
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $data);
        return View::fetch('active/treasurePool/index');
    }

    /**
     * @return mixed
     * 三人夺宝奖池列表
     * dongbozhao
     */
    public function treasurePoolDetails()
    {
        $id = Request::param('id');
        $data = TreasurePoolModel::getInstance()->getModel()->where('id', $id)->value('pool_info');
        $giftList = GiftsCommon::getInstance()->getGiftMap();
        foreach ($giftList as $k => $v) {
            $giftList[$k]['gift_image'] = config('config.APP_URL_image') . $v['gift_image'];
        }
        $gifts_map = array_column($giftList, null, 'id');
        if ($data) {
            $gift = json_decode($data, true);
            foreach ($gift as $k => $v) {
                $gift[$k]['gift_image'] = config('config.APP_URL_image') . $v['gift_image'];
                if (!isset($v['gift_name'])) {
                    $gift[$k]['gift_name'] = $gifts_map[(int) $v['gift_id']]['gift_name'];
                    $gift[$k]['gift_coin'] = $gifts_map[(int) $v['gift_id']]['gift_coin'];
                    $gift[$k]['gift_image'] = $gifts_map[(int) $v['gift_id']]['gift_image'];
                }
            }
        }

        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('type', $id);
        View::assign('list', $gift);
        View::assign('gift', $giftList);
        return View::fetch('active/treasurePool/details');
    }

    /**
     * 三人夺宝奖品添加
     * dongbozhao
     */
    public function treasurePoolDetailsAdd()
    {
        $pay_price = Request::param('pay_price');
        $gift = Request::param('gift');
        $type = Request::param('type');

        $giftList = GiftsCommon::getInstance()->getGiftMap();
        $gifts_map = array_column($giftList, null, 'id');

        $data1['gift_id'] = $gift;
        $data1['pay_price'] = $pay_price;
        $data1['gift_name'] = $gifts_map[$gift]['gift_name'];
        $data1['gift_coin'] = $gifts_map[$gift]['gift_coin'];
        $data1['gift_image'] = $gifts_map[$gift]['gift_image'];
        $data = TreasurePoolModel::getInstance()->getModel()->where('id', $type)->value('pool_info');
        if ($data) {
            $gift = json_decode($data, true);
            $gift[count($gift)] = $data1;
        } else {
            $gift = [$data1];
        }
        $is = TreasurePoolModel::getInstance()->getModel()->where('id', $type)->save(['pool_info' => json_encode($gift)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']); //php编译join
        }
    }

    /**
     * 三人夺宝删除奖励
     */
    public function treasurePoolDetailsDel()
    {
        $gift = Request::param('gift');
        $type = Request::param('type');
        $data = json_decode(TreasurePoolModel::getInstance()->getModel()->where('id', $type)->value('pool_info'), true);
        foreach ($data as $k => $v) {
            if ($v['gift_id'] != $gift) {
                $giftnew[$k]['gift_id'] = $v['gift_id'];
                $giftnew[$k]['pay_price'] = $v['pay_price'];
                $giftnew[$k]['gift_name'] = $v['gift_name'];
                $giftnew[$k]['gift_coin'] = $v['gift_coin'];
                $giftnew[$k]['gift_image'] = $v['gift_image'];
            }
        }

        //删除逻辑代码
        $is = TreasurePoolModel::getInstance()->getModel()->where('id', $type)->save(['pool_info' => json_encode(array_merge($giftnew))]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']); //php编译join
        }
    }

    public function goldCoinBox()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = empty(Request::param('uid')) ? '' : Request::param('uid');
        $where = [];
        if (!empty($uid)) {
            $where[] = ['uid', '=', $uid];
        }
        $count = GoldCoinModel::getInstance()->getModel()->where($where)->count();
        $data = GoldCoinModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->order('id desc')->select()->toArray();
        $url = config('config.APP_URL_image');
        foreach ($data as $k => $v) {
            $data[$k]['create_time'] = date('Y-m-d H:i:s');
            if ($v['reward_type'] == 1) {
                $att = AttireModel::getInstance()->getModel()->where('id', $v['reward_id'])->select()->toArray();
                if (count($att) > 0) {
                    $data[$k]['img'] = $url . $att[0]['attire_image'];
                    $data[$k]['name'] = $att[0]['attire_name'];
                    $data[$k]['num'] = $att[0]['goldbox_time'] . '天';
                    $data[$k]['reward_type'] = '装扮';
                }
            } elseif ($v['reward_type'] == 2) {
                $data[$k]['img'] = $url . 'gold.png';
                $data[$k]['name'] = '金币';
                $data[$k]['num'] = $data[$k]['reward_desc'] . '个';
                $data[$k]['reward_type'] = '金币';
            } elseif ($v['reward_type'] == 3) {
                $gift = GiftModel::getInstance()->getModel()->where('id', $v['reward_id'])->select()->toArray();
                if (count($gift) > 0) {
                    $data[$k]['img'] = $url . $gift[0]['gift_image'];
                    $data[$k]['name'] = $gift[0]['gift_name'];
                    $data[$k]['num'] = $gift[0]['goldbox_num'] . '个';
                    $data[$k]['reward_type'] = '礼物';
                }
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('uid', $uid);
        return View::fetch('active/goldCoinBox');
    }

    /**
     * @return mixed
     * 金币列表
     */
    public function listGold()
    {
        $data = [];
        //查询金币
        $data = GoldModel::getInstance()->getModel()->where(['gold_type' => 2])->select()->toArray();
        $admin_url = config('config.admin_url');
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('active/listGold');
    }

    /**
     * 金币添加
     */
    public function addlistGold()
    {
        $gold_num = empty(Request::param('gold_num')) ? 0 : Request::param('gold_num');
        $goldbox_weight = empty(Request::param('goldbox_weight')) ? 0 : Request::param('goldbox_weight');
        $goldbox_order = empty(Request::param('goldbox_order')) ? 0 : Request::param('goldbox_order');
        $gold_type = 2;
        if ($gold_num == 0 || $goldbox_weight == 0 || $goldbox_order == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $GoldCount = GoldModel::getInstance()->getModel()->where(['gold_type' => 2])->select()->count();
        $AttireCount = AttireModel::getInstance()->getModel()->where(['is_goldbox' => 1])->select()->count();
        $GiftCount = GiftModel::getInstance()->getModel()->where(['is_goldbox' => 1])->select()->count();
        if (($GoldCount + $AttireCount + $GiftCount) == 8) {
            echo json_encode(['code' => 500, 'msg' => '奖品大于8条，请删除其他奖励后操作']);
            die;
        }
        $data = ['gold_num' => $gold_num, 'goldbox_weight' => $goldbox_weight, 'gold_type' => $gold_type, 'goldbox_order' => $goldbox_order];
        $is = GoldModel::getInstance()->getModel()->insert($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']);
            die;
        }
    }

    /**
     * 金币删除
     */
    public function dellistGold()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        if ($id == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $is = GoldModel::getInstance()->getModel()->where('id', $id)->delete();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
            die;
        }
    }

    /**
     * 金币修改
     */
    public function updlistGold()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $gold_num = empty(Request::param('gold_num')) ? 0 : Request::param('gold_num');
        $goldbox_weight = empty(Request::param('goldbox_weight')) ? 0 : Request::param('goldbox_weight');
        $goldbox_order = empty(Request::param('goldbox_order')) ? 0 : Request::param('goldbox_order');
        $gold_type = 2;
        if ($id == 0 || $gold_num == 0 || $goldbox_weight == 0 || $goldbox_order == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $data = ['gold_num' => $gold_num, 'goldbox_weight' => $goldbox_weight, 'goldbox_order' => $goldbox_order];
        $is = GoldModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);
            die;
        }
    }

    /**
     * @return mixed
     * 金币抽奖奖励装扮
     */
    public function listGoldAttire()
    {
        $data = [];
        $data = AttireModel::getInstance()->getModel()->where(['is_goldbox' => 1])->select()->toArray();

        if (count($data) > 0) {
            foreach ($data as $k => $v) {
                $data[$k]['attire_image'] = config('config.APP_URL_image') . $v['attire_image'];
            }
        }

        $list = AttireModel::getInstance()->getModel()->where(['is_goldbox' => 0])->field('id,attire_name')->select()->toArray();
        $admin_url = config('config.admin_url');
        View::assign('data', $data);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('active/listGoldAttire');
    }

    /**
     * 添加金币抽奖奖励装扮
     */
    public function addlistGoldAttire()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $goldbox_time = empty(Request::param('goldbox_time')) ? 0 : Request::param('goldbox_time');
        $goldbox_weight = empty(Request::param('goldbox_weight')) ? 0 : Request::param('goldbox_weight');
        $goldbox_order = empty(Request::param('goldbox_order')) ? 0 : Request::param('goldbox_order');
        $is_goldbox = 1;
        if ($id == 0 || $goldbox_time == 0 || $goldbox_weight == 0 || $goldbox_order == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $GoldCount = GoldModel::getInstance()->getModel()->where(['gold_type' => 2])->select()->count();
        $AttireCount = AttireModel::getInstance()->getModel()->where(['is_goldbox' => 1])->select()->count();
        $GiftCount = GiftModel::getInstance()->getModel()->where(['is_goldbox' => 1])->select()->count();
        if (($GoldCount + $AttireCount + $GiftCount) == 8) {
            echo json_encode(['code' => 500, 'msg' => '奖品大于8条，请删除其他奖励后操作']);
            die;
        }
        $data = ['goldbox_time' => $goldbox_time, 'goldbox_weight' => $goldbox_weight, 'is_goldbox' => $is_goldbox, 'goldbox_order' => $goldbox_order];
        $is = AttireModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']);
            die;
        }
    }

    /**
     * 修改金币抽奖奖励装扮
     */
    public function updlistGoldAttire()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $goldbox_time = empty(Request::param('goldbox_time')) ? 0 : Request::param('goldbox_time');
        $goldbox_weight = empty(Request::param('goldbox_weight')) ? 0 : Request::param('goldbox_weight');
        $goldbox_order = empty(Request::param('goldbox_order')) ? 0 : Request::param('goldbox_order');
        if ($id == 0 || $goldbox_time == 0 || $goldbox_weight == 0 || $goldbox_order == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $data = ['goldbox_time' => $goldbox_time, 'goldbox_weight' => $goldbox_weight, 'goldbox_order' => $goldbox_order];
        $is = AttireModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);
            die;
        }
    }

    /**
     * 删除金币抽奖奖励装扮状态
     */
    public function dellistGoldAttire()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $is_goldbox = 0;
        if ($id == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $data = ['goldbox_time' => 0, 'goldbox_weight' => 1, 'is_goldbox' => $is_goldbox, 'goldbox_order' => 0];
        $is = AttireModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
            die;
        }
    }

    /**
     * @return mixed
     * 添加金币抽奖奖励礼物
     */
    public function listGoldGift()
    {
        $data = GiftModel::getInstance()->getModel()->where(['is_goldbox' => 1])->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['gift_image'] = config('config.APP_URL_image') . $v['gift_image'];
        }
        $list = GiftModel::getInstance()->getModel()->where(['is_goldbox' => 0])->field('id,gift_name')->select()->toArray();
        $admin_url = config('config.admin_url');
        View::assign('data', $data);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('active/listGoldGift');
    }
    /**
     * 添加金币抽奖奖励礼物
     */
    public function addlistGoldGift()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $goldbox_num = empty(Request::param('goldbox_num')) ? 0 : Request::param('goldbox_num');
        $goldbox_weight = empty(Request::param('goldbox_weight')) ? 0 : Request::param('goldbox_weight');
        $goldbox_order = empty(Request::param('goldbox_order')) ? 0 : Request::param('goldbox_order');
        $is_goldbox = 1;
        if ($id == 0 || $goldbox_num == 0 || $goldbox_weight == 0 || $goldbox_order == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $GoldCount = GoldModel::getInstance()->getModel()->where(['gold_type' => 2])->select()->count();
        $AttireCount = AttireModel::getInstance()->getModel()->where(['is_goldbox' => 1])->select()->count();
        $GiftCount = GiftModel::getInstance()->getModel()->where(['is_goldbox' => 1])->select()->count();
        if (($GoldCount + $AttireCount + $GiftCount) == 8) {
            echo json_encode(['code' => 500, 'msg' => '奖品大于8条，请删除其他奖励后操作']);
            die;
        }
        $data = ['goldbox_num' => $goldbox_num, 'goldbox_weight' => $goldbox_weight, 'is_goldbox' => $is_goldbox, 'goldbox_order' => $goldbox_order];
        $is = GiftModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']);
            die;
        }
    }

    /**
     * 添加金币抽奖奖励礼物
     */
    public function updlistGoldGift()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $goldbox_num = empty(Request::param('goldbox_num')) ? 0 : Request::param('goldbox_num');
        $goldbox_weight = empty(Request::param('goldbox_weight')) ? 0 : Request::param('goldbox_weight');
        $goldbox_order = empty(Request::param('goldbox_order')) ? 0 : Request::param('goldbox_order');
        if ($id == 0 || $goldbox_num == 0 || $goldbox_weight == 0 || $goldbox_order == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $data = ['goldbox_num' => $goldbox_num, 'goldbox_weight' => $goldbox_weight, 'goldbox_order' => $goldbox_order];
        $is = GiftModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);
            die;
        }
    }

    /**
     * 添加金币抽奖奖励礼物
     */
    public function dellistGoldGift()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $is_goldbox = 0;
        if ($id == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误，请重试']);
            die;
        }
        $data = ['goldbox_num' => 0, 'goldbox_weight' => 1, 'is_goldbox' => $is_goldbox, 'goldbox_order' => 0];
        $is = GiftModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
            die;
        }
    }
    /**
     * @param $gift
     * @return string
     * 获取礼物
     */
    public function getGift($gift)
    {
        if (!empty($gift)) {
            $giftname = [];
            foreach ($gift as $k => $v) {
                $gift_name = GiftModel::getInstance()->getModel()->where('id', $v['gift_id'])->field('gift_name,id')->select()->toArray();
                if (count($gift_name) <= 0) {
                    $giftname = [];
                } else {
                    if (isset($gift[$k]['rand_num'])) {
                        $giftname[] = $gift_name[0]['gift_name'] . '-' . $gift[$k]['num'] . '-' . $gift[$k]['rand_num'];
                    } else {
                        $giftname[] = $gift_name[0]['gift_name'] . '-' . $gift[$k]['num'];
                    }

                }
            }
            return implode(',', $giftname);
        }
        return '';
    }

    /**
     * @param $attire
     * @return string
     * 获取装扮
     */
    public function getAttire($attire)
    {
        if (!empty($attire)) {
            $attirename = [];
            foreach ($attire as $k => $v) {
                $attire_name = AttireModel::getInstance()->getModel()->where('id', $v['attid'])->field('attire_name')->select()->toArray();
                if (count($attire_name) <= 0) {
                    $attirename = [];
                } else {
                    if (isset($attire[$k]['rand_num'])) {
                        $attirename[] = $attire_name[0]['attire_name'] . '-' . $attire[$k]['num'] . '-' . $attire[$k]['rand_num'];
                    } else {
                        $attirename[] = $attire_name[0]['attire_name'] . '-' . $attire[$k]['num'];
                    }

                }
            }
            if (count($attirename) <= 0) {
                return implode(',', $attirename);
            }

        }
    }

    /**多为数组根据指定字段排序
     * @param $array
     * @param $field
     * @param bool $desc
     * @return mixed
     */
    public function sortArrByField(&$array, $field, $desc = false)
    {
        $fieldArr = array();
        foreach ($array as $k => $v) {
            $fieldArr[$k] = $v[$field];
        }
        $sort = $desc == false ? SORT_ASC : SORT_DESC;
        array_multisort($fieldArr, $sort, $array);
        return $array;
    }

    /**
     * @return mixed
     * 新手任务
     */
    public function ListNewTask()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $gift = GiftModel::getInstance()->getModel()->where('status', 1)->field('id,gift_name')->select()->toArray();
        $attire = AttireModel::getInstance()->getModel()->where('status', 1)->field('id,attire_name')->select()->toArray();
        $count = TaskModel::getInstance()->getModel()->where('task_type', 1)->field('id')->select()->count();
        $data = TaskModel::getInstance()->getModel()->where('task_type', 1)->limit($page, $pagenum)->select()->toArray();
        $gift = [];
        foreach ($data as $k => $v) {
            //'任务状态1上架0下架'
            if ($data[$k]['task_status'] == 1) {
                $data[$k]['task_status'] = '上架';
            } else {
                $data[$k]['task_status'] = '下架';
            }
            $task_reward[$k]['task_reward'] = json_decode($data[$k]['task_reward'], true);
            $data[$k]['coin'] = $task_reward[$k]['task_reward']['coin'];
            $data[$k]['gold_coin'] = $task_reward[$k]['task_reward']['gold_coin'];
            $data[$k]['active_degree'] = $task_reward[$k]['task_reward']['active_degree'];
            $data[$k]['gift'] = $this->getGift($task_reward[$k]['task_reward']['gift']);
            $data[$k]['attire'] = $this->getAttire($task_reward[$k]['task_reward']['attire']);

            unset($data[$k]['task_reward']);
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('gift', $gift);
        View::assign('attire', $attire);
        return View::fetch('active/listnewtask');
    }

    /**
     * @return mixed
     * 新手任务更新
     */
    public function NewTaskSave()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $attirearr = empty(Request::param('attire')) ? [] : Request::param('attire');
        $attirenum = empty(Request::param('attirenum')) ? [] : Request::param('attirenum');
        $a_rand_num = empty(Request::param('a_rand_num')) ? [] : Request::param('a_rand_num');
        $g_rand_num = empty(Request::param('g_rand_num')) ? [] : Request::param('g_rand_num');
        $giftarr = empty(Request::param('gift')) ? [] : Request::param('gift');
        $giftnum = empty(Request::param('giftnum')) ? [] : Request::param('giftnum');
        $coin = empty(Request::param('coin')) ? 0 : Request::param('coin');
        $gold_coin = empty(Request::param('gold_coin')) ? 0 : Request::param('gold_coin');
        $active_degree = empty(Request::param('active_degree')) ? 0 : Request::param('active_degree');
        foreach ($attirearr as $k => $v) {
            $attis = AttireModel::getInstance()->getModel()->where('id', $v)->field('id')->select()->toArray();
            if (!$attis) {
                echo json_encode(['code' => 500, 'msg' => '装扮ID不存在']);die;
            }
        }
        foreach ($giftarr as $k => $v) {
            $attis = GiftModel::getInstance()->getModel()->where('id', $v)->field('id')->select()->toArray();
            if (!$attis) {
                echo json_encode(['code' => 500, 'msg' => '礼物ID不存在']);die;
            }
        }

        $attire = [];
        if (isset($attirearr) && isset($attirenum)) {
            $attire = [];
            foreach ($attirearr as $key => $value) {
                $attire[$key]['attid'] = intval($value);
            }
            foreach ($attirenum as $key => $value) {
                $attire[$key]['num'] = intval($value);
            }
            if (count($a_rand_num) <= 1) {
                foreach ($a_rand_num as $key => $value) {
                    $attire[$key]['rand_num'] = 1;
                }
            } else {
                foreach ($a_rand_num as $key => $value) {
                    $attire[$key]['rand_num'] = intval($value);
                }
            }
        }

        $gift = [];
        if (isset($giftarr) && isset($giftnum)) {
            foreach ($giftarr as $key => $value) {
                $gift[$key]['gift_id'] = intval($value);
            }
            foreach ($giftnum as $key => $value) {
                $gift[$key]['num'] = intval($value);
            }
            if (count($g_rand_num) <= 1) {
                foreach ($g_rand_num as $key => $value) {
                    $gift[$key]['rand_num'] = 0;
                }
            } else {
                foreach ($g_rand_num as $key => $value) {
                    $gift[$key]['rand_num'] = intval($value);
                }
            }
        }

        $data = ['task_reward' => json_encode(['gift' => $this->sortArrByField($gift, 'num'), 'attire' => $this->sortArrByField($attire, 'num'), 'coin' => intval($coin), 'gold_coin' => intval($gold_coin), 'active_degree' => intval($active_degree), 'create_time' => time()])];
        $is = TaskModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);die;
        }
        die;
    }

    /**
     * @return mixed
     * 每日任务
     */
    public function ListDailyTask()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $gift = GiftModel::getInstance()->getModel()->where('status', 1)->field('id,gift_name')->select()->toArray();
        $attire = AttireModel::getInstance()->getModel()->where('status', 1)->field('id,attire_name')->select()->toArray();
        $count = TaskModel::getInstance()->getModel()->where('task_type', 2)->field('id')->select()->count();
        $data = TaskModel::getInstance()->getModel()->where('task_type', 2)->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            //'任务状态1上架0下架'
            if ($data[$k]['task_status'] == 1) {
                $data[$k]['task_status'] = '上架';
            } else {
                $data[$k]['task_status'] = '下架';
            }
            $task_reward[$k]['task_reward'] = json_decode($data[$k]['task_reward'], true);
            $data[$k]['coin'] = $task_reward[$k]['task_reward']['coin'];
            $data[$k]['gold_coin'] = $task_reward[$k]['task_reward']['gold_coin'];
            $data[$k]['active_degree'] = $task_reward[$k]['task_reward']['active_degree'];
            $data[$k]['gift'] = $this->getGift($task_reward[$k]['task_reward']['gift']);
            $data[$k]['attire'] = $this->getAttire($task_reward[$k]['task_reward']['attire']);

            unset($data[$k]['task_reward']);
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('gift', $gift);
        View::assign('attire', $attire);
        return View::fetch('active/ListDailyTask');
    }

    /**
     * @return mixed
     * 每日任务更新
     */
    public function DailyTaskSave()
    {
        $a_rand_num = empty(Request::param('a_rand_num')) ? [] : Request::param('a_rand_num');
        $g_rand_num = empty(Request::param('g_rand_num')) ? [] : Request::param('g_rand_num');
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $attirearr = empty(Request::param('attire')) ? [] : Request::param('attire');
        $attirenum = empty(Request::param('attirenum')) ? [] : Request::param('attirenum');
        $giftarr = empty(Request::param('gift')) ? [] : Request::param('gift');
        $giftnum = empty(Request::param('giftnum')) ? [] : Request::param('giftnum');
        $coin = empty(Request::param('coin')) ? 0 : Request::param('coin');
        $gold_coin = empty(Request::param('gold_coin')) ? 0 : Request::param('gold_coin');
        $active_degree = empty(Request::param('active_degree')) ? 0 : Request::param('active_degree');
        $att = count($attirearr);
        $attnum = count($attirenum);
        $gift = count($giftarr);
        $gnum = count($giftnum);

        if ($att == 0 && $attnum == 0 && $gift == 0 && $gnum == 0 && $coin == 0 && $gold_coin == 0) {
            echo json_encode(['code' => 500, 'msg' => '奖励必选一种']);die;
        } elseif ($att >= 1 && $attnum >= 1 && $gift >= 1 && $gnum >= 1) {
            echo json_encode(['code' => 500, 'msg' => '礼物和装扮不可同时存在']);die;
        }

        foreach ($attirearr as $k => $v) {
            $attis = AttireModel::getInstance()->getModel()->where('id', $v)->field('id')->select()->toArray();
            if (!$attis) {
                echo json_encode(['code' => 500, 'msg' => '装扮ID不存在']);die;
            }
        }
        foreach ($giftarr as $k => $v) {
            $attis = GiftModel::getInstance()->getModel()->where('id', $v)->field('id')->select()->toArray();
            if (!$attis) {
                echo json_encode(['code' => 500, 'msg' => '礼物ID不存在']);die;
            }
        }
        $attire = [];
        if (isset($attirearr) && isset($attirenum)) {
            $attire = [];
            foreach ($attirearr as $key => $value) {
                $attire[$key]['attid'] = intval($value);
            }
            foreach ($attirenum as $key => $value) {
                $attire[$key]['num'] = intval($value);
            }
            if (count($a_rand_num) <= 1) {
                foreach ($a_rand_num as $key => $value) {
                    $attire[$key]['rand_num'] = 1;
                }
            } else {
                foreach ($a_rand_num as $key => $value) {
                    $attire[$key]['rand_num'] = intval($value);
                }
            }
        }
        $gift = [];
        if (isset($giftarr) && isset($giftnum)) {
            foreach ($giftarr as $key => $value) {
                $gift[$key]['gift_id'] = intval($value);
            }
            foreach ($giftnum as $key => $value) {
                $gift[$key]['num'] = intval($value);
            }
            if (count($g_rand_num) <= 1) {
                foreach ($g_rand_num as $key => $value) {
                    $gift[$key]['rand_num'] = 0;
                }
            } else {
                foreach ($g_rand_num as $key => $value) {
                    $gift[$key]['rand_num'] = intval($value);
                }
            }
        }
        $active = TaskModel::getInstance()->getModel()->where([['task_type', '=', 2], ['id', '<>', $id]])->field('task_reward')->select()->toArray();
        foreach ($active as $k => $v) {
            $info[$k] = json_decode($active[$k]['task_reward'], true);
        }
        $active = 0;
        foreach ($info as $k => $v) {
            $active += $v['active_degree'];
        }
        if (($active + $active_degree) > 100) {
            echo json_encode(['code' => 500, 'msg' => '添加活跃值总合大于100']);die;
        }
        $data = ['task_reward' => json_encode(['gift' => $this->sortArrByField($gift, 'num'), 'attire' => $this->sortArrByField($attire, 'num'), 'coin' => intval($coin), 'gold_coin' => intval($gold_coin), 'active_degree' => intval($active_degree), 'create_time' => time()])];
        $is = TaskModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);die;
        }
        die;
    }
    /**
     * @return mixed
     * 签到任务
     */
    public function ListSignTask()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $gift = GiftModel::getInstance()->getModel()->where('status', 1)->field('id,gift_name')->select()->toArray();
        $attire = AttireModel::getInstance()->getModel()->where('status', 1)->field('id,attire_name')->select()->toArray();
        $count = TaskModel::getInstance()->getModel()->where('task_type', 3)->field('id')->select()->count();
        $data = TaskModel::getInstance()->getModel()->where('task_type', 3)->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            //'任务状态1上架0下架'
            if ($data[$k]['task_status'] == 1) {
                $data[$k]['task_status'] = '上架';
            } else {
                $data[$k]['task_status'] = '下架';
            }
            $task_reward[$k]['task_reward'] = json_decode($data[$k]['task_reward'], true);
            $data[$k]['coin'] = $task_reward[$k]['task_reward']['coin'];
            $data[$k]['gold_coin'] = $task_reward[$k]['task_reward']['gold_coin'];
            $data[$k]['active_degree'] = $task_reward[$k]['task_reward']['active_degree'];
            $data[$k]['gift'] = $this->getGift($task_reward[$k]['task_reward']['gift']);

            $data[$k]['giftid'] = '';
            $data[$k]['giftnum'] = '';
            $data[$k]['gift_rand_num'] = 0;
            if (!empty($task_reward[$k]['task_reward']['gift'])) {
                $data[$k]['giftid'] = $task_reward[$k]['task_reward']['gift'][0]['gift_id'];
                $data[$k]['giftnum'] = $task_reward[$k]['task_reward']['gift'][0]['num'];
//                $data[$k]['gift_rand_num'] = $task_reward[$k]['task_reward']['gift'][0]['rand_num'];
            }
            $data[$k]['attire'] = $this->getAttire($task_reward[$k]['task_reward']['attire']);

            $data[$k]['attireid'] = '';
            $data[$k]['attirenum'] = '';
            $data[$k]['attire_rand_num'] = 0;
            if (!empty($task_reward[$k]['task_reward']['attire'])) {
                $data[$k]['attireid'] = $task_reward[$k]['task_reward']['attire'][0]['attire_id'];
                $data[$k]['giftnum'] = $task_reward[$k]['task_reward']['attire'][0]['num'];
//                $data[$k]['attire_rand_num'] = $task_reward[$k]['task_reward']['attire'][0]['rand_num'];
            }

            unset($data[$k]['task_reward']);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('gift', $gift);
        View::assign('attire', $attire);
        return View::fetch('active/ListSignTask');
    }

    /**
     * @return mixed
     * 签到更新
     */
    public function SignTaskSave()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $attirearr = empty(Request::param('attire')) ? [] : Request::param('attire');
        $attirenum = empty(Request::param('attirenum')) ? [] : Request::param('attirenum');
        $giftarr = empty(Request::param('gift')) ? [] : Request::param('gift');
        $giftnum = empty(Request::param('giftnum')) ? [] : Request::param('giftnum');
        $a_rand_num = empty(Request::param('a_rand_num')) ? [] : Request::param('a_rand_num');
        $g_rand_num = empty(Request::param('g_rand_num')) ? [] : Request::param('g_rand_num');
        $coin = empty(Request::param('coin')) ? 0 : Request::param('coin');
        $gold_coin = empty(Request::param('gold_coin')) ? 0 : Request::param('gold_coin');
        $active_degree = empty(Request::param('active_degree')) ? 0 : Request::param('active_degree');
        $attire = [];
        $gift = [];

        if (!empty($attirearr[0])) {
            foreach ($attirearr as $k => $v) {
                $attis = AttireModel::getInstance()->getModel()->where('id', $v)->field('id')->select()->toArray();
                if (!$attis) {
                    echo json_encode(['code' => 500, 'msg' => '装扮ID不存在']);die;
                }
            }
            foreach ($attirearr as $key => $value) {
                $attire[$key]['attid'] = intval($value);
            }
            foreach ($attirenum as $key => $value) {
                $attire[$key]['num'] = intval($value);
            }
            if (count($a_rand_num) <= 1) {
                foreach ($a_rand_num as $key => $value) {
                    $attire[$key]['rand_num'] = 1;
                }
            } else {
                foreach ($a_rand_num as $key => $value) {
                    $attire[$key]['rand_num'] = intval($value);
                }
            }
        } elseif (!empty($giftarr[0]) > 0) {
            foreach ($giftarr as $k => $v) {
                $attis = GiftModel::getInstance()->getModel()->where('id', $v)->field('id')->select()->toArray();
                if (!$attis) {
                    echo json_encode(['code' => 500, 'msg' => '礼物ID不存在']);die;
                }
            }
            foreach ($giftarr as $key => $value) {
                $gift[$key]['gift_id'] = intval($value);
            }
            foreach ($giftnum as $key => $value) {
                $gift[$key]['num'] = intval($value);
            }
            if (count($g_rand_num) <= 1) {
                foreach ($g_rand_num as $key => $value) {
                    $gift[$key]['rand_num'] = 0;
                }
            } else {
                foreach ($g_rand_num as $key => $value) {
                    $gift[$key]['rand_num'] = intval($value);
                }
            }
        }

        $data = ['task_reward' => json_encode(['gift' => $this->sortArrByField($gift, 'num'), 'attire' => $this->sortArrByField($attire, 'num'), 'coin' => intval($coin), 'gold_coin' => intval($gold_coin), 'active_degree' => intval($active_degree), 'create_time' => time()])];
        $is = TaskModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);die;
        }
        die;
    }

    /**
     * @return mixed
     * 日常任务
     */
    public function ListOftenTasks()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $gift = GiftModel::getInstance()->getModel()->where('status', 1)->field('id,gift_name')->select()->toArray();
        $attire = AttireModel::getInstance()->getModel()->where('status', 1)->field('id,attire_name')->select()->toArray();
        $count = TaskModel::getInstance()->getModel()->where('task_type', 4)->field('id')->select()->count();
        $data = TaskModel::getInstance()->getModel()->where('task_type', 4)->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            //'任务状态1上架0下架'
            if ($data[$k]['task_status'] == 1) {
                $data[$k]['task_status'] = '上架';
            } else {
                $data[$k]['task_status'] = '下架';
            }
            $task_reward[$k]['task_reward'] = json_decode($data[$k]['task_reward'], true);
            $data[$k]['coin'] = $task_reward[$k]['task_reward']['coin'];
            $data[$k]['gold_coin'] = $task_reward[$k]['task_reward']['gold_coin'];
            $data[$k]['active_degree'] = $task_reward[$k]['task_reward']['active_degree'];
            $data[$k]['gift'] = $this->getGift($task_reward[$k]['task_reward']['gift']);
            $data[$k]['attire'] = $this->getAttire($task_reward[$k]['task_reward']['attire']);

            unset($data[$k]['task_reward']);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('gift', $gift);
        View::assign('attire', $attire);
        return View::fetch('active/ListOftenTasks');
    }

    /**
     * @return mixed
     * 日常任务更新
     */
    public function OftenTasksSave()
    {
        $id = empty(Request::param('id')) ? 0 : Request::param('id');
        $attirearr = empty(Request::param('attire')) ? [] : Request::param('attire');
        $attirenum = empty(Request::param('attirenum')) ? [] : Request::param('attirenum');
        $giftarr = empty(Request::param('gift')) ? [] : Request::param('gift');
        $giftnum = empty(Request::param('giftnum')) ? [] : Request::param('giftnum');
        $a_rand_num = empty(Request::param('a_rand_num')) ? [] : Request::param('a_rand_num');
        $g_rand_num = empty(Request::param('g_rand_num')) ? [] : Request::param('g_rand_num');
        $coin = empty(Request::param('coin')) ? 0 : Request::param('coin');
        $gold_coin = empty(Request::param('gold_coin')) ? 0 : Request::param('gold_coin');
        $active_degree = empty(Request::param('active_degree')) ? 0 : Request::param('active_degree');
        foreach ($attirearr as $k => $v) {
            $attis = AttireModel::getInstance()->getModel()->where('id', $v)->field('id')->select()->toArray();
            if (!$attis) {
                echo json_encode(['code' => 500, 'msg' => '装扮ID不存在']);die;
            }
        }
        foreach ($giftarr as $k => $v) {
            $attis = GiftModel::getInstance()->getModel()->where('id', $v)->field('id')->select()->toArray();
            if (!$attis) {
                echo json_encode(['code' => 500, 'msg' => '礼物ID不存在']);die;
            }
        }
        $attire = [];
        if (isset($attirearr) && isset($attirenum)) {
            $attire = [];
            foreach ($attirearr as $key => $value) {
                $attire[$key]['attid'] = intval($value);
            }
            foreach ($attirenum as $key => $value) {
                $attire[$key]['num'] = intval($value);
            }
            if (count($a_rand_num) <= 1) {
                foreach ($a_rand_num as $key => $value) {
                    $attire[$key]['rand_num'] = 1;
                }
            } else {
                foreach ($a_rand_num as $key => $value) {
                    $attire[$key]['rand_num'] = intval($value);
                }
            }
        }
        $gift = [];
        if (isset($giftarr) && isset($giftnum)) {
            foreach ($giftarr as $key => $value) {
                $gift[$key]['gift_id'] = intval($value);
            }
            foreach ($giftnum as $key => $value) {
                $gift[$key]['num'] = intval($value);
            }
            if (count($g_rand_num) <= 1) {
                foreach ($g_rand_num as $key => $value) {
                    $gift[$key]['rand_num'] = 0;
                }
            } else {
                foreach ($g_rand_num as $key => $value) {
                    $gift[$key]['rand_num'] = intval($value);
                }
            }
        }

        $data = ['task_reward' => json_encode(['gift' => $this->sortArrByField($gift, 'num'), 'attire' => $this->sortArrByField($attire, 'num'), 'coin' => intval($coin), 'gold_coin' => intval($gold_coin), 'active_degree' => intval($active_degree), 'create_time' => time()])];
        $is = TaskModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);die;
        }
        die;
    }

    /**
     * @return mixed
     * 活动列表
     */
    public function activeList()
    {
        $search_name = $this->request->param('search_name');
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = [];
        if ($search_name) {
            $where[] = ['name', 'like', $search_name . '%'];
        }
        $field = 'id,name,start_time,end_time,created_user,updated_user,active_status,updated_time';
        $list = ActiveModel::getInstance()->getModel()->field($field)->where($where)->order('id', 'desc')->limit($page, $pagenum)->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['start_time'] = $v['start_time'] > 0 ? date('Y-m-d H:i:00', $v['start_time']) : '';
            $list[$k]['end_time'] = $v['end_time'] > 0 ? date('Y-m-d H:i:00', $v['end_time']) : '';
            $list[$k]['updated_time'] = $v['updated_time'] > 0 ? date('Y-m-d H:i:00', $v['updated_time']) : '';
            switch ($v['active_status']) {
                case 1:
                    $list[$k]['status_name'] = '开始中';
                    break;
                case 2:
                    $list[$k]['status_name'] = '已结束';
                    break;
                default:
                    $list[$k]['status_name'] = '未开始';
            }
        }
        $attirelist = AttireModel::getInstance()->getModel()->where('status', 1)->select()->toArray();
        foreach ($attirelist as $k => $v) {
            $type[] = $v;
        }
        $count = ActiveModel::getInstance()->getModel()->where($where)->count();
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $gift = GiftModel::getInstance()->getModel()->where('status', 1)->column('id,gift_name');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('attiretype', json_encode($type));
        View::assign('gifttype', json_encode($gift));
        View::assign('attire', $type);
        View::assign('list', $list);
        View::assign('gift', $gift);
        View::assign('img_url', config('config.APP_URL_image'));
        View::assign('search_name', $search_name);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        return View::fetch('active/index');
    }

    /**
     * 添加活动
     */
    public function addActive()
    {
        $active = $this->request->param('active');
//        if (!$active) {
        //            echo $this->return_json(\constant\CodeConstant::CODE_活动信息不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动信息不可为空]);
        //            die;
        //        }
        if (!$active['name']) {
            echo $this->return_json(\constant\CodeConstant::CODE_请正确填写活动名称, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_请正确填写活动名称]);
            die;
        }
        if (!$active['start_time']) {
            echo $this->return_json(\constant\CodeConstant::CODE_开始时间不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_开始时间不可为空]);
            die;
        }
        if (!$active['end_time']) {
            echo $this->return_json(\constant\CodeConstant::CODE_结束时间不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间不可为空]);
            die;
        }
        if (strtotime($active['start_time']) >= strtotime($active['end_time'])) {
            echo $this->return_json(\constant\CodeConstant::CODE_结束时间必须大于开始时间, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间必须大于开始时间]);
            die;
        }
        if (!$active['active_address']) {
            echo $this->return_json(\constant\CodeConstant::CODE_活动页地址不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动页地址不可为空]);
            die;
        }

        $active['start_time'] = strtotime($active['start_time']);
        $active['end_time'] = strtotime($active['end_time']);
        $active['is_associated'] = empty($active['is_associated']) ? 0 : $active['is_associated'];
        //获取活动内容
        //        if (empty($active['content'])) {
        //            echo $this->return_json(\constant\CodeConstant::CODE_活动信息不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动信息不可为空]);
        //            die;
        //        }
        $active['created_time'] = time();
        $active['created_user'] = $this->token['username'];
//        $active_content = $active['content'];
        //        if (count($active_content) > 4) {
        //            echo $this->return_json(\constant\CodeConstant::CODE_活动内容档位最大不可超过四个, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动内容档位最大不可超过四个]);
        //            die;
        //        }
        //        unset($active['content']);
        $data = [];
        $id = ActiveModel::getInstance()->getModel()->insert($active);
//        try {
        //            ActiveModel::getInstance()->getModel()->startTrans();
        //            $id = ActiveModel::getInstance()->getModel()->insertGetId($active);
        //
        //            foreach ($active_content as $k => $v) {
        //                $data[$k]['active_id'] = $id;
        //                $data[$k]['type'] = $v['type'];
        //                $data[$k]['sort'] = $v['sort'];
        //                $data[$k]['content'] = json_encode($v['content']);
        //                $data[$k]['createtime'] = $active['created_time'];
        //            }
        //            ActivePositionModel::getInstance()->getModel()->insertAll($data);
        //            ActiveModel::getInstance()->getModel()->commit();
        //            $ok = 1;
        //        } catch (\Exception $e) {
        //            $ok = 2;
        //            ActiveModel::getInstance()->getModel()->rollback();
        //        }
        if ($id == 1) {
            Log::record('活动添加成功:操作人:' . $this->token['username'] . ':active_id:' . $id . ':数据:' . json_encode($active), 'addActive');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        } else {
            Log::record('活动添加失败:操作人:' . $this->token['username'] . ':数据:' . json_encode($active), 'addActive');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, '', $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }

    }

    /**
     * 活动详情
     */
    public function activeItems()
    {
        $id = $this->request->param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, '', $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $active_item = ActiveModel::getInstance()->getModel()
            ->field('id,name,start_time,end_time,active_button,active_address,active_img,introduce,is_associated')
            ->where('id', $id)
            ->select()
            ->toArray();
        $data = [];
        if (!empty($active_item)) {
            $data['content'] = $active_item[0];
            if ($active_item[0]['is_associated'] == 1) {
                $data['content']['is_associated'] = '关联';
            } else {
                $data['content']['is_associated'] = '不关联';
            }
            $data['content']['start_time'] = date('Y-m-d H:i:s', $active_item[0]['start_time']);
            $data['content']['end_time'] = date('Y-m-d H:i:s', $active_item[0]['end_time']);
//            $data['content']['content'] = ActivePositionModel::getInstance()->getModel()->field('id,type,sort,content')->where(array('active_id' => $data['content']['id'],'is_show' => 0))->select()->toArray();
            //            $data_attire_id = ActivePositionModel::getInstance()->getModel()->field('content')->where(array('active_id' => $data['content']['id'],'is_show' => 0))->select()->toArray();
            //            foreach($data_attire_id as $k => $v){
            //                $attireid[] = array_column(object_array(json_decode($v['content']))['attire_id'],'attire_id');
            //            }
            //            foreach ($attireid as $k => $v){
            //                foreach ($v as $kk => $vv){
            //                    $data['attireid'][] = $vv;
            //                }
            //            }
        }
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /**
     * 修改活动
     */
    public function exitActive()
    {
        $active = $this->request->param('active');
        $id = $this->request->param('id');
//        if (!$active || !$id) {
        //            echo $this->return_json(\constant\CodeConstant::CODE_活动信息不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动信息不可为空]);
        //            die;
        //        }

        if (!$active['name']) {
            echo $this->return_json(\constant\CodeConstant::CODE_请正确填写活动名称, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_请正确填写活动名称]);
            die;
        }

        if (!$active['start_time']) {
            echo $this->return_json(\constant\CodeConstant::CODE_开始时间不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_开始时间不可为空]);
            die;
        }
        if (!$active['end_time']) {
            echo $this->return_json(\constant\CodeConstant::CODE_结束时间不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间不可为空]);
            die;
        }
        if (strtotime($active['start_time']) >= strtotime($active['end_time'])) {
            echo $this->return_json(\constant\CodeConstant::CODE_结束时间必须大于开始时间, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间必须大于开始时间]);
            die;
        }
        $active['start_time'] = strtotime($active['start_time']);
        $active['end_time'] = strtotime($active['end_time']);
        if (!$active['active_address']) {
            echo $this->return_json(\constant\CodeConstant::CODE_活动页地址不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动页地址不可为空]);
            die;
        }
        $active['is_associated'] = empty($active['is_associated']) ? 0 : $active['is_associated'];
        //获取活动内容
        //        if (empty($active['content'])) {
        //            echo $this->return_json(\constant\CodeConstant::CODE_活动信息不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动信息不可为空]);
        //            die;
        //        }
        $active['updated_time'] = time();
        $active['updated_user'] = $this->token['username'];
//        $active_content = $active['content'];
        //        if (count($active_content) > 4) {
        //            echo $this->return_json(\constant\CodeConstant::CODE_活动内容档位最大不可超过四个, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动内容档位最大不可超过四个]);
        //            die;
        //        }
        //        unset($active['content']);
        $id1 = $id;
        $ok = ActiveModel::getInstance()->getModel()->where(array('id' => $id))->save($active);
//        try {
        //            ActiveModel::getInstance()->getModel()->startTrans();
        //            ActiveModel::getInstance()->getModel()->where(array('id' => $id))->save($active);
        //            $zb_active_position_id = ActivePositionModel::getInstance()->getModel()->field('id')->where(['active_id' => $id1])->select()->toArray();
        //            $is_showid = [];
        //            foreach ($active_content as $k => $v) {
        //                $is_showid[$k] = $zb_active_position_id[$k]['id'];
        //                $v['content'] = json_encode($v['content']);
        //                if (isset($zb_active_position_id[$k])) {
        //                    //更新
        //                    $where1 = ['id'=>$zb_active_position_id[$k]['id']];
        ////                    ActivePositionModel::getInstance()->getModel()->where($where)->update($v);
        //                    ActivePositionModel::getInstance()->setAttire($where1,$v);
        //
        //                } else {
        //                    //插入
        //                    $v['active_id'] = $id;
        //                    ActivePositionModel::getInstance()->getModel()->insert($v);
        //                }
        //            }
        //            ActivePositionModel::getInstance()->setAttire(array('active_id'=>$id1),array('is_show' => 1));
        //            ActivePositionModel::getInstance()->setAttire(array('id' => $is_showid),array('is_show'=>0));
        //            ActiveModel::getInstance()->getModel()->commit();
        //            $ok = 1;
        //        } catch (\Exception $e) {
        //            ActiveModel::getInstance()->getModel()->rollback();
        //            $ok = 2;
        //        }

        if ($ok == 1) {
            Log::record('活动修改成功:操作人:' . $this->token['username'] . ':active_id:' . $id1 . ':数据:' . json_encode($active), 'addActive');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }
        Log::record('活动修改失败:操作人:' . $this->token['username'] . ':数据:' . json_encode($active), 'addActive');
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, '', $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }

    /**
     * 活动开启
     */
    public function activeStart()
    {
        $id = $this->request->param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_活动信息不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动信息不可为空]);
            die;
        }
        $res = ActiveModel::getInstance()->getModel()->where(array('id' => $id))->update(array('updated_user' => $this->token['username'], 'updated_time' => time(), 'active_status' => 1, 'start_time' => time()));
        if ($res) {
            Log::record('活动开启成功:操作人:' . $this->token['username'] . ':active_id:' . $id, 'activeStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }
        Log::record('活动开启失败:操作人:' . $this->token['username'] . ':active_id:' . $id, 'activeStart');
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, '', $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }

    /**
     * 活动结束
     */
    public function activeStop()
    {
        $id = $this->request->param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_活动信息不可为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_活动信息不可为空]);
            die;
        }
        $res = ActiveModel::getInstance()->getModel()->where(array('id' => $id))->update(array('updated_user' => $this->token['username'], 'updated_time' => time(), 'active_status' => 2));
        if ($res) {
            Log::record('活动结束成功:操作人:' . $this->token['username'] . ':active_id:' . $id, 'activeStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }
        Log::record('活动结束失败:操作人:' . $this->token['username'] . ':active_id:' . $id, 'activeStart');
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, '', $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }

}
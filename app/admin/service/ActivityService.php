<?php

namespace app\admin\service;

use app\admin\model\ConfigModel;
use app\admin\model\GiftModel;
use app\admin\model\MemberModel;
use app\admin\model\WeeksConfigModel;
use app\common\model\CoindetailModel;
use app\common\RedisCommon;
use think\facade\Log;

class ActivityService
{
    protected static $instance;
    public static $type = ['万千宠爱' => 1, '君临天下' => 2, '荣誉星耀' => 3, '周星礼物' => 4];
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ActivityService();
        }
        return self::$instance;
    }

    /****************************************** 周星 ***********************************/
    //万千宠爱奖励
    public function weeksCharmConf($getPage, $username)
    {
        $pagenum = 10;
        $page = !empty($getPage) ? ($getPage - 1) * $pagenum : 0;
        $master_page = !empty($getPage) ? $getPage : 1;
        $url = config('config.APP_URL_image');
        $count = WeeksConfigModel::getInstance()->getModel()->where([['type', '=', self::$type['万千宠爱']]])->count();
        $data = [];
        if ($count > 0) {
            $data = WeeksConfigModel::getInstance()->getModel()->where([['type', '=', self::$type['万千宠爱']]])->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $key => $val) {
                $data[$key]['gift_url'] = $url . $val['gift_url'];
                $data[$key]['gift_avatar'] = $url . $val['gift_avatar'];
                $data[$key]['named_url'] = $url . $val['named_url'];
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物列表获取成功:操作人:' . $username, 'giftList');
        return ['data' => $data, 'page_array' => $page_array];
    }

    //君临天下
    public function weeksWealthConf($getPage, $username)
    {
        $pagenum = 10;
        $page = !empty($getPage) ? ($getPage - 1) * $pagenum : 0;
        $master_page = !empty($getPage) ? $getPage : 1;
        $url = config('config.APP_URL_image');
        $count = WeeksConfigModel::getInstance()->getModel()->where([['type', '=', self::$type['君临天下']]])->count();
        $data = [];
        if ($count > 0) {
            $data = WeeksConfigModel::getInstance()->getModel()->where([['type', '=', self::$type['君临天下']]])->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $key => $val) {
                $data[$key]['gift_avatar'] = $url . $val['gift_avatar'];
                $data[$key]['rich_car_url'] = $url . $val['rich_car_url'];
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物列表获取成功:操作人:' . $username, 'giftList');
        return ['data' => $data, 'page_array' => $page_array];
    }

    //月榜配置
    public function weeksMonthConf($getPage, $username)
    {
        $pagenum = 10;
        $page = !empty($getPage) ? ($getPage - 1) * $pagenum : 0;
        $master_page = !empty($getPage) ? $getPage : 1;
        $url = config('config.APP_URL_image');
        $count = WeeksConfigModel::getInstance()->getModel()->where([['type', '=', self::$type['荣誉星耀']]])->count();
        $data = [];
        if ($count > 0) {
            $data = WeeksConfigModel::getInstance()->getModel()->where([['type', '=', self::$type['荣誉星耀']]])->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $key => $val) {
                $data[$key]['gift_avatar'] = $url . $val['gift_avatar'];
                $data[$key]['gift_url'] = $url . $val['gift_url'];
                $data[$key]['rich_box_url'] = $url . $val['rich_box_url'];
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物列表获取成功:操作人:' . $username, 'giftList');
        return ['data' => $data, 'page_array' => $page_array];
    }

    //周星礼物
    public function weeksGiftConf($getPage, $username)
    {
        $pagenum = 10;
        $page = !empty($getPage) ? ($getPage - 1) * $pagenum : 0;
        $master_page = !empty($getPage) ? $getPage : 1;
        $url = config('config.APP_URL_image');
        $count = WeeksConfigModel::getInstance()->getModel()->where([['type', '=', self::$type['周星礼物']]])->count();
        $data = [];
        $giftList = $this->getGift();
        if ($count > 0) {
            $data = WeeksConfigModel::getInstance()->getModel()->field('id,gift_id,status')->where([['type', '=', self::$type['周星礼物']]])->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $key => $val) {
                $data[$key]['gift_url'] = $url . $giftList[$val['gift_id']]['image'];
                $data[$key]['gift_name'] = $giftList[$val['gift_id']]['name'];
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物列表获取成功:操作人:' . $username, 'giftList');
        return ['data' => $data, 'page_array' => $page_array];
    }
    public function getGift()
    {
        foreach (json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true) as $k => $v) {
            $data[$v['giftId']] = $v;
        }
        return $data;
    }
    /************************************ 用户回归 *****************************/
    public function returnUserActivityConfig()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $data = $redis->hGetAll('return_user_activity_config');
        foreach ($data as $k => $v) {
            $data[$k] = json_decode($v, true);
        }
        if($data){
            $data['start_time'] = date('Y-m-d H:i:s', $data['start_time']);
            $data['end_time'] = date('Y-m-d H:i:s', $data['end_time']);
        }
        return $data;
    }

    public function returnSave($data)
    {
        $keys = 'return_user_activity_config';
        $redis = RedisCommon::getInstance()->getRedis();
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']);
        $data['id'] = (int) $data['id'];
        foreach ($data as $k => $v) {
            $redis->hSet($keys, $k, json_encode($v));
        }
        echo json_encode(['code' => 200, 'msg' => '编辑成功']);die;
    }

    /**********************三人夺宝***********************/

    /**
     * 获取奖池详细信息
     * @param $poolInfo
     * @param $pool_type
     * @return array
     */
    public function getTreasurePooWinner($page, $pagenum, $pool_type, $uid, $demo): array
    {
        list($start, $end) = getBetweenDate($demo);

        $where[] = ['addtime', '>=', date('Y-m-d H:i:s', strtotime($start))];
        $where[] = ['addtime', '<', date('Y-m-d H:i:s', strtotime($end))];
        $where1[] = ['addtime', '>=', date('Y-m-d H:i:s', strtotime($start))];
        $where1[] = ['addtime', '<', date('Y-m-d H:i:s', strtotime($end))];

        if ($uid) {
            $where[] = ['uid', '=', $uid];
            $where1[] = ['uid', '=', $uid];
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $where[] = ['action', '=', 'three_treasures'];
        $where1[] = ['action', '=', 'three_treasures_return'];
        $where[] = ['content', 'like', 'duobaoorder:' . $pool_type . ':%'];
        $where1[] = ['content', 'like', 'duobaoorder:' . $pool_type . ':%'];
        $content = CoindetailModel::getInstance()->getModel()->where($where1)->group('content')->column('content');
        if (count($content) > 0) {
            $where[] = ['content', 'not in', $content];
        }
        $countContent = CoindetailModel::getInstance()->getModel()->where($where)->value('content');
        if ($countContent) {
            $douZhuan = 0;
            $coinGift = 0;
            $uidKey = CoindetailModel::getInstance()->getModel()->where($where)->group('content')->order('id desc')->limit($page, $pagenum)->column('content,count(content) count,coin,giftid');
            $data = [];
            foreach ($uidKey as $k => $v) {
                if ($v['count'] >= 3) {
                    $key = explode(':', $v['content']);
                    $keys = sprintf($key[0] . ':' . $key[1] . ':' . $key[2]);
                    $data[] = $redis->hGetAll($keys);
                }
            }
            $giftUid = CoindetailModel::getInstance()->getModel()->where($where)->group('content')->order('id desc')->column('content,count(content) count,coin,giftid');
            foreach ($giftUid as $k => $v) {
                if ($v['count'] >= 3) {
                    $coinGift += GiftModel::getInstance()->getModel()->where('id', $v['giftid'])->value('gift_coin');
                }
            }
            if ($data) {
                foreach ($data as $k => $v) {
                    $userid = json_decode($v['seatInfos'], true);
                    $winnerIndex = $userid[$v['winnerIndex']]['userId'];
                    $data[$k]['uid'] = $winnerIndex;
                    $data[$k]['aid'] = $userid[0]['userId'];
                    $data[$k]['bid'] = $userid[1]['userId'];
                    $data[$k]['cid'] = $userid[2]['userId'];
                    $data[$k]['nickname'] = MemberModel::getInstance()->getModel($winnerIndex)->where('id', $winnerIndex)->value('nickname');
                    $data[$k]['avatar'] = config('config.APP_URL_image') . MemberModel::getInstance()->getModel($winnerIndex)->where('id', $winnerIndex)->value('avatar');
                    $data[$k]['gift'] = config('config.APP_URL_image') . $v['giftImage'];
                    $data[$k]['type'] = $pool_type == 'min' ? '小' : ($pool_type == 'mid' ? '中' : '大');
                    $data[$k]['addtime'] = date('Y-m-d H:i', $v['createTime']);
                }
            }
            $uidTui = CoindetailModel::getInstance()->getModel()->where($where1)->count();
            $uidZhuan = CoindetailModel::getInstance()->getModel()->where($where)->count();
            $douTui = CoindetailModel::getInstance()->getModel()->where($where1)->value('sum(coin)');
            $douZhuan = CoindetailModel::getInstance()->getModel()->where($where)->value('sum(coin)');
            $maxId = CoindetailModel::getInstance()->getModel()->where($where)->group('content')->order('id desc')->count();
            return ['data' => $data, 'uidZhuan' => $uidZhuan, 'douZhuan' => $douZhuan, 'uidTui' => $uidTui, 'douTui' => $douTui, 'coinGift' => $coinGift, 'maxId' => $maxId];
        } else {
            return ['data' => 0, 'uidZhuan' => 0, 'douZhuan' => 0, 'uidTui' => 0, 'douTui' => 0, 'coinGift' => 0, 'maxId' => 0];
        }
    }

    /********************************* 福星降临 瓜分番茄豆 ***********************************/
    public function shavePoints()
    {
        $key = 'luck_star_config';
        $redis = RedisCommon::getInstance()->getRedis();
        $res = $redis->hGetAll($key);
        if (count($res) > 1) {
            $partition_time_str = substr($res['partition_time'], 1, strlen($res['partition_time']) - 1);
            $partition_time_str = substr($partition_time_str, 0, strlen($partition_time_str) - 1);

            $partition_time_str = explode(',', $partition_time_str);

            $partition_date = substr($res['partition_date'], 1, strlen($res['partition_date']) - 1);
            $partition_date = substr($partition_date, 0, strlen($partition_date) - 1);
            $partition_date = explode(',', $partition_date);

            foreach ($partition_time_str as $key => $val) {
                $vals = substr($val, 1, strlen($val) - 1);
                $partition_time_str[$key] = substr($vals, 0, strlen($vals) - 1);
            }
            $res['partition_time'] = $partition_time_str;
            $res['partition_date'] = $partition_date;
            $res['partition_rate'] = json_decode($res['partition_rate'], true);
        } else {
            $res['partition_time'] = ['2021-03-05 00:00', '2021-03-06 00:00'];
            $res['first_partition_time'] = '2021-03-05 00:00:00';
            $res['end_time'] = '2021-03-05 23:59:59';
            $res['init_pool_value'] = 3000000;
            $res['last_partition_time'] = '2021-03-06 00:00:00';
            $res['rate'] = 1;
            $res['partition_rate'] = [0.4, 0.2, 0.15, 0.05, 0.03, 0.03, 0.03, 0.03, 0.03, 0.03];
            $res['partition_date'] = ['2021-03-04', '2021-03-05'];
            $res['start_time'] = '2021-03-04 00:00:00';
        }

        return $res;
    }
    /**
     * @param $startdate 开始日期
     * @param $enddate  结束日期
     * @return array
     * 获取指定之间段内的每一天的日期
     */
    public function getDateFromRange($startdate, $enddate)
    {
        $stimestamp = strtotime($startdate);
        $etimestamp = strtotime($enddate);
        // 计算日期段内有多少天
        $days = ($etimestamp - $stimestamp) / 86400 + 1;
        // 保存每天日期
        $date = array();
        for ($i = 0; $i < $days - 1; $i++) {
            $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
        }
        return $date;
    }

}

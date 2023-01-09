<?php

namespace app\admin\controller;

ini_set('memory_limit', -1);
use app\admin\common\AdminBaseController;
use app\admin\model\GiftModel;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

class BoxGiftController extends AdminBaseController
{
    /**
     * 用户拥有礼物列表
     * @return string
     * @throws \Exception
     */
    public function BoxGiftList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;

        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $gift_name = Request::param('gift_name'); //装扮名称
        $type = Request::param('type'); //状态
        $is_time = Request::param('is_time'); //状态

        $sql = '';
        $gift_name_names = $array = array();
        $giftArr = GiftModel::getInstance()->getModel()->select()->toArray();
        foreach ($giftArr as $k => $v) {
            $gift_name_arr[$v['id']] = $v['gift_name'];
            $gift_name_names[$v['gift_name']] = $v['id'];
        }

        $where = [];
        $where[] = ['event_id', '=', 10009];
        $where[] = ['type', '=', 3];
        $where[] = ['ext_1', '=', 'box'];
        $where[] = ['success_time', '>=', strtotime($start)];
        $where[] = ['success_time', '<', strtotime($end)];

        if ($gift_name) {
            $where[] = ['asset_id', '=', $gift_name_names[$gift_name]];
        }

        if ($type != 0) {
            $where[] = ['type', '=', $type];
        }
        $arr = Db::table(getTable($start, $end))->where($where)->select()->toArray();

        $result = array();

        foreach ($arr as $keys => $val) {
            $result[$val['giftid']]['giftid'] = (int) $val['giftid'];
            $result[$val['giftid']]['type'] = $val['type'] == 'silver' ? 2 : 1;
            if (!isset($result[$val['giftid']]['num'])) {
                $result[$val['giftid']]['num'] = 1;
            } else {
                $result[$val['giftid']]['num'] += 1;
            }

            $array[] = $val['giftid'];
        }

        foreach ($result as $k => $v) {
            $result[$k]['gift_name'] = $gift_name_arr[$v['giftid']];
            $result[$k]['giftid'] = $v['giftid'];
        }
        //PoolNumPropKey_G  个人金宝箱
        //PoolNumPropFullKey_G  全局金宝箱
        //PoolNumPropKey_S   个人银宝箱
        //PoolNumPropFullKey_S  全局银宝箱
        $redis = $this->getRedis();
        $PoolNumPropKey_G = $redis->get('PoolNumPropKey_G');
        $PoolNumPropFullKey_G = $redis->get('PoolNumPropFullKey_G');
        $PoolNumPropKey_S = $redis->get('PoolNumPropKey_S');
        $PoolNumPropFullKey_S = $redis->get('PoolNumPropFullKey_S');
        if ($PoolNumPropKey_G) {
            View::assign('PoolNumPropKey_G', $PoolNumPropKey_G);
        } else {
            View::assign('PoolNumPropKey_G', 0);
        }
        if ($PoolNumPropFullKey_G) {
            View::assign('PoolNumPropFullKey_G', $PoolNumPropFullKey_G);
        } else {
            View::assign('PoolNumPropFullKey_G', 0);
        }
        if ($PoolNumPropKey_S) {
            View::assign('PoolNumPropKey_S', $PoolNumPropKey_S);
        } else {
            View::assign('PoolNumPropKey_S', 0);
        }
        if ($PoolNumPropFullKey_S) {
            View::assign('PoolNumPropFullKey_S', $PoolNumPropFullKey_S);
        } else {
            View::assign('PoolNumPropFullKey_S', 0);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = 0;
        View::assign('list', $result);
        View::assign('type', $type);
        View::assign('demo', $demo);
        View::assign('is_time', $is_time);
        View::assign('gift_name', $gift_name);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('boxgift/index');
    }
}

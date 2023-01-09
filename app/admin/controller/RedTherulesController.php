<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ActivityRedModel;
use app\admin\model\GiftModel;
use app\admin\model\RedPacketsDetailModel;
use app\admin\model\RedPacketsModel;
use app\admin\model\RedTherulesModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class RedTherulesController extends AdminBaseController
{
    /**
     * @return mixed
     * @红包活动列表
     */
    public function activityRed()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        //统计用户条数
        $where = [];
        $count = ActivityRedModel::getInstance()->getModel()->where($where)->count();
        $data = ActivityRedModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        $image_url = config('config.APP_URL_image');
        foreach ($data as $k => $v) {
            $data[$k]['image'] = $image_url . $v['image'];
            $data[$k]['grabbutton'] = $image_url . $v['grabbutton'];
            $data[$k]['progressbar_image'] = $image_url . $v['progressbar_image'];
            $data[$k]['animation'] = $image_url . $v['animation'];
            $data[$k]['create'] = date('m-d H:i', strtotime($v['create_time']));
            $data[$k]['update'] = date('m-d H:i', strtotime($v['update_time']));
        }
        Log::record('用户列表查询:操作人:' . $this->token['username'], 'memberList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('用户管理列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('redtherules/activityRed/index');
    }

    public function addActivityRed()
    {
        $id = Request::param('id');
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $create_time = strtotime($start);
        $update_time = strtotime($end);
        $giftid = Request::param('giftid');
        $grabbutton = Request::param('grabbutton');
        $image = Request::param('image');
        $animation = Request::param('animation');
        $progressbar_image = Request::param('progressbar_image');
        $where[] = ['giftid', '=', $giftid];
        $where[] = ['update_time', '>', $create_time];
        $valIs = ActivityRedModel::getInstance()->getModel()->where($where)->value('id');
        if ($valIs) {
            echo json_encode(['code' => 500, 'msg' => '此礼物存在并活动时间冲突']);die;
        }

        if ($id) {
            $data = [
                'name' => Request::param('name'),
                'money' => Request::param('money'),
                'proportion' => Request::param('proportion'),
                'red_number' => Request::param('red_number'),
                'create_time' => $create_time,
                'update_time' => $update_time,
            ];

            if ($image) {
                $data['image'] = $image;
            }
            if ($progressbar_image) {
                $data['progressbar_image'] = $progressbar_image;
            }
            if ($animation) {
                $data['animation'] = $animation;
            }
            if ($grabbutton) {
                $data['grabbutton'] = $grabbutton;
            }
            $is = ActivityRedModel::getInstance()->getModel()->where('id', $id)->save($data);
        } else {
            if (!$grabbutton) {
                echo json_encode(['code' => 500, 'msg' => '按钮图片片选']);die;
            }
            if (!$image) {
                echo json_encode(['code' => 500, 'msg' => '红包图片片选']);die;
            }
            if (!$progressbar_image) {
                echo json_encode(['code' => 500, 'msg' => '进度条图片选']);die;
            }
            if (!$animation) {
                echo json_encode(['code' => 500, 'msg' => '动画条图片选']);die;
            }
            $data = [
                'name' => Request::param('name'),
                'money' => Request::param('money'),
                'proportion' => Request::param('proportion'),
                'red_number' => Request::param('red_number'),
                'create_time' => $create_time,
                'update_time' => $update_time,
                'image' => $image,
                'grabbutton' => $grabbutton,
                'progressbar_image' => $progressbar_image,
                'animation' => $animation,
            ];

            $is = ActivityRedModel::getInstance()->getModel()->insert($data);
        }

        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function activityRedDetails()
    {
        $id = Request::param('id');
        $data = ActivityRedModel::getInstance()->getModel()->where('id', $id)->value('giftid');
        $list = [];
        if ($data) {
            foreach (json_decode($data) as $k => $v) {
                $list[$k]['id'] = $v;
                $list[$k]['gift_name'] = GiftModel::getInstance()->getModel()->where('id', $v)->value('gift_name');
                $list[$k]['gift_image'] = config('config.APP_URL_image') . GiftModel::getInstance()->getModel()->where('id', $v)->value('gift_image');
            }
        }

        $gift = GiftModel::getInstance()->getModel()->column('id,gift_name');
        View::assign('list', $list);
        View::assign('gift', $gift);
        View::assign('id', $id);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('redtherules/activityRed/activityRedDetails');
    }

    public function actRedGiftAdd()
    {
        $id = Request::param('id');
        $gift = Request::param('gift');
        $is = ActivityRedModel::getInstance()->getModel()->where('id', $id)->save(['giftid' => json_encode(array_merge(json_decode(ActivityRedModel::getInstance()->getModel()->where('id', $id)->value('giftid')), [$gift]))]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']); //php编译join
        }
    }

    public function actRedGiftDel()
    {
        $id = Request::param('id');
        $gift = Request::param('gift');
        $is = ActivityRedModel::getInstance()->getModel()->where('id', $id)->save(['giftid' => json_encode(array_diff(json_decode(ActivityRedModel::getInstance()->getModel()->where('id', $id)->value('giftid')), [$gift]))]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']); //php编译join
        }
    }

    /**
     * 红包规则列表
     * @return string
     * @throws \Exception
     */
    public function RedTherulesList()
    {
        $list = RedTherulesModel::getInstance()->getModel()->where('id', 1)->field('red_packets')->select()->toArray();
        $conf = json_decode($list[0]['red_packets'], true);
        $coin_android = $conf['coin_android'];
        $coin_conf = $conf['coin_conf'];
        View::assign('coin_android', $coin_android);
        View::assign('coin_conf', $coin_conf);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('redtherules/index');
    }

    public function AddRedCoin()
    {
        $list = RedTherulesModel::getInstance()->getModel()->where('id', 1)->field('red_packets')->select()->toArray();
        $conf = json_decode($list[0]['red_packets'], true);
        foreach (Request::param('price') as $key => $value) {
            if ($value != 0 && $value != '') {
                $coin_android[] = intval($value);
                $coin_conf[] = intval($value);
            }
            sort($coin_android);
            sort($coin_conf);
        }
        $conf['coin_android'] = $coin_android;
        $conf['coin_conf'] = $coin_conf;
        $data = json_encode($conf);
        $is = RedTherulesModel::getInstance()->getModel()->where('id', 1)->save(['red_packets' => $data]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']); //php编译join
        }
    }

    /**
     * @return mixed
     * 发红包列表
     */
    public function RedPackets()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;

        $send_uid = empty(Request::param('send_uid')) ? 0 : Request::param('send_uid'); //发红包人
        $get_uid = empty(Request::param('get_uid')) ? 0 : Request::param('get_uid'); //领红包人
        $room_id = empty(Request::param('room_id')) ? 0 : Request::param('room_id'); //发红包房间
        $demo = Request::param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $where = [];
        $where[] = ['status', '<>', 0];
        if (!empty($send_uid)) {
            $where[] = ['send_uid', '=', $send_uid];
        }
        if (!empty($get_uid)) {
            $where[] = ['get_uid', '=', $get_uid];
        }
        $strtime = strtotime($strtime);
        $endtime = strtotime($endtime);
        $where[] = ['send_time', '>=', $strtime];
        $where[] = ['send_time', '<', $endtime];

        if (!empty($room_id)) {
            $where[] = ['room_id', '=', $room_id];
        }

        $count = RedPacketsModel::getInstance()->getModel()->where($where)->count();
        $list = RedPacketsModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->field('id,red_num,red_countcoin,send_uid,send_time,room_id,type,status')->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['send_time'] = date("Y-m-d H:i", $v['send_time']);
            if ($v['type'] == 1) {
                $list[$k]['type'] = '支付宝';
            } else {
                $list[$k]['type'] = '微信';
            }
            if ($v['status'] == 0) {
                $list[$k]['status'] = '未生效';
            } elseif ($v['status'] == 1) {
                $list[$k]['status'] = '生效，可抢...';
            } elseif ($v['status'] == 2) {
                $list[$k]['status'] = '已抢光';
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('list', $list);
        View::assign('send_uid', $send_uid);
        View::assign('get_uid', $get_uid);
        View::assign('demo', $demo);
        View::assign('room_id', $room_id);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('redtherules/eedpackets');
    }

    /**
     * @return mixed
     * 红包详情列表
     */
    public function RedpacketsDetail()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;

        $id = empty(Request::param('id')) ? 0 : Request::param('id'); //红包id
        $where = [];
        if (!empty($id)) {
            $where[] = ['red_id', '=', $id];
        }

        $count = RedPacketsDetailModel::getInstance()->getModel()->where($where)->count();
        $list = RedPacketsDetailModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->field('red_id,get_uid,get_time,get_coin,is_get')->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['get_time'] = date("Y-m-d H:i", $v['get_time']);
            if ($v['is_get'] == 1) {
                $list[$k]['is_get'] = '已领取';
            } else {
                $list[$k]['is_get'] = '未领取';
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('list', $list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('redid', $id);
        return View::fetch('redtherules/RedpacketsDetail');
    }
}

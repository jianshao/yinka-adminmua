<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\GiftCollectionListModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\GiftCollectionListService;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class GiftCollectionDetailController extends AdminBaseController
{
    //配置
    function list() {
        $page = Request::param('page', 1);
        $daochu = $this->request->param('daochu');
        $status = $this->request->param('status');
        $coll_id = $this->request->param('coll_id', 0);
        $title = $this->request->param('title', '');

        $where = [];

        if (!$coll_id) {
            return rjson([], 403, '参数错误');
        }
        $where[] = ['coll_id', '=', (int) $coll_id];
        $data = GiftCollectionListService::getInstance()->getList($page, $where);

        foreach ($data['list'] as $_ => &$item) {
            $item['image_url'] = '';
            if ($item['image']) {
                $item['image_url'] = $this->img_url . $item['image'];
            }
        }

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('gifts', GiftsCommon::getInstance()->getGifts());
        View::assign('coll_id', $coll_id);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('giftCollection/detail');
    }

    //添加
    public function add()
    {
        try {
            $gift_id = (int) Request::param('gift_id');
            $coll_id = (int) Request::param('coll_id');
            $image = Request::param('image', '');
            $intro = Request::param('intro', '');
            $seq = (int) Request::param('seq', 0);

            if (strlen($intro) > 60) {
                throw new \Exception("介绍超出范围", 403);
            }

            if (!$coll_id) {
                throw new \Exception("参数错误", 403);
            }

            $gift_info = GiftCollectionListModel::getInstance()->getModel()->where('coll_id', $coll_id)->where('gift_id', $gift_id)->find();

            if ($gift_info) {
                throw new \Exception("当前礼物已存在", 403);
            }

            $data = [];
            $data[0]['gift_id'] = $gift_id;
            $data[0]['coll_id'] = $coll_id;
            $data[0]['seq'] = $seq;
            $data[0]['image'] = $image;
            $data[0]['intro'] = $intro;
            $data[0]['create_time'] = time();
            $data[0]['update_time'] = time();

            GiftCollectionListModel::getInstance()->getModel()->insertAll($data);
            return rjson([], 200, '添加成功');
        } catch (Exception $e) {
            return rjson([], 403, $e->getMessage());
        }
    }

    //编辑
    public function save()
    {
        try {
            $action = Request::param('action', '');
            $gift_id = Request::param('gift_id');
            $image = Request::param('image', '');
            $intro = Request::param('intro', '');
            $seq = Request::param('seq');
            $id = Request::param('id');
            if (strlen($intro) > 60) {
                throw new \Exception("介绍超出范围", 403);
            }

            if ($action == 'delete') {
                $data = ['status' => 0];
                GiftCollectionListModel::getInstance()->getModel()->where('id', $id)->delete();
            } else {
                $data = ['gift_id' => $gift_id, 'seq' => $seq, 'image' => $image, 'intro' => $intro];
                GiftCollectionListModel::getInstance()->getModel()->where('id', $id)->update($data);
            }
            return rjson([], 200, '成功');
        } catch (Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}

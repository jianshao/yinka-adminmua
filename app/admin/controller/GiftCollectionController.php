<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\GiftCollectionListModel;
use app\admin\model\GiftCollectionModel;
use app\admin\service\GiftCollectionService;
use app\common\RedisCommon;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class GiftCollectionController extends AdminBaseController
{
    //配置
    function list() {
        $page = Request::param('page', 1);
        $daochu = $this->request->param('daochu');
        $status = $this->request->param('status');
        $is_show = $this->request->param('is_show', 1);
        $title = $this->request->param('title', '');

        $where = [];

        if ($status > -1) {
            $where[] = ['status', '=', (int) $status];
        } else {
            $where[] = ['status', '<>', 2];
        }
        $data = GiftCollectionService::getInstance()->getList($page, $where);
        foreach ($data['list'] as $_ => &$item) {
            $item['gift_count'] = GiftCollectionListModel::getInstance()->getModel()->where('coll_id', $item['id'])->count();
        }
        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('app_types', array_column(config('config.APP_TYPE_MAP'), null, 'source'));
        View::assign('status', $status);
        View::assign('title', $title);
        View::assign('is_show', $is_show);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
        }
        return View::fetch('giftCollection/list');
    }

    //添加
    public function add()
    {
        try {
            $title = Request::param('title');
            $seq = Request::param('seq');
            $is_show = Request::param('is_show');

            $data = [];
            $data[0]['title'] = $title;
            $data[0]['seq'] = $seq;
            $data[0]['is_show'] = $is_show;
            $data[0]['status'] = 1;
            $data[0]['create_time'] = time();
            $data[0]['update_time'] = time();

            GiftCollectionModel::getInstance()->getModel()->insertAll($data);

            return rjson([], 200, '添加成功');
        } catch (\Throwable $th) {
            return rjson([], 403, '添加失败');
        }
    }

    //编辑
    public function save()
    {
        try {
            $action = Request::param('action', '');
            $title = Request::param('title');
            $is_show = Request::param('is_show');
            $seq = Request::param('seq');
            $id = Request::param('id');

            if ($action == 'delete') {
                $data = ['status' => 0];
            } else {
                $data = ['title' => $title, 'is_show' => $is_show, 'seq' => $seq];
            }

            GiftCollectionModel::getInstance()->getModel()->where('id', $id)->update($data);
            return rjson([], 200, '修改成功');
        } catch (\Throwable $th) {
            return rjson([], 403, '修改失败');
        }
    }

    public function online()
    {
        try {
            $gift_collection = GiftCollectionModel::getInstance()->getModel()
                ->where('status', 1)
                ->where('is_show', 1)
                ->order('seq desc')
                ->column('id,title');

            $data = [];
            foreach ($gift_collection as $key => $collection) {
                $coll_id = $collection['id'];
                $gifts = GiftCollectionListModel::getInstance()->getModel()
                    ->field('gift_id kindId,intro collectionDesc,image collectionImg')
                    ->where('coll_id', $coll_id)
                    ->order('seq desc')
                    ->select()
                    ->toArray();

                $data[$key]['displayName'] = $collection['title'];
                $data[$key]['gifts'] = $gifts;
            }

            RedisCommon::getInstance()->getRedis(['select' => 3])->set('gift_collection_conf', json_encode(array_values($data)));
            return rjson([], 200, '成功');
        } catch (\Throwable $th) {
            return rjson([], 403, '失败');
        }
    }
}

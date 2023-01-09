<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ImBgImgModel;
use app\admin\service\ImBgImgService;
use app\common\RedisCommon;
use Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ImBgImgController extends AdminBaseController
{
    //配置
    function list() {
        $page = Request::param('page', 1);

        $where = [];

        $data = ImBgImgService::getInstance()->getList($page, $where);

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
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('imBg/detail');
    }

    //添加
    public function add()
    {
        try {
            $status = (int) Request::param('status');
            $image = Request::param('image', '');
            $intro = Request::param('intro', '');
            $seq = (int) Request::param('seq', 0);

            if (strlen($intro) > 60) {
                throw new \Exception("介绍超出范围", 403);
            }

            $data = [];
            $data[0]['status'] = $status;
            $data[0]['seq'] = $seq;
            $data[0]['image'] = $image;
            $data[0]['intro'] = $intro;
            $data[0]['create_time'] = time();
            $data[0]['update_time'] = time();

            ImBgImgModel::getInstance()->getModel()->insertAll($data);
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
            $status = Request::param('status');
            $image = Request::param('image', '');
            $intro = Request::param('intro', '');
            $seq = Request::param('seq');
            $id = Request::param('id');
            if (strlen($intro) > 60) {
                throw new \Exception("介绍超出范围", 403);
            }

            if ($action == 'delete') {
                $data = ['status' => 0];
                ImBgImgModel::getInstance()->getModel()->where('id', $id)->delete();
            } else {
                $data = ['status' => $status, 'seq' => $seq, 'image' => $image, 'intro' => $intro];
                ImBgImgModel::getInstance()->getModel()->where('id', $id)->update($data);
            }
            return rjson([], 200, '成功');
        } catch (Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function online()
    {
        try {
            $data = ImBgImgModel::getInstance()->getModel()
                ->field('id,intro title,image')
                ->where('status', 1)
                ->order('seq desc')
                ->order('id desc')
                ->select()
                ->toArray();

            RedisCommon::getInstance()->getRedis(['select' => 3])->set('im_background_conf', json_encode($data));
            return rjson([], 200, '成功');
        } catch (\Throwable $th) {
            return rjson([], 403, '失败');
        }
    }
}

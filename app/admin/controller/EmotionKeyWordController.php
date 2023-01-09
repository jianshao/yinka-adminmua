<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\EmotionKeywordModel;
use app\admin\service\EmotionKeywordService;
use Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class EmotionKeyWordController extends AdminBaseController
{
    //配置
    public function getlist()
    {
        $page = Request::param('page', 1);
        $keyword = Request::param('keyword', '');

        $where = [];
        if ($keyword) {
            $where[] = ['keyword', 'like', '%' . $keyword . '%'];
        }

        $data = EmotionKeywordService::getInstance()->getList($page, $where);

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('keyword', $keyword);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('imBg/emotionKeyword');
    }

    //添加
    public function add()
    {
        try {
            $keyword = Request::param('keyword', '');

            $data = [];
            $data['keyword'] = $keyword;
            $data['keyword_hash'] = md5($keyword);
            $data['create_time'] = time();
            $data['admin_id'] = $this->token['id'];

            EmotionKeywordModel::getInstance()->getModel()->insert($data);
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
            $keyword = Request::param('keyword', '');
            $id = Request::param('id');

            if ($action == 'delete') {
                EmotionKeywordModel::getInstance()->getModel()->where('id', $id)->delete();
            } else {
                $data = ['keyword' => $keyword];
                EmotionKeywordModel::getInstance()->getModel()->where('id', $id)->update($data);
            }
            return rjson([], 200, '成功');
        } catch (Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}

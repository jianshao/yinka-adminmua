<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\RedisKeysConst;
use app\admin\model\AnchorSearchModel;
use app\admin\service\AnchorSearchService;
use app\admin\service\MemberService;
use app\common\RedisCommon;
use Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class AnchorController extends AdminBaseController
{
    //配置
    public function getlist()
    {
        $page = Request::param('page', 1);
        $uid = Request::param('uid', '');

        $where = [];
        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }

        $data = AnchorSearchService::getInstance()->getList($page, $where);

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('uid', $uid);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('imBg/anchor');
    }

    //添加
    public function add()
    {
        try {
            $uid = Request::param('uid', '');
            $seq = (int) Request::param('seq', 0);

            //校验用户是否存在
            MemberService::getInstance()->checkUser($uid);

            //校验用户是否存在
            AnchorSearchService::getInstance()->checkAnchorExists([['uid', '=', $uid]], true);

            $data = [];
            $data[0]['seq'] = $seq;
            $data[0]['uid'] = $uid;
            $data[0]['create_time'] = time();
            $data[0]['update_time'] = time();

            //设置redis
            $res = RedisCommon::getInstance()->getRedis()->zadd(RedisKeysConst::SEARCH_HOT_ANCHOR_BUCKET, $seq, $uid);
            if ($res) {
                AnchorSearchModel::getInstance()->getModel()->insertAll($data);
            }
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
            $uid = Request::param('uid', '');
            $seq = Request::param('seq');
            $id = Request::param('id');

            //校验用户是否存在
            AnchorSearchService::getInstance()->checkAnchorExists([['uid', '=', $uid], ['id', '<>', $id]], true);

            $info = AnchorSearchService::getInstance()->checkAnchorNotExists([['id', '=', $id]], true);
            $res = RedisCommon::getInstance()->getRedis()->zrem(RedisKeysConst::SEARCH_HOT_ANCHOR_BUCKET, $uid);
            if ($action == 'delete') {
                $uid = $info->uid;
                AnchorSearchModel::getInstance()->getModel()->where('id', $id)->delete();
            } else {
                $data = ['seq' => $seq, 'uid' => $uid];
                $res = RedisCommon::getInstance()->getRedis()->zadd(RedisKeysConst::SEARCH_HOT_ANCHOR_BUCKET, $seq, $uid);
                AnchorSearchModel::getInstance()->getModel()->where('id', $id)->update($data);
            }
            return rjson([], 200, '成功');
        } catch (Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}

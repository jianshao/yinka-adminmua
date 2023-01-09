<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ChannelVersionModel;
use app\admin\service\ChannelVersionService;
use app\common\RedisCommon;
use think\App;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ChannelVersionController extends AdminBaseController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    //配置
    function list() {
        $page = Request::param('page', 1);
        $daochu = $this->request->param('daochu');
        $status = $this->request->param('status', -1);
        $app_type = $this->request->param('app_type', '');
        $channel_name = $this->request->param('channel_name', '');

        $where = [];
        if ($channel_name) {
            $where[] = ['channel_name', 'like', $channel_name];
        }
        if ($app_type) {
            $where[] = ['app_type', '=', $app_type];
        }

        if ($status > -1) {
            $where[] = ['status', '=', (int) $status];
        } else {
            $where[] = ['status', '<>', 2];
        }
        $data = ChannelVersionService::getInstance()->getList($page, $where);

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('channel_name', $channel_name);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('app_types', array_column(config('config.APP_TYPE_MAP'), null, 'source'));
        View::assign('app_type', $app_type);
        View::assign('status', $status);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
        }
        return View::fetch('version/ChannelVersionList');
    }

    //添加
    public function add()
    {
        try {
            $channel_name = Request::param('channel_name');
            $status = Request::param('status');
            $app_type = Request::param('app_type');
            $app_version = Request::param('app_version');

            $data = [];
            $count = count($channel_name);

            for ($i = 0; $i < $count; $i++) {
                $data[$i]['channel_name'] = $channel_name[$i];
                $data[$i]['status'] = $status[$i];
                $data[$i]['app_type'] = $app_type[$i];
                $data[$i]['app_version'] = $app_version[$i];
            }
            ChannelVersionService::getInstance()->addOrUpdate($data);
            $this->_updateCache();
            return rjson([], 200, '添加成功');
        } catch (\Throwable $th) {
            return rjson([], 403, '添加失败');
        }
    }

    //编辑
    public function save()
    {
        try {
            $channel_name = Request::param('channel_name');
            $app_version = Request::param('app_version');
            $status = Request::param('status');
            $id = Request::param('id');
            ChannelVersionService::getInstance()->addOrUpdate(['channel_name' => $channel_name, 'app_version' => $app_version, 'status' => $status], ['id', '=', $id]);
            $this->_updateCache();
            return rjson([], 200, '修改成功');
        } catch (\Throwable $th) {
            return rjson([], 403, '修改失败');
        }
    }

    //通过
    public function agree()
    {
        try {
            $id = (int) Request::param('id');
            ChannelVersionService::getInstance()->addOrUpdate(['status' => 1], ['id', '=', $id]);
            $this->_updateCache();
            return rjson([], 200, '修改成功');
        } catch (\Throwable $th) {
            return rjson([], 403, '修改失败');
        }
    }

    private function _updateCache()
    {
        $res = ChannelVersionModel::getInstance()->getModel()->select()->toArray();
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $redis->set('channel_version_conf', json_encode($res));
    }
}

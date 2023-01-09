<?php

/**
 * Created by PhpStorm.
 * User: pussycat
 * Date: 2019/7/23
 * Time: 21:01
 */

namespace app\admin\controller;

ini_set('memory_limit', '1024M');

use app\admin\common\AdminBaseController;
use app\admin\model\BlackIndustryModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class BlackIndustryController extends AdminBaseController
{
    // 用户触发事件列表
    public function getUserScore()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = Request::param('user_id');
        $detail = Request::param('detail');

        $where[] = ['user_id', '<>', '0'];
        if ($uid) {
            $where[] = ['user_id', '=', $uid];
        }

        try {
            if ($detail) {
                $data = BlackIndustryModel::getInstance()->getModel()->where($where)->order('id', 'desc')->limit($page, $pagenum)->select()->toArray();
                $count = count($data);
            } else {
                $ids = BlackIndustryModel::getInstance()->getModel()->field("max(id) id")->where($where)->group("user_id")->select()->toArray();
                $ids = array_column($ids, "id");
                $data = BlackIndustryModel::getInstance()->getModel()->whereIn('id', $ids)->order('id', 'desc')->limit($page, $pagenum)->select()->toArray();
                $count = count($ids);
            }
        } catch (\Throwable $e) {
            $data = [];
            $count = 0;
        }

        Log::record('防黑产事件列表查询:操作人:' . $this->token['username'], 'blackIndustry');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('search_id', $uid);
        View::assign('detail', $detail);
        View::assign('token', $this->request->param('token'));
        return View::fetch('member/blackIndustry');
    }

}
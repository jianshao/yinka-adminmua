<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\AdminLogsService;
use think\facade\Log;
use think\facade\View;

class AdminLogsController extends AdminBaseController
{
    public function getAdminLogsList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $list = AdminLogsService::getInstance()->getAdminLogsList(array(), '*', array($page, $pagenum));
        $num = 0;
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['created_time'] = date('Y-m-d H:i:s', $value['created_time']);
            }
            $num = AdminLogsService::getInstance()->getAdminLogsCountNum(array());
        }
        Log::record('日志列表:操作人:' . $this->token['username'], 'getAdminLogsList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('logs/index');
    }
}
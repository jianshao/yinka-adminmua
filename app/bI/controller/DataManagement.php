<?php

namespace app\bI\controller;

use app\bI\common\BIBaseController;
use app\bI\model\BIDataModel;
use think\facade\View;

class DataManagement extends BIBaseController
{
    public function index()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $strtime = $this->request->param('strtime');
        $endtime = $this->request->param('endtime');
        if ($strtime) {
            $strtime = explode(' ', $this->request->param('strtime'))[0];
        }
        if ($endtime) {
            $endtime = explode(' ', $this->request->param('endtime'))[0];
        } else {
            $endtime = date('Y-m-d');
        }
        $where = [];
        if ($strtime) {
            if (strtotime($strtime) > strtotime($endtime)) {
                echo $this->return_json(\constant\CodeConstant::CODE_结束时间必须大于开始时间, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_结束时间必须大于开始时间]);
                die;
            }
            $where = [['riq', '>=', strtotime($strtime)]];
        }

        $where = array_merge($where, [['riq', '<=', strtotime($endtime)]]);
        $list = BIDataModel::getInstance()->getBIDataByWhereList($where, '*', array($page, $pagenum));
        $num = 0;
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['riq'] = date('Y-m-d', $v['riq']);
            }
            //查询总数
            $num = BIDataModel::getInstance()->getBIDataByWhereCount($where);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->userinfo['token']);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        return View::fetch('dataManagement/index');
    }
}
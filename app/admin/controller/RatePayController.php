<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\BiChargeModel;
use think\facade\Request;
use think\facade\View;

class RatePayController extends AdminBaseController
{
    /**
     * 充值续费率记录
     * @param string $value [description]
     */
    public function ratePayList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $default_date = date('Y-m-d', strtotime("-7 days")) . ' - ' . date('Y-m-d');
        $demo = $this->request->param('demo', $default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');
        $where = [];
        $where[] = ['riq', '>=', strtotime($strtime)];
        $where[] = ['riq', '<', strtotime($endtime)];
        $field = 'riq,cinczl,sannczl,qinczl,ciczl,sanczl,qiczl';
        if ($daochu == 1) {
            $list = BiChargeModel::getInstance()->getModel()->field($field)->where($where)->order('id desc')->select();
        } else {
            $list = BiChargeModel::getInstance()->getModel()->field($field)->where($where)->limit($page, $pagenum)->order('id desc')->select();
        }
        $num = 0;
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $k => $v) {
                $list[$k]['riq'] = date('Y-m-d', $v['riq']);
            }
            //查询总数
            $num = BiChargeModel::getInstance()->getModel()->where($where)->count();
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
            $this->putcsv($list);
        }
        return View::fetch('charge/ratepay');
    }

    //导出csv
    public function putcsv($data)
    {
        $headerArray = ['日期', '新增次日充值率', '新增三日充值率', '新增七日充值率', '次日充值率', '三日充值率', '七日充值率'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['riq'] = $value['riq'];
            $outArray['cinczl'] = ($value['cinczl'] / 100) . "%";
            $outArray['sannczl'] = ($value['sannczl'] / 100) . "%";
            $outArray['qinczl'] = ($value['qinczl'] / 100) . "%";
            $outArray['ciczl'] = ($value['ciczl'] / 100) . "%";
            $outArray['sanczl'] = ($value['sanczl'] / 100) . "%";
            $outArray['qiczl'] = ($value['qiczl'] / 100) . "%";
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = date('Ymd') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

}

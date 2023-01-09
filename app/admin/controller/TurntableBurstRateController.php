<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\BoxService;
use app\admin\service\ExportExcelService;
use app\admin\service\TurntableBurstRateService;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

class TurntableBurstRateController extends AdminBaseController
{
    public function RealTimeRate()
    {
        $pagenum = 20;
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;

        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $daochu = (int) Request::param('daochu', 0);
        $uid = (int) Request::param('uid');
        $turntableId = (int) Request::param('turntableId', 1);

        //渠道数据用户数据
        $conditions[] = [
            ['date', '>=', $start],
            ['date', '<', $end],
            ['json_data', 'like', '%turntable%'],
        ];

        if ($uid) {
            $conditions[] = ['uid', '=', $uid];
        }

        $day_datas = Db::table('bi_user_stats_1day')->where($conditions)->select()->toArray();

        $res = BoxService::getBoxData($day_datas, 'turntable', $turntableId);
        $res_ = $res[$turntableId];
        $consumption = $res_["consumption"];
        $output = $res_["output_amount"];
        $explodeRate = $res_["explodeRate"];

        $count = count($res_['data']);

        $turntable_data = [];
        foreach ($res_['data'] as $k => $item) {
            $turntable_data[$k]['date'] = $item['date'];
            $turntable_data[$k]['uid'] = $item['uid'];
            $turntable_data[$k]['turntableId'] = $turntableId;
            $turntable_data[$k]['consumption'] = $item["consume_amount"];
            $turntable_data[$k]['output'] = $item["output_amount"];
            $turntable_data[$k]['explodeRate'] = $item["explodeRate"];
        }

        $data = [];
        $data = array_slice($turntable_data, $offset, $pagenum);
        if ($daochu == 1) {
            $columns = [
                'date' => '日期',
                'uid' => '用户Id',
                'turntableId' => '转盘Id',
                'consumption' => '消耗',
                'output' => '产出',
                'explodeRate' => '爆率',
            ];
            ExportExcelService::getInstance()->export($turntable_data, $columns);
        }

        $page_info['page'] = $page;
        $page_info['total_page'] = (int) ceil($count / $pagenum);
        View::assign('page', $page_info);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('turntableId', $turntableId);
        View::assign('consumption', $consumption);
        View::assign('output', $output);
        View::assign('explodeRate', $explodeRate);
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('turntable/RealTimeRate');
    }

    public function cancelTheSpecifiedTurntableGift()
    {
        echo TurntableBurstRateService::getInstance()->cancelTheSpecifiedTurntableGift(Request::param());
    }

    public function addTheSpecifiedTurntableGift()
    {
        $username = $this->token['username'];
        echo TurntableBurstRateService::getInstance()->addTheSpecifiedTurntableGift(Request::param(), $username);
    }

    public function TheSpecifiedTurntableGift()
    {
        $data = TurntableBurstRateService::getInstance()->TheSpecifiedTurntableGift(Request::param());
        View::assign('data', $data['data']);
        View::assign('demo', $data['demo']);
        View::assign('uid', $data['uid']);
        View::assign('type', $data['type']);
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('turntable/TheSpecifiedTurntableGift');
    }

    public function TurntableDetails()
    {
        $data = TurntableBurstRateService::getInstance()->TurntableDetails(Request::param());
        View::assign('change_amount', $data['change_amount']);
        View::assign('asset_id', $data['asset_id']);
        View::assign('data', $data['data']);
        View::assign('demo', $data['demo']);
        View::assign('uid', $data['uid']);
        View::assign('type', $data['type']);
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('turntable/TurntableDetails');
    }

    public function TurntableBurstRate()
    {
        $data = TurntableBurstRateService::getInstance()->TurntableBurstRate(Request::param());
        View::assign('order', $data['order']);
        View::assign('info', $data['info']);
        View::assign('data', $data['data']);
        View::assign('demo', $data['demo']);
        View::assign('uid', $data['uid']);
        View::assign('type', $data['type']);
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('turntable/TurntableBurstRate');
    }

}
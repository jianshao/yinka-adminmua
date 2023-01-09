<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\GameConst;
use app\admin\model\BoxSpecialGiftModel;
use app\admin\model\MemberModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\BoxBurstRateService;
use app\admin\service\BoxService;
use app\admin\service\ExportExcelService;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

class BoxBurstRateController extends AdminBaseController
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
        $boxId = (int) Request::param('boxId', 1);

        //渠道数据用户数据
        $conditions[] = [
            ['date', '>=', $start],
            ['date', '<', $end],
            ['json_data', 'like', '%box2%'],
        ];

        if ($uid) {
            $conditions[] = ['uid', '=', $uid];
        }

        $day_datas = Db::table('bi_user_stats_1day')->where($conditions)->select()->toArray();
        $res = BoxService::getBoxData($day_datas, 'box2', $boxId);
        $res_ = $res[$boxId];
        $consumption = $res_["consumption"];
        $output = $res_["output_amount"];
        $explodeRate = $res_["explodeRate"];

        $count = count($res_['data']);

        $turntable_data = [];
        foreach ($res_['data'] as $k => $item) {
            $turntable_data[$k]['date'] = $item['date'];
            $turntable_data[$k]['uid'] = $item['uid'];
            $turntable_data[$k]['boxId'] = $boxId;
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
                'boxId' => '转盘Id',
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
        View::assign('boxId', $boxId);
        View::assign('consumption', $consumption);
        View::assign('output', $output);
        View::assign('explodeRate', $explodeRate);
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/RealTimeRate');
    }

    public function specialGiftLog()
    {
        $pagenum = 20;
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;

        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        $daochu = (int) Request::param('daochu', 0);
        $uid = (int) Request::param('uid');
        $boxId = (int) Request::param('boxId', '');

        //渠道数据用户数据
        $conditions[] = [
            ['create_time', '>=', strtotime($start)],
            ['create_time', '<', strtotime($end)],
        ];

        if ($uid) {
            $conditions[] = ['user_id', '=', $uid];
        }

        if ($boxId) {
            $conditions[] = ['box_id', '=', $boxId];
        }

        $data = BoxSpecialGiftModel::getInstance()->getModel()->field('*,user_id uid')->where($conditions);

        if (!$daochu) {
            $data = $data->limit($offset, $pagenum);
        }

        $data = $data->select()->toArray();

        $count = BoxSpecialGiftModel::getInstance()->getModel()->where($conditions)->count();

        $gift_map = GiftsCommon::getInstance()->getGifts();
        if ($data) {
            $uids = array_column($data, 'uid');
            $memberInfo = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], "id,nickname");
            $member_info_map = array_column($memberInfo, null, 'id');

            foreach ($data as $k => &$item) {
                $item['nickname'] = $member_info_map[$item['uid']]['nickname'];
                $item['box_name'] = GameConst::BOX_TYPE[$item['box_id']];
                $item['gift_name'] = $gift_map[$item['gift_id']];
                $item['time'] = date('Y-m-d H:i:s', $item['create_time']);
            }
        }

        if ($daochu == 1) {
            $columns = [
                'uid' => '用户Id',
                'nickname' => '用户昵称',
                'box_name' => '宝箱名称',
                'gift_name' => '礼物名称',
                'gift_id' => '礼物ID',
                'time' => '获得时间',
            ];
            ExportExcelService::getInstance()->export($data, $columns);
        }

        $page_info['page'] = $page;
        $page_info['total_page'] = (int) ceil($count / $pagenum);
        View::assign('page', $page_info);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('boxId', $boxId);
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/specialGiftLog');
    }

/*    public function cancelTheSpecifiedBoxGift()
{
echo BoxBurstRateService::getInstance()->cancelTheSpecifiedBoxGift(Request::param());
}*/

    /*   public function addTheSpecifiedBoxGift()
    {
    $username = $this->token['username'];
    echo BoxBurstRateService::getInstance()->addTheSpecifiedBoxGift(Request::param(), $username);
    }*/

/*    public function TheSpecifiedBoxGift()
{
$data = BoxBurstRateService::getInstance()->TheSpecifiedBoxGift(Request::param());
View::assign('data', $data['data']);
View::assign('demo', $data['demo']);
View::assign('uid', $data['uid']);
View::assign('type', $data['type']);
View::assign('page', $data['page_array']);
View::assign('token', $this->request->param('token'));
View::assign('user_role_menu', $this->user_role_menu);
return View::fetch('box/TheSpecifiedBoxGift');
}*/

    public function BoxDetails()
    {
        $data = BoxBurstRateService::getInstance()->BoxDetails(Request::param());
        View::assign('change_amount', $data['change_amount']);
        View::assign('asset_id', $data['asset_id']);
        View::assign('data', $data['data']);
        View::assign('demo', $data['demo']);
        View::assign('uid', $data['uid']);
        View::assign('type', $data['type']);
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/BoxDetails');
    }

    public function BoxBurstRate()
    {
        $data = BoxBurstRateService::getInstance()->BoxBurstRate(Request::param());
        View::assign('order', $data['order']);
        View::assign('info', $data['info']);
        View::assign('data', $data['data']);
        View::assign('demo', $data['demo']);
        View::assign('uid', $data['uid']);
        View::assign('type', $data['type']);
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/BoxBurstRate');
    }

    public function TheLottery()
    {
        $pagenum = 20;
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;

        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        $daochu = (int) Request::param('daochu', 0);
        $uid = (int) Request::param('uid');
        $boxId = (string) Request::param('boxId', 'silver');

        //渠道数据用户数据
        $conditions[] = [
            ['date', '>=', $start],
            ['date', '<', $end],
            ['json_data', 'like', '%"box"%'],
        ];

        if ($uid) {
            $conditions[] = ['uid', '=', $uid];
        }

        $day_datas = Db::table('bi_user_stats_1day')->where($conditions)->select()->toArray();
        $res = BoxService::getBoxData($day_datas, 'box', $boxId, 'user:bean');
        $res_ = $res[$boxId];
        $consumption = $res_["consumption"];
        $output = $res_["output_amount"];
        $explodeRate = $res_["explodeRate"];

        $count = count($res_['data']);

        $turntable_data = [];
        foreach ($res_['data'] as $k => $item) {
            $turntable_data[$k]['date'] = $item['date'];
            $turntable_data[$k]['uid'] = $item['uid'];
            $turntable_data[$k]['boxId'] = $boxId;
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
                'boxId' => '宝箱Id',
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
        View::assign('boxId', $boxId);
        View::assign('consumption', $consumption);
        View::assign('output', $output);
        View::assign('explodeRate', $explodeRate);
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/RealTimeRateSilver');
    }

    public function TheLotterj()
    {
        $pagenum = 20;
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;

        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        $daochu = (int) Request::param('daochu', 0);
        $uid = (int) Request::param('uid');
        $boxId = Request::param('boxId', 'gold');

        //渠道数据用户数据
        $conditions[] = [
            ['date', '>=', $start],
            ['date', '<', $end],
            ['json_data', 'like', '%"box"%'],
        ];

        if ($uid) {
            $conditions[] = ['uid', '=', $uid];
        }

        $day_datas = Db::table('bi_user_stats_1day')->where($conditions)->select()->toArray();

        $res = BoxService::getBoxData($day_datas, 'box', $boxId, 'user:bean');
        $res_ = $res[$boxId];
        $consumption = $res_["consumption"];
        $output = $res_["output_amount"];
        $explodeRate = $res_["explodeRate"];

        $count = count($res_['data']);

        $turntable_data = [];
        foreach ($res_['data'] as $k => $item) {
            $turntable_data[$k]['date'] = $item['date'];
            $turntable_data[$k]['uid'] = $item['uid'];
            $turntable_data[$k]['boxId'] = $boxId;
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
                'boxId' => '宝箱Id',
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
        View::assign('boxId', $boxId);
        View::assign('consumption', $consumption);
        View::assign('output', $output);
        View::assign('explodeRate', $explodeRate);
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/RealTimeRateGold');
    }
}
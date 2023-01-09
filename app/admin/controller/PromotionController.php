<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\LanguageroomModel;
use app\admin\model\MemberModel;
use app\admin\model\PromotionModel;
use app\admin\model\PromotionRoomConfModel;
use app\admin\model\PromotionRoomTimesConfModel;
use app\admin\service\ExportExcelService;
use app\admin\service\PromotionService;
use think\App;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class PromotionController extends AdminBaseController
{
    protected $promote_list = [];
    protected $room_list = [];
    protected $promote_room_list = [];

    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->promote_list = PromotionModel::getInstance()->getModel()->column('channel_name', 'id');

        $room_list = LanguageroomModel::getInstance()->getGuildRoomListMap();
        $this->room_list = array_column($room_list, 'room_name', 'id');

        $promote_room_list = PromotionRoomConfModel::getInstance()->getModel()->select()->toArray();

        $this->promote_room_list = array_column($promote_room_list, null, 'id');
    }

    //推广场次配置
    public function getPromotionList()
    {
        $page = Request::param('page', 1);
        $daochu = $this->request->param('daochu');
        $app_type = $this->request->param('app_type');
        $channel_name = $this->request->param('channel_name', '');

        $where[] = ['status', '=', 1];
        if ($channel_name) {
            $where[] = ['channel_name', 'like', $channel_name];
        }

        $data = PromotionService::getInstance()->promotionList($page, $where);

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('channel_name', $channel_name);
        View::assign('room_list', $this->room_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('app_types', config('config.APP_TYPE_MAP'));
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
        }
        return View::fetch('paoliang/promote/PromotionList');
    }

    //推广渠道添加
    public function PromotionAdd()
    {
        $channel_name = Request::param('channel_name');
        $status = Request::param('status');
        $app_type = Request::param('app_type');

        $data = [];
        $count = count($channel_name);

        for ($i = 0; $i < $count; $i++) {
            $data[$i]['channel_name'] = $channel_name[$i];
            $data[$i]['status'] = $status[$i];
            $data[$i]['app_type'] = $app_type[$i];
        }
        PromotionService::getInstance()->addOrUpdatePromotion($data);
    }

    //推广渠道编辑
    public function PromotionSave()
    {
        $channel_name = Request::param('channel_name');
        $status = Request::param('status');
        $id = Request::param('id');
        PromotionService::getInstance()->addOrUpdatePromotion(['channel_name' => $channel_name, 'status' => $status], ['id', '=', $id]);
    }

    //推广房间配置
    public function getPromotionRoomList()
    {
        $page = Request::param('page', 1);
        $daochu = $this->request->param('daochu');
        $room_id = $this->request->param('room_id');
        $promote_id = $this->request->param('promote_id');

        $data = PromotionService::getInstance()->promotionRoomList($page, $room_id, $promote_id, $this->token['id']);

        foreach ($data['list'] as $key => &$promote) {
            $app_type = PromotionModel::getInstance()->getModel()->where('id', $promote['promote_id'])->value('app_type');
            if ($promote['promote_id'] == 21) {
                $promote['land_url'] = "https://newmapi2.muayuyin.com/web/xingTuCallBack" . "?promote_code={$promote['id']}" . "&os=__OS__&ua=__UA__&ip=__IP__&ts=__TS__";
            } else {
                $promote['land_url'] = config('config.APP_TYPE_MAP')[$app_type]['url'] . "?bindType=promote&promoteCode={$promote['id']}";
            }

            $promote['room_name'] = $this->room_list[$promote['room_id']] ?? 0 ;
            $promote['channel_name'] = $this->promote_list[$promote['promote_id']] ?? '';
        }

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('room_id', $room_id);
        View::assign('promote_id', $promote_id);
        View::assign('promote_list', $this->promote_list);
        View::assign('room_list', $this->room_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
        }
        return View::fetch('paoliang/promote/PromotionRoomList');
    }

    //推广渠道房间添加
    public function PromotionRoomAdd()
    {
        $id = Request::param('id');
        $promote_id = Request::param('promote_id');
        $room_id = Request::param('room_id');
        $promote_info = PromotionModel::getInstance()->getModel()->where('id', (int) $promote_id[0])->find();
        if (empty($promote_info)) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户不存在, null, '渠道不存在');
            exit;
        }
        $room_info = LanguageroomModel::getInstance()->getModel((int) $room_id[0])->where('id', (int) $room_id[0])->find();
        if (empty($room_info)) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户不存在, null, '房间不存在');
            exit;
        }

        $data = [];
        $count = count($promote_id);

        for ($i = 0; $i < $count; $i++) {
            $data[$i]['room_id'] = $room_id[$i];
            $data[$i]['promote_id'] = $promote_id[$i];
            $data[$i]['operator'] = $this->token['id'] ?? 0;
        }
        echo PromotionService::getInstance()->addOrUpdatePromotionRoom($data);
    }

    public function getRoom()
    {
        $room_id = Request::param('room_id');
        $room_name = $this->room_list[$room_id] ?? '房间不存在!!!';
        return rjson(['room_name' => $room_name], 200, '成功');
    }

    public function getPromote()
    {
        $promote_id = Request::param('promote_id');
        $promote_name = PromotionModel::getInstance()->getModel()->where('id', $promote_id)->value('channel_name');
        return rjson(['promote_name' => $promote_name], 200, '成功');
    }

    public function getPromoteRoom()
    {
        $promote_code = Request::param('promote_code'); //推广码
        $promote_info = PromotionRoomConfModel::getInstance()->getModel()->where('id', $promote_code)->select()->toArray();

        $str = '';
        foreach ($promote_info as $key => $item) {
            $room_id = $item['room_id'];
            $promote_id = $item['promote_id'];
            $promote_name = $this->promote_list[$promote_id];
            $room_name = $this->room_list[$room_id] ?? '';
            $str .= "{$room_id}({$room_name})|{$promote_id}({$promote_name})";
        }
        return rjson(['promote_name' => $str], 200, '成功');
    }

    //推广渠道添加
    public function PromotionRoomSave()
    {
        $channel_name = Request::param('channel_name');
        $status = Request::param('status');
        $id = Request::param('id');
        echo PromotionService::getInstance()->addOrUpdatePromotion(['channel_name' => $channel_name, 'status' => $status], ['id', '=', $id]);
    }

    //推广场次配置
    public function getPromotionRoomTimesList()
    {
        $page = Request::param('page', 1);
        $room_id = Request::param('room_id');
        $start_time = Request::param('stime');
        $end_time = Request::param('etime');
        $daochu = $this->request->param('daochu');

        $where[] = ['A.status', '=', 0];
        if (!empty($start_time) && !empty($end_time)) {
            $where[] = ['A.start_time', '>=', $start_time];
            $where[] = ['A.end_time', '<', $end_time];
        }

        if (!empty($room_id)) {
            $promote_codes = PromotionRoomConfModel::getInstance()->getModel()->field('room_id, promote_id, id')->where('room_id', $room_id)->column('id');
            $where[] = ['A.promote_code', 'in', array_keys($promote_codes)];
        }

        //设置特定的运营人员的只能看到他自己添加的数据--宋阳提的需求
        if (in_array($this->token['id'], config("config.operate_black"))) {
            $where[] = ['A.operator', '=', $this->token['id']];
        }

        //推广链接
        $promotes = $this->promote_room_list;

        $promote_list = [];
        foreach ($promotes as $key => $promote) {
            $room_name = $this->room_list[$promote['room_id']] ?? '';
            $channel_name = $this->promote_list[$promote['promote_id']];
            $promote_list[$promote['id']] = "渠道：{$channel_name}|房间：{$promote['room_id']}({$room_name})|{$promote['id']}";
        }
        $export = $daochu == 1 ? true : false;
        $data = PromotionService::getInstance()->getPromotionRoomTimes($page, $where, $export);
        foreach ($data['list'] as $key => &$item) {
            $item['consume_count'] = Db::table('bi_days_room_promotion_stats_by_times')->where('promotion_id', $item['id'])->order('date asc')->limit(1)->value('consume_count');
            $item['start_date'] = date("Y-m-d", strtotime($item['start_time']));
            $item['end_date'] = date("Y-m-d", strtotime($item['end_time']));

            $item['start'] = date("H:i:s", strtotime($item['start_time']));
            $item['end'] = date("H:i:s", strtotime($item['end_time']));

            $promote = $this->promote_room_list[$item['promote_code']] ?? '';

            $item['channel_name'] = isset($promote['promote_id']) ? $this->promote_list[$promote['promote_id']] : '';
            $item['room_id'] = isset($promote['room_id']) ? $promote['room_id'] : '';
            $item['room_name'] = isset($promote['room_id']) ? $this->room_list[$promote['room_id']] : '';

            $item['promote_pay_rate'] = 0;
            $item['promote_pay_rate2'] = 0;

            if ($item['enter_count'] > 0) {
                $item['promote_pay_rate'] = round($item['promote_pay_count'] * 100 / $item['enter_count'], 2);
            }

            if ($item['promote_pay_count'] > 0) {
                $item['promote_pay_rate2'] = round($item['promote_pay_amount'] / $item['promote_pay_count'], 2);
            }

            $item['total_pay'] = $item['total_pay_amount'] + $item['total_member_pay_amount'];

            $login = PromotionService::getInstance()->PromotionXinzeng($item['id']);
            $charge = PromotionService::getInstance()->PromotionChongzhi($item['id']);
            $consume = PromotionService::getInstance()->PromotionConsume($item['id']);
            $bag_consume = PromotionService::getInstance()->PromotionBagConsume($item['id']);
            $consume_count = PromotionService::getInstance()->PromotionConsumeCount($item['id']);

            $item['consume_day_all'] = $consume['day_all'] / 10;
            $item['consume_day_0'] = $consume['day_0'] / 10;
            $item['consume_day_1'] = $consume['day_1'] / 10;
            $item['consume_day_2'] = $consume['day_2'] / 10;
            $item['consume_day_3'] = $consume['day_3'] / 10;
            $item['consume_day_4'] = $consume['day_4'] / 10;
            $item['consume_day_5'] = $consume['day_5'] / 10;
            $item['consume_day_6'] = $consume['day_6'] / 10;
            $item['consume_day_7'] = $consume['day_7'] / 10;

            $item['consume_count_day_1'] = 0;
            $item['consume_count_day_2'] = 0;
            $item['consume_count_day_3'] = 0;
            $item['consume_count_day_4'] = 0;
            $item['consume_count_day_5'] = 0;
            $item['consume_count_day_6'] = 0;

            $consume_count_0 = $consume_count['day_0'];
            $item['consume_count_1'] = $consume_count['day_1'];
            $item['consume_count_2'] = $consume_count['day_2'];
            $item['consume_count_3'] = $consume_count['day_3'];
            $item['consume_count_4'] = $consume_count['day_4'];
            $item['consume_count_5'] = $consume_count['day_5'];
            $item['consume_count_6'] = $consume_count['day_6'];

            if ($consume_count_0 > 0) {
                $item['consume_count_day_1'] = round($consume_count['day_1'] * 100 / $consume_count_0, 2);
                $item['consume_count_day_2'] = round($consume_count['day_2'] * 100 / $consume_count_0, 2);
                $item['consume_count_day_3'] = round($consume_count['day_3'] * 100 / $consume_count_0, 2);
                $item['consume_count_day_4'] = round($consume_count['day_4'] * 100 / $consume_count_0, 2);
                $item['consume_count_day_5'] = round($consume_count['day_5'] * 100 / $consume_count_0, 2);
                $item['consume_count_day_6'] = round($consume_count['day_6'] * 100 / $consume_count_0, 2);
            }

            $item['bag_consume_day_all'] = $bag_consume['day_all'] / 10;
            $item['bag_consume_day_0'] = $bag_consume['day_0'] / 10;
            $item['bag_consume_day_1'] = $bag_consume['day_1'] / 10;
            $item['bag_consume_day_2'] = $bag_consume['day_2'] / 10;
            $item['bag_consume_day_3'] = $bag_consume['day_3'] / 10;
            $item['bag_consume_day_4'] = $bag_consume['day_4'] / 10;
            $item['bag_consume_day_5'] = $bag_consume['day_5'] / 10;
            $item['bag_consume_day_6'] = $bag_consume['day_6'] / 10;
            $item['bag_consume_day_7'] = $bag_consume['day_7'] / 10;

            $item['login_day_1'] = 0;
            $item['login_day_2'] = 0;
            $item['login_day_3'] = 0;
            $item['login_day_4'] = 0;
            $item['login_day_5'] = 0;
            $item['login_day_6'] = 0;
            $item['login_day_7'] = 0;
            $item['login_day_15'] = 0;
            $item['login_day_30'] = 0;

            $login_day_1 = $login['day_0'];
            if ($login_day_1 > 0) {
                $item['login_day_1'] = round($login['day_1'] * 100 / $login_day_1, 2);
                $item['login_day_2'] = round($login['day_2'] * 100 / $login_day_1, 2);
                $item['login_day_3'] = round($login['day_3'] * 100 / $login_day_1, 2);
                $item['login_day_4'] = round($login['day_4'] * 100 / $login_day_1, 2);
                $item['login_day_5'] = round($login['day_5'] * 100 / $login_day_1, 2);
                $item['login_day_6'] = round($login['day_6'] * 100 / $login_day_1, 2);
                $item['login_day_7'] = round($login['day_7'] * 100 / $login_day_1, 2);
                $item['login_day_15'] = round($login['day_15'] * 100 / $login_day_1, 2);
                $item['login_day_30'] = round($login['day_30'] * 100 / $login_day_1, 2);
            }

            $item['charge_count_day_1'] = $charge['day_1']['pay_login_count'];
            $item['charge_count_day_2'] = $charge['day_2']['pay_login_count'];
            $item['charge_count_day_3'] = $charge['day_3']['pay_login_count'];
            $item['charge_count_day_4'] = $charge['day_4']['pay_login_count'];
            $item['charge_count_day_5'] = $charge['day_5']['pay_login_count'];
            $item['charge_count_day_6'] = $charge['day_6']['pay_login_count'];
            $item['charge_count_day_7'] = $charge['day_7']['pay_login_count'];
            $item['charge_count_day_15'] = $charge['day_15']['pay_login_count'];
            $item['charge_count_day_30'] = $charge['day_30']['pay_login_count'];

            $item['charge_day_1'] = 0;
            $item['charge_day_2'] = 0;
            $item['charge_day_3'] = 0;
            $item['charge_day_4'] = 0;
            $item['charge_day_5'] = 0;
            $item['charge_day_6'] = 0;
            $item['charge_day_7'] = 0;
            $item['charge_day_15'] = 0;
            $item['charge_day_30'] = 0;
            $charge_day_1 = $charge['day_0']['pay_count'];
            if ($charge_day_1 > 0) {
                $item['charge_day_1'] = round($charge['day_1']['pay_login_count'] * 100 / $charge_day_1, 2);
                $item['charge_day_2'] = round($charge['day_2']['pay_login_count'] * 100 / $charge_day_1, 2);
                $item['charge_day_3'] = round($charge['day_3']['pay_login_count'] * 100 / $charge_day_1, 2);
                $item['charge_day_4'] = round($charge['day_4']['pay_login_count'] * 100 / $charge_day_1, 2);
                $item['charge_day_5'] = round($charge['day_5']['pay_login_count'] * 100 / $charge_day_1, 2);
                $item['charge_day_6'] = round($charge['day_6']['pay_login_count'] * 100 / $charge_day_1, 2);
                $item['charge_day_7'] = round($charge['day_7']['pay_login_count'] * 100 / $charge_day_1, 2);
                $item['charge_day_15'] = round($charge['day_15']['pay_login_count'] * 100 / $charge_day_1, 2);
                $item['charge_day_30'] = round($charge['day_30']['pay_login_count'] * 100 / $charge_day_1, 2);
            }
        }
        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('start_time', $start_time);
        View::assign('end_time', $end_time);
        View::assign('room_id', $room_id);
        View::assign('promote_list', $promote_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
            $columns = [
                'channel_name' => '推广渠道',
                'room_id' => '房间ID',
                'room_name' => '房间昵称',
                'start_time' => '开始时间',
                'end_time' => '结束时间',
                'enter_count' => '引流新增人数',
                'promote_pay_count' => '引流充值人数',
                'promote_pay_amount' => '引流充值金额',
                'promote_pay_rate' => '引流付费率',
                'promote_pay_rate2' => '引流付费UP',
                'consume_count' => '引流消费人数',
                'consume_day_0' => '引流非背累计消费',
                'bag_consume_day_0' => '引流背包累计消费',
                'total_pay' => '总充值',
                'total_pay_amount' => '总直冲金额',
                'total_pay_count' => '总直冲人数',
                'total_member_pay_amount' => '总代充金额',
                'total_member_pay_count' => '总代充人数',
                'consume_day_all' => '非背包累计消费',
                'bag_consume_day_all' => '背包累计消费',
                'consume_count_day_1' => '消费次留(%)',
                'consume_count_day_2' => '消费三留(%)',
                'consume_count_day_3' => '消费四留(%)',
                'consume_count_day_4' => '消费五留(%)',
                'consume_count_day_5' => '消费六留(%)',
                'consume_count_day_6' => '消费七留(%)',
                'consume_count_1' => '消费次留人数',
                'consume_count_2' => '消费三留人数',
                'consume_count_3' => '消费四留人数',
                'consume_count_4' => '消费五留人数',
                'consume_count_5' => '消费六留人数',
                'consume_count_6' => '消费七留人数',
                'charge_day_1' => '充值次留（%)',
                'charge_day_2' => '充值三留（%)',
                'charge_day_3' => '充值四留（%)',
                'charge_day_4' => '充值五留（%)',
                'charge_day_5' => '充值六留（%)',
                'charge_day_6' => '充值七留（%)',
                'charge_day_15' => '充值十五留（%)',
                'charge_day_30' => '充值三十留（%)',
                'charge_count_day_1' => '充值次留人数',
                'charge_count_day_2' => '充值三留人数',
                'charge_count_day_3' => '充值四留人数',
                'charge_count_day_4' => '充值五留人数',
                'charge_count_day_5' => '充值六留人数',
                'charge_count_day_6' => '充值七留人数',
                'charge_count_day_15' => '充值十五留人数',
                'charge_count_day_30' => '充值三十留人数',
                'consume_day_1' => '非背次留消费金额',
                'consume_day_2' => '非背三留消费金额',
                'consume_day_3' => '非背四留消费金额',
                'consume_day_4' => '非背五留消费金额',
                'consume_day_5' => '非背六留消费金额',
                'consume_day_6' => '非背七留消费金额',
                'bag_consume_day_1' => '背包次留消费金额',
                'bag_consume_day_2' => '背包三留消费金额',
                'bag_consume_day_3' => '背包四留消费金额',
                'bag_consume_day_4' => '背包五留消费金额',
                'bag_consume_day_5' => '背包六留消费金额',
                'bag_consume_day_6' => '背包七留消费金额',
                'promote_code' => '推广码',
                'login_day_1' => '新增次留',
                'login_day_2' => '新增三留',
                'login_day_3' => '新增四留',
                'login_day_4' => '新增五留',
                'login_day_5' => '新增六留',
                'login_day_6' => '新增七留',
                'login_day_15' => '新增十五留',
                'login_day_30' => '新增三十留',

            ];
            ExportExcelService::getInstance()->export($data['list'], $columns);
        }
        return View::fetch('paoliang/promote/PromotionTimesList');
    }

    //添加跑量
    public function PromotionRoomTimesAdd()
    {
        $promote_code = Request::param('promote_code');
        $rmb = Request::param('rmb');
        $start_time = Request::param('start_time');
        $end_time = Request::param('end_time');

        $promote_code_info = PromotionRoomConfModel::getInstance()->getModel()->where('id', (int) $promote_code[0])->find();
        if (empty($promote_code_info)) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户不存在, null, '房间推广ID不存在');
            exit;
        }

        PromotionService::getInstance()->PromotionRoomTimesAdd($promote_code, $rmb, $start_time, $end_time);
    }

    //编辑跑量
    public function PromotionRoomTimesSave()
    {
        $params = Request::param();
        $rmb = Request::param('rmb', 0);
        $data = [
            "promote_code" => $params['promote_code'],
            "rmb" => $rmb ?? 0,
            "start_time" => $params['start_time'],
            "end_time" => $params['end_time'],
        ];
        PromotionService::getInstance()->addOrUpdatePromotionRoomTimes($data, ['id', '=', $params['id']]);
    }

    //删除跑量
    public function PromotionRoomTimesDel()
    {
        try {
            $params = Request::param();
            $data = ["status" => 1];
            PromotionService::getInstance()->addOrUpdatePromotionRoomTimes($data, ['id', '=', $params['id']]);
            return rjsonadmin([], 200, '操作成功');
        } catch (\Throwable $th) {
            return rjsonadmin([], 400, '操作失败');
        }
    }

    public function xingzengxiangqing()
    {
        $data = PromotionService::getInstance()->xingzengxiangqing(Request::param('id'));
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/xiangqing');
    }

    public function PaoLiangLiuCun()
    {
        $data = PromotionService::getInstance()->PaoLiangLiuCun(Request::param('id'));
        Log::record('跑量留存列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/PaoLiangLiuCun');
    }

    public function PromotionXinzeng()
    {
        $data = PromotionService::getInstance()->PromotionXinzeng(Request::param('id'));
        Log::record('跑量留存列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/new/PromotionXinzeng');
    }

    //跑量充值用户
    // public function PaoLiangChongzhi()
    // {
    //     $id = Request::param('id');
    //     $data = PromotionService::getInstance()->PaoLiangChongzhi($id);
    //     Log::record('跑量充值详情:操作人:' . $this->token['username'], 'memberList');
    //     View::assign('list', $data);
    //     View::assign('pid', $id);
    //     View::assign('count', count($data));
    //     View::assign('rmb', array_sum(array_column($data, 'rmb')));
    //     View::assign('user_role_menu', $this->user_role_menu);
    //     View::assign('token', $this->request->param('token'));
    //     View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
    //     return View::fetch('paoliang/PaoLiangChongzhi');
    // }

    //跑量充值用户
    public function PromotionChongzhi()
    {
        $id = Request::param('id');
        $data = PromotionService::getInstance()->PromotionChongzhi($id);
        Log::record('跑量充值详情:操作人:' . $this->token['username'], 'memberList');
        View::assign('data', $data);
        View::assign('pid', $id);
        View::assign('count', count($data));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/new/PromotionChongzhi');
    }
    //付费导出
    public function PaoLiangDaoChu()
    {
        $id = Request::param('id');
        return PromotionService::getInstance()->PaoLiangDaoChu($id);
    }
    //删除跑量
    public function DelPaoLiang()
    {
        echo PromotionService::getInstance()->DelPaoLiang(Request::param('id'));
    }

    public function getUsers()
    {
        $pagenum = 20;
        $id = Request::get('id');
        $page = Request::get('page', 1);
        $column = Request::get('column');
        // dump($id, $column);

        $master_page = Request::get('master_page');
        $offest = ($page - 1) * $pagenum;
        $charge_arr = [
            'charge_count_day_1',
            'charge_count_day_2',
            'charge_count_day_3',
            'charge_count_day_4',
            'charge_count_day_5',
            'charge_count_day_6',
            'charge_count_day_15',
            'charge_count_day_30',

        ];
        $login__arr = [
            'login_day_1',
            'login_day_2',
            'login_day_3',
            'login_day_4',
            'login_day_5',
            'login_day_6',
            'login_day_15',
            'login_day_30',
        ];

        $consume__arr = [
            'consume_count_day_1',
            'consume_count_day_2',
            'consume_count_day_3',
            'consume_count_day_4',
            'consume_count_day_5',
            'consume_count_day_6',
        ];

        $select_column = $column;
        if (in_array($column, $charge_arr)) {
            $date = str_replace('charge_count_', '', $column);
            $select_column = 'pay_login_users';
        } elseif (in_array($column, $consume__arr)) {
            $date = str_replace('consume_count_', '', $column);
            $select_column = 'consume_users';
        } elseif (in_array($column, $login__arr)) {
            $date = str_replace('login_', '', $column);
            $select_column = 'login_users';
            // } elseif ($column == 'total_pay_count') {
            //     $select_column = 'pay_users';
        } elseif ($column == 'consume_day') {
            $select_column = 'consume_users';
        } elseif ($column == 'promote_pay_count') {
            $select_column = 'pay_users';
        } elseif ($column == 'total_member_pay_count') {
            $select_column = 'member_pay_users';
        } elseif ($column == 'total_pay_count') {
            $select_column = 'pay_users';
        }

        $days = 0;
        if (isset($date)) {
            $days = PromotionService::getInstance()->days[$date];
            $start_time = PromotionRoomTimesConfModel::getInstance()->getModel()->where('id', (int) $id)->value('start_time');

            $date = date("Y-m-d", strtotime(date("Y-m-d", strtotime($start_time))) + $days * 24 * 60 * 60);
            $users = Db::table('bi_days_room_promotion_stats_by_times')->where('promotion_id', $id)->where('date', $date)->value($select_column);
        }
        if ($column == 'promote_pay_count' || $column == 'consume_day') {
            $users = Db::table('bi_days_room_promotion_stats_by_times')->where('promotion_id', $id)->order('date asc')->limit(1)->value($select_column);
        }
        if ($column == 'total_member_pay_count' || $column == 'total_pay_count') {
            $res = Db::table('bi_days_room_promotion_stats_by_times')->where('promotion_id', $id)->column($select_column);
            $users = '';
            if (!empty($res)) {
                foreach (array_values($res) as $key => $uids) {
                    if (!empty($uids)) {
                        $users .= $uids;
                        if ($key < count($res) - 1) {
                            $users .= ',';
                        }
                    }
                }
            }
        }

        $data = [];
        $count = 0;
        if (!empty($users)) {
            $users = explode(',', $users);
            asort($users);
            $user_arr = array_values(array_unique($users));
            $count = count($user_arr);
            $arr = array_slice($user_arr, $offest, $pagenum);
            $data = MemberModel::getInstance()->getWhereAllData([["id", "in", $arr]], "id,nickname");
        }

        $page_array = [];
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $pagenum);

        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('column', $column);
        View::assign('id', $id);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('master_page', $master_page);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/promote/users');

    }
}

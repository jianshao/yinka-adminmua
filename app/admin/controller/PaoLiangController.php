<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\LanguageroomModel;
use app\admin\model\RoomPromotionConfModel;
use app\admin\service\PaoliangService;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class PaoLiangController extends AdminBaseController
{
    public function retainedUpdate()
    {
        echo PaoliangService::getInstance()->retainedUpdate();
    }
    public function retainedDel()
    {
        echo PaoliangService::getInstance()->retainedDel(Request::param());
    }
    public function retainedAdd()
    {
        echo PaoliangService::getInstance()->retainedAdd(Request::param());
    }

    public function retainedShow()
    {
        $data = PaoliangService::getInstance()->retainedShow();
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/retainedShow');
    }

    //编辑跑量
    public function zbRoomPromotionConfSave()
    {
        echo PaoliangService::getInstance()->zbRoomPromotionConfSave(Request::param('id'), Request::param('room_name'), Request::param('room_id'), Request::param('type'),
            Request::param('rmb'), Request::param('start_time'), Request::param('end_time'));
    }

    //添加跑量
    public function zbRoomPromotionConfAdd()
    {
        echo PaoliangService::getInstance()->zbRoomPromotionConfAdd(Request::param('room_name'), Request::param('room_id'), Request::param('type'),
            Request::param('rmb'), Request::param('start_time'), Request::param('end_time'), $this->token['id']);
    }

    //删除跑量
    public function zbRoomPromotionConfDel()
    {
        $id = Request::param('id');
        RoomPromotionConfModel::getInstance()->getModel()->where('id', $id)->save(["status" => 1]);
        Log::info('跑量删除配置:操作人:' . $this->token['username']);
        echo json_encode(['code' => 200, 'msg' => '添加成功']);die;
    }

    //新跑量配置
    public function zbRoomPromotionConf()
    {
        $page = Request::param('page', 1);
        $room_id = Request::param('room_id');
        $type = Request::param('type', 0);
        $demo = $this->request->param('demo');
        list($start_time, $end_time) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu', 0);

        $where[] = ['A.status', '<>', 1];
        if (!empty($start_time) && !empty($end_time)) {
            $where[] = ['A.start_time', '>=', $start_time];
            $where[] = ['A.end_time', '<', $end_time];
        }

        if (!empty($room_id)) {
            $where[] = ['A.room_id', '=', $room_id];
        }

        if (!empty($type)) {
            $where[] = ['A.type', '=', $type];
        }

        //设置特定的运营人员的只能看到他自己添加的数据--宋阳提的需求
        if (in_array($this->token['id'], config("config.operate_black"))) {
            $where[] = ['A.operator', '=', $this->token['id']];
        }

        $data = PaoliangService::getInstance()->zbRoomPromotionConf($where, $page, $daochu);

        foreach ($data['list'] as $key => &$item) {
            $item['start_date'] = date("Y-m-d", strtotime($item['start_time']));
            $item['end_date'] = date("Y-m-d", strtotime($item['end_time']));
            $item['start'] = date("H:i:s", strtotime($item['start_time']));
            $item['end'] = date("H:i:s", strtotime($item['end_time']));

            $item['roomname'] = LanguageroomModel::getInstance()->getOneById($item['room_id'], 'room_name');
            $item['login'] = PaoliangService::getInstance()->getPromoteRetentionById('register', $item);
            $item['charge'] = PaoliangService::getInstance()->getPromoteRetentionById('charge', $item);
            $item['room_consume'] = PaoliangService::getInstance()->getPromoteRetentionById('room_consume', $item);
            $users = explode(',', $item['enter_users']);
            $item['total_pay_sum'] = round(PaoliangService::getInstance()->getPaySumDataByUid($users) / 10, 2);
            $total_pay_users = PaoliangService::getInstance()->getPayTotalUsersByUid($users);

            $item['total_pay_users'] = implode(',', array_values(array_keys($total_pay_users)));
            $item['total_pay_count'] = 0;
            if ($total_pay_users) {
                $item['total_pay_count'] = count($total_pay_users);
            }

            if ($item['promote_pay_users']) {
                $promote_pay_users = json_decode($item['promote_pay_users'], true);
                $item['promote_pay_users'] = implode(',', $promote_pay_users);
            } else {
                $item['promote_pay_users'] = '';
            }
            $item['pay_rate'] = $item['enter_count'] > 0 ? (round($item['total_pay_count'] * 100 / $item['enter_count'], 2)) . '%' : 0;
            $item['arppu'] = $item['total_pay_count'] > 0 ? round(($item['total_pay_amount'] + $item['total_member_pay_amount']) / $item['total_pay_count'], 2) : 0;

            // $item['login'] = PaoliangService::getInstance()->PromotionXinzeng($item['id']);
            // $item['charge'] = PaoliangService::getInstance()->PromotionChongzhi($item['id']);
            // $retention_data = PaoliangService::getInstance()->getPromoteRetentionDataById($item['id']);
        }

        if ($daochu == 1) {
            $this->getcsv($data['list']);
        }

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('demo', $demo);
        View::assign('room_id', $room_id);
        View::assign('type', $type);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/new/zbRoomPromotionConf');
    }

    public function getPromoteDetailByUids()
    {
        $uids = $this->request->param('uids', '');
        $type = $this->request->param('type', 0);

        return rjson(PaoliangService::getInstance()->getPromoteDetailByUids($type, $uids));
    }

    public function getPromoteRetentionById()
    {
        $promotion_id = (int) $this->request->param('promotion_id', 0);
        $type = (string) $this->request->param('type', '');

        $promotion = PaoliangService::getInstance()->zbRoomPromotionConf([['A.id', '=', $promotion_id]]);
        return rjson(PaoliangService::getInstance()->getPromoteRetentionById($type, $promotion['list'][0], 1));
    }

    //新跑量配置
    public function roomPromotionDayData()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start_time, $end_time) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu', 0);
        $page = Request::param('page', 1);

        $where = [];
        if (!empty($start_time) && !empty($end_time)) {
            $where[] = ['promotion_date', '>=', $start_time];
            $where[] = ['promotion_date', '<', $end_time];
            // $where[] = ['promotion_date', '=', '2021-12-11'];
        }

        $data = PaoliangService::getInstance()->roomPromotionDayData($page, $where, $daochu);

        foreach ($data['list'] as $key => &$item) {
            //总直冲
            $promotion_info = Db::table('bi_days_room_promotion_stats_by_day')->where('promotion_date', $item['date'])->order('date desc')->limit(1)->find();

            //总代充
            $item['total_member_pay_amount'] = $promotion_info['total_member_pay_amount'];
            //代充人数
            $item['total_member_pay_count'] = $promotion_info['total_member_pay_count'];
            //总充值
            $item['total_pay_amount'] = $promotion_info['total_pay_amount'];
            //充值人数
            $item['total_pay_count'] = $promotion_info['total_pay_count'];
            //充值用户
            $item['total_pay_users'] = $promotion_info['total_pay_users'];
            //ROI
            $item['roi'] = $promotion_info['roi'];
            //价格
            $item['price'] = $promotion_info['price'];
            //登陆留存
            $item['login'] = PaoliangService::getInstance()->PromotionXinzengByDay($item['date']);
            //充值留存
            $item['charge'] = PaoliangService::getInstance()->PromotionChongzhiByDay($item['date']);
        }

        if ($daochu == 1) {
            $this->getPromoteDaycsv($data['list']);
        }

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));

        return View::fetch('paoliang/new/roomPromotionDayData');
    }

    //数据导出
    private function getPromoteDaycsv($data)
    {
        $headerArray = [
            '日期',
            '跑量价格',
            '注册人数',
            '进厅人数',
            '引流充值金额',
            '引流充值人数',
            '总直冲',
            '总代充',
            '总充值',
            'ROI',
            '充值人数',
            '代充人数',
            '新增次留',
            '新增三留',
            '新增七留',
            '新增十五留',
            '新增三十留',
            '充值次留',
            '充值三留',
            '充值七留',
            '充值十五留',
            '充值三十留',
        ];

        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['date'] = $value['date'];
            $outArray['price'] = $value['price'];
            $outArray['reg_count'] = $value['reg_count'];
            $outArray['enter_count'] = $value['enter_count'];
            $outArray['promote_pay_amount'] = $value['promote_pay_amount'];
            $outArray['promote_pay_count'] = $value['promote_pay_count'];
            $outArray['total_pay_amount'] = $value['total_pay_amount'];
            $outArray['total_member_pay_amount'] = $value['total_member_pay_amount'];
            $outArray['total_pay'] = $value['total_pay_amount'] + $value['total_member_pay_amount'];
            $outArray['roi'] = $value['roi'];
            $outArray['total_pay_count'] = $value['total_pay_count'];
            $outArray['total_member_pay_count'] = $value['total_member_pay_count'];
            $outArray['login_day_1'] = $value['login']['day_1']['login_count'];
            $outArray['login_day_3'] = $value['login']['day_3']['login_count'];
            $outArray['login_day_7'] = $value['login']['day_7']['login_count'];
            $outArray['login_day_15'] = $value['login']['day_15']['login_count'];
            $outArray['login_day_30'] = $value['login']['day_30']['login_count'];
            $outArray['charge_day_1'] = $value['charge']['day_1']['pay_count'];
            $outArray['charge_day_3'] = $value['charge']['day_3']['pay_count'];
            $outArray['charge_day_7'] = $value['charge']['day_7']['pay_count'];
            $outArray['charge_day_15'] = $value['charge']['day_15']['pay_count'];
            $outArray['charge_day_30'] = $value['charge']['day_30']['pay_count'];

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
    //数据导出
    private function getcsv($data)
    {
        $headerArray = [
            '房间ID',
            '房间昵称',
            // '昵称',
            '类型',
            '开始时间',
            '结束时间',
            // '跑量价格',
            // '注册人数',
            '进厅人数',
            '引流充值金额',
            '引流充值人数',
            // '总直冲',
            // '总代充',
            '付费率',
            'ARPPU',
            '总充值',
            // 'ROI',
            '总充值人数',
            // '代充人数',
            '新增次留',
            '新增三留',
            '新增七留',
            // '新增十五留',
            '新增三十留',
            '充值次留',
            '充值三留',
            '充值七留',
            // '充值十五留',
            '充值三十留',
            '消费次留',
            '消费三留',
            '消费七留',
            // '消费十五留',
            '消费三十留',
        ];

        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['room_id'] = $value['room_id'];
            $outArray['roomname'] = $value['roomname'];
            // $outArray['room_name'] = $value['room_name'];
            $outArray['type'] = $value['type'] == 1 ? 'KOC' : ($value['type'] == 2 ? 'KOL' : 'KOL(马)');
            $outArray['start_time'] = $value['start_time'];
            $outArray['end_time'] = $value['end_time'];
            // $outArray['rmb'] = $value['rmb'];
            // $outArray['reg_count'] = $value['reg_count'];
            $outArray['enter_count'] = $value['enter_count'];
            $outArray['promote_pay_amount'] = $value['promote_pay_amount'];
            $outArray['promote_pay_count'] = $value['promote_pay_count'];
            // $outArray['total_pay_amount'] = $value['total_pay_amount'];
            // $outArray['total_member_pay_amount'] = $value['total_member_pay_amount'];
            $outArray['pay_rate'] = $value['pay_rate'];
            $outArray['arppu'] = $value['arppu'];
            $outArray['total_pay'] = $value['total_pay_sum'];
            // $outArray['roi'] = $value['roi'];
            $outArray['total_pay_count'] = $value['total_pay_count'];
            // $outArray['total_member_pay_count'] = $value['total_member_pay_count'];
            $outArray['login_day_2'] = $value['login']['info']['day_2']['count'] ?? '';
            $outArray['login_day_3'] = $value['login']['info']['day_3']['count'] ?? '';
            $outArray['login_day_7'] = $value['login']['info']['day_7']['count'] ?? '';
            // $outArray['login_day_15'] = $value['login']['info']['day_15']['count'];
            $outArray['login_day_30'] = $value['login']['info']['day_30']['count'] ?? '';
            $outArray['charge_day_2'] = $value['charge']['info']['day_2']['count'] ?? '';
            $outArray['charge_day_3'] = $value['charge']['info']['day_3']['count'] ?? '';
            $outArray['charge_day_7'] = $value['charge']['info']['day_7']['count'] ?? '';
            // $outArray['charge_day_15'] = $value['charge']['info']['day_15']['count'];
            $outArray['charge_day_30'] = $value['charge']['info']['day_30']['count'] ?? '';
            $outArray['consume_day_2'] = $value['room_consume']['info']['day_2']['count'] ?? '';
            $outArray['consume_day_3'] = $value['room_consume']['info']['day_3']['count'] ?? '';
            $outArray['consume_day_7'] = $value['room_consume']['info']['day_7']['count'] ?? '';
            // $outArray['consume_day_15'] = $value['room_consume']['info']['day_15']['count'];
            $outArray['consume_day_30'] = $value['room_consume']['info']['day_30']['count'] ?? '';

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

    public function PromotionXinzeng()
    {
        $data = PaoliangService::getInstance()->PromotionXinzeng(Request::param('id'));
        Log::record('跑量留存列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/new/PromotionXinzeng');
    }

    /**
     * @return mixed
     * @name 跑量列表
     */
    public function PaoLiangList()
    {
        $daochu = Request::param('daochu', 0);
        $page = Request::param('page', 1);
        $data = PaoliangService::getInstance()->PaoLiangList($page, $daochu);
        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('paoliang/index');
    }

    //导出id
    public function PaoLiangXinzeng()
    {
        return PaoliangService::getInstance()->PaoLiangXinzeng(Request::param('id'), Request::param('type'));
    }

    //跑量编辑
    public function PaoLiangSave()
    {
        echo PaoliangService::getInstance()->PaoLiangSave(Request::param('id'), Request::param('nickname'), Request::param('rmb'), Request::param('create'), Request::param('update'));
    }
    //添加跑量
    public function AddPaoLiang()
    {
        echo PaoliangService::getInstance()->AddPaoLiang(Request::param('nickname'), Request::param('rmb'), Request::param('create'), Request::param('update'), Request::param('room_id'));
    }

    //跑量充值用户
    // public function PaoLiangChongzhi()
    // {
    //     $id = Request::param('id');
    //     $data = PaoliangService::getInstance()->PaoLiangChongzhi($id);
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
        $data = PaoliangService::getInstance()->PromotionChongzhi($id);
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
        return PaoliangService::getInstance()->PaoLiangDaoChu($id);
    }
    //删除跑量
    public function DelPaoLiang()
    {
        echo PaoliangService::getInstance()->DelPaoLiang(Request::param('id'));
    }

}
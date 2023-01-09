<?php

namespace app\admin\controller;

ini_set('memory_limit', '1024M');

use app\admin\common\AdminBaseController;
use app\admin\model\LanguageroomModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\RoomWholewheatCensusModel;
use app\admin\model\WholewheatGiftPointModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ExportExcelService;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

class SendGiftToAllController extends AdminBaseController
{
    //全麦送礼记录
    public function sendGiftToAllMembers()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $room_id = $this->request->param('room_id');
        $daochu = $this->request->param('daochu');
        $type = $this->request->param('type', 9.6);
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $gifts_where = [];

        $gift_list = $this->getGiftsByType($type);
        if ($gift_list) {
            $gifts_where[] = [
                'gift_id', 'not in', $gift_list,
            ];
        }
        $gifts = WholewheatGiftPointModel::getInstance()->getModel()->where($gifts_where)->column('gift_id');

        $where = [];
        $where[] = ['A.gift_id', 'in', $gifts];
        $where[] = ['A.create_time', '>=', strtotime($start)];
        $where[] = ['A.create_time', '<', strtotime($end)];
        if ($room_id > 0) {
            $where[] = ['A.room_id', '=', $room_id];
        }
        $point_data_all = RoomWholewheatCensusModel::getInstance()->getModel()
            ->alias('A')
            ->leftJoin('zb_wholewheat_gift_point F', 'A.gift_id = F.gift_id')
            ->field('A.room_id, sum(A.count) count, sum(A.gift_value) amount,sum(A.count * F.point) point')
            ->where($where)
            ->group('room_id')
            ->order("point desc")
            ->select()
            ->toArray();

        $count = count($point_data_all);

        $point_data = array_slice($point_data_all, $page, $pagenum);

        $callfunc = function ($data) {
            $room_ids = array_column($data, "room_id");
            $condition = [];
            $condition[] = ["id", "in", $room_ids];
            $point_data_map = array_column($data, null, 'room_id');
            $list = LanguageroomModel::getInstance()->getWhereAllData($condition, 'user_id room_belong, id room_id, room_name, guild_id');
            $guild_ids = array_column($list, "guild_id");
            $roomWithGuild = array_column($list, null, "room_id");
            $memberGuildIList = MemberGuildModel::getInstance()->getWhereAllData([["id", "in", $guild_ids]], "nickname,id,user_id");
            $memeberGuildListById = array_column($memberGuildIList, null, "id");
            foreach ($data as &$item) {
                $guild_id = $roomWithGuild[$item['room_id']]['guild_id'] ?? 0;
                $item['guild_nickname'] = $memeberGuildListById[$guild_id]['nickname'] ?? '';
                $item['guild_id'] = $guild_id;
                $item['count'] = isset($point_data_map[$item['room_id']]) ? $point_data_map[$item['room_id']]['count'] : 0;
                $item['amount'] = isset($point_data_map[$item['room_id']]) ? $point_data_map[$item['room_id']]['amount'] : 0;
                $item['points'] = isset($point_data_map[$item['room_id']]) ? $point_data_map[$item['room_id']]['point'] : 0;
                $item['room_belong'] = $memberGuildIList[$guild_id]['user_id'] ?? 0;
                $item['room_name'] = $roomWithGuild[$item['room_id']]['room_name'] ?? '';
            }

            return $data;
        };

        if ($daochu == 1) {
            $columns = [
                'guild_id' => '公会长ID',
                'guild_nickname' => '公会名称',
                'room_id' => '房间ID',
                'room_name' => '房间名称',
                'room_belong' => '房主ID',
                'count' => '房间全麦礼物数量',
                'amount' => '房间全麦礼物价值',
                'points' => '房间全麦礼物积分',
            ];
            ExportExcelService::getInstance()->export($callfunc($point_data_all), $columns);
        }
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('data', $callfunc($point_data));
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('room_id', $room_id);
        View::assign('type', $type);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('sendAllGifts/sendGiftToAllMembers');
    }

    //全麦送礼记录
    public function sendGiftToAllMembersByDay()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $room_id = $this->request->param('room_id');
        $type = $this->request->param('type', 9.6);
        $daochu = $this->request->param('daochu');
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $gifts_where = [];
        $gift_list = $this->getGiftsByType($type);
        if ($gift_list) {
            $gifts_where[] = [
                'gift_id', 'not in', $gift_list,
            ];
        }
        $gifts = WholewheatGiftPointModel::getInstance()->getModel()->where($gifts_where)->column('gift_id');

        $where = [];
        $where[] = ['A.gift_id', 'in', $gifts];
        $where[] = ['A.create_time', '>=', strtotime($start)];
        $where[] = ['A.create_time', '<', strtotime($end)];

        if ($room_id) {
            $where[] = ['A.room_id', '=', $room_id];
        }

        //充值用户数据
        $subquery = RoomWholewheatCensusModel::getinstance()->getModel()
            ->alias('A')
            ->leftJoin('zb_wholewheat_gift_point F', 'A.gift_id = F.gift_id')
            ->field("A.room_id, sum(A.count) count, sum(A.gift_value) amount,sum(A.count * F.point) point, DATE_FORMAT(FROM_UNIXTIME(A.create_time),'%Y-%m-%d') date")
            ->where($where)
            ->group('A.room_id,date')
            ->buildSql();

        $query = Db::table("$subquery")
            ->alias('D')
            ->field('D.room_id, ifnull(D.count,0) count,ifnull(D.amount,0) amount,ifnull(D.amount,0) amount,ifnull(D.point,0) points,D.date');

        $count = $query->count();

        if ($daochu == 1) {
            $list = $query->select()->toArray();
        } else {
            $list = $query->order('date desc,amount desc')->limit($page, $pagenum)->select()->toArray();
        }

        $room_map = LanguageroomModel::getInstance()->getGuildRoomListMap();
        $guild_map = MemberGuildModel::getInstance()->getGuildListMap();

        foreach ($list as $key => &$item) {
            $room_info = $room_map[$item['room_id']];
            $item['room_belong'] = $room_info['user_id'];
            $item['room_id'] = $room_info['id'];
            $item['room_name'] = $room_info['room_name'];
            $item['guild_id'] = $room_info['guild_id'];
            $item['guild_nickname'] = isset($guild_map[$item['guild_id']]) ? $guild_map[$item['guild_id']]['nickname'] : '';
        }

        if ($daochu == 1) {
            $columns = [
                'date' => '日期',
                'guild_id' => '公会长ID',
                'guild_nickname' => '公会名称',
                'room_id' => '房间ID',
                'room_name' => '房间名称',
                'room_belong' => '房主ID',
                'count' => '房间全麦礼物数量',
                'amount' => '房间全麦礼物价值',
                'points' => '房间全麦礼物积分',
            ];
            ExportExcelService::getInstance()->export($list, $columns);
        }
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);

        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('data', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('room_id', $room_id);
        View::assign('type', $type);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('sendAllGifts/sendGiftToAllMembersByDay');
    }

    //全麦送礼详情
    public function sendGiftToAllMembersDetail()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = $this->request->param('uid');
        $room_id = $this->request->param('room_id');
        $type = $this->request->param('type', 9.6);
        $daochu = $this->request->param('daochu');
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $gifts_where = [];
        $gift_list = $this->getGiftsByType($type);
        if ($gift_list) {
            $gifts_where[] = [
                'gift_id', 'not in', $gift_list,
            ];
        }
        $gifts = WholewheatGiftPointModel::getInstance()->getModel()->where($gifts_where)->column('gift_id');

        $where = [];
        $where[] = ['A.gift_id', 'in', $gifts];
        $where[] = ['A.create_time', '>=', strtotime($start)];
        $where[] = ['A.create_time', '<', strtotime($end)];
        if ($uid) {
            $where[] = ['A.send_uid', '=', $uid];
        }
        if ($room_id) {
            $where[] = ['A.room_id', '=', $room_id];
        }
        //充值用户数据
        $query = RoomWholewheatCensusModel::getinstance()
            ->getModel()
            ->alias('A')
            ->leftJoin('zb_wholewheat_gift_point F', 'A.gift_id = F.gift_id')
            ->field('A.*,F.point')
            ->where($where);

        $count = $query->count();
        if ($daochu == 1) {
            $list = $query->select()->toArray();
        } else {
            $list = $query->order('create_time desc')->limit($page, $pagenum)->select()->toArray();
        }

        $gift_map = GiftsCommon::getInstance()->getGifts();

        foreach ($list as $_ => &$info) {
            $info['gift_name'] = isset($gift_map[$info['gift_id']]) ? $gift_map[$info['gift_id']] : '';
            $info['create_time'] = date('Y-m-d H:i:s', $info['create_time']);
        }

        if ($daochu == 1) {
            $columns = [
                'send_uid' => '用户ID',
                'gift_id' => '礼物ID',
                'gift_name' => '礼物名称',
                'count' => '礼物数量',
                'gift_value' => '礼物价值',
                'point' => '礼物积分',
                'create_time' => '时间',
            ];
            ExportExcelService::getInstance()->export($list, $columns);
        }
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);

        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('data', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('room_id', $room_id);
        View::assign('type', $type);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('sendAllGifts/sendGiftToAllMembersDetail');
    }

    private function getGiftsByType($type)
    {
        $gifts = [];
        if ($type == 1) {
            $gifts = [514, 588, 391, 397, 535, 536, 537, 538, 539, 548, 550, 556, 557, 455, 454, 453, 515, 516, 521, 517, 541, 542, 566, 567, 447, 568, 415, 416, 571, 572, 573, 574, 575, 576, 579, 580, 581, 569, 582, 583, 584, 585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 2) {
            $gifts = [391, 397, 535, 536, 537, 538, 539, 548, 550, 556, 557, 455, 454, 453, 515, 516, 521, 517, 541, 542, 566, 567, 447, 568, 415, 416, 571, 572, 573, 574, 575, 576, 579, 580, 581, 569, 582, 583, 584, 585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 3) {
            $gifts = [455, 454, 453, 515, 516, 521, 517, 541, 542, 566, 567, 447, 568, 415, 416, 571, 572, 573, 574, 575, 576, 579, 580, 581, 569, 582, 583, 584, 585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 4) {
            $gifts = [515, 516, 521, 517, 541, 542, 566, 567, 447, 568, 415, 416, 571, 572, 573, 574, 575, 576, 579, 580, 581, 569, 582, 583, 584, 585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 5) {
            $gifts = [571, 572, 573, 574, 575, 576, 579, 580, 581, 569, 582, 583, 584, 585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 6) {
            $gifts = [574, 575, 576, 579, 580, 581, 569, 582, 583, 584, 585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 7) {
            $gifts = [579, 580, 581, 569, 582, 583, 584, 585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 8) {
            $gifts = [582, 583, 584, 585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 9) {
            $gifts = [585, 586, 587, 590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 9.1) {
            $gifts = [590, 591, 592, 593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 9.2) {
            $gifts = [593, 594, 595, 597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 9.3) {
            $gifts = [597, 598, 599, 600, 601, 602, 603, 604, 605];
        } else if ($type == 9.4) {
            $gifts = [600, 601, 602, 603, 604, 605];
        } else if ($type == 9.5) {
            $gifts = [603, 604, 605];
        }
        return $gifts;
    }
}

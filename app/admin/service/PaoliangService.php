<?php

namespace app\admin\service;

use app\admin\model\BiPaoLiangModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\admin\model\PaoLiangModel;
use app\admin\model\RoomPromotionConfModel;
use app\admin\model\RoomPromotionStatsByDayModel;
use app\admin\model\RoomPromotionStatsModel;
use think\facade\Db;

class PaoliangService
{
    protected static $instance;

    protected $pagenum = 20;
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new PaoliangService();
        }
        return self::$instance;
    }

    //编辑跑量
    public function zbRoomPromotionConfSave($id, $room_name, $room_id, $type, $rmb, $start_time, $end_time)
    {
        $data = [
            'room_name' => $room_name,
            'room_id' => $room_id,
            'type' => $type,
            'rmb' => $rmb,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ];
        $is = RoomPromotionConfModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //添加跑量
    public function zbRoomPromotionConfAdd($room_name, $room_id, $type, $rmb, $start_time, $end_time, $admin_id = 0)
    {
        // foreach ($room_name as $key => $value) {
        //     $list[$key]['room_name'] = $value;
        // }
        foreach ($room_id as $key => $value) {
            $list[$key]['room_id'] = $value;
        }
        foreach ($type as $key => $value) {
            $list[$key]['type'] = $value;
        }
        foreach ($rmb as $key => $value) {
            $list[$key]['rmb'] = $value;
        }
        foreach ($start_time as $key => $value) {
            $list[$key]['start_time'] = $value;
        }
        foreach ($end_time as $key => $value) {
            $list[$key]['end_time'] = $value;
        }
        // foreach ($list as $k => $v) {
        //     if (empty($v['room_name'])) {
        //         unset($list[$k]);
        //     }
        // }

        if (count($list) < 1) {
            echo json_encode(['code' => 500, 'msg' => '参数必填']);die;
        }

        foreach ($list as $k => $v) {
            if (date('Y-m-d', strtotime($v['start_time'])) != date('Y-m-d', strtotime($v['end_time']))) {
                echo json_encode(['code' => 500, 'msg' => '开始结束日期必须是同一天']);die;
            }

            $room_info = LanguageroomModel::getInstance()->getModel($v['room_id'])->where('id', $v['room_id'])->find();
            if (empty($room_info)) {
                echo json_encode(['code' => 500, 'msg' => '房间不存在']);die;
            }

            $data = [
                // 'room_name' => $v['room_name'],
                'room_id' => $v['room_id'],
                'type' => $v['type'],
                'rmb' => (int) $v['rmb'],
                'start_time' => $v['start_time'],
                'end_time' => $v['end_time'],
                'create_time' => date('Y-m-d H:i:s'),
                'operator' => $admin_id,
            ];
            $is = RoomPromotionConfModel::getInstance()->getModel()->insert($data);
            if ($is) {
                echo json_encode(['code' => 200, 'msg' => '添加成功']);die;
            } else {
                echo json_encode(['code' => 500, 'msg' => '添加失败']);die;
            }
        }
    }

    //新跑量配置
    public function zbRoomPromotionConf($where, $page = 1, $is_export = 0)
    {
        $offset = ($page - 1) * $this->pagenum;

        $count = RoomPromotionConfModel::getInstance()->getModel()->alias('A')->where($where)->count();

        $info = RoomPromotionConfModel::getInstance()->getModel()
            ->alias('A')
            ->field('
                A.*,
				A.id as promotion_id,
                B.date,
				reg_count,
                login_count,
                enter_users,
                enter_count,
                pay_users,
                promote_pay_amount,
                promote_pay_count,
                promote_pay_users,
                pay_amount,
                pay_count,
                total_pay_amount,
                total_pay_count,
                total_pay_users,
                member_pay_users,
                member_pay_amount,
                member_pay_count,
                total_member_pay_amount,
                total_member_pay_count,
                total_member_pay_users,
                roi')
            ->leftJoin("bi_days_room_promotion_stats B", "A.id = B.promotion_id and B.date = DATE_FORMAT(A.start_time,'%Y-%m-%d')")
            ->where($where)
            ->order('A.start_time desc');

        if (!$is_export) {
            $info = $info->limit($offset, $this->pagenum);
        }

        $info = $info->select()->toArray();
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $this->pagenum);
        return ['page_array' => $page_array, 'list' => $info];
    }

    //新跑量留存
    public function PromotionXinzeng($id)
    {
        $days = ['day_1' => 1, 'day_3' => 3, 'day_7' => 7, 'day_15' => 15, 'day_30' => 30];
        $promotion = RoomPromotionConfModel::getInstance()->getModel()->where('id', (int) $id)->find();
        $end_time = $promotion->end_time;
        $retention_days = $this->getRetentionDays($days, $end_time);

        $retention_info = RoomPromotionStatsModel::getInstance()->getModel()->where('date', 'in', array_values($retention_days))->where('promotion_id', $id)->column('date,login_count,login_users');
        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day]['login_count'] = 0;
            $data[$retention_day]['login_users'] = [];
            if (array_key_exists($retention_date, $retention_info)) {
                $data[$retention_day]['login_count'] = $retention_info[$retention_date]['login_count'];
                $data[$retention_day]['login_users'] = explode(',', $retention_info[$retention_date]['login_users']);
            }
        }
        return $data;
    }

    //新跑量数据每日数据
    public function roomPromotionDayData($page, $where, $is_export = 0)
    {
        $offset = ($page - 1) * $this->pagenum;

        $count = Db::table('bi_days_room_promotion_stats_by_day')->where($where)->where('date = promotion_date')->count();

        $list = Db::table('bi_days_room_promotion_stats_by_day')
            ->field(
                [
                    'id',
                    'price',
                    'date',
                    'promotion_date',
                    'reg_count',
                    'login_count',
                    'enter_users',
                    'enter_count',
                    'promote_pay_amount',
                    'promote_pay_count',
                    'promote_pay_users',
                ]
            )
            ->where($where)
            ->having('date = promotion_date')
            ->order('date desc');

        if (!$is_export) {
            $list = $list->limit($offset, $this->pagenum);
        }

        $list = $list->select()->toArray();
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $this->pagenum);
        return ['page_array' => $page_array, 'list' => $list];
    }

    //新版跑量充值用户
    public function PromotionChongzhiByDay($date)
    {
        $days = ['day_1' => 1, 'day_3' => 3, 'day_7' => 7, 'day_15' => 15, 'day_30' => 30];
        $retention_days = $this->getRetentionDays($days, $date);

        $retention_info = RoomPromotionStatsByDayModel::getInstance()->getModel()
            ->where('date', 'in', array_values($retention_days))
            ->where('promotion_date', $date)
            ->column('date,pay_users,member_pay_users');

        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day]['pay_count'] = 0;
            $data[$retention_day]['pay_users'] = [];
            if (array_key_exists($retention_date, $retention_info)) {
                $pay_users = explode(',', $retention_info[$retention_date]['pay_users']);
                $member_pay_users = explode(',', $retention_info[$retention_date]['member_pay_users']);
                $users = array_filter(array_unique(array_merge($pay_users, $member_pay_users)));
                $data[$retention_day]['pay_count'] = count($users);
                $data[$retention_day]['pay_users'] = $users;
            }
        }

        return $data;
    }

    //新跑量日数据留存
    public function PromotionXinzengByDay($date)
    {
        $days = ['day_1' => 1, 'day_3' => 3, 'day_7' => 7, 'day_15' => 15, 'day_30' => 30];
        $retention_days = $this->getRetentionDays($days, $date);

        $retention_info = RoomPromotionStatsByDayModel::getInstance()->getModel()
            ->where('date', 'in', array_values($retention_days))
            ->where('promotion_date', $date)
            ->column('date,login_count,login_users');
        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day]['login_count'] = 0;
            $data[$retention_day]['login_users'] = [];
            if (array_key_exists($retention_date, $retention_info)) {
                $data[$retention_day]['login_count'] = $retention_info[$retention_date]['login_count'];
                $data[$retention_day]['login_users'] = explode(',', $retention_info[$retention_date]['login_users']);
            }
        }
        return $data;
    }

    public function getRetentionDays($days, $start)
    {
        $start_date = date("Y-m-d", strtotime($start));
        $retention_days = [];
        foreach ($days as $k => $day) {
            $retention_days[$k] = date("Y-m-d", strtotime($start_date) + $day * 24 * 60 * 60);
        }
        return $retention_days;
    }
    /**
     * @return mixed
     * @name 跑量列表
     */
    public function PaoLiangList($page, $daochu)
    {
        $pagenum = 10;
        $offset = ($page - 1) * $pagenum;
        $date = date("Y-m-d");

        $query = BiPaoLiangModel::getInstance()->getModel()
            ->field('a.*,b.nickname name,b.status,b.create,b.update,b.create_time,b.update_time,b.rmb consumption,b.room_id')
            ->alias('a')
            ->join('zb_paoliang b', 'a.promotion_id = b.id')
        // ->where('a.promotion_id', 54)
            ->where('a.date', $date);
        $count = $query->count();
        if ($daochu == 1) {
            $data = $query->order('b.create', 'desc')->select()->toArray();
        } else {
            $data = $query->order('b.create', 'desc')->limit($offset, $pagenum)->select()->toArray();
        }

        if (!empty($data)) {
            foreach ($data as &$item) {
                $item['zhibodaichongshu'] = $item['zhibodaichongshu'] / 10000;
                $item['daichong'] = $item['daichong'] / 10000;

                $date = date('Y-m-d', strtotime($item['create']));
                $days_1 = date('Y-m-d', strtotime($date) + 24 * 60 * 60);
                $days_3 = date('Y-m-d', strtotime($date) + 24 * 60 * 60 * 2);

                $item['day1_consume_count'] = Db::table('bi_days_yinliu_stats_by_times')->where('date', $days_1)->value('consume_count');
                $item['day3_consume_count'] = Db::table('bi_days_yinliu_stats_by_times')->where('date', $days_3)->value('consume_count');
            }
        }
        $page_array = [];
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $pagenum);
        if ($daochu == 1) {
            $this->daochu($data);
        }
        return ['page_array' => $page_array, 'list' => $data];
    }

    public function daochu($data)
    {
        $name = ['名称', '开始时间', '结束时间', '直播付费数', '直播付费人', '直播代充数', '直播代充人', '新增', '价格', '充值', '代充', '回收', '充值人数', '代充人数', '新增留存', '充值留存', '消费人数', '消费次留人数', '消费三留人数'];
        $string = implode(",", $name) . "\n";
        foreach ($data as $key => $value) {
            $outArray['name'] = $value['name'];
            $outArray['create'] = $value['create'];
            $outArray['update'] = $value['update'];
            $outArray['fufeishu'] = $value['fufeishu'];
            $outArray['fufeirenshu'] = $value['fufeirenshu'];
            $outArray['zhibodaichongshu'] = $value['zhibodaichongshu'];
            $outArray['zhibodaichongren'] = $value['zhibodaichongren'];
            $outArray['count'] = $value['count'];
            $outArray['consumption'] = $value['consumption'];
            $outArray['rmb'] = $value['rmb'];
            $outArray['daichong'] = $value['daichong'];
            $outArray['huishou'] = $value['rmb'] + $value['daichong'] - $value['consumption'];
            $outArray['uidcount'] = $value['uidcount'];
            $outArray['daichongren'] = $value['daichongren'];
            $outArray['logincount'] = $value['logincount'];
            $outArray['loginrmbcount'] = $value['loginrmbcount'];
            $outArray['consume_count'] = $value['consume_count'];
            $outArray['day1_consume_count'] = $value['day1_consume_count']; //次留
            $outArray['day3_consume_count'] = $value['day3_consume_count']; //三留
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = '跑量配置' . date('YmdHis') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    //跑量编辑
    public function PaoLiangSave($id, $nickname, $rmb, $create, $update)
    {
        $data = [
            'nickname' => $nickname,
            'rmb' => $rmb,
            'create' => $create,
            'update' => $update,
        ];
        $is = PaoLiangModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //添加跑量
    public function AddPaoLiang($nickname, $rmb, $create, $update, $room_id)
    {
        foreach ($nickname as $key => $value) {
            $list[$key]['nickname'] = $value;
        }
        foreach ($rmb as $key => $value) {
            $list[$key]['rmb'] = $value;
        }
        foreach ($create as $key => $value) {
            $list[$key]['create'] = $value;
        }
        foreach ($update as $key => $value) {
            $list[$key]['update'] = $value;
        }
        foreach ($room_id as $key => $value) {
            $list[$key]['room_id'] = $value;
        }
        foreach ($list as $k => $v) {
            if (empty($v['nickname'])) {
                unset($list[$k]);
            }
        }
        if (count($list) >= 1) {
            foreach ($list as $k => $v) {
                $data = [
                    'nickname' => $v['nickname'],
                    'rmb' => $v['rmb'],
                    'create' => $v['create'],
                    'update' => $v['update'],
                    'room_id' => $v['room_id'],
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                $is = PaoLiangModel::getInstance()->getModel()->insert($data);
                if ($is) {
                    echo json_encode(['code' => 200, 'msg' => '添加成功']);die;
                } else {
                    echo json_encode(['code' => 500, 'msg' => '添加失败']);die;
                }
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '参数必填']);die;
        }

    }

    //新版跑量充值用户
    public function PromotionChongzhi($id)
    {
        $days = ['day_1' => 1, 'day_3' => 3, 'day_7' => 7, 'day_15' => 15, 'day_30' => 30];
        $promotion = RoomPromotionConfModel::getInstance()->getModel()->where('id', (int) $id)->find();
        $end_time = $promotion->end_time;
        $retention_days = $this->getRetentionDays($days, $end_time);

        $retention_info = RoomPromotionStatsModel::getInstance()->getModel()->where('date', 'in', array_values($retention_days))->where('promotion_id', $id)->column('date,pay_users,member_pay_users');

        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day]['pay_count'] = 0;
            $data[$retention_day]['pay_users'] = [];
            if (array_key_exists($retention_date, $retention_info)) {
                $pay_users = explode(',', $retention_info[$retention_date]['pay_users']);
                $member_pay_users = explode(',', $retention_info[$retention_date]['member_pay_users']);
                $users = array_filter(array_unique(array_merge($pay_users, $member_pay_users)));
                $data[$retention_day]['pay_count'] = count($users);
                $data[$retention_day]['pay_users'] = $users;
            }
        }

        return $data;
    }

    //付费导出
    public function PaoLiangDaoChu($id)
    {
        $where[] = ['register_time', '>', PaoLiangModel::getInstance()->getModel()->where('id', $id)->value('create')];
        $where[] = ['register_time', '<', PaoLiangModel::getInstance()->getModel()->where('id', $id)->value('update')];
        $where[] = ['invitcode', 'not in', MarketChannelModel::getInstance()->getModel()->column('invitcode')];

        $member_list = MemberModel::getInstance()->getWhereAllData($where, "id");

        $uid = implode(",", array_column($member_list, 'id'));
        $data = Db::query('select uid,sum(rmb) rmb from zb_chargedetail where status in (1,2) and uid in (' . $uid . ') group by uid order by sum(rmb) desc');
        $name = ['付费id', '充值总额'];
        $string = implode(",", $name) . "\n";
        foreach ($data as $key => $value) {
            $outArray['uid'] = $value['uid']; //充值总豆
            $outArray['rmb'] = $value['rmb']; //充值总豆
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = '充值详情' . date('YmdHis') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }
    //删除跑量
    public function DelPaoLiang($id)
    {
        $is = PaoLiangModel::getInstance()->getModel()->where('id', $id)->delete();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function getUserInfo(array $users)
    {
        // return MemberModel::whereIn('id', $users)->field(['nickname', 'id uid'])->select()->toArray();
        return MemberModel::getInstance()->getWhereAllData([["id", "in", $users]], 'id uid,nickname');
    }

    public function getPayDataByUid(array $users)
    {
        return Db::table('bi_days_user_charge')
            ->field('uid,ROUND(sum(amount/10),2) amount')
            ->whereIn('uid', $users)
            ->group('uid')
            ->select()
            ->toArray();
    }

    public function getPaySumDataByUid(array $users)
    {
        return Db::table('bi_days_user_charge')
            ->whereIn('uid', $users)
            ->sum('amount');
    }

    public function getPayTotalUsersByUid(array $users)
    {
        return Db::table('bi_days_user_charge')
            ->distinct(true)
            ->whereIn('uid', $users)
            ->column('uid');
    }

    public function getPayUsers(array $users)
    {
        return Db::table('bi_days_user_charge')->distinct(true)->whereIn('uid', $users)->column('uid');
    }

    public function getPayDataByUidAndType(array $users, $type = 0)
    {
        $res = Db::table('bi_days_user_charge')->field('uid,ROUND(sum(amount/10),2) pay_amount');

        if ($type) {
            $res = $res->where('type', $type);
        }

        return $res->whereIn('uid', $users)
            ->group('uid')
            ->select()
            ->toArray();
    }

    public function getConsumeDataByUid(array $users, $start_date = '', $end_date = '')
    {
        $query = Db::table('bi_days_user_gift_datas_bysend_type')
            ->field('uid,ROUND(sum(consume_amount/10),2) consume_amount');
        if ($start_date) {
            $query = $query->where('date', '>=', $start_date);
        }

        if ($end_date) {
            $query = $query->where('date', '<', $end_date);
        }

        return $query->where('type', 1)
            ->whereIn('uid', $users)
            ->group('uid')
            ->select()
            ->toArray();
    }

    public function getPromoteRetentionById(string $type, $promotion, $is_consume = 0)
    {
        $columns = ['留存日期', '留存人数', '留存率', '当日消费总金额'];

        $date = $promotion['date'];
        $info = [];
        if ($type == 'register') {
            $users = array_filter(explode(',', $promotion['enter_users']));
            $info = $this->getRetentionInfo(RetentionService::getInstance()->getRetentionByType($users, $type, $date), $is_consume);
        }

        if ($type == 'charge') {
            $users = json_decode($promotion['promote_pay_users'], true);
            $users = $users == null ? [] : $users;
            $info = $this->getRetentionInfo(RetentionService::getInstance()->getRetentionByType($users, $type, $date), $is_consume);
        }

        if ($type == 'room_consume') {
            $users = array_filter(explode(',', $promotion['enter_users']));
            $room_id = $promotion['room_id'];
            $info = $this->getRetentionInfo(RetentionService::getInstance()->getRetentionByType($users, $type, $date, $room_id), $is_consume);
        }

        return ['info' => $info, 'columns' => $columns];
    }

    public function getRetentionInfo($info, $is_consume)
    {
        // 计算当日消费
        if (!empty($info)) {
            foreach ($info as $_ => &$retention_info) {
                if ($is_consume) {
                    $users = explode(',', $retention_info['users']);
                    $consume_info = $this->getConsumeDataByUid($users, $retention_info['start_date'], $retention_info['end_date']);
                    $consume_map = array_column($consume_info, null, 'uid');
                    $retention_info['consume_map'] = $consume_map;
                    $retention_info['consume_amount'] = round(array_sum(array_column($consume_info, 'consume_amount')), 2);
                }
            }
            return $info;
        }
        return [];
    }

    public function getPromoteDetailByUids($type, string $uids)
    {
        $users = explode(',', $uids);
        if ($type == 1) {
            // 进厅用户信息
            $info = $this->getPromoteUserInfo($users);
            $columns = ['用户昵称', '用户ID'];
        }

        if ($type == 2) {
            $info = $this->getPromotePayData($users);
            $columns = ['用户昵称', '用户ID', '用户消费金额', '累计充值金额'];
        }

        if ($type == 3) {
            $info = $this->getTotalPayData($users);
            $columns = ['用户昵称', '用户ID', '用户消费金额', '用户直充金额', '用户代充金额', '累计充值金额'];
        }

        return ['info' => $info, 'columns' => $columns];
    }

    public function getPromoteUserInfo(array $users)
    {
        $user_info = array_column($this->getUserInfo($users), null, 'uid');

        $data = [];
        foreach ($users as $k => $uid) {
            $item = [];
            $item[] = isset($user_info[$uid]) ? $user_info[$uid]['nickname'] : '';
            $item[] = $uid;

            $data[$k] = $item;
        }
        return $data;
    }

    public function getPromotePayData(array $users)
    {
        $user_info = array_column($this->getUserInfo($users), null, 'uid');
        $pay_info = array_column($this->getPayDataByUidAndType($users), null, 'uid');
        $consume_info = array_column($this->getConsumeDataByUid($users), null, 'uid');

        $data = [];
        foreach ($users as $k => $uid) {
            $item = [];
            $item[] = isset($user_info[$uid]) ? $user_info[$uid]['nickname'] : '';
            $item[] = $uid;
            $item[] = isset($consume_info[$uid]) ? $consume_info[$uid]['consume_amount'] : 0;
            $item[] = isset($pay_info[$uid]) ? $pay_info[$uid]['pay_amount'] : 0;

            $data[$k] = $item;
        }
        return $data;
    }

    public function getTotalPayData(array $users)
    {
        $users = $this->getPayUsers($users);
        $user_info = array_column($this->getUserInfo($users), null, 'uid');
        $direct_pay_info = array_column($this->getPayDataByUidAndType($users, 1), null, 'uid');
        $agent_pay_info = array_column($this->getPayDataByUidAndType($users, 2), null, 'uid');
        $consume_info = array_column($this->getConsumeDataByUid($users), null, 'uid');

        $data = [];
        foreach ($users as $k => $uid) {
            $item = [];

            $item[] = isset($user_info[$uid]) ? $user_info[$uid]['nickname'] : '';
            $item[] = $uid;

            $consume_amount = isset($consume_info[$uid]) ? $consume_info[$uid]['consume_amount'] : 0;
            $direct_pay_amount = isset($direct_pay_info[$uid]) ? $direct_pay_info[$uid]['pay_amount'] : 0;
            $agent_pay_amount = isset($agent_pay_info[$uid]) ? $agent_pay_info[$uid]['pay_amount'] : 0;

            $item[] = $consume_amount;
            $item[] = $direct_pay_amount;
            $item[] = $agent_pay_amount;

            $pay_amount = $direct_pay_amount + $agent_pay_amount;
            $item[] = $pay_amount;

            $data[$k] = $item;
        }
        return $data;
    }
}
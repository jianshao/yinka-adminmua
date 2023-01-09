<?php

namespace app\admin\service;

use app\admin\common\RedisKeysConst;
use app\admin\model\ChargedetailModel;
use app\admin\model\LogindetailModel;
use app\admin\model\PaoLiangModel;
use app\admin\model\PromotionModel;
use app\admin\model\PromotionRoomConfModel;
use app\admin\model\PromotionRoomTimesConfModel;
use app\admin\model\RoomPromotionStatsByTimesModel;
use app\common\RedisCommon;

class PromotionService
{

    public $days = ['day_0' => 0, 'day_1' => 1, 'day_2' => 2, 'day_3' => 3, 'day_4' => 4, 'day_5' => 5, 'day_6' => 6, 'day_7' => 7, 'day_15' => 15, 'day_30' => 30];
    protected static $instance;

    protected $pagenum = 20;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addOrUpdatePromotion($data, $where = [])
    {
        if (empty($where)) {
            $res = PromotionModel::getInstance()->getModel()->insertAll($data);
        } else {
            $res = PromotionModel::getInstance()->getModel()->where([$where])->update($data);
        }

        if ($res) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        }
        echo json_encode(['code' => 500, 'msg' => '操作失败']);
        die;
    }

    public function addOrUpdatePromotionRoom($data)
    {
        foreach ($data as $_ => $promote) {
            $res = PromotionRoomConfModel::getInstance()->getModel()->insert($promote);

            $promote_info = PromotionRoomConfModel::getInstance()->getModel()
                ->where('room_id', $promote['room_id'])
                ->where('promote_id', $promote['promote_id'])
                ->find();
            RedisCommon::getInstance()->getRedis(['select' => 3])->zadd(RedisKeysConst::INVITCODE_LIST, strtotime($promote_info['create_time']), $promote_info['id']);
        }

        if ($res) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        }
        echo json_encode(['code' => 500, 'msg' => '操作失败']);
        die;
    }

    public function addOrUpdatePromotionRoomTimes($data, $where = [])
    {
        if (empty($where)) {
            $res = PromotionRoomTimesConfModel::getInstance()->getModel()->insert($data);
        } else {
            $res = PromotionRoomTimesConfModel::getInstance()->getModel()->where([$where])->update($data);
        }
        if ($res) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        }
        echo json_encode(['code' => 500, 'msg' => '操作失败']);
        die;
    }

    //编辑跑量
    public function PromotionRoomTimesSave($id, $room_name, $room_id, $type, $rmb, $start_time, $end_time)
    {
        $data = [
            'room_name' => $room_name,
            'room_id' => $room_id,
            'type' => $type,
            'rmb' => $rmb,
            'start_time' => $start_time,
            'end_time' => $end_time,
        ];
        $is = PromotionRoomTimesConfModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        }
        echo json_encode(['code' => 500, 'msg' => '操作失败']);
        die;
    }

    //添加跑量
    public function PromotionRoomTimesAdd($promote_code, $rmb, $start_time, $end_time)
    {
        foreach ($promote_code as $key => $value) {
            $list[$key]['promote_code'] = $value;
            $list[$key]['operator'] = $this->token['id'] ?? 0;
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

        if ($list) {
            $res = PromotionRoomTimesConfModel::getInstance()->getModel()->insertAll($list);
            if ($res) {
                echo json_encode(['code' => 200, 'msg' => '添加成功']);
                die;
            } else {
                echo json_encode(['code' => 500, 'msg' => '添加失败']);
                die;
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '参数错误']);
            die;
        }
    }

    //推广渠道列表
    public function promotionList($page, $where)
    {
        $offset = ($page - 1) * $this->pagenum;
        $count = PromotionModel::getInstance()->getModel()->where($where)->count();
        $list = PromotionModel::getInstance()->getModel()
            ->where($where)
            ->order('id desc')
            ->limit($offset, $this->pagenum)
            ->select()
            ->toArray();
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $this->pagenum);
        return ['page_array' => $page_array, 'list' => $list];
    }

    //推广渠道列表
    public function promotionRoomList($page, $room_id = 0, $promote_id = 0, $token = 0)
    {
        $offset = ($page - 1) * $this->pagenum;
        $query = PromotionRoomConfModel::getInstance()->getModel();

        if ($room_id > 0) {
            $query = $query->where('room_id', $room_id);
        }

        if ($promote_id > 0) {
            $query = $query->where('promote_id', $promote_id);
        }

        //设置特定的运营人员的只能看到他自己添加的数据--宋阳提的需求
        if ($token > 0 && in_array($token, config("operate_black"))) {
            $query = $query->where('operator', $token);
        }

        $count = $query->count();
        $list = $query
            ->order('id desc')
            ->limit($offset, $this->pagenum)
            ->select()
            ->toArray();
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $this->pagenum);
        return ['page_array' => $page_array, 'list' => $list];
    }

    //房间场次信息
    public function getPromotionRoomTimes($page, $where, $export)
    {
        $offset = ($page - 1) * $this->pagenum;
        $date = date("Y-m-d");

        $count = PromotionRoomTimesConfModel::getInstance()->getModel()->alias('A')->where($where)->count();
        $info = [];
        $info = PromotionRoomTimesConfModel::getInstance()->getModel()
            ->alias('A')
            ->field('
            A.*,
            B.reg_count,
            login_count,
            enter_users,
            enter_count,
            pay_users,
            promote_pay_amount,
            promote_pay_count,
            pay_amount,
            pay_count,
            total_pay_amount,
            total_pay_count,
            member_pay_users,
            member_pay_amount,
            member_pay_count,
            total_member_pay_amount,
            total_member_pay_count,
            consume_users,
            consume_count,
            roi')
            ->leftJoin('bi_days_room_promotion_stats_by_times B', "A.id=B.promotion_id and B.date = '{$date}'")
            ->where($where)
            ->order('A.id desc');
        if (!$export) {
            $info = $info->limit($offset, $this->pagenum);
        }

        $info = $info->select()->toArray();

        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $this->pagenum);
        return ['page_array' => $page_array, 'list' => $info];
    }

    //跑量留存导出
    public function PaoLiangLiuCun($id)
    {
        $create = PaoLiangModel::getInstance()->getModel()->where('id', $id)->value('create');
        $update = PaoLiangModel::getInstance()->getModel()->where('id', $id)->value('update');
        $uid = array_column($this->uid($create, $update), 'id');

        $fufeiuid = ChargedetailModel::getInstance()->getModel()->where([['status', 'in', [1, 2]], ['uid', 'in', $uid]])->group('uid')->column('uid');

        $where1[] = ['ctime', '>', strtotime(date('Y-m-d 00:00:00', strtotime($create))) + (86400 * 1)];
        $where1[] = ['ctime', '<', strtotime(date('Y-m-d 23:59:59', strtotime($create))) + (86400 * 1)];
        $where1[] = ['user_id', 'in', $uid];
        $arr = LogindetailModel::getInstance()->getModel()->where($where1)->group('user_id')->field('user_id')->select()->toArray();
        $data['ciliu'] = (int) count($arr);

        $where2[] = ['ctime', '>', strtotime(date('Y-m-d 00:00:00', strtotime($create))) + (86400 * 3)];
        $where2[] = ['ctime', '<', strtotime(date('Y-m-d 23:59:59', strtotime($update))) + (86400 * 3)];
        $where2[] = ['user_id', 'in', $uid];
        $arr = LogindetailModel::getInstance()->getModel()->where($where2)->group('user_id')->field('user_id')->select()->toArray();
        $data['sanliu'] = (int) count($arr);

        $where3[] = ['ctime', '>', strtotime(date('Y-m-d 00:00:00', strtotime($create))) + (86400 * 7)];
        $where3[] = ['ctime', '<', strtotime(date('Y-m-d 23:59:59', strtotime($update))) + (86400 * 7)];
        $where3[] = ['user_id', 'in', $uid];
        $arr = LogindetailModel::getInstance()->getModel()->where($where3)->group('user_id')->field('user_id')->select()->toArray();
        $data['qiliu'] = (int) count($arr);

        $fufeiwhere1[] = ['ctime', '>', strtotime(date('Y-m-d 00:00:00', strtotime($create)))];
        $fufeiwhere1[] = ['ctime', '<', strtotime(date('Y-m-d 23:59:59', strtotime($update)))];
        $fufeiwhere1[] = ['user_id', 'in', $fufeiuid];
        $arr = LogindetailModel::getInstance()->getModel()->where($fufeiwhere1)->group('user_id')->field('user_id')->select()->toArray();
        $data['fufeiciliu'] = (int) count($arr);

        $fufeiwhere2[] = ['ctime', '>', strtotime(date('Y-m-d 00:00:00', strtotime($create))) + (86400 * 3)];
        $fufeiwhere2[] = ['ctime', '<', strtotime(date('Y-m-d 23:59:59', strtotime($update))) + (86400 * 3)];
        $fufeiwhere2[] = ['user_id', 'in', $fufeiuid];
        $arr = LogindetailModel::getInstance()->getModel()->where($fufeiwhere2)->group('user_id')->field('user_id')->select()->toArray();
        $data['fufeisanliu'] = (int) count($arr);

        $fufeiwhere3[] = ['ctime', '>', strtotime(date('Y-m-d 00:00:00', strtotime($create))) + (86400 * 7)];
        $fufeiwhere3[] = ['ctime', '<', strtotime(date('Y-m-d 23:59:59', strtotime($update))) + (86400 * 7)];
        $fufeiwhere3[] = ['user_id', 'in', $fufeiuid];
        $arr = LogindetailModel::getInstance()->getModel()->where($fufeiwhere3)->group('user_id')->field('user_id')->select()->toArray();
        $data['fufeiqiliu'] = (int) count($arr);
        return $data;
    }

    //新跑量留存
    public function PromotionXinzeng($id)
    {
        $promotion = PromotionRoomTimesConfModel::getInstance()->getModel()->where('id', (int) $id)->find();
        $start_time = $promotion->start_time;
        $retention_days = $this->getRetentionDays($this->days, $start_time);

        $retention_info = RoomPromotionStatsByTimesModel::getInstance()->getModel()->where('date', 'in', array_values($retention_days))->where('promotion_id', $id)->column('date,login_count');

        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day] = 0;
            if (array_key_exists($retention_date, $retention_info)) {
                $data[$retention_day] = $retention_info[$retention_date]['login_count'];
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
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    //添加跑量
    public function AddPaoLiang($nickname, $rmb, $create, $update)
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
                    'create_time' => date('Y-m-d H:i:s'),
                ];
                $is = PaoLiangModel::getInstance()->getModel()->insert($data);
                if ($is) {
                    echo json_encode(['code' => 200, 'msg' => '添加成功']);
                    die;
                } else {
                    echo json_encode(['code' => 500, 'msg' => '添加失败']);
                    die;
                }
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '参数必填']);
            die;
        }

    }

    //新版跑量消费用户
    public function PromotionConsume($id)
    {
        $promotion = PromotionRoomTimesConfModel::getInstance()->getModel()->where('id', (int) $id)->find();
        $start_time = $promotion->start_time;
        $retention_days = $this->getRetentionDays($this->days, $start_time);

        $retention_info = RoomPromotionStatsByTimesModel::getInstance()->getModel()->where('date', 'in', array_values($retention_days))->where('promotion_id', $id)->column('date,room_consume_amount');

        //历史消费总额
        $total_consume = RoomPromotionStatsByTimesModel::getInstance()->getModel()->where('promotion_id', $id)->sum('room_consume_amount');
        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day] = 0;
            if (array_key_exists($retention_date, $retention_info)) {
                $data[$retention_day] = $retention_info[$retention_date]['room_consume_amount'];
            }
        }
        $data['day_all'] = $total_consume;
        return $data;
    }

    //新版跑量消费用户
    public function PromotionBagConsume($id)
    {
        $promotion = PromotionRoomTimesConfModel::getInstance()->getModel()->where('id', (int) $id)->find();
        $start_time = $promotion->start_time;
        $retention_days = $this->getRetentionDays($this->days, $start_time);

        $retention_info = RoomPromotionStatsByTimesModel::getInstance()->getModel()->where('date', 'in', array_values($retention_days))->where('promotion_id', $id)->column('date,room_bagconsume_amount');

        //历史消费总额
        $total_consume = RoomPromotionStatsByTimesModel::getInstance()->getModel()->where('promotion_id', $id)->sum('room_bagconsume_amount');
        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day] = 0;
            if (array_key_exists($retention_date, $retention_info)) {
                $data[$retention_day] = $retention_info[$retention_date]['room_bagconsume_amount'];
            }
        }
        $data['day_all'] = $total_consume;
        return $data;
    }

    //消费人数
    public function PromotionConsumeCount($id)
    {
        $promotion = PromotionRoomTimesConfModel::getInstance()->getModel()->where('id', (int) $id)->find();
        $start_time = $promotion->start_time;
        $retention_days = $this->getRetentionDays($this->days, $start_time);

        $retention_info = RoomPromotionStatsByTimesModel::getInstance()->getModel()->where('date', 'in', array_values($retention_days))->where('promotion_id', $id)->column('date,consume_count');

        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day] = 0;
            if (array_key_exists($retention_date, $retention_info)) {
                $data[$retention_day] = $retention_info[$retention_date]['consume_count'];
            }
        }
        return $data;
    }

    //新版跑量充值用户
    public function PromotionChongzhi($id)
    {
        $promotion = PromotionRoomTimesConfModel::getInstance()->getModel()->where('id', (int) $id)->find();
        $start_time = $promotion->start_time;
        $retention_days = $this->getRetentionDays($this->days, $start_time);

        $retention_info = RoomPromotionStatsByTimesModel::getInstance()->getModel()->where('date', 'in', array_values($retention_days))->where('promotion_id', $id)->column('date,pay_count,pay_login_count,member_pay_users');

        $data = [];
        foreach ($retention_days as $retention_day => $retention_date) {
            $data[$retention_day] = [
                'pay_login_count' => 0,
                'pay_count' => 0,
            ];
            if (array_key_exists($retention_date, $retention_info)) {
                // $pay_users = explode(',', $retention_info[$retention_date]['pay_users']);
                // $member_pay_users = explode(',', $retention_info[$retention_date]['member_pay_users']);
                // $users = array_unique(array_merge($pay_users, $member_pay_users));
                $data[$retention_day]['pay_login_count'] = $retention_info[$retention_date]['pay_login_count'];
                $data[$retention_day]['pay_count'] = $retention_info[$retention_date]['pay_count'];
            }
        }
        return $data;
    }

    //删除跑量
    public function DelPaoLiang($id)
    {
        $is = PaoLiangModel::getInstance()->getModel()->where('id', $id)->delete();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

}

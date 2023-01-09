<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\LanguageroomModel;
use app\admin\model\MemberModel;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class AsaComeRoomController extends AdminBaseController
{
    const ASAROOMTEMPSAVE = "puton_room_come_temp";
    const ASAROOMSAVE = "puton:come_room:type:";
    const CACHE_KEY = "%s:%s:%s:%s";

    /**
     * 推荐房间列表
     */
    public function asaComeRoomList()
    {
        $s_type = Request::param('s_type', ''); //结束的时间

        if ($this->request->param("isRequest") == 1) {
            $redis = $this->getRedis();
            $getRes = $redis->hGetAll(SELF::ASAROOMTEMPSAVE);
            $content = array_values($getRes);
            $data = [];
            foreach ($content as $k => $v) {
                //循环将数组的键值拼接起来
                $params = json_decode($v, true);
                $room_id = $params['room_id'] ?? 0;
                $roominfo = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->find();
                if (!empty($roominfo)) {
                    if (!empty($s_type)) {
                        if ($params['type'] != $s_type) {
                            continue;
                        }
                    }
                    $data[$k]['begin_time'] = $params['begin_time'] ?? '';
                    $data[$k]['room_id'] = $params['room_id'] ?? '';
                    $data[$k]['end_time'] = $params['end_time'] ?? '';
                    $data[$k]['type'] = $params['type'] ?? '';
                    $data[$k]['room_name'] = $roominfo['room_name'] ?? '';
                }
            }

            echo json_encode(["msg" => '', "count" => count($content), "code" => 0, "data" => $data]);
        } else {
            View::assign('token', $this->request->param('token'));
            return View::fetch('asacomeroom/configlist');
        }

    }

    /**
     * 添加推荐房间列表
     */
    public function asaComeRoomAdd()
    {
        $room_id = Request::param('room_id', 0); //房间ID
        $begin_time = Request::param('begin_time', ''); //开始的时间
        $end_time = Request::param('end_time', ''); //结束的时间
        $type = Request::param('type', ''); //数据类型
        if (!$room_id) {
            echo json_encode(["code" => 1, "msg" => "房间ID为空"]);
            die;
        }
        $res = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->find();
        if (!$res) {
            echo json_encode(["code" => 1, "msg" => "房间ID为空"]);
            die;
        }

        if (strtotime($begin_time) >= strtotime($end_time) || strtotime($end_time) < time()) {
            echo json_encode(["code" => 1, "msg" => "时间设置错误"]);
            die;
        }

        if (date('Y-m-d', strtotime($begin_time)) != date('Y-m-d', strtotime($end_time))) {
            echo json_encode(["code" => 1, "msg" => "禁止跨天设置"]);
            die;
        }

        if (empty($type)) {
            echo json_encode(["code" => 1, "msg" => "数据类型设置错误"]);
            die;
        }

        $hashkey = sprintf(SELF::CACHE_KEY, $room_id, $type, strtotime($begin_time), strtotime($end_time));

        $redis = $this->getRedis();
        $body = [
            "room_id" => $room_id,
            "begin_time" => $begin_time,
            "end_time" => $end_time,
            "type" => $type,
        ];

        $haveRes = $redis->hget(SELF::ASAROOMTEMPSAVE, $hashkey);
        if ($haveRes) {
            echo json_encode(["code" => 1, "msg" => "房间已经配置存在"]);
            die;
        }
        //比较没有过期的 没有删除的
        $currentTime = date('Y-m-d H:i:s');
        $historyRecord = Db::name('bi_asa_room_promotion')
            ->where('begin_time', '<=', $currentTime)
            ->where('end_time', '>', $currentTime)
            ->where("status", "=", 0)
            ->where("room_id", "=", $room_id)
            ->where("type", "=", $type)
        //->fetchSql(true)
            ->select()->toArray();

        if ($historyRecord) {
            foreach ($historyRecord as $historyitem) {
                if ($this->isTimeCross(strtotime($historyitem['begin_time']), strtotime($historyitem['end_time']), strtotime($begin_time), strtotime($end_time))) {
                    echo json_encode(["code" => 1, "msg" => "时间设置有重叠"]);
                    die;
                }
            }
        }

        $redis->hset(SELF::ASAROOMTEMPSAVE, $hashkey, json_encode($body));
        Db::name("bi_asa_room_promotion")->insert([
            "room_id" => $room_id,
            "begin_time" => $begin_time,
            "end_time" => $end_time,
            "type" => $type,
        ]);
        echo json_encode(["code" => 0, "msg" => "插入成功"]);
        die;
    }

    /**
     * 取消推荐房间列表
     */
    public function asaComeRoomDel()
    {
        $room_id = Request::param('room_id'); //房间ID
        $type = Request::param('type'); //数据类型
        $begin_time = Request::param('begin_time'); //数据类型
        $end_time = Request::param('end_time'); //数据类型
        $hashkey = sprintf(SELF::CACHE_KEY, $room_id, $type, strtotime($begin_time), strtotime($end_time));
        $redis = $this->getRedis();
        $result = $redis->hGet(SELF::ASAROOMTEMPSAVE, $hashkey);
        if (!$result) {
            echo json_encode(["code" => -1, "msg" => "获取配置失败"]);
            Log::info('asaComeRoomDel:error:' . $this->token['username'] . '@' . json_encode(["room_id" => $room_id, "type" => $type]));
            exit;
        } else {
            $redis->hDel(SELF::ASAROOMTEMPSAVE, $hashkey);
            $redis->srem(SELF::ASAROOMSAVE . $type, $room_id);
            //更新数据库的里面的数据
            $id = Db::name("bi_asa_room_promotion")->where("room_id", "=", $room_id)->value("id");
            Db::name("bi_asa_room_promotion")->where('id', "=", $id)->update(['status' => 1]);
            Log::info('asaComeRoomDel:success:' . $this->token['username'] . '@' . json_encode(["room_id" => $room_id, "type" => $type]));
            echo json_encode(["code" => 0, "msg" => "操作成功"]);
            die;
        }

    }

    /**
     * PHP计算两个时间段是否有交集（边界重叠也算）
     *
     * @param string $beginTime1 开始时间1
     * @param string $endTime1 结束时间1
     * @param string $beginTime2 开始时间2
     * @param string $endTime2 结束时间2
     * @return bool
     */
    public function isTimeCross($beginTime1 = '', $endTime1 = '', $beginTime2 = '', $endTime2 = '')
    {
        $status = $beginTime2 - $beginTime1;
        if ($status > 0) {
            $status2 = $beginTime2 - $endTime1;
            if ($status2 > 0) {
                return false;
            } else {
                return true;
            }
        } else {
            $status2 = $endTime2 - $beginTime1;
            if ($status2 >= 0) {
                return true;
            } else {
                return false;
            }
        }

    }

    /*asa推广数据列表*/
    public function asapromotedataList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $daochu = $this->request->param("daochu", 0);
        $type = $this->request->param("s_type", '');
        $date_b = $this->request->param("date_b", date('Y-m-d', strtotime("-7days")));
        $date_e = $this->request->param("date_e", date('Y-m-d', strtotime("+1days")));
        $where = [];
        if ($date_e && $date_b) {
            $where[] = ['begin_time', '>', $date_b];
            $where[] = ['end_time', '<', $date_e];
        }

        if ($type) {
            $where[] = ['type', '=', $type];
        }

        if ($this->request->param("isRequest") == 1) {
            $res = Db::name("bi_asa_room_promotion")->where($where)->page($page, $limit)->order("id desc")->select()->toArray();
            $room_ids = array_column($res, "room_id");

            $roominfo = LanguageroomModel::getInstance()->getWhereAllData([['id', 'in', $room_ids]], 'id,room_name');
            $roomnameList = array_column($roominfo, null, "id");
            $sum_charge_number = [];
            $sum_charge_amount = 0;
            $sum_register_number = 0;
            foreach ($res as $key => $item) {
                /*
                首日付费率（当日付费人数/当日进厅人数），
                次日留存（首日付费人数在第二天登录的人数/当日付费人数-用百分比统计），
                三日留存（首日付费人数在第三天登录的人数/当日付费人数-用百分比统计）
                 */
                $res[$key]['room_name'] = $roomnameList[$item['room_id']]['room_name'] ?? '';
                $res[$key]['room_name_short'] = mb_substr($res[$key]['room_name'], 0, 3);
                $charge_rate = $this->divedFunc($item['user_charge_number'], $item['enter_user_number'], 2);
                $res[$key]['charge_rate'] = $charge_rate;
                $res[$key]['keep_2_rate'] = 0;
                $res[$key]['keep_3_rate'] = 0;
                $date = date('Y-m-d', strtotime($item['begin_time']));
                $keepinfo = Db::name("bi_user_keep_day")->field('keep_2,keep_3')
                    ->where('date', '=', $date)->where('type', '=', 'charge')->find();
                //充值人数的更新 保存 方便计算留存
                $charge_users = $item['charge_users']; //当日引流的充值人数
                $charge_users_arr = explode(",", $charge_users);
                //充值用户的次日留存
                if (isset($keepinfo['keep_2'])) {
                    $keep_charge_arr = explode(",", $keepinfo['keep_2']);
                    $keep_2_charge_users = array_intersect($keep_charge_arr, $charge_users_arr);
                    $res[$key]['keep_2_rate'] = $this->divedFunc(count($keep_2_charge_users), count($charge_users_arr), 2) * 100 . "%";
                }
                //充值用户的3日留存
                if (isset($keepinfo['keep_3'])) {
                    $keep_charge_arr = explode(",", $keepinfo['keep_3']);
                    $keep_3_charge_users = array_intersect($keep_charge_arr, $charge_users_arr);
                    $res[$key]['keep_3_rate'] = $this->divedFunc(count($keep_3_charge_users), count($charge_users_arr), 2) * 100 . "%";
                }
                $sum_charge_amount += $item['user_charge_amount_sum']; //累计的充值金额
                $sum_register_number += $item['register_user_number']; //累计的注册人数
                if ($item['charge_users']) {
                    $charge_users_arr = explode(",", $item['charge_users']);
                    foreach ($charge_users_arr as $charge_users_item) {
                        if (!in_array($charge_users_item, $sum_charge_number)) {
                            array_unshift($sum_charge_number, $charge_users_item);
                        }
                    }
                }
            }

            if ($daochu == 1) {
                $headerArray = [
                    'room_id' => '房间ID',
                    'room_name' => '房间名称',
                    'begin_time' => '开始时间',
                    'end_time' => '结束时间',
                    'type' => '推广类型',
                    'register_user_number' => '注册人数',
                    'enter_user_number' => '进厅人数',
                    'user_charge_number' => '首日充值人数',
                    'user_charge_amount' => '首日付费金额',
                    'charge_rate' => '当日付费率',
                    'user_charge_number_sum' => '累计充值人数',
                    'user_charge_amount_sum' => '累计充值金额',
                    'keep_2_rate' => '次日留存',
                    'keep_3_rate' => '三日留存',
                ];
                $this->exportcsv($res, $headerArray);exit;
            }
            $count = Db::name("bi_asa_room_promotion")->where($where)->count();
            $hz = ["sum_charge_number" => count($sum_charge_number), "sum_charge_amount" => $sum_charge_amount, "sum_register_number" => $sum_register_number];
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res, "hz" => $hz];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('date_b', $date_b);
            View::assign('date_e', $date_e);
            View::assign('user_role_menu', $this->user_role_menu);
            return View::fetch('asacomeroom/asapromotelist');
        }
    }

    // asa引流充值详情
    public function asapromotechargelist()
    {
        $id = $this->request->param("id", 0);
        if ($this->request->param("isRequest") == 1) {
            $res = Db::name("bi_asa_room_promotion")->field('id,charge_users,begin_time,end_time')->where('id', '=', $id)->find();
            if (isset($res['charge_users']) && $res['charge_users']) {
                $uids = explode(",", $res['charge_users']);
                $chargeUserList = Db::name("bi_days_user_charge")
                    ->field("uid,amount,type")
                    ->where("uid", "in", $uids)
                    ->where("date", "=", date('Y-m-d', strtotime($res['begin_time'])))
                    ->select()
                    ->toArray();
                $data = array_map(function ($v) {
                    $v['amount'] = $v['amount'] / 10;
                    return $v;
                }, $chargeUserList);
                $data = ["msg" => '', "count" => count($chargeUserList), "code" => 0, "data" => $data];
                echo json_encode($data);
            }

        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('id', $id);
            return View::fetch('asacomeroom/chargelist');
        }

    }

    public function asapromoteuserlist()
    {

        $id = $this->request->param("id", 0);
        $type = $this->request->param("type", ''); //进厅:enterroom   注册:register
        if ($this->request->param("isRequest") == 1) {
            $uids = [];
            if ($type == 'enterroom') {
                $res = Db::name("bi_asa_room_promotion")->field('id,enter_users')->where('id', '=', $id)->find();
                $uids = explode(",", $res['enter_users']);
            }

            if ($type == 'register') {
                $res = Db::name("bi_asa_room_promotion")->field('id,register_users')->where('id', '=', $id)->find();
                $uids = explode(",", $res['register_users']);
            }

            $memberList = [];
            if (count($uids) >= 1) {
                $memberList = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], "id,register_time,register_channel");
            }

            $data = ["msg" => '', "count" => count($memberList), "code" => 0, "data" => $memberList];
            echo json_encode($data);

        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('id', $id);
            View::assign('type', $type);
            return View::fetch('asacomeroom/userlist');
        }

    }

    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }

}

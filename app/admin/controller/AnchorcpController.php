<?php
/**
 * Created by PhpStorm.
 * User: pussycat
 * Date: 2019/7/23
 * Time: 21:01
 */

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AnchorCpModel;
use app\admin\model\AnchorCpPromotionModel;
use app\admin\model\BiAnchorCpSendGiftModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiDaysUserSendgiftModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\MemberModel;
use app\admin\service\ExportExcelService;
use think\facade\Db;
use think\facade\Log;
use think\facade\View;
use Throwable;

class AnchorcpController extends AdminBaseController
{

    //主播cp模式列表
    public function AnchorcpList()
    {
        $uid_id = $this->request->param('user_id', 0, 'trim');
        $status = $this->request->param('status', 1, 'trim');
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $export = $this->request->param("export", 0, 'trim');
        $sortfield = $this->request->param("sortfield", '', 'trim');
        $sorttype = $this->request->param("sorttype", '', 'trim');
        $where = [];
        $where[] = ['status', '=', $status];
        if ($uid_id > 0) {
            $where[] = ["user_id", "=", $uid_id];
        }

        $orderstring = 'id desc';
        if ($sortfield && $sorttype) {
            if ($sortfield == 'user_id') {
                $orderstring = "$sortfield $sorttype";
            } elseif ($sortfield == 'guild_id') {
                $orderstring = "$sortfield $sorttype";
            }

        }

        if ($this->request->param('isRequest')) {

            $callbackFunc = function ($items) {
                $uids = array_column($items, "user_id");
                $where[] = ["id", "in", $uids];
                $memberInfo = MemberModel::getInstance()->getWhereAllData($where, "id,nickname,guild_id,username");
                $guilds = array_filter(array_column($memberInfo,"guild_id"));
                $memberList = array_column($memberInfo, null, "id");
                $condition[] = ["id", "in", $guilds];
                $memberGuildInfo = MemberGuildModel::getInstance()->getWhereAllData($condition, "id,nickname");
                $memberGuildList = array_column($memberGuildInfo, null, "id");
                $reward_amounts = BiDaysUserSendgiftModel::getInstance()->getModel()
                    ->field("touid,sum(reward_amount) as reward_amounts")
                    ->where([["touid","in",$uids]])->group("touid")->select()->toArray();
                $rewardamountByuid = array_column($reward_amounts,NULL,"touid");

                foreach ($items as &$item) {
                    $guild_id = $memberList[$item['user_id']]['guild_id'] ?? 0;
                    $item['username'] = $memberList[$item['user_id']]['username'] ?? '';
                    $item['nickname'] = $memberList[$item['user_id']]['nickname'] ?? '';
                    $item['guild_id'] = $guild_id;
                    $item['reward_amount'] = $rewardamountByuid[$item['user_id']]['reward_amounts'] ?? 0;
                    $item['guild_nickname'] = $memberGuildList[$guild_id]['nickname'] ?? '';
                }
                return $items;
            };

            if ($export == 1) {
                $resSource = AnchorCpModel::getInstance()->getModel()->where($where)->field("user_id");
                $headerArray = [
                    "user_id" => "主播ID",
                    "username" => "手机号",
                    "nickname" => "主播昵称",
                    "guild_id" => "工会ID",
                    "guild_nickname" => "工会昵称",
                    "reward_amount" => "累计收入(豆)",
                ];
                ExportExcelService::getInstance()->dataExpormetCsvByFormat($resSource, $headerArray, $callbackFunc);exit;
            }

            $res = AnchorCpModel::getInstance()->getModel()->field("user_id,create_time,id,status")
                ->where($where)->order($orderstring)->page($page, $limit)
                ->select()
                ->toarray();

            $res  = $callbackFunc($res);

            $count = AnchorCpModel::getInstance()->getModel()->where($where)->count();
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
            exit;
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('user_id', $this->request->param('user_id'));
            View::assign('status', $status);
            return View::fetch('anchorcp/anchorcplist');
        }
    }

    //添加主播cp
    public function anchorcpadd()
    {
        $user_id = $this->request->param('add_user_id', 0, 'trim');
        $where[] = ["user_id", "=", $user_id];
        //先判断用户是否存在
        if (!MemberModel::getInstance()->getModel($user_id)->where('id', '=', $user_id)->find()) {
            $msg = json_encode(["code" => -1, "msg" => '此用户不存在']);
        } else {
            //判断主播用户是否已经存在
            $res = AnchorCpModel::getInstance()->getModel()->where($where)->where("status", "=", 1)->find();
            if ($res) {
                $msg = json_encode(["code" => -1, "msg" => '主播已存在']);
            } else {
                if (AnchorCpModel::getInstance()->getModel()->insert(["user_id" => $user_id])) {
                    $msg = json_encode(["code" => 0, "msg" => '操作成功']);
                } else {
                    $msg = json_encode(["code" => -1, "msg" => '添加失败']);
                }
            }
        }

        echo $msg;
        exit;
    }

    //删除主播cp
    public function anchorcpdel()
    {
        $ids = $this->request->param('ids', '', 'trim');
        $idsarr = explode(",", $ids);
        $msg = "";
        try {
            if ($idsarr) {
                $updateRes = [
                    "status" => 0,
                    "admin_id" => $this->token['id'],
                    "update_time" => date('Y-m-d H:i:s'),
                ];
                if (AnchorCpModel::getInstance()->getModel()->where("id", "in", $idsarr)->update($updateRes)) {
                    Log::info("anchorcpcontroller:anchorcpdel:" . json_encode($updateRes));
                    $msg = json_encode(["code" => 0, "msg" => '操作成功']);
                }
            }
        } catch (Throwable $e) {
            $msg = json_encode(["code" => -1, "msg" => "操作失败"]);
        }
        echo $msg;
        exit;
    }

    //同意工会长申请绑定主播
    public function anchorcpagree()
    {
        $id = $this->request->param('id', 0, 'trim');
        $msg = "";
        try {
            if ($id > 0) {
                $updateRes = [
                    "status" => 1,
                    "admin_id" => $this->token['id'],
                    "update_time" => date('Y-m-d H:i:s'),
                ];
                if (AnchorCpModel::getInstance()->getModel()->where("id", "=", $id)->update($updateRes)) {
                    Log::info("anchorcpcontroller:anchorcpagree:" . json_encode($updateRes));
                    $msg = json_encode(["code" => 0, "msg" => '操作成功']);
                }
            }
        } catch (Throwable $e) {
            $msg = json_encode(["code" => -1, "msg" => "操作失败"]);
        }
        echo $msg;
        exit;
    }

    /*
     *
    CREATE TABLE `bi_anchor_cp_promotion` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
    `anchor_id` int(11) NOT NULL DEFAULT '0' COMMENT '主播id',
    `user_id` varchar(100) DEFAULT '' COMMENT '用户id',
    `direct_consume_sum` varchar(50) NOT NULL DEFAULT '0' COMMENT '直刷消费累计',
    `charge_sum` varchar(50) NOT NULL DEFAULT '0' COMMENT '累计充值',
    `status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1:绑定 0:解绑 2:已删除',
    `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `bind_date` int(11) NOT NULL DEFAULT '0' COMMENT '绑定日期',
    `last_login_time` varchar(50) NOT NULL DEFAULT '' COMMENT '最后登录时间',
    `guild_id` int(11) NOT NULL DEFAULT '0' COMMENT '工会ID',
    `guild_name` varchar(100) NOT NULL DEFAULT '' COMMENT '工会名称',
    `register_time` int(11) NOT NULL DEFAULT '0' COMMENT '用户注册日期',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `user_id` (`user_id`),
    KEY `register_time` (`register_time`)
    ) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 ROW_FORMAT=COMPACT COMMENT='cp引流'
     *
     *
     * */

    //主播cp绑定用户列表
    public function anchorcppromoteList()
    {
        $uid_id = $this->request->param('user_id', 0, 'trim');
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $export = $this->request->param("export", 0, 'trim');
        $status = $this->request->param("status", 1, 'trim');
        $sortfield = $this->request->param("sortfield", '', 'trim');
        $sorttype = $this->request->param("sorttype", '', 'trim');
        $where = [];
        $condition = [];

        if ($status >= 0) {
            $where[] = ['status', '=', $status];
            $condition[] = ['A.status', '=', $status];
        }
        if ($uid_id > 0) {
            $where[] = ["user_id", "=", $uid_id];
            $condition[] = ["A.user_id", "=", $uid_id];
        }
        $orderstring = 'register_time desc';

        if ($sortfield && $sorttype) {
            $orderstring = "$sortfield $sorttype";
        }

        if ($this->request->param('isRequest')) {

            $callbackFunc = function ($items) {
                $uids = array_column($items, "user_id");
                $anchorids = array_column($items, "anchor_id");
                $guilds = array_column($items, "guild_id");
                $uidList = array_merge($uids, $anchorids);
                $where[] = ["id", "in", $uidList];
                $memberInfo = MemberModel::getInstance()->getWhereAllData($where, "id,nickname");
                $memberList = array_column($memberInfo, null, "id");
                $condition[] = ["id", "in", $guilds];
                $memberGuildInfo = MemberGuildModel::getInstance()->getWhereAllData($condition, "id,nickname");
                $memberGuildList = array_column($memberGuildInfo, null, "id");
                foreach ($items as &$item) {
                    $item['u_nickname'] = $memberList[$item['user_id']]['nickname'] ?? '';
                    $item['a_nickname'] = $memberList[$item['anchor_id']]['nickname'] ?? '';
                    $item['g_nickname'] = $memberGuildList[$item['guild_id']]['nickname'] ?? '';
                }
                return $items;
            };

            if ($export == 1) {
                $resSource = AnchorCpPromotionModel::getInstance()
                    ->getModel()
                    ->alias("A")
                    ->where($condition)
                    ->field("A.anchor_id,A.user_id,A.guild_id,A.direct_consume_sum,A.charge_sum,
                         FROM_UNIXTIME(A.bind_date) as bind_date,FROM_UNIXTIME(A.register_time) as register_time");

                $headerArray = [
                    "anchor_id" => "主播ID",
                    'a_nickname' => '主播昵称',
                    "user_id" => "用户ID",
                    'u_nickname' => '用户昵称',
                    "guild_id" => "工会ID",
                    'g_nickname' => '工会名称',
                    "direct_consume_sum" => "直刷消费累计",
                    "charge_sum" => "累计充值",
                    "bind_date" => "绑定日期",
                    "register_time" => "注册日期",
                ];
                ExportExcelService::getInstance()->dataExpormetCsvByFormat($resSource, $headerArray, $callbackFunc);
                exit;
            }

            $res = AnchorCpPromotionModel::getInstance()->getModel()
                ->where($where)
                ->order($orderstring)
                ->page($page, $limit)->select()->toArray();
            $count = AnchorCpPromotionModel::getInstance()->getModel()->where($where)->count();

            $res_ids = array_reduce($res, function ($re, $item) {
                $re['uid'][] = $item['anchor_id'] ?: '';
                $re['uid'][] = $item['user_id'] ?: '';
                $re['gid'][] = $item['guild_id'] ?: '';
                return $re;
            }, []);

            //$res_ids 有可能保持为空
            $statusmap = ["0" => "已解绑", "1" => "已绑定", "2" => "已删除"];

            $memberinfo = [];
            if (isset($res_ids['uid']) && !empty($res_ids['uid'])) {
                $memberinfo = MemberModel::getInstance()->getWhereAllData([["id", "in", $res_ids['uid']]], 'id,nickname');
                $memberinfo = array_column($memberinfo, 'nickname', 'id');
            }

            $guildinfo = [];
            if (isset($res_ids['gid']) && !empty($res_ids['gid'])) {
                $guildinfo = MemberGuildModel::getInstance()->getWhereAllData([["id", "in", $res_ids['gid']]], 'id,nickname');
                $guildinfo = array_column($guildinfo, 'nickname', 'id');
            }

            $user_ids = array_column($res, "user_id");
            $todaychargedetail = BiDaysUserChargeModel::getInstance()->getModel()->where("uid", "in", $user_ids)
                ->where("date", "=", date('Y-m-d'))
                ->column("id", "uid");

            foreach ($res as $key => $item) {
                $sendgifttip = BiAnchorCpSendGiftModel::getInstance()->getModel()
                    ->where("promote_id", "=", $item['id'])
                    ->where("success_time", ">=", strtotime(date('Y-m-d')))
                    ->where("success_time", "<", strtotime("+1days", strtotime(date('Y-m-d'))))
                    ->sum('consume_amount');

                $res[$key]['anchor_nickname'] = $memberinfo[$item['anchor_id']] ?? '';
                $res[$key]['nickname'] = $memberinfo[$item['user_id']] ?? '';
                $res[$key]['guild_nickname'] = $guildinfo[$item['guild_id']] ?? '';
                $res[$key]['bind_date'] = date('Y-m-d H:i:s', $item['bind_date']);
                $res[$key]['status'] = $statusmap[$item['status']] ?? '';
                $res[$key]['consume_tip'] = $sendgifttip ?: 0;
                $res[$key]['charge_tip'] = $todaychargedetail[$item['user_id']] ?? 0;
                $res[$key]['register_time'] = $item['register_time'] ? date('Y-m-d H:i:s', $item['register_time']) : '';
            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
            exit;
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('user_id', $uid_id);
            View::assign('status', $status);
            return View::fetch('anchorcp/anchorcppromoteuserlist');
        }
    }

    //主播cp绑定或者解绑用户操作
    public function anchorcppromoteBinduser()
    {

        $action = $this->request->param('action', '', 'trim');
        $uid_id = $this->request->param('user_id', 0, 'trim');
        $anchor_id = $this->request->param('anchor_id', 0, 'trim');
        $id = $this->request->param('id', 0, 'trim');

        if ($action == 'unbind') { //解除绑定
            //如果是绑定的关系才可以解除绑定
            try {
                if (empty($id)) {
                    echo json_encode(['code' => -1, 'msg' => '参数错误']);
                    exit;
                }
                if (!$result = AnchorCpPromotionModel::getInstance()->getModel()->where(['id' => $id])->find()) {
                    echo json_encode(['code' => -1, 'msg' => '参数错误']);
                    exit;
                }

                if ($result['status'] != 1) {
                    echo json_encode(['code' => -1, 'msg' => '操作失败无法解除绑定']);
                    exit;
                }

                if (AnchorCpPromotionModel::getInstance()->getModel()->where("id", "=", $id)->update(['status' => 0])) {
                    Log::info("anchorcppromotebinduser:unbind:success" . $id . "操作人:" . $this->token['id']);
                    echo json_encode(['code' => 0, 'msg' => '操作成功']);
                    exit;
                }
            } catch (Throwable $e) {
                Log::error("anchorcppromotebinduser:unbind:error:" . $e->getMessage());
                echo json_encode(['code' => -1, 'msg' => '操作异常']);
                exit;
            }
        }

        if ($action == 'deletebind') { //绑定用户
            try {
                if (AnchorCpPromotionModel::getInstance()->getModel()->where("id", "=", $id)->update(['status' => 2])) {
                    Log::info("anchorcppromotebinduser:deletebind:success" . $id . "操作人:" . $this->token['id']);
                    echo json_encode(['code' => 0, 'msg' => '操作成功']);
                    exit;
                }
            } catch (Throwable $e) {
                Log::error("anchorcppromotebinduser:deletebind:error:" . $e->getMessage());
                echo json_encode(['code' => -1, 'msg' => '操作异常']);
                exit;
            }
        }

        if ($action == 'addbind') { //添加绑定用户
            try {

                if (empty($anchor_id)) {
                    echo json_encode(['code' => -1, 'msg' => '参数错误']);
                    exit;
                }

                if (empty($uid_id)) {
                    echo json_encode(['code' => -1, 'msg' => '参数错误']);
                    exit;
                }

                $whereuid = [$anchor_id, $uid_id];
                $memberInfo = MemberModel::getInstance()->getWhereAllData([["id", "in", $whereuid]], "id,register_time,guild_id");
                if (empty($whereuid) || count($memberInfo) < 2) {
                    echo json_encode(['code' => -1, 'msg' => '用户不存在']);
                    exit;
                }

                $res = AnchorCpPromotionModel::getInstance()->getModel()
                    ->where("user_id", "=", $uid_id)
                    ->where("status", "=", 1)
                    ->find();

                if ($res) {
                    Log::info("anchorcppromotebinduser:deletebind:success" . $id . "操作人:" . $this->token['id']);
                    echo json_encode(['code' => -1, 'msg' => '用户已绑定']);
                    exit;
                } else {
                    $memberRes = array_column($memberInfo, null, "id");
                    $insertRes = [
                        "user_id" => $uid_id,
                        "anchor_id" => $anchor_id,
                        "status" => 1,
                        "bind_date" => time(),
                        "guild_id" => $memberRes[$anchor_id]['guild_id'] ?? 0,
                        "register_time" => strtotime($memberRes[$uid_id]['register_time'] ?? 0),
                    ];
                    AnchorCpPromotionModel::getInstance()->getModel()->insert($insertRes);
                    echo json_encode(['code' => 0, 'msg' => '操作成功']);
                    exit;
                }

            } catch (Throwable $e) {
                Log::error("anchorcppromotebinduser:addbind:error:" . $e->getMessage());
                echo json_encode(['code' => -1, 'msg' => '操作异常']);
                exit;
            }
        }

    }

    //主播cp绑定用户的直刷送礼详情
    public function cpSendGiftDetail()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $promoteid = $this->request->param("promoteid", 0);
        $export = $this->request->param("export", 0, 'trim');
        $where = [];
        if ($promoteid > 0) {
            $promote = AnchorCpPromotionModel::getInstance()->getModel()->where(['id'=>$promoteid])->find()->toArray();
            $anchor_id = $promote['anchor_id'];
            $user_id = $promote['user_id'];
            $where[] = ["uid", "=", $user_id]; //送礼用户
            $where[] = ["touid", "=",$anchor_id]; //收礼用户
        }
        if ($this->request->param('isRequest')) {
            if ($export == 1) {
                $resSource = BiDaysUserSendgiftModel::getInstance()->getModel()
                    ->where($where)
                    ->field("date,uid,touid,reward_amount");
                $headerArray = [
                    'date' => '日期',
                    "uid" => "送礼用户",
                    "touid" => "收礼用户",
                    "reward_amount" => "价值(豆)",
                ];
                ExportExcelService::getInstance()->dataExpormetCsvByFormat($resSource, $headerArray);
                exit;
            }

            $res = BiDaysUserSendgiftModel::getInstance()->getModel()
                ->field('date,uid,touid,reward_amount')
                ->where($where)
                ->page($page, $limit)->select()->toArray();
            $count = BiDaysUserSendgiftModel::getInstance()->getModel()->where($where)->count();

            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
            exit;
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('promoteid', $promoteid);
            return View::fetch('anchorcp/cpsendgiftdetail');
        }
    }

    //主播cp绑定用户的直刷送礼详情
    public function cpChargeDetail()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $user_id = $this->request->param("user_id", 0);
        $export = $this->request->param("export", 0, 'trim');
        $where = [];
        if ($user_id > 0) {
            $where[] = ["uid", "=", $user_id];
        }
        if ($this->request->param('isRequest')) {
            if ($export == 1) {
                $resSource = BiDaysUserChargeModel::getInstance()->getModel()
                    ->where($where)
                    ->field("date,uid,type,amount");
                $headerArray = [
                    "date" => "日期",
                    "uid" => "用户id",
                    "type" => "类型",
                    "amount" => "豆",
                ];
                ExportExcelService::getInstance()->dataExpormetCsvByFormat($resSource, $headerArray);
                exit;
            }

            $res = BiDaysUserChargeModel::getInstance()->getModel()
                ->where($where)
                ->page($page, $limit)->select()->toArray();
            $count = Db::name("bi_days_user_charge")->where($where)->count();
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
            exit;
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('user_id', $user_id);
            return View::fetch('anchorcp/cpchargedetail');
        }
    }

}
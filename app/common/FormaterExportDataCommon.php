<?php

namespace app\common;

use app\admin\common\AdminCommonConfig;
use app\admin\model\AdminUserModel;
use app\admin\model\MembercashModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\MemberModel;
use app\admin\service\MemberBlackService;
use app\admin\service\MemberGuildService;
use app\admin\service\MemberService;

class FormaterExportDataCommon
{
    public static $instance = null;

    public static function getInstance()
    {

        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //im消息列表的格式化
    public function formaterImCheckList($data)
    {
        foreach ($data as $key => $item) {
            if (isset($item['created_time'])) {
                $data[$key]['created_time'] = date('Y-m-d H:i:s', $item['created_time']);
            }
        }
        return $data;
    }

    //用户封禁列表的格式化
    public function formatterBlackDataList($items)
    {
        $uids = array_column($items, "user_id");
        $membersRes = MemberService::getInstance()->getUserInfoFieldByUids($uids, ["lv_dengji"]);
        $memberGuiRes = MemberGuildService::getInstance()->getUserGuildByUid($uids);
        $adminids = array_column($items, "admin_id");
        $adminuserRes = AdminUserModel::getInstance()->getModel()->where("id", "in", $adminids)->column('username,id', "id");
        foreach ($items as &$item) {
            $uid = $item['user_id'];
            if ($item['status'] == 1) {
                $item['status'] = "封禁";
            } else {
                $item['status'] = "解禁";
            }
            $item['info'] = MemberBlackService::getInstance()->blackTimeFormat($item['time']);
            $item['type_desc'] = AdminCommonConfig::FORBID_MAP[$item['type']];
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['update_time'] = date('Y-m-d H:i:s', $item['update_time']);
            $item['lv_dengji'] = $membersRes[$uid]['lv_dengji'] ?? '';
            $item['guild_nickname'] = $memberGuiRes[$uid]['g_nickname'] ?? '';
            $item['admin_user'] = $adminuserRes[$item['admin_id']]['username'] ?? '';
            $item['blackinfo'] = $item['blackinfo'];
        }

        return $items;
    }

    //用户封禁列表的格式化
    public function formatterVipOrderList($items)
    {
        $typeMap = [2 => "vip", 3 => "svip"];
        $statusMap = [0 => "未支付", 1 => "支付完成", 2 => "支付完成"];
        $activeMap = [0 => "充值", 1 => "续费vip", 2 => "激活vip", 4 => "自动续费"]; //1续费vip 2激活vip 0充值
        foreach ($items as &$item) {
            $item['platform_info'] = $platformMap[$item['platform']] ?? '';
            $item['status_info'] = $statusMap[$item['status']] ?? '';
            $item['type_info'] = $typeMap[$item['type']] ?? '';
            $item['active_info'] = $activeMap[$item['is_active']] ?? '';
        }
        return $items;
    }

    //提现列表格式化
    public function formatterWithdrawOrderList($items)
    {
        $withdrawstatusList = ["0" => "待审核", "1" => "打款中", "2" => "打款失败", "3" => "打款成功", "4" => "拒绝", "5" => "已取消"];
        $withdrawtypeList = ["0" => "支付宝", "1" => "微信", "2" => "银行卡"];
        $withdrawwhiteList = ["1" => "白名单", "2" => "普通用户"];
        $uids = array_column($items, "uid");
        $memberList = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], "id,username,guild_id,nickname");
        $guilds = array_column($memberList, "guild_id");
        $memberGuildList = MemberGuildModel::getInstance()->getWhereAllData([["id", "in", $guilds]], "id,nickname");
        $memberGuildListById = array_column($memberGuildList, null, "id");
        $memberListById = array_column($memberList, null, "id");

        $accounts = array_column($items, "accounts");
        $account_list = MembercashModel::getInstance()->getWhereAllData([["alipay", "in", $accounts]], "alipay,name", 'alipay');
        $account_list_map = array_column($account_list, null, "alipay");

        foreach ($items as &$item) {
            $guild = $memberListById[$item['uid']]['guild_id'] ?? 0;
            $item['status_info'] = $withdrawstatusList[$item['status']] ?? '';
            $item['type_info'] = $withdrawtypeList[$item['type']] ?? '';
            $item['g_nickname'] = $memberGuildListById[$guild]['nickname'] ?? '';
            $item['mobile'] = $memberListById[$item['uid']]['username'] ?? '';
            $item['username'] = $account_list_map[$item['accounts']]['name'] ?? '';
            $item['nickname'] = $memberListById[$item['uid']]['nickname'] ?? '';
            $item['created_time'] = date('Y-m-d H:i:s', $item['created_time']);
            $item['updated_time'] = date('Y-m-d H:i:s', $item['updated_time']);
            $item['guild'] = $guild;
            $item['user_role_info'] = $withdrawwhiteList[$item['user_role']] ?? '';
            $item['accounts'] = $item['accounts'] . "\t";
        }
        return $items;
    }

}

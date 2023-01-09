<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AdminUserModel;
use app\admin\model\MemberMoneyModel;
use app\admin\model\UserAssetLogModel;
use app\admin\model\UserLastInfoModel;
use app\admin\service\ElasticsearchService;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class MemberMoneyController extends AdminBaseController
{
    public function getMemberMoneyDouList()
    {
        $uid = $this->request->param('uid');

        $admin = AdminUserModel::getInstance()->getModel()->select()->toArray();
        $admin_map = array_column($admin, 'username', 'id');

        $field = "id,event_id,change_amount as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5";
        $list = UserAssetLogModel::getInstance()->getModel($uid)->field($field)
            ->where("uid",$uid)
            ->where("type",4)
            ->where("event_id",10020)
            ->order("success_time desc")
            ->limit(200)
            ->select()->toArray();

//        $sql = "select id,event_id,change_amount as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5 from zb_user_asset_log where type = 4 and uid = {$uid} and event_id in (10020) order by id asc";
//        $list = Db::query($sql);
//
        foreach ($list as &$item) {
            $item['money'] = round($item['money'], 2);
            $item['created_user'] = $admin_map[$item['ext_1']];
            $item['desc'] = $item['ext_5'];
            $item['status'] = $item['money'] > 0 ? '+' : '-';
        }

        Log::record('用户充值记录列表:', 'getMemberMoneyDouList');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    public function getMemberScoreList()
    {
        $uid = $this->request->param('uid');

        $admin = AdminUserModel::getInstance()->getModel()->select()->toArray();
        $admin_map = array_column($admin, 'username', 'id');

//        $sql = "select id,event_id,change_amount as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5 from zb_user_asset_log where type = 2 and uid = {$uid} and event_id in (10020) order by id asc";
//        $list = Db::query($sql);

        $field = "id,event_id,change_amount as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5";

        $list = UserAssetLogModel::getInstance()->getModel($uid)->field($field)
            ->where("uid",$uid)
            ->where("type",2)
            ->where("event_id",10020)
            ->order("success_time desc")
            ->limit(200)
            ->select()->toArray();

        foreach ($list as &$item) {
            $item['money'] = round($item['money'], 2);
            $item['created_user'] = $admin_map[$item['ext_1']];
            $item['desc'] = $item['ext_5'];
            $item['status'] = $item['money'] > 0 ? '+' : '-';
        }
        Log::record('用户充值记录列表:', 'getMemberScoreList');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    public function getMemberGoldList()
    {
        $uid = $this->request->param('uid');

        $admin = AdminUserModel::getInstance()->getModel()->select()->toArray();
        $admin_map = array_column($admin, 'username', 'id');

//        $sql = "select id,event_id,change_amount as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5 from zb_user_asset_log where type = 6 and uid = {$uid} and event_id in (10020) order by id asc";
//        $list = Db::query($sql);

        $field = "id,event_id,change_amount as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5";
        $list = UserAssetLogModel::getInstance()->getModel($uid)->field($field)
            ->where("uid",$uid)
            ->where("type",6)
            ->where("event_id",10020)
            ->order("success_time desc")
            ->limit(200)
            ->select()->toArray();

        foreach ($list as &$item) {
            $item['status'] = $item['money'] > 0 ? '+' : '-';
            $item['desc'] = $item['ext_5'];
            $item['created_user'] = isset($admin_map[$item['ext_1']]) ? $admin_map[$item['ext_1']] : '';
        }

        Log::record('用户充值记录列表:', 'getMemberGoldList');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    public function addMemberMoneyDou()
    {
        $uid = $this->request->param('uid');
        $money = $this->request->param('money');
        $reason = $this->request->param('desc');
        $adminId = $this->getAdminIdByToken(Request::param('token'))['id'];
        echo $this->inner($uid, 'user:bean', $money, $adminId, $reason);die;
    }

    public function addMemberScore()
    {
        $uid = $this->request->param('uid');
        $money = $this->request->param('money');
        $reason = $this->request->param('desc');
        $adminId = $this->getAdminIdByToken(Request::param('token'))['id'];

        echo $this->inner($uid, 'bank:game:score', $money, $adminId, $reason);die;
    }

    public function addMemberGold()
    {
        $uid = $this->request->param('uid');
        $money = $this->request->param('money');
        $reason = $this->request->param('desc');
        $adminId = $this->getAdminIdByToken(Request::param('token'))['id'];
        echo $this->inner($uid, 'user:coin', $money, $adminId, $reason);die;
    }

    public function addMemberMoneyDiamondOne()
    {

        $uid = $this->request->param('uid');
        $money = $this->request->param('money') * 10000;
        $reason = $this->request->param('desc');
        $adminId = $this->getAdminIdByToken(Request::param('token'))['id'];
        $src = $this->inner($uid, 'user:diamond', $money, $adminId, $reason);
        $is = json_decode($src, true);

        $data = [];
        $data['money'] = $money;
        $data['desc'] = $reason;
        $data['type'] = 1;
        $data['created_time'] = time();
        $data['created_user'] = $this->token['username'];
        $data['status'] = 1;

        if ($is['code'] == 200) {
            Log::record('用户充值钻石记录减少成功:操作人:' . $this->token['username'] . ':数据:' . json_encode($data), 'addMemberMoneyDiamondTwo');
            $data['created_time'] = date('Y-m-d H:i:s', $data['created_time']);
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        }
        Log::record('用户充值钻石记录减少失败:操作人:' . $this->token['username'] . ':数据:' . json_encode($data), 'addMemberMoneyDiamondTwo');
        echo $this->return_json(\constant\CodeConstant::CODE_插入失败, $data, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
        die;
    }

    public function addMemberMoneyDiamondTwo()
    {
        $uid = $this->request->param('uid');
        $money = $this->request->param('money');
        $reason = $this->request->param('desc');
        $adminId = $this->getAdminIdByToken(Request::param('token'))['id'];
        $src = $this->inner($uid, 'user:diamond', -($money * 10000), $adminId, $reason);
        $is = json_decode($src, true);

        $data = [];
        $data['money'] = $money;
        $data['desc'] = $reason;
        $data['type'] = 1;
        $data['created_user'] = $this->token['username'];
        $data['created_user'] = 2;
        $data['types'] = '+';
        if ($is['code'] == 200) {
            Log::record('用户充值钻石记录减少成功:操作人:' . $this->token['username'] . ':数据:' . json_encode($data), 'addMemberMoneyDiamondTwo');
            $data['created_time'] = date('Y-m-d H:i:s', $data['created_time']);
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        }
        Log::record('用户充值钻石记录减少失败:操作人:' . $this->token['username'] . ':数据:' . json_encode($data), 'addMemberMoneyDiamondTwo');
        echo $this->return_json(\constant\CodeConstant::CODE_插入失败, $data, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
        die;
    }

    public function getMemberMoneyDiamondOneList()
    {
        $uid = $this->request->param('uid');
        // $where = [];
        // if ($uid) {
        //     $where['uid'] = $uid;
        // }
        // $where['type'] = 1;
        // $where['status'] = 1;
        // $list = MemberMoneyService::getInstance()->getMemberMoneyByWhere($where, '*');

        $admin = AdminUserModel::getInstance()->getModel()->select()->toArray();
        $admin_map = array_column($admin, 'username', 'id');

//        $sql = "select id,event_id,change_amount *0.0001 as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5 from zb_user_asset_log where type = 5 and uid = {$uid} and event_id in (10020) and change_amount>0 order by id asc";
//
//        $list = Db::query($sql);

        $field = "id,event_id,change_amount *0.0001 as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5";

        $list = UserAssetLogModel::getInstance()->getModel($uid)->field($field)
            ->where("uid",$uid)
            ->where("type",5)
            ->where("event_id",10020)
            ->where("change_amount",">",0)
            ->order("success_time desc")
            ->limit(200)
            ->select()->toArray();

        foreach ($list as &$item) {
            $item['money'] = round($item['money'], 2);
            $item['created_user'] = $admin_map[$item['ext_1']];
            $item['desc'] = $item['ext_5'];
            $item['status'] = $item['money'] > 0 ? '+' : '-';
        }

        Log::record('用户充值钻石添加列表:', 'getMemberMoneyDiamondOneList');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    public function getMemberMoneyDiamondTwoList()
    {
        $uid = $this->request->param('uid');

        $admin = AdminUserModel::getInstance()->getModel()->select()->toArray();
        $admin_map = array_column($admin, 'username', 'id');

//        $sql = "select id,event_id,change_amount * 0.0001 as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5 from zb_user_asset_log where type = 5 and uid = {$uid} and event_id in (10020) and change_amount<0 order by id asc";
//        $list = Db::query($sql);
//
        $field = "id,event_id,change_amount *0.0001 as money,FROM_UNIXTIME(success_time) as created_time,ext_1,ext_3 as channel,ext_4 as content,ext_5";

        $list = UserAssetLogModel::getInstance()->getModel($uid)->field($field)
            ->where("uid",$uid)
            ->where("type",5)
            ->where("event_id",10020)
            ->where("change_amount","<",0)
            ->order("success_time desc")
            ->limit(200)
            ->select()->toArray();

        foreach ($list as &$item) {
            $item['money'] = round($item['money'], 2);
            $item['created_user'] = $admin_map[$item['ext_1']];
            $item['desc'] = $item['ext_5'];
            $item['status'] = $item['money'] > 0 ? '+' : '-';
        }

        Log::record('用户充值钻石减少列表:', 'getMemberMoneyDiamondTwoList');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /**
     * 后台操作用户钱包明细记录
     */
    public function userMoneyList()
    {
        $redis = $this->getRedis();
        $limit = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $user_id = Request::param('user_id');
        $where = [];
        if (!empty($user_id)) {
            $where[] = ['uid', '=', $user_id];
        }
        $data = MemberMoneyModel::getInstance()->getModel()->field('uid,money,desc,type,created_time,created_user,status')->where($where)->limit($offset, $limit)->order('created_time desc')->select();
        if (!empty($data)) {
            $data = $data->toArray();
            $count = MemberMoneyModel::getInstance()->getModel()->where($where)->count();
            foreach ($data as $key => $value) {
                $data[$key]['nickname'] = $redis->hget('userinfo_' . $value['uid'], 'nickname');
                $data[$key]['created_time'] = date('Y-m-d H:i:s', $value['created_time']);
                if ($data[$key]['type'] == 1) {
                    $data[$key]['type'] = '钻石';
                } elseif ($data[$key]['type'] == 2) {
                    $data[$key]['type'] = '豆';
                } elseif ($data[$key]['type'] == 3) {
                    $data[$key]['type'] = '金币';
                }
                if ($data[$key]['status'] == 1) {
                    $data[$key]['status'] = '增加';
                } else {
                    $data[$key]['status'] = '减少';
                }

            }
        } else {
            $data = [];
            $count = 0;
        }
        $admin_url = config('config.admin_url');
        $totalPage = ceil($count / $limit);
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        View::assign('list', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('user_id', $user_id);
        View::assign('admin_url', $admin_url);
        return View::fetch('member/usercharge');
    }
}
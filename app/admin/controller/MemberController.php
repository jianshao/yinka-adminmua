<?php
/**
 * Created by PhpStorm.
 * User: pussycat
 * Date: 2019/7/23
 * Time: 21:01
 */

namespace app\admin\controller;

ini_set('memory_limit', '1024M');

use app\admin\common\AdminBaseController;
use app\admin\common\AdminCommonConfig;
use app\admin\common\ApiUrlConfig;
use app\admin\model\ActiveModel;
use app\admin\model\AdminOperationLogModel;
use app\admin\model\AdminUserModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BlackDataModel;
use app\admin\model\CheckImMessageModel;
use app\admin\model\ComplaintsNewFollowModel;
use app\admin\model\ComplaintsNewModel;
use app\admin\model\DaichongModel;
use app\admin\model\FreeDiamondLogModel;
use app\admin\model\GiftModel;
use app\admin\model\GivePackModel;
use app\admin\model\ImMessageModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\LogindetailModel;
use app\admin\model\LoginDetailNewModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MembercashModel;
use app\admin\model\MemberDetailAuditLogModel;
use app\admin\model\MemberDetailAuditModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberSocityModel;
use app\admin\model\PackModel;
use app\admin\model\RoomcheckSwitchModel;
use app\admin\model\RoomUserBlackModel;
use app\admin\model\UserBlackModel;
use app\admin\model\UserLastInfoModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ApiService;
use app\admin\service\ConfigService;
use app\admin\service\ElasticsearchService;
use app\admin\service\ExportExcelService;
use app\admin\service\MemberService;
use app\common\FormaterExportDataCommon;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use app\common\UploadOssFileCommon;
use app\exceptions\ApiExceptionHandle;
use think\Exception;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class MemberController extends AdminBaseController
{
    public function daichong()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = Request::param('uid');

        $where[] = ['status', '<>', '1'];
        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }

        $data = DaichongModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->order('id desc')->select()->toArray();
        $count = DaichongModel::getInstance()->getModel()->where($where)->count();
        foreach ($data as $k => $v) {
            $data[$k]['name'] = MemberModel::getInstance()->getModel($v['uid'])->where('id', $v['uid'])->value('nickname');
            $data[$k]['tel'] = MemberModel::getInstance()->getModel($v['uid'])->where('id', $v['uid'])->value('username');
            $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        Log::record('用户列表查询:操作人:' . $this->token['username'], 'memberList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('用户管理列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('uid', $uid);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/daichong');
    }

    public function daichongAdd()
    {
        $uid = Request::param('uid');
        $type = Request::param('type');
        if (!$uid) {
            echo json_encode(['code' => 500, 'msg' => '用户id不可为空']);
            die;
        }
        $username = MemberModel::getInstance()->getModel($uid)->where('id', $uid)->value('username');
        if (!$username) {
            echo json_encode(['code' => 500, 'msg' => '用户未绑定手机号']);
            die;
        }

        $daichong_info = DaichongModel::getInstance()->getModel()->where('uid', $uid)->where('type', $type)->where('status', 0)->find();
        if ($daichong_info) {
            echo json_encode(['code' => 500, 'msg' => '代充用户已存在']);
            die;
        }

        $data = [
            'uid' => $uid,
            'type' => $type,
            'create_time' => time(),
        ];

        //判断添加的用户是否是工会长
        $guild_info = MemberGuildModel::getInstance()->getModel()->where('user_id', $uid)->find();

        //工会用户
        if ($type == 1 && $guild_info) {
            echo json_encode(['code' => 500, 'msg' => '该用户是工会长，请选择正确的类型']);
            die;
        }

        //普通用户
        if ($type == 0 && !$guild_info) {
            echo json_encode(['code' => 500, 'msg' => '该用户是普通用户，请选择正确的类型']);
            die;
        }

        $data1 = [
            'user_id' => Request::param('uid'),
            'phone' => $username,
            'nickname' => MemberModel::getInstance()->getModel($uid)->where('id', $uid)->value('nickname'),
            'password' => md5(123456),
        ];

        try {
            DaichongModel::getInstance()->getModel()->startTrans();
            DaichongModel::getInstance()->getModel()->insert($data);
            if (!$guild_info) {
                MemberGuildModel::getInstance()->getModel()->insert($data1);
            }
            DaichongModel::getInstance()->getModel()->commit();
            $is = true;
        } catch (Exception $e) {
            DaichongModel::getInstance()->getModel()->rollback();
            $is = false;
        }

        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']); //php编译join
        }
    }

    public function daichongStatus()
    {
        $id = Request::param('id');
        $is = DaichongModel::getInstance()->getModel()->where('id', $id)->save(['status' => 1]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']); //php编译join
        }
    }

    /**
     * @return mixed
     * @用户登录日志
     * @dongbozhao
     * @2020-12-15 10:55
     */
    public function loginDetail()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id', ''); //用户id
        $device_id = Request::param('device_id'); //设别id
        $imei = Request::param('imei'); //设备唯一标识
        $login_ip = Request::param('login_ip'); //登录ip
        $where = [];

        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if ($device_id) {
            $where[] = ['device_id', '=', $device_id];
        }
        if ($login_ip) {
            $where[] = ['login_ip', '=', $login_ip];
        }
        if ($imei) {
            $where[] = ['imei', '=', $imei];
        }

        //统计用户条数
        $data = [];
        if (($device_id || $login_ip || $imei) && empty($user_id)) {
            $count = LoginDetailNewModel::getInstance()->getCount($where, $user_id);
            $data = LoginDetailNewModel::getInstance()->getListPage($where, $offset, $pagenum);
        } else {
            $count = LoginDetailNewModel::getInstance()->getModel($user_id)->where($where)->count();
            $data = LoginDetailNewModel::getInstance()->getModel((int) $user_id)
                ->where($where)
                ->limit($offset, $pagenum)
                ->order('ctime desc')
                ->select()
                ->toArray();
        }

        foreach ($data as $k => $v) {
            $data[$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
        }
        Log::record('用户列表查询:操作人:' . $this->token['username'], 'memberList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('用户管理列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $user_id);
        View::assign('device_id', $device_id);
        View::assign('login_ip', $login_ip);
        View::assign('imei', $imei);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/loginDetail');
    }

    /**
     * @return mixed
     * 用户注销列表
     * @dongbozhao
     * @2020-12-15 10:55
     */
    public function cancelUserStatus()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $id = $this->request->param('uid');
        $status = $this->request->param('status');
        $pretty_id = $this->request->param('pretty_id');
        $mobile = $this->request->param('mobile');
        if ($id) {
            $where[] = ['id', '=', $id];
        }
        if ($pretty_id) {
            $where[] = ['pretty_id', '=', $pretty_id];
        }
        if ($mobile) {
            $where[] = ['username', '=', $mobile];
        }
        if ($status > -1) {
            $where[] = ['cancel_user_status', '=', $status];
        } else {
            $where[] = ['cancel_user_status', '>', -1];
        }
        $count = MemberModel::getInstance()->getModel($id)->where($where)->count();
        $data = MemberModel::getInstance()->getModel($id)->alias('A')->field('A.*')->where($where)->order('id desc')->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['totalcoin'] = intval(floor($v['totalcoin'] - $v['freecoin']));
            $data[$k]['diamond'] = intval(floor(($v['diamond'] - $v['exchange_diamond'] - $v['free_diamond']) * 0.0001));

            $cancel_time = empty($v['cancellation_time']) ? strtotime($v['login_time']) : $v['cancellation_time'];
            $data[$k]['cancellation_time'] = date('Y-m-d H:i:s', $cancel_time);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('data', $data);
        View::assign('uid', $id);
        View::assign('mobile', $mobile);
        View::assign('count', $count);
        View::assign('pretty_id', $pretty_id);
        View::assign('status', $status);
        View::assign('count', $count);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/cancelUserStatus');
    }

    public function cancelUserStatusSave()
    {
        $status = $this->request->param('status');
        $id = $this->request->param('id');
        if ($status == 2 || $status == 3) {
            $is = MemberModel::getInstance()->getModel($id)->where('id', $id)->save(['cancel_user_status' => 1, 'is_cancel' => 1]);
        } elseif ($status == 1) {
            $is = MemberModel::getInstance()->getModel($id)->where('id', $id)->save(['cancel_user_status' => 0, 'is_cancel' => 0]);
        }
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']); //php编译join
        } else {
            echo json_encode(['code' => 200, 'msg' => '修改成功']); //php编译join
        }
    }

    public function cancelUserStatusCancel()
    {
        $id = $this->request->param('id');
        $is = MemberModel::getInstance()->getModel($id)->where('id', $id)->save(['cancel_user_status' => 0, 'is_cancel' => 0]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']); //php编译join
        } else {
            echo json_encode(['code' => 200, 'msg' => '修改成功']); //php编译join
        }
    }

    //隐身用户 online
    private $user_key = "invis_user";

    /**
     * @return mixed
     * ！用户流失列表
     */
    public function memberLoss()
    {
        try {
            $pagenum = 10;
            $master_page = $this->request->param('page', 1);
            $page = ($master_page - 1) * $pagenum;
            $uid = $this->request->param('uid', 0);
            $daochu = $this->request->param('daochu', '');
            $type = $this->request->param('type');
            $time = $this->request->param('time');
            if (!empty($uid)) {
                $LogindetaiWhere[] = ['a.user_id', '=', $uid];
            }
            if ($time == 0) {
                $LogindetaiWhere[] = ['login_time', '<=', date("Y-m-d H:00:00", strtotime("-28 hour"))];
                $LogindetaiWhere[] = ['login_time', '>=', date("Y-m-d H:00:00", strtotime("-48 hour"))];
            } elseif ($time == 1) {
                $LogindetaiWhere[] = ['login_time', '<=', date("Y-m-d 00:00:00", strtotime("-5 day"))];
                $LogindetaiWhere[] = ['login_time', '>=', date("Y-m-d 00:00:00", strtotime("-15 day"))];
            } elseif ($time == 2) {
                $LogindetaiWhere[] = ['login_time', '<=', date("Y-m-d 00:00:00", strtotime("-15 day"))];
                $LogindetaiWhere[] = ['login_time', '>=', date("Y-m-d 00:00:00", strtotime("-30 day"))];
            }
            if ($daochu) {
                if ($type == 1) {
                    $LogindetaiWhere[] = ['totalcoin', '>', 0];
                    $count = MemberModel::getInstance()->getWhereCount($LogindetaiWhere);

                    $data = MemberModel::getInstance()->getWhereAllData($LogindetaiWhere, "login_time ctime,id");
                } elseif ($type == 2) {
                    $LogindetaiWhere[] = ['totalcoin', '<=', 0];
                    $count = MemberModel::getInstance()->getWhereCount($LogindetaiWhere);
                    $data = MemberModel::getInstance()->getWhereAllData($LogindetaiWhere, "login_time ctime,id");
                } else {
                    $count = MemberModel::getInstance()->getWhereCount($LogindetaiWhere);
                    $data = MemberModel::getInstance()->getWhereAllData($LogindetaiWhere, "login_time ctime,id");
                }

            } else {
                if ($type == 1) {
                    $LogindetaiWhere[] = ['totalcoin', '>', 0];
                    $count = MemberModel::getInstance()->getWhereCount($LogindetaiWhere);
                    $data = MemberModel::getInstance()->getModel($uid)->where($LogindetaiWhere)->field('login_time ctime,id')->limit($page, $pagenum)->select()->toArray();
                } elseif ($type == 2) {
                    $LogindetaiWhere[] = ['totalcoin', '<=', 0];
                    $count = MemberModel::getInstance()->getWhereCount($LogindetaiWhere);
                    $data = MemberModel::getInstance()->getModel($uid)->where($LogindetaiWhere)->field('login_time ctime,id')->limit($page, $pagenum)->select()->toArray();
                } else {
                    $count = MemberModel::getInstance()->getWhereCount($LogindetaiWhere);
                    $data = MemberModel::getInstance()->getModel($uid)->where($LogindetaiWhere)->field('login_time ctime,id')->limit($page, $pagenum)->select()->toArray();
                }
            }

            $uids = array_column($data, "id");
            $userchargeList = BiDaysUserChargeModel::getInstance()->getModel()->field("uid,sum(amount)/10 as rmb")->where("uid", "in", $uids)->group("uid")->select()->toArray();
            $userchargeListByUid = array_column($userchargeList, null, "uid");
            $memberList = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], "totalcoin,username,freecoin,id,guild_id");
            $memberByUid = array_column($memberList, null, "id");

            foreach ($data as $k => $v) {
                $data[$k]['type'] = '普通用户';
                $data[$k]['totalcoin'] = $memberByUid[$v['id']]['totalcoin'] ?? 0;
                $data[$k]['username'] = $memberByUid[$v['id']]['username'] ?? '';
                $data[$k]['freecoin'] = $memberByUid[$v['id']]['freecoin'] ?? 0;
                $data[$k]['rmb'] = $userchargeListByUid[$v['id']]['rmb'] ?? 0;
                $data[$k]['guild_id'] = $memberByUid[$v['id']]['guild_id'] ?? 0;
                if ($data[$k]['totalcoin'] > 0.00) {
                    $data[$k]['type'] = '消费用户';
                }
            }

            if ($daochu) {
                $tilie = ['id', '最后登录', '用户类型', '充值金额', '豆总收入', '消费M豆', '剩余M豆', '手机号', '工会ID'];
                $string = implode(",", $tilie) . "\n";
                foreach ($data as $key => $value) {
                    $outArray['id'] = $value['id']; //统计日期
                    $outArray['ctime'] = $value['ctime'];
                    $outArray['type'] = $value['type'];
                    $outArray['rmb'] = $value['rmb'];
                    $outArray['totalcoin'] = $value['totalcoin']; //登录账号
                    $outArray['freecoin'] = $value['freecoin']; //登录账号
                    $outArray['totalcoin-freecoin'] = $value['totalcoin'] - $value['freecoin']; //登录账号
                    $outArray['username'] = $value['username']; //登录账号
                    $outArray['guild_id'] = $value['guild_id']; //登录账号
                    $string .= implode(",", $outArray) . "\n";
                }
                $this->_Daochu($string, '用户流失');
            }
            $page_array = [];
            $page_array['page'] = $master_page;
            $page_array['total_page'] = ceil($count / $pagenum);
            $admin_url = config('config.admin_url');
            View::assign('page', $page_array);
            View::assign('data', $data);
            View::assign('uid', $uid);
            View::assign('type', $type);
            View::assign('time', $time);
            View::assign('token', $this->request->param('token'));
            View::assign('user_role_menu', $this->user_role_menu);
            View::assign('admin_url', $admin_url);
            View::assign('count', $count);
            View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
            return View::fetch('member/memberLoss');
        } catch (\Throwable $e) {
            Log::error($e->getMessage() . $e->getFile() . $e->getLine());
        }

    }

    public function memberCash()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = $this->request->param('uid');
        $where = [];
        if (!empty($uid)) {
            $where[] = ['uid', '=', $uid];
        }
        $count = MembercashModel::getInstance()->getModel()->where($where)->field('id')->select()->count();
        $data = MembercashModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->field('uid,name,alipay')->select()->toArray();

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('uid', $uid);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/membercash');
    }

    public function MemberCashDel()
    {
        $uid = $this->request->param('uid');
        $is = MembercashModel::getInstance()->getModel()->where('uid', $uid)->delete();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
            die;
        }
    }

    public function online()
    {
        $pagenum = 10;
        $where = [];
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id', 0); //用户id
        $username = Request::param('username'); //用户id

        if ($user_id) {
            $where[] = ['id', '=', $user_id];
        }
        if ($username) {
            $where[] = ["username", "like", $username . '%'];
        }
        $count = MemberService::getInstance()->getCount($where, $user_id);

        $arr = MemberService::getInstance()->getMemberListPage($where, $page, $pagenum);

        foreach ($arr as $k => $v) {
            $daichong_res = Db::table('bi_days_user_charge')->where([['uid', '=', $v['id']]])->field('sum(amount/10) rmb')->select()->toArray();

            $daichong = 0;
            if ($daichong_res && isset($daichong_res[0]['rmb'])) {
                $daichong = $daichong_res[0]['rmb'];
            }

            $arr[$k]['rmb'] = $daichong;
            $arr[$k]['ctime'] = '';

            $ctime = LogindetailModel::getInstance()->getModel($v['id'])->where('user_id', $v['id'])->value('ctime');
            if ($ctime) {
                $arr[$k]['ctime'] = date('Y-m-d H:i:s', $ctime);
            }
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_id', $user_id);
        View::assign('username', $username);
        View::assign('page', $page_array);
        View::assign('data', $arr);
        return View::fetch('member/online');
    }

    /**
     * 用户添加邀请码
     */
    public function addUserCode()
    {
        $uid = Request::param('id'); //id
        $invitcode = md5(Request::param('invitcode')); //邀请码
        $id = MemberModel::getInstance()->getModel($uid)->where('id', $uid)->value('id');
        if (!$id) {
            echo json_encode(['code' => 500, 'msg' => '错误用户id']);
            die; //php编译join
        }
        $invitcodeis = MarketChannelModel::getInstance()->getModel()->where('invitcode', $invitcode)->value('id');
        if (!$invitcodeis) {
            echo json_encode(['code' => 500, 'msg' => '错误邀请码']);
            die; //php编译join
        }
        $is = MemberModel::getInstance()->getModel($uid)->where('id', $uid)->save(['invitcode' => $invitcode]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功']);
            die; //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败']);
            die; //php编译join
        }
    }

    public function memberList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id', ''); //用户id
        $pretty_id = Request::param('pretty_id'); //用户靓号id
        $mobile = Request::param('mobile'); //手机号
        $head_frame = Request::param('head_frame'); //头像框
        $demo = Request::param('demo'); //头像框
        $ip = Request::param('ip'); //登录ip
        $deviceid = Request::param('deviceid'); //设备id
        $head_frame = $head_frame ? $head_frame : 2;
        $where=[];
        try{
            $elasticService = ElasticsearchService::getInstance()->index("zb_member");
            $elasticService->page($page,$pagenum);
            $elasticService->order("id","desc");
            if ($head_frame == 1) {
                $elasticService->mustNot(["pretty_avatar"=>""]);
                $where[] = ["pretty_avatar","<>",""];
            }
            if ($user_id) {
                $elasticService->must(["id"=>$user_id]);
                $where[] = ["id","=",$user_id];
            }
            if ($pretty_id) {
                $elasticService->must(["pretty_id"=>$pretty_id]);
                $where[] = ["pretty_id","=",$pretty_id];
            }
            if ($mobile) {
                $elasticService->must(["username"=>$mobile]);
                $where[] = ["username","=",$mobile];
            }
            if ($ip) {
                $elasticService->must(["login_ip"=>$ip]);
                $where[] = ["login_ip","=",$ip];
            }
            if ($deviceid) {
                $elasticService->must(["deviceid"=>$deviceid]);
                $where[] = ["deviceid","=",$deviceid];
            }
            $searchInfo = $elasticService->select();
            $count = $searchInfo['total'];
            $data = $searchInfo['data'];
        }catch (\Throwable $e){
            $count = 0;
            $data = [];
        }


        //获取所有的用户ID
        $uids = array_column($data,"id");
        $models = MemberModel::getInstance()->getModels($uids);
        $memberInfo = [];
        foreach($models as $model){
            $res =  $model->getModel()->where("id","in",$model->getList())->where($where)->select()->toArray();
            $memberInfo = array_merge($memberInfo,$res);
        }

        if ($count > 0) {
            $data = $memberInfo;
            foreach ($data as $key => $vo) {
                $data[$key]['sex'] = $vo['sex'] == 1 ? '男' : '女';
                $data[$key]['avatar'] = getavatar($vo['avatar']);
                if ($vo['pretty_avatar']) { //靓号地址
                    $data[$key]['pretty_avatar'] = $this->img_url . $vo['pretty_avatar'];
                }

                $black_info = BlackDataModel::getInstance()->getOneByWhere([['blackinfo', '=', $vo['id']], ['status', '=', '1'], ['time', '<>', 0], ['type', '=', 4]], 'user_id');
                $data[$key]['black'] = !empty($black_info) ? '封禁' : '正常'; // 1 被封禁 2没有被封禁

                if ($data[$key]['black'] == '正常') {
                    $data[$key]['black'] = !empty(UserBlackModel::getInstance()->getModel()->where(['user_id' => $vo['id'], 'status' => '1'])->find()) ? '封禁' : '正常'; // 1 被封禁 2没有被封禁
                }
                $data[$key]['guild_name'] = !empty($vo['guild_id']) ? MemberGuildModel::getInstance()->getOneById($vo['guild_id'], 'nickname') : "暂无";
            }
        }

        Log::record('用户列表查询:操作人:' . $this->token['username'], 'memberList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('用户管理列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $user_id);
        View::assign('pretty_id', $pretty_id);
        View::assign('mobile', $mobile);
        View::assign('head_frame', $head_frame);
        View::assign('demo', $demo);
        View::assign('ip', $ip);
        View::assign('deviceid', $deviceid);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/index');
    }

    /*用户提现列表
     * @param $token    token值
     * @param $page     分页
     * @param $pagenum  条数
     * @param $user_id  搜索用户id
     */
    public function cashOutList()
    {
        $page = Request::param('page'); //分页
        $limit = Request::param('pagenum'); //条数
        if (!$page || !$limit) {
            return $this->return_json(\constant\CodeConstant::CODE_分页或条数不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_分页或条数不能为空]);
        }
        //搜索筛选
        $user_id = Request::param('user_id'); //用户ID
        if ($user_id) {
            $where = ['uid' => $user_id];
        } else {
            $where = [];
        }
        $offset = ($page - 1) * $limit;
        $count = FreeDiamondLogModel::getInstance()->getModel()->where($where)->count();
        $totalPage = ceil($count / $limit);
        $pageInfo = array("page" => $page, "pageNum" => $limit, "totalPage" => $totalPage, "count" => $count);
        $data = FreeDiamondLogModel::getInstance()->getList($where, $offset, $limit);
        if ($data) {
            $result = [
                "list" => $data,
                "pageInfo" => $pageInfo,
            ];
            return $this->return_json(\constant\CodeConstant::CODE_成功, $result, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        } else {
            return $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_暂无数据]);
        }
    }

    /*
     * 用户详情数据
     */
    public function memberItem()
    {
        $id = $this->request->param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[$this->return_json(\constant\CodeConstant::CODE_参数错误)]);
            die;
        }

        //查询个人基本信息
        $field = 'attestation,avatar,nickname,gold_coin,id,pretty_id,sex,username,intro,login_time,register_time,login_ip,totalcoin,freecoin,diamond,exchange_diamond,free_diamond,pretty_avatar,deviceid,invitcode';
        $user_data = MemberService::getInstance()->getOneById($id, $field);
        if (empty($user_data)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $user_data = $user_data->toarray();
        //用户最后离开时间
        $redis = RedisCommon::getInstance()->getRedis();
        $score = $redis->zscore(sprintf('user_online_history_%s_list', 'all'), $id);
        $user_data['leavetime'] = ((int) $score > 0 ? date('Y-m-d H:i:s', $score) : '');

        $guild_id = MemberSocityModel::getInstance()->find($id);
        if ($guild_id) {
            $user_data['guild'] = MemberGuildModel::getInstance()->getOneById($guild_id['guild_id'], 'nickname');
        }
        $user_data['coin'] = $user_data['totalcoin'] - $user_data['freecoin'];
        $user_data['diamond'] = intval((round($user_data['diamond']) - round($user_data['exchange_diamond']) - round($user_data['free_diamond'])) * 0.0001);
        if ($user_data['avatar']) {
            $user_data['avatar'] = config('config.APP_URL_image') . '/' . $user_data['avatar'];
        } else {
            $user_data['avatar'] = config('config.APP_URL_image') . '/' . '/images/mualogo.png';
        }

        if ($user_data['sex'] == 1) {
            $user_data['sex'] = '男';
        } elseif ($user_data['sex'] == 2) {
            $user_data['sex'] = '女';
        } else {
            $user_data['sex'] = '保密';
        }
        Log::record('用户详情查询:操作人:' . $this->token['username'] . '@id:' . $id, 'memberItem');
        //查询用户是否为封禁
        $user_data['black'] = !empty(BlackDataModel::getInstance()->getOneByWhere(array(['blackinfo', '=', $id], ['status', '=', '1'], ['time', '<>', 0], ['type', '=', 4]), 'user_id')) ? 1 : 2; // 1 被封禁 2没有被封禁
        $user_data['threeBlack'] = !empty(UserBlackModel::getInstance()->getModel()->where(['user_id' => $id, "status" => 1])->find()) ? 1 : 2; // 1 被封禁 2没有被封禁
        unset($user_data['totalcoin']);
        unset($user_data['freecoin']);
        unset($user_data['exchange_diamond']);
        unset($user_data['free_diamond']);
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $user_data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /*
     *用户靓号修改
     */
    public function editMemberPretty()
    {
        try {
            $pretty_id = $this->request->param('member_val');
            $id = $this->request->param('id');

            if (!$pretty_id || !$id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);die;
            }

            // //查询此靓号是否存在
            // $ok = MemberService::getInstance()->getOneByWhere(array('pretty_id' => $pretty_id), 'id');
            // if (!empty($ok)) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_此靓号已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此靓号已存在]);die;
            // }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $id,
                'datas' => json_encode([
                    'prettyId' => $pretty_id,
                ]),
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);

            Log::record('用户靓号修改成功:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $pretty_id, 'editMemberPretty');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('用户靓号修改失败:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $pretty_id, 'editMemberPretty');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /*
     *用户昵称修改
     */
    public function editMemberNickname()
    {
        try {
            $nickname = $this->request->param('member_val');
            $id = $this->request->param('id');

            if (!$nickname || !$id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }
            //查询此昵称是否存在
            // $ok = MemberService::getInstance()->getOneByWhere(array('nickname' => $nickname), 'id');
            // if (!empty($ok)) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_此昵称已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此昵称已存在]);
            //     die;
            // }

            // $updata = MemberService::getInstance()->editOneByWhere(array('id' => $id), array('nickname' => $nickname));
            // if ($updata) {
            //     //发送消息
            //     $str = ['userId' => (int) $id];
            //     $socket_url = config('config.socket_url_base') . 'iapi/syncUserData';
            //     $msgData = json_encode($str);
            //     $res = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("系统修改用户昵称发送参数数据-----" . $msgData, "info");
            //     Log::record("系统修改用户昵称发送数据-----" . $res, "info");

            //     $redis = $this->getRedis();
            //     $redis->hset('userinfo_' . $id, 'nickname', $nickname);
            //     HandleRedisService::getInstance()->delUserCache($id);
            // }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $id,
                'datas' => json_encode([
                    'nickname' => $nickname,
                ]),
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);

            Log::record('用户昵称修改成功:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $nickname, 'editMemberNickname');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (\Throwable $th) {
            Log::record('用户昵称修改失败:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $nickname, 'editMemberNickname');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     *用户手机号修改
     */
    public function editMemberUsername()
    {
        try {
            $username = $this->request->param('member_val');
            $id = $this->request->param('id');
            if (!$username || !$id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            //$pattern = "/^1(3[0-9]|5[012356789]|8[0256789]|7[0678])\d{8}$/";
            $pattern = "/^1\d{10}$/";
            if (!preg_match($pattern, $username)) {
                echo $this->return_json(\constant\CodeConstant::CODE_此手机号不合法, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此手机号不合法]);
                die;
            }
            //查询此用户名是否存在
            // $ok = MemberService::getInstance()->getOneByWhere(array('username' => $username), 'id');
            // if (!empty($ok)) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_此手机号已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此手机号已存在]);
            //     die;
            // }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $id,
                'datas' => json_encode([
                    'mobile' => $username,
                ]),
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);

            Log::record('用户用户名修改成功:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $username, 'editMemberUsername');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('用户昵称修改失败:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $username, 'editMemberUsername');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /*
     *用户简介修改
     */
    public function editMemberIntro()
    {
        try {
            $intro = $this->request->param('member_val');
            $id = $this->request->param('id');

            if (!$intro || !$id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            $check_nickname = mb_strlen($intro, 'gb2312');
            if ($check_nickname > 50) {
                echo $this->return_json(\constant\CodeConstant::CODE_用户个性签名可超过50字符, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户个性签名可超过50字符]);
                die;
            }
            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $id,
                'datas' => json_encode([
                    'intro' => $intro,
                ]),
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);

            Log::record('用户简介修改成功:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $intro, 'editMemberIntro');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('用户简介修改失败:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $intro, 'editMemberIntro');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /*
     *用户头像框地址修改
     */
    public function editPrettyAvatar()
    {
        try {
            $pretty_avatar = $this->request->param('member_val');
            $id = $this->request->param('id');

            if (!$id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $id,
                'datas' => json_encode([
                    'pretty_avatar' => $pretty_avatar,
                ]),
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);

            Log::record('用户头像框地址修改成功:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $pretty_avatar, 'editPrettyAvatar');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('用户头像框地址修改失败:操作人:' . $this->token['username'] . ':被修改人:' . $id . ':修改值为:' . $pretty_avatar, 'editPrettyAvatar');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     *用户头像框地址修改
     */
    public function editMemberAttention()
    {
        try {
            $uid = $this->request->param('id');
            if (!$uid) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            $attention_info = MemberService::getInstance()->getMemeberAttention([['uid', '=', $uid], ['status', '=', 1]]);
            // if (!$attention_info) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, '用户实名信息不存在');
            //     die;
            // }

            //记录
            $operator_id = AdminOperationLogModel::getInstance()->getModel()->insertGetId(
                [
                    'uid' => $uid,
                    'type' => 'attention',
                    'before_info' => json_encode($attention_info),
                    'after_info' => json_encode([]),
                    'admin_id' => $this->token['id'],
                    'created' => time(),
                    'updated' => time(),
                ]
            );

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $uid,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$reset_attention, $params);

            //更新操作后信息
            $after_info = MemberService::getInstance()->getMemeberAttention([['uid', '=', $uid]]);
            AdminOperationLogModel::getInstance()->getModel()->where('id', $operator_id)->update(['after_info' => json_encode($after_info)]);

            Log::record('用户实名信息清空成功:操作人:' . $this->token['username'] . ':被修改人:' . $uid);
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('用户实名信息清空失败:操作人:' . $this->token['username'] . ':被修改人:' . $uid);
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     *用户装备列表
     */
    public function userPackList()
    {
        $id = $this->request->param('id');
        $url = config('config.APP_URL_image');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[$this->return_json(\constant\CodeConstant::CODE_参数错误)]);
            die;
        }
        //$user_data = PackModel::getInstance()->getByWhere(['user_id' => $id],$id);
        $user_data  = PackModel::getInstance()->getModel($id)->where(['user_id' => $id])->order('endtime', 'desc')->select()->toArray();
        $gift_list = GiftsCommon::getInstance()->giftMapList();

        foreach ($user_data as $key => $val) {
            $user_data[$key]['gift_img'] = $url;
            $user_data[$key]['gift_coin'] = 0 . 'M豆';

            if (isset($gift_list[$val['gift_id']])) {
                $gift_info = $gift_list[$val['gift_id']];
                $user_data[$key]['gift_img'] = $url . $gift_info['gift_image'];
                $user_data[$key]['gift_coin'] = $gift_info['gift_coin'] . 'M豆';
            }

            if ($val['channel_id'] == 0) {
                $user_data[$key]['channel_name'] = '管理员赠送';
            } else {
                $channel = ActiveModel::getInstance()->getModel()->getById($val['channel_id'], 'name');
                $user_data[$key]['channel_name'] = $channel['name'];
            }
            $user_data[$key]['createtime'] = date('Y-m-d H:i:s', $val['createtime']);
            if ($val['endtime'] == 0) {
                $user_data[$key]['endtime'] = '永久有效';
            } else if ($val['endtime'] < time()) {
                $user_data[$key]['endtime'] = '已过期';
            } else {
                $user_data[$key]['endtime'] = date('Y-m-d H:i:s', $val['endtime']);
            }

        }
        Log::record('用户装备详情查询:操作人:' . $this->token['username'] . '@id:' . $id, 'userPackList');
        //查询用户是否为封禁
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $user_data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    //用户登录信息列表
    public function loginUserInfo()
    {
        $id = $this->request->param('id');

        $data = UserLastInfoModel::getInstance()->getModel($id)->where('user_id', $id)->find();
        $res = [];
        if ($data) {
            $res = $data->toArray();
            $res['update_time'] = date('Y-m-d H:i:s', $res['update_time']);
        }

        Log::record('用户登录信息列表查询:操作人:' . $this->token['username'] . '@id:' . $id, 'userPackList');
        //查询用户是否为封禁
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    //用户装备删除
    public function userPackDel()
    {
        $id = $this->request->param('uid');
        $gift_id = $this->request->param('gift_id');
        if (!$gift_id || !$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        //查询是否存在
        $where = [
            'user_id' => $id,
            'gift_id' => $gift_id,
        ];
       // $ok = PackModel::getInstance()->getOneByWhere($where,$id);
        $ok = PackModel::getInstance()->getModel($id)->where($where)->find();
        if (!$ok) {
            echo $this->return_json(\constant\CodeConstant::CODE_没有查询到数据, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_没有查询到数据]);
            die;
        }
        $data = [
            'endtime' => time(),
            'update_time' => date('Y-m-d H:i:s', time()),
        ];
        $updata = PackModel::getInstance()->getModel($id)->where($where)->delete();
        if ($updata) {
            Log::record('删除用户装备成功:操作人:' . $this->token['username'] . ':条件:' . json_encode($where) . ':修改值为:' . json_encode($data), 'userPackDel');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        }
        Log::record('删除用户装备失败:操作人:' . $this->token['username'] . ':条件:' . json_encode($where) . ':修改值为:' . json_encode($data), 'userPackDel');
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }

    //用户装备佩戴
    public function userPackAdorn()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;

        $id = $this->request->param('uid');
        $gift_id = $this->request->param('gift_id');
        if (!$gift_id || !$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        //查询是否存在
        $where = [
            'user_id' => $id,
            'gift_id' => $gift_id,
        ];
        //$ok = PackModel::getInstance()->getOneByWhere($where);
        $ok = PackModel::getInstance()->getModel($id)->where($where)->find();
        if (!$ok) {
            echo $this->return_json(\constant\CodeConstant::CODE_没有查询到数据, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_没有查询到数据]);
            die;
        }
        if ($ok['is_ware'] == 1) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户当前装备已穿戴, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户当前装备已穿戴]);
            die;
        }
        if ($ok['endtime'] != 0 && $ok['endtime'] < time()) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户当前装备已过期, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户当前装备已过期]);
            die;
        }
        $isWhere = ['user_id' => $id, 'gift_type' => $ok['gift_type']];
        //$isWare = PackModel::getInstance()->getByWhere($isWhere);
        $isWare =  PackModel::getInstance()->getModel($id)->where($where)->order('endtime', 'desc')->select()->toArray();
        foreach ($isWare as $key => $val) {
            if ($val['is_ware'] == 1) {
                //$ware = PackModel::getInstance()->editOneByWhere(array('user_id' => $id, 'gift_type' => $ok['gift_type'], 'gift_id' => $val['gift_id']), array('is_ware' => 0));
                $ware = PackModel::getInstance()->getModel($id)->where(array('user_id' => $id, 'gift_type' => $ok['gift_type'], 'gift_id' => $val['gift_id']))->update(array('is_ware' => 0));
                if (!$ware) {
                    echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);
                    die;
                }
            }
        }
        $data = ['is_ware' => 1, 'update_time' => date('Y-m-d H:i:s', time())];
        //$updata = PackModel::getInstance()->editOneByWhere($where, $data);
        $updata = PackModel::getInstance()->getModel($id)->where($where)->update($data);
        if ($updata) {
            Log::record('穿戴用户装备成功:操作人:' . $this->token['username'] . ':条件:' . json_encode($where) . ':修改值为:' . json_encode($data), 'userPackAdorn');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        }
        Log::record('穿戴用户装备失败:操作人:' . $this->token['username'] . ':条件:' . json_encode($where) . ':修改值为:' . json_encode($data), 'userPackAdorn');
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }

    //用户装备赠送列表
    public function userPackGiveList()
    {
        $id = $this->request->param('uid');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[$this->return_json(\constant\CodeConstant::CODE_参数错误)]);
            die;
        }
        $givePackList = GivePackModel::getInstance()->givePropByWhere(array('uid' => $id));
        foreach ($givePackList as $key => $val) {
            $coin = GiftModel::getInstance()->getOneById($val['gift_id'], 'gift_coin');
            $givePackList[$key]['gift_coin'] = $coin['gift_coin'] . 'M豆';
            $givePackList[$key]['created_time'] = date('Y-m-d H:i:s', $val['created_time']);
            if ($val['gift_time'] == 0) {
                $givePackList[$key]['gift_time'] = '永久有效';
            } else {
                $givePackList[$key]['gift_time'] = $val['gift_time'] . '天';
            }
        }
        Log::record('用户装备赠送列表:操作人:' . $this->token['username'] . '数据:' . json_encode($givePackList), 'userPackGiveList');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $givePackList, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    //用户装备赠送
    public function userPackGive()
    {
        echo $this->return_json('接口已停止使用', null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
        die;
        $id = $this->request->param('uid');
        $gift_id = $this->request->param('gift_id');
        $gift_time = $this->request->param('gift_time');
        $give_desc = $this->request->param('give_desc');
        if (!$id || !$gift_id || !$give_desc) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        };
        if ($gift_time == 0) {
            $time = 0;
        } else {
            $time = date('Y-m-d H:i:s', time() + (int) $gift_time * 24 * 60 * 60);
        };
        $coin = GiftModel::getInstance()->getOneById($gift_id, 'gift_coin,type');
//        print_r($coin);die;
        if (empty($coin)) {
            echo $this->return_json(\constant\CodeConstant::CODE_该装备不存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_该装备不存在]);
            die;
        } else {
            $coin = $coin->toArray();
        }
//        if($coin['type'] == 1){
        //            $type = '头像框';
        //        }else if($coin['type'] == 2){
        //            $type = '音波纹';
        //        }else if($coin['type'] == 3){
        //            $type = '进场动画';
        //        }else{
        //            echo $this->return_json(\constant\CodeConstant::CODE_该装备不存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_该装备不存在]);
        //            die;
        //        }
        $data = [
            'uid' => $id,
            'gift_id' => $gift_id,
            'gift_time' => $gift_time,
            'give_desc' => $give_desc,
            'created_time' => time(),
            'created_user' => $this->token['username'],
        ];
        //$ok = PackModel::getInstance()->getOneByWhere(array('user_id' => $id, 'gift_id' => $gift_id));
        $ok = PackModel::getInstance()->getModel($id)->where(array('user_id' => $id, 'gift_id' => $gift_id))->find();
        if (!empty($ok)) {
            $ok = $ok->toArray();
            $packData['pack_num'] = $ok['pack_num'] + 1;
            $giveRes = GivePackModel::getInstance()->givePropAdd($data);
            //$packRes = PackModel::getInstance()->editOneByWhere(array('user_id' => $id, 'gift_id' => $gift_id), $packData);
            $packRes = PackModel::getInstance()->getModel($id)->where(array('user_id' => $id, 'gift_id' => $gift_id))->update($packData);
        } else {
            $packData = [
                'user_id' => $id,
                'gift_id' => $gift_id,
                'gift_type' => $coin['type'],
                'channel_id' => 0,
                'gift_coin' => $coin['gift_coin'],
                'pack_num' => 1,
                'is_ware' => 0,
                'endtime' => strtotime($time),
                'createtime' => time(),
            ];
            $giveRes = GivePackModel::getInstance()->givePropAdd($data);
            //$packRes = PackModel::getInstance()->addPack($packData);
            $packRes = PackModel::getInstance()->getModel($id)->insert($packData);
        }
        if ($giveRes && $packRes) {
            Log::record('赠送用户装备成功:操作人:' . $this->token['username'] . '@' . json_encode($data) . '@' . json_encode($packData), 'userPackGive');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('赠送用户装备失败:操作人:' . $this->token['username'] . '@' . json_encode($data) . '@' . json_encode($packData), 'userPackGive');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /**
     * 修改用户头像
     */
    public function avatarOssFile()
    {
        try {
            $user_id = Request::param('id');
            if (!$user_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            };

            // $where['id'] = $user_id;
            $avatar = request()->file('avatar');
            $file_dir = "/useravatar";
            $UploadOssFileCommon = new UploadOssFileCommon();
            $avatarurl = $UploadOssFileCommon->ossFile($avatar, $file_dir);
            // $is = MemberService::getInstance()->saveAvatarurl(['user_id' => $user_id, 'where' => $where, 'avatarurl' => $avatarurl]);

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $user_id,
                'datas' => json_encode([
                    'avatar' => $avatarurl,
                ]),
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);

            Log::record('修改用户头像:操作人:' . $this->token['username'] . ':条件:' . json_encode($params), 'avatarOssFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('修改用户头像:操作人:' . $this->token['username'] . ':条件:' . json_encode($params), 'avatarOssFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /**
     * 修改用户头像框
     */
    public function prettyavatarOssFile()
    {
        try {
            //获取数据
            $user_id = Request::param('id');
            if (!$user_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            };
            $where['id'] = $user_id;
            $avatar = request()->file('pretty_avatar');
            $file_dir = "/prettyavatar";
            $UploadOssFileCommon = new UploadOssFileCommon();
            $avatarurl = $UploadOssFileCommon->ossFile($avatar, $file_dir);
            $UploadOssFileCommon->ossFile($avatar, $file_dir, 2); //上传另一个目录下

            $result = parse_url($avatarurl);
            $pretty_avatar = $result['path'];

            // $data = ['pretty_avatar' => $pretty_avatar];
            // $res = MemberModel::getInstance()->setMember($where, $data);
            // if ($res) {
            //     $redis->hset('userinfo_' . $user_id, 'pretty_avatar', $pretty_avatar);
            // }
            // HandleRedisService::getInstance()->delUserCache($user_id);

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $user_id,
                'datas' => json_encode([
                    'pretty_avatar' => $pretty_avatar,
                ]),
            ];
            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);

            Log::record('用户修改头像框成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($params), 'prettyavatarOssFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('用户修改头像框失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($params), 'prettyavatarOssFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        }
    }

    /**
     * 隐身管理员列表
     */
    public function ysuserList()
    {
        $redis = $this->getRedis();
        $list = $redis->SMEMBERS($this->user_key);
        $data = [];
        foreach ($list as $k => $uid) {
            $data[$k]['id'] = $uid;
        }
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/ysuserAdmin');
    }

    /**
     * 添加隐身管理员
     */
    public function addYsUser()
    {
        try {
            $user_id = Request::param('user_id'); //用户ID
            if (!$user_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }
            // $where['id'] = $user_id;
            // $res = MemberModel::getInstance()->getModel($user_id)->where($where)->find();
            // if (!$res) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_没有查询到数据, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_没有查询到数据]);
            //     die;
            // }

            // $redis = $this->getRedis();
            // $addUser = $redis->SISMEMBER($this->user_key, $user_id);
            // if ($addUser) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_隐身用户ID已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_隐身用户ID已存在]);
            //     die;
            // }
            // $result = $redis->SADD($this->user_key, $user_id);
            // $data = ['role' => 3];
            // $dbRes = MemberModel::getInstance()->getModel($user_id)->where($where)->save($data);

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $user_id,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$add_room_manager, $params, true);

            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('添加隐身用户失败:操作人:' . $this->token['username'] . '@' . json_encode($user_id), 'roomRecommendAdd');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /**
     * 添加虚拟用户
     */
    public function addUser()
    {
        try {
            $type = Request::param('type'); //状态
            if ($type == 1) {
                $invitcode = Request::param('invitcode'); //邀请码
                $uid = Request::param('uid');

                if (!$invitcode || !$uid) {
                    echo json_encode(['code' => 500, 'msg' => '参数为空']);
                }

                $params = [
                    'operatorId' => $this->token['id'],
                    'token' => $this->token['admin_token'],
                    'user_id' => $uid,
                    'invitcode' => $invitcode,
                ];

                ApiService::getInstance()->curlApi(ApiUrlConfig::$update_member_invitcode, $params, true);

            } else {
                $username = Request::param('username'); //手机号
                $password = Request::param('password'); //密码
                $sex = Request::param('sex');

                if (!$username || !$password) {
                    echo json_encode(['code' => 500, 'msg' => '参数为空']);
                }

                $params = [
                    'operatorId' => $this->token['id'],
                    'token' => $this->token['admin_token'],
                    'username' => $username,
                    'password' => $password,
                    'sex' => (int) $sex,
                ];

                ApiService::getInstance()->curlApi(ApiUrlConfig::$add_virtual_member, $params, true);
            }

            $this->return_json(200, null, '添加成功', true);
        } catch (ApiExceptionHandle $e) {
            $this->return_json($e->getCode(), null, $e->getMessage(), true);
        } catch (Throwable $th) {
            $this->return_json(['code' => 500, 'msg' => '添加失败'], true);
        }
    }

    /**
     * 取消隐身管理员
     */
    public function delYsUser()
    {
        try {
            $user_id = Request::param('id'); //用户ID

            if (!$user_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => (int) $user_id,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$del_room_manager, $params, true);

            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('取消隐身用户失败:操作人:' . $this->token['username'] . '@' . json_encode($user_id), 'delYsUser');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /**
     * @return mixed
     * 聊天记录
     */
    public function messageList()
    {
        $pagenum = 10;
        $TheCustomPage = $this->request->param('TheCustomPage'); //自定义页码
        if ($TheCustomPage) {
            $page = ($TheCustomPage - 1) * $pagenum;
            $master_page = $TheCustomPage;
        } else {
            $page = !empty($this->request->param('page')) ? ($this->request->param('page') - 1) * $pagenum : 0;
            $master_page = $this->request->param('page', 1);
        }
        $user_id = Request::param('user_id'); //用户id
        $userid = Request::param('userid'); //用户id
        $text = Request::param('text'); //关键字
        $sort = Request::param('sort'); //排序
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $where = [];
        if ($text) {
            $where[] = ['textContent', 'like', '%' . $text . '%'];
        }

        $where[] = ['createTime', '>=', strtotime($start)];
        $where[] = ['createTime', '<', strtotime($end)];
        $time[] = ['register_time', '>=', $start];
        $time[] = ['register_time', '<', $end];

        $member_list = MemberModel::getInstance()->getWhereAllData($time, "id");
        $uid = array_column($member_list, 'id');

        $newfromcount = ImMessageModel::getInstance()->getModel()->group('fromUid')->where($where)->whereIn('fromUid', $uid)->count();
        $newtocount = ImMessageModel::getInstance()->getModel()->group('toUid')->where($where)->whereIn('toUid', $uid)->count();
        $fromcount = ImMessageModel::getInstance()->getModel()->group('fromUid')->where($where)->count();
        $tocount = ImMessageModel::getInstance()->getModel()->group('toUid')->where($where)->count();

        $data = [];

        if ($user_id && $userid) {
            $where[] = ['fromUid', '=', $user_id];
            $where[] = ['toUid', '=', $userid];
            $count = ImMessageModel::getInstance()->getModel()->group('toUid')->group('toUid,fromUid')->where($where)->count();
            if ($sort == 1) {
                $data = ImMessageModel::getInstance()->getModel()->where($where)->order('createTime', 'desc')->group('toUid,fromUid')->limit($page, $pagenum)->select()->toArray();
            } else {
                $data = ImMessageModel::getInstance()->getModel()->where($where)->order('createTime', 'asc')->group('toUid,fromUid')->limit($page, $pagenum)->select()->toArray();
            }
        } elseif ($user_id && empty($userid)) {
            $where[] = ['fromUid', '=', $user_id];
            $count = ImMessageModel::getInstance()->getModel()->group('toUid')->group('toUid')->where($where)->count();
            if ($sort == 1) {
                $data = ImMessageModel::getInstance()->getModel()->where($where)->order('createTime', 'desc')->group('toUid')->limit($page, $pagenum)->select()->toArray();
            } else {
                $data = ImMessageModel::getInstance()->getModel()->where($where)->order('createTime', 'asc')->group('toUid')->limit($page, $pagenum)->select()->toArray();
            }
        } elseif ($userid && empty($user_id)) {
            $where[] = ['toUid', '=', $userid];
            $count = ImMessageModel::getInstance()->getModel()->group('toUid')->group('fromUid')->where($where)->count();
            if ($sort == 1) {
                $data = ImMessageModel::getInstance()->getModel()->where($where)->order('createTime', 'desc')->group('fromUid')->limit($page, $pagenum)->select()->toArray();
            } else {
                $data = ImMessageModel::getInstance()->getModel()->where($where)->order('createTime', 'asc')->group('fromUid')->limit($page, $pagenum)->select()->toArray();
            }
        } else {
            $count = ImMessageModel::getInstance()->getModel()->group('toUid')->group('toUid,fromUid')->where($where)->count();
            if ($sort == 1) {
                $data = ImMessageModel::getInstance()->getModel()->where($where)->order('createTime', 'desc')->group('toUid,fromUid')->limit($page, $pagenum)->select()->toArray();
            } else {
                $data = ImMessageModel::getInstance()->getModel()->where($where)->order('createTime', 'asc')->group('toUid,fromUid')->limit($page, $pagenum)->select()->toArray();
            }
        }

        foreach ($data as $key => $vo) {
            $content_len = mb_strlen($vo['textContent']);
            $rows = ceil($content_len / 50);
            $data[$key]['rows'] = $rows;
            $data[$key]['createTime'] = date('Y-m-d H:i:s', $vo['createTime']);
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('聊天记录:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $user_id);
        View::assign('searchid', $userid);
        View::assign('text', $text);
        View::assign('sort', $sort);
        View::assign('demo', $demo);
        View::assign('newfromcount', $newfromcount);
        View::assign('newtocount', $newtocount);
        View::assign('fromcount', $fromcount);
        View::assign('tocount', $tocount);
        View::assign('TheCustomPage', $TheCustomPage);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/message');
    }

    /**
     * @return mixed
     * 聊天记录详情
     */
    public function messageData()
    {
        $where = [];

        $fromUid = Request::param('fromUid'); //A用户
        $toUid = Request::param('toUid'); //B用户
        $createTime = $this->request->param('createTime'); //时间

        $where[] = ['createTime', '>=', strtotime($createTime)];

        if ($fromUid) {
            $where[] = ['fromUid', '=', $fromUid];
        }
        if ($toUid) {
            $where[] = ['toUid', '=', $toUid];
        }
        //统计用户条数
        $count = ImMessageModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = ImMessageModel::getInstance()->getModel()->where($where)->select()->toArray();
            $where[1] = ['fromUid', '=', $toUid];
            $where[2] = ['toUid', '=', $fromUid];
            $data1 = ImMessageModel::getInstance()->getModel()->where($where)->select()->toArray();
            $data = array_merge($data, $data1);
            foreach ($data as $key => $vo) {
                $data[$key]['create'] = date('Y-m-d H:i:s', $vo['createTime']);
            }
        }
        $last_names = array_column($data, 'createTime');
        array_multisort($last_names, SORT_ASC, $data);
        Log::record('聊天记录:操作人:' . $this->token['username'], 'memberList');
        View::assign('data', $data);
        View::assign('demo', $createTime);
        View::assign('toUid', $toUid);
        View::assign('fromUid', $fromUid);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/messageData');
    }

    /**
     * @return mixed
     * 用户信息审核
     */
    public function changeMemberInfo()
    {
        $user_id = (int) Request::param('user_id', 0); //用户ID
        $room_id = (int) Request::param('room_id', 0); //房间ID
        $status = (int) Request::param('status', 0); //状态
        $page = $this->request->param('page', 1);
        $offset = ($page - 1) * $page;

        $where = [];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }

        if ($room_id > 0) {
            $where[] = ['room_id', '=', $room_id];
        }
        $where[] = ['status', '=', $status];

        //统计用户条数
        $count = MemberDetailAuditModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        $data = MemberDetailAuditModel::getInstance()->getModel()->where($where)->limit($offset, self::LIMIT)->order('update_time desc,create_time desc')->select()->toArray();
        $roomids = array_column($data, "room_id");
        $roomList = LanguageroomModel::getInstance()->getWhereAllData([["id", "in", $roomids]], "id,room_name,guild_id");
        $roomTypeList = array_column($roomList, 'guild_id', 'id');
        foreach ($data as $key => &$item) {
            if (in_array($item['action'], ['avatar', 'wall'])) {
                if (strpos($item['content'], 'image2.fqparty.com') === false) {
                    $imgs = explode(',', $item['content']);
                    $item['content'] = array_map(function ($img) {
                        return config('config.APP_URL_image') . $img;
                    }, $imgs);
                } else {
                    $item['content'] = [$item['content']];
                }
            } elseif ($item['action'] == 'voice') {
                $voice_content = json_decode($item['content'], true);
                $item['content'] = config('config.APP_URL_image') . $voice_content['voiceIntro'];
            }
            $item['update_time'] = $item['update_time'] == 0 ? '' : date('Y-m-d H:i:s', $item['update_time']);
            $item['room_type'] = ($item['room_id'] == 0) ? '' : (isset($roomTypeList[$item['room_id']]) && $roomTypeList[$item['room_id']] > 0 ? '工会房' : '个人房');
            if ($item['status'] == 0) {
                $item['admin_user_name'] = "";
            }
        }
        $personroom_isopen = RoomcheckSwitchModel::getInstance()->getModel()->where('type', 'person_room')->value('is_open');
        $guildroom_isopen = RoomcheckSwitchModel::getInstance()->getModel()->where('type', 'guild_room')->value('is_open');

        $page_array = [];
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / self::LIMIT);
        Log::record('用户管理列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('status_map', AdminCommonConfig::STATUS_MAP);
        View::assign('action_map', AdminCommonConfig::MEMBER_ACTION);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_id', $user_id);
        View::assign('status', $status);
        View::assign('room_id', $room_id);
        View::assign('personroom_isopen', $personroom_isopen);
        View::assign('guildroom_isopen', $guildroom_isopen);
        return View::fetch('member/memberInfo');
    }

    //通过
    public function memberInfoAgree()
    {
        try {
            $ids = Request::param('id');
            $status = (int) Request::param('status');
            $ids_arr = explode(',', $ids);

            $fail_msg = '';
            $succ_msg = '';
            $succ_count = 0;
            $fail_count = 0;
            for ($i = 0; $i < count($ids_arr); $i++) {
                $id = (int) $ids_arr[$i];
                $res = MemberDetailAuditModel::getInstance()->getModel()->where('id', $id)->findOrEmpty()->toArray();
                Log::info('curlRegister:audit_info====>{data}', ['data' => json_encode($res)]);
                if ($res['status'] == 0) {
                    if ($res['room_id'] > 0) {
                        $curl_data = ConfigService::getInstance()->changeRoomInfo($id, $status, $this->token['admin_token'], $this->token['id']);
                    } else {
                        $curl_data = ConfigService::getInstance()->changeMemberInfo($id, $status, $this->token['admin_token'], $this->token['id']);
                    }

                    if ($curl_data['code'] != 200) {
                        $fail_count += 1;
                        $msg = "用户ID:{$res['user_id']},失败信息:{$curl_data['desc']};";
                        $fail_msg .= $msg;
                    } else {
                        $succ_count += 1;
                        $logData = [
                            "user_id" => $res['user_id'] ?? 0,
                            "content" => $res['content'] ?? '',
                            "room_id" => $res['room_id'] ?? 0,
                            "status" => $status,
                            "action" => $res['action'] ?? '',
                            "admin_user_name" => $this->token['username'] ?? '',
                            "update_time" => time(),
                        ];
                        MemberDetailAuditLogModel::getInstance()->getModel()->insert($logData);
                    }

                } elseif ($res['status'] == 1) {
                    $succ_count += 1;
                    $suc_msg = "用户ID:{$res['user_id']},已审核;";
                    $succ_msg .= $suc_msg;
                }
            }

            $deal_msg = "处理成功:{$succ_count}条,处理失败:{$fail_count}条,失败信息:【{$fail_msg}】,已审核:【{$succ_msg}】";
            return rjson([], 200, $deal_msg);
        } catch (\Throwable $th) {
            Log::INFO("memberinfoagree:" . $th->getMessage());
            return rjson([], 403, '操作失败');
        }
    }

    public function checkimMsgList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $from_uid = $this->request->param('from_uid', 0, 'trim'); //发送者
        $to_uid = $this->request->param('to_uid', 0, 'trim'); //接收者
        $type = $this->request->param('type', -1, 'trim');
        $status = $this->request->param('status', 0, 'trim');
        $date_b = $this->request->param('date_b', date('Y-m-d'), 'trim');
        $date_e = $this->request->param('date_e', date('Y-m-d', strtotime("+1days")), 'trim');
        $daochu = $this->request->param('daochu', 0);
        $keyword = $this->request->param('keyword', '', 'trim'); //关键词
        $es_from = ($page - 1) * $limit;
        $checkimmessageElas = ElasticsearchService::getInstance()->index("zb_check_im_message");
        if ($type === "") {
            $type = -1;
        }
        $checkimmessageElas->page($es_from,$limit);
        if ($type >= 0) {
            $checkimmessageElas->must(['type' => $type]);
        }

        if ($status > 0) {
            $checkimmessageElas->must(['status' => $status]);
        }

        if ($from_uid > 0) {
            $checkimmessageElas->must(['from_uid' => $from_uid]);
        }

        if ($to_uid > 0) {
            $checkimmessageElas->must(['to_uid' => $to_uid]);
        }

        if ($date_b && $date_e) {;
            $checkimmessageElas->range("created_time",['gte' => strtotime($date_b), 'lt' => strtotime($date_e)]);
        }

        if ($keyword) {
            $checkimmessageElas->must(['message' => $keyword],"match_phrase");
        }

        $checkimmessageElas->order("created_time","desc");

        $typeList = CheckImMessageModel::TYPEMAP;
        $statusList = CheckImMessageModel::STATUSMAP;

        if ($this->request->param("isRequest") == 1) {
            if ($daochu == 1) {
                $headerArray = [
                    'from_uid' => '发送者',
                    'to_uid' => '接收者',
                    'type_mark' => '消息类型',
                    'message' => '聊天内容',
                    'api_response' => 'api反馈',
                    'created_time' => '创建时间',
                ];
                $checkimmessageElas->fields(array_keys($headerArray));
                try {
                    ExportExcelService::getInstance()->dataElasExportCsv($checkimmessageElas, $headerArray,[FormaterExportDataCommon::getInstance(),"formaterImCheckList"]);
                } catch (\Throwable $e) {
                    Log::info("checkimmsglist:error" . $e->getMessage() . $e->getLine() . $e->getFile());
                }
                exit;
            } else {
                $searchData = $checkimmessageElas->select();
                $res = $searchData['data'] ?? [];
                $count = $searchData['total'] ?? 0;
            }

            foreach ($res as $key => $item) {
                $res[$key]['oss'] = config("config.APP_URL_image");
                $res[$key]['type_mark'] = $typeList[$item['type']] ?? '';
                $res[$key]['status_mark'] = $statusList[$item['status']] ?? '';
                $res[$key]['created_time'] = date('Y-m-d H:i:s', $item['created_time']);
                $check_response = json_decode($item['check_response'], true);
                $check_response_content = $check_response['riskDescription'] ?? '';
                $api_response_content = $item['api_response'] ?? '';
                $res[$key]['check_response'] = $check_response_content;
                if (mb_strlen($check_response_content) > 10) {
                    $res[$key]['check_response_part'] = mb_substr($check_response_content, 0, 10) . "..";
                } else {
                    $res[$key]['check_response_part'] = $check_response_content;
                }

                if (mb_strlen($api_response_content) > 10) {
                    $res[$key]['api_response_part'] = mb_substr($api_response_content, 0, 10) . "..";
                } else {
                    $res[$key]['api_response_part'] = $api_response_content;
                }

                if ($item['type'] == 0) {
                    $message = $item['message'] ?? '';
                    $message = strFilter($message);
                    if (mb_strlen($message) > 20) {
                        $res[$key]['message_part'] = mb_substr($message, 0, 20) . "...";
                    } else {
                        $res[$key]['message_part'] = $message;
                    }
                    $res[$key]['message'] = $message;
                }

                if ($item['type'] == 1 && strpos($res[$key]['message'], 'http') !== false) {
                    $res[$key]['type'] = 8;
                }
            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('date_b', $date_b);
            View::assign('date_e', $date_e);
            View::assign('typeList', $typeList);
            View::assign('statusList', $statusList);
            return View::fetch('member/immessage');
        }
    }

    public function checkimMsgDetail()
    {
        $from_uid = $this->request->param('from_uid', 0, 'trim'); //发送者
        $to_uid = $this->request->param('to_uid', 0, 'trim'); //接收者
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $checkimmessageElas = ElasticsearchService::getInstance()->index('zb_check_im_message');

        if ($from_uid > 0 && $to_uid > 0) {
            $checkimmessageElas->should([["from_uid" => $from_uid,"to_uid" => $to_uid],["from_uid" => $to_uid,"to_uid" => $from_uid]]);
        }

        $es_from = ($page - 1) * $limit;
        $checkimmessageElas->page($es_from,$limit);
        $checkimmessageElas->order("created_time","desc");

        $typeList = CheckImMessageModel::TYPEMAP;
        $statusList = CheckImMessageModel::STATUSMAP;

        if ($this->request->param("isRequest") == 1) {
            $result = $checkimmessageElas->select();
            $res = $result['data'] ?? [];
            $count = $result['total'] ?? [];
            foreach ($res as $key => $item) {
                $res[$key]['oss'] = config("config.APP_URL_image");
                $res[$key]['type_mark'] = $typeList[$item['type']] ?? '';
                $res[$key]['status_mark'] = $statusList[$item['status']] ?? '';
                $res[$key]['created_time'] = date('Y-m-d H:i:s', $item['created_time']);
                $check_response = json_decode($item['check_response'], true);
                $check_response_content = $check_response['riskDescription'] ?? '';
                $api_response_content = $item['api_response'] ?? '';
                $res[$key]['check_response'] = $check_response_content;
                if (mb_strlen($check_response_content) > 10) {
                    $res[$key]['check_response_part'] = mb_substr($check_response_content, 0, 10) . "..";
                } else {
                    $res[$key]['check_response_part'] = $check_response_content;
                }

                if (mb_strlen($api_response_content) > 10) {
                    $res[$key]['api_response_part'] = mb_substr($api_response_content, 0, 10) . "..";
                } else {
                    $res[$key]['api_response_part'] = $api_response_content;
                }

                if ($item['type'] == 0) {
                    $message = $item['message'] ?? '';
                    if (mb_strlen($message) > 20) {
                        $res[$key]['message_part'] = mb_substr($message, 0, 20) . "...";
                    } else {
                        $res[$key]['message_part'] = $message;
                    }
                }

                if ($item['type'] == 1 && strpos($res[$key]['message'], 'http') !== false) {
                    $res[$key]['type'] = 8;
                }

            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('from_uid', $from_uid);
            View::assign('to_uid', $to_uid);
            return View::fetch('member/immessagedetail');
        }
    }

    //平台权限-用户房间内封禁列表
    public function userRoomBlackList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $user_id = $this->request->param('user_id', '', 'trim'); //用户id
        $room_id = $this->request->param('room_id', '', 'trim'); //房间id
        $kickout = "system_room_user_kickout_%s_%s";
        //1:执行中 2:已完成 3:已取消(已删除)
        $statusMap = ["1" => "执行中", "2" => "已完成"];
        $where = [];
        if ($user_id > 0) {
            $where[] = ['user_id', '=', $user_id];
        }

        if ($room_id > 0) {
            $where[] = ['room_id', '=', $room_id];
        }

        $where[] = ["is_delete", "=", 0]; //删除的记录不需要显示

        $redis = RedisCommon::getInstance()->getRedis();

        if ($this->request->param("isRequest") == 1) {
            $res = RoomUserBlackModel::getInstance()->getModel()->where($where)->page($page, $limit)->order("id desc")->select()->toArray();
            foreach ($res as $key => $item) {
                $res[$key]["nickname"] = MemberModel::getInstance()->getModel($item['user_id'])->where('id', $item['user_id'])->value('nickname');
                $res[$key]["room_name"] = LanguageroomModel::getInstance()->getModel($item['room_id'])->where('id', $item['room_id'])->value('room_name');

                $res[$key]["ctime"] = date('Y-m-d H:i:s', $item['ctime']);
                $blackKey = sprintf($kickout, $item['room_id'], $item['user_id']);
                if ($redis->get($blackKey)) {
                    $status = 1; //封禁执行中
                } else {
                    $status = 2; //已封禁
                }
                $res[$key]['status'] = $status;
                $res[$key]['statusinfo'] = $statusMap[$status] ?? '';
            }
            $count = RoomUserBlackModel::getInstance()->getModel()->where($where)->count();
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            return View::fetch('member/userroomblacklist');
        }

    }

    //平台权限-用户房间内封禁编辑
    public function userRoomBlackListEdit()
    {
        $room_id = $this->request->param('room_id', '', 'trim');
        $user_id = $this->request->param('user_id', '', 'trim');
        $longtime = $this->request->param('longtime', 0, 'trim');
        $reason = $this->request->param('reason', '', 'trim');
        $action = $this->request->param('action', '', 'trim');
        $id = $this->request->param('id', 0, 'trim');
        $redis = RedisCommon::getInstance()->getRedis();
        $kickout = "system_room_user_kickout_%s_%s";

        if ($action == 'del') { //这是删除操作
            $roomBlackRes = RoomUserBlackModel::getInstance()->getModel()->where("id", $id)->find();
            $user_id = $roomBlackRes["user_id"];
            $room_id = $roomBlackRes["room_id"];
            $blackKey = sprintf($kickout, $room_id, $user_id);
            if ($redis->get($blackKey)) {
                echo json_encode(["code" => 1, "msg" => "禁封的记录禁止删除"]);
                exit;
            } else {
                RoomUserBlackModel::getInstance()->getModel()->where("id", $id)->save(["is_delete" => 1]);
                Log::record('userRoomBlackListEdit:delete:操作人:' . $this->token['username']);
                echo json_encode(["code" => 0, "msg" => ""]);
                exit;
            }
            echo json_encode(["code" => 1, "msg" => "操作失败"]);
            exit;
        }

        //验证用户uid是否合法
        $memberinfo = MemberModel::getInstance()->getModel($user_id)->where("id", $user_id)->find();
        if (empty($memberinfo)) {
            echo json_encode(["code" => 1, "msg" => "用户ID不存在"]);
            exit;
        }

        $roominfo = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->find();
        if (empty($roominfo)) {
            echo json_encode(["code" => 1, "msg" => "房间ID不存在"]);
            exit;
        }

        $blackKey = sprintf($kickout, $room_id, $user_id);

        if ($action == 'unblack') { //解封
            $redis->del($blackKey);
            Log::info(sprintf("userroomblacklistedit:unblack=%s,operator=%s", $blackKey, $this->token['username']));
            echo json_encode(["code" => 0, "msg" => ""]);
            exit;
        }

        //以下是新增封禁的逻辑
        $data = ["room_id" => $room_id, "user_id" => $user_id, "longtime" => $longtime, "reason" => $reason];
        //先判断是否存在记录中
        $haveRes = RoomUserBlackModel::getInstance()->getModel()
            ->where("user_id", $user_id)
            ->where("room_id", $room_id)
            ->where("is_delete", 0)
            ->find();
        try {
            $currentTimestamp = time();
            if ($haveRes) {
                $data['utime'] = $currentTimestamp;
                RoomUserBlackModel::getInstance()->getModel()->where("id", $haveRes['id'])->save($data);
            } else {
                $data['utime'] = $currentTimestamp;
                $data['ctime'] = $currentTimestamp;
                RoomUserBlackModel::getInstance()->getModel()->insert($data);
            }

            $blackKey = sprintf($kickout, $room_id, $user_id);
            if ($redis->get($blackKey)) {
                echo json_encode(["code" => 1, "msg" => "此用户正处于封禁中"]);
                exit;
            }
            if ($longtime > 0) {
                $redis->setex($blackKey, intval($longtime), 1);
            } else {
                $redis->set($blackKey, 1); //永久封禁
            }

            $msg = [
                'roomId' => intval($room_id),
                'toUserId' => (string) ($user_id),
            ];

            $data = json_encode($msg);
            $url = config('config.socket_url_base') . 'iapi/kickout';
            $resMsg = curlData($url, $data, 'POST', 'json');
            Log::info(sprintf('userroomblacklistedit:kickout roomId=%d userId=%d data=%s resMsg=%s operator=%s', $room_id, $user_id, $data, $resMsg, $this->token['username']));
            //添加封禁的业务逻辑
            echo json_encode(["code" => 0, "msg" => ""]);
            exit;
        } catch (Throwable $e) {
            Log::info(sprintf("userroomblacklistedit:error=%s", $e->getMessage()));
            echo json_encode(["code" => 1, "msg" => "操作异常"]);
            exit;
        }
    }

    //用户审核的操作日志
    public function memberAuditLog()
    {
        if ($this->request->param('isRequest')) {
            $user_id = $this->request->param('user_id', 0, 'trim');
            $res = MemberDetailAuditLogModel::getInstance()->getModel()->where(["user_id" => $user_id])->select()->toArray();
            $returnRes = array_map(function ($v) {
                $v['update_time'] = date('Y-m-d H:i:s', $v['update_time']);
                return $v;
            }, $res);
            $data = ["msg" => '', "count" => count($returnRes), "code" => 0, "data" => $returnRes];
            echo json_encode($data);
            exit;
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('user_id', $this->request->param('user_id'));
            return View::fetch('member/memberauditlog');
        }
    }

    //举报用户列表
    public function complaintsUserList()
    {
        $from_uid = $this->request->param('from_uid', 0, 'trim');
        $status = $this->request->param('status', -1, 'trim'); // '状态：0待处理，1跟进中，2已完结',
        $date_range = $this->request->param('date_range', 0, 'trim');
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $export = $this->request->param("export", 0, 'trim');
        $where = [];
        if ($from_uid) {
            $where[] = ["from_uid", "=", $from_uid];
        }

        if ($status >= 0) {
            $where[] = ["status", "=", $status];
        }

        if (empty($date_range)) {
            $begin_date = date('Y-m-d', time());
            $end_date = date('Y-m-d', strtotime("+1days"));
            $date_range = $begin_date . " - " . $end_date;
        } else {
            $params = explode(" - ", $date_range);
            $begin_date = $params[0];
            $end_date = $params[1];
        }

        $where[] = ['create_time', '>=', strtotime($begin_date)];
        $where[] = ['create_time', '<', strtotime($end_date)];

        if ($this->request->param('isRequest')) {
            if ($export == 1) {
                $deal_func = function ($item) {
                    $item['nickname'] = MemberModel::getInstance()->getModel($item['from_uid'])->where('id', $item['from_uid'])->value('nickname');
                    $item['to_nickname'] = MemberModel::getInstance()->getModel($item['to_uid'])->where('id', $item['to_uid'])->value('nickname');
                    return $item;
                };

                $daochuDataSource = ComplaintsNewModel::getInstance()->getModel()->field("from_uid,to_uid,description,contents,status,admin_id,FROM_UNIXTIME(create_time) as create_time")->where($where);
                $headerArray = [
                    "from_uid" => "用户ID",
                    "from_nickname" => "用户昵称",
                    "to_uid" => "被举报用户ID",
                    "to_nickname" => "被举报用户昵称",
                    "description" => "举报类型",
                    "contents" => "违规说明",
                    "status" => "处理状态",
                    "admin_id" => "处理人ID",
                    "create_time" => "创建时间",
                ];
                ExportExcelService::getInstance()->exportBigDataByFn($daochuDataSource, $headerArray, $deal_func);
                exit;

            }
            $res = ComplaintsNewModel::getInstance()->getModel()->where($where)->page($page, $limit)->select()->toArray();
            $count = ComplaintsNewModel::getInstance()->getModel()->where($where)->count();
            $from_uids = array_column($res, "from_uid");
            $to_uids = array_column($res, "to_uid");
            $admin_ids = array_column($res, "admin_id");
            $uids = array_merge($from_uids, $to_uids);

            $member_list = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], 'nickname, id');
            $memberinfo = array_column($member_list, null, 'id');
            $admininfo = AdminUserModel::getInstance()->getModel()->where("id", "in", $admin_ids)->column("username", "id");
            foreach ($res as $k => $item) {
                $res[$k]['from_nickname'] = $memberinfo[$item['from_uid']]['nickname'] ?? '';
                $res[$k]['to_nickname'] = $memberinfo[$item['to_uid']]['nickname'] ?? '';
                $res[$k]['admin_username'] = $admininfo[$item['admin_id']] ?? '';
                $res[$k]['update_time'] = $item['update_time'] ? date('Y-m-d', $item['update_time']) : '';
                $res[$k]['update_time_detail'] = date('Y-m-d H:i:s', $item['update_time']);
            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
            exit;
        } else {
            View::assign('date_range', $date_range);
            View::assign('token', $this->request->param('token'));
            View::assign('user_id', $this->request->param('user_id'));
            return View::fetch('user/complaintsuserlist');
        }
    }

    //举报用户 -- 跟进状态或者设置完成
    public function complaintsUserChangeStatus()
    {
        $cid = $this->request->param('cid', 0, 'trim');
        $contents = $this->request->param('contents', '', 'trim'); // '状态：0待处理，1跟进中，2已完结',
        $complete = $this->request->param('complete', 0, 'trim'); // '设置完成,
        $admin_id = $this->token['id'] ?? 0;
        if ($complete == 1) { //设置投诉完成

            $requestParams = [
                'cid' => $cid,
                'adminId' => $admin_id,
            ];
            Log::info("complaintsuserchangestatus:complaintuserchangere:questParams" . json_encode($requestParams));
            $api_url = config('config.app_api_url') . 'api/inner/complaintUserChange';
            $res = curlData($api_url, json_encode($requestParams), 'POST');
            Log::info("complaintsuserchangestatus:complaintuserchangere:res" . $res);

        } else { //更新举报跟进状态

            $requestParams = [
                'cid' => $cid,
                'content' => $contents,
                'adminId' => $admin_id,
            ];
            Log::info("complaintsuserchangestatus:complaintuserfollow:requestParams" . json_encode($requestParams));
            $api_url = config('config.app_api_url') . 'api/inner/complaintUserFollow';
            $res = curlData($api_url, json_encode($requestParams), 'POST');
            Log::info("complaintsuserchangestatus:complaintuserfollow:res" . $res);
        }

        $parseRes = json_decode($res, true);
        if (isset($parseRes['code']) && $parseRes['code'] == 200) {
            echo json_encode(["code" => 0, "msg" => '操作成功']);
        } else {
            echo json_encode(["code" => -1, "msg" => $parseRes['desc'] ?? '操作异常']);
        }
    }

    //举报用户  -- 查看详情
    public function complaintsUserDetail()
    {
        $id = $this->request->param('id', 0, 'trim');
        if ($this->request->param('isRequest')) {
            $res = ComplaintsNewFollowModel::getInstance()->getModel()->where("cid", "=", $id)->order("id desc ")->select()->toArray();
            $admin_ids = array_column($res, "admin_id");
            $admininfo = AdminUserModel::getInstance()->getModel()->where("id", "in", $admin_ids)->column("username", "id");
            foreach ($res as $k => $item) {
                $res[$k]['admin_username'] = $admininfo[$item['admin_id']] ?? '';
                $res[$k]['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
            }
            $data = ["msg" => '', "count" => count($res), "code" => 0, "data" => $res];
            echo json_encode($data);
            exit;
        } else {
            $detail = ComplaintsNewModel::getInstance()->getModel()->where("id", "=", $id)->find();
            $uids = [$detail['from_uid'], $detail['to_uid']];
            $admin_ids = [$detail['admin_id']];

            $member_list = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], "nickname,id");
            $memberinfo = array_column($member_list, null, 'id');
            $detail['from_nickname'] = $memberinfo[$detail['from_uid']]['nickname'] ?? '';
            $detail['to_nickname'] = $memberinfo[$detail['to_uid']]['nickname'] ?? '';
            $images = json_decode($detail['images'], true) ?: [];
            $imagesList = [];
            foreach ($images as $k => $img) {
                $imagesList[] = config("config.APP_URL_image") . $img;
            }
            if ($detail['videos']) {
                $videos = json_decode($detail['videos'], true);
                if ($videos) {
                    $detail['videos'] = config("config.APP_URL_image") . $videos[0] ?: '';
                } else {
                    $detail['videos'] = '';
                }
            }
            $detail['create_time'] = date('Y-m-d H:i:s', $detail['create_time']);
            View::assign('token', $this->request->param('token'));
            View::assign('detail', $detail);
            View::assign('imagesList', $imagesList);
            View::assign('user_id', $this->request->param('user_id'));
            View::assign('id', $id);
            return View::fetch('user/complaintsuserdetail');
        }
    }

    /**
     * 房间信息是否开启人工审核
     */
    public function roominfoPersonCheck()
    {
        $type = $this->request->param('type', 0, 'trim');
        $is_open = $this->request->param('is_open', '', 'trim'); // '状态：0待处理，1跟进中，2已完结',
        try {
            RoomcheckSwitchModel::getInstance()->getModel()->where(['type' => $type])->update(['is_open' => $is_open]);
            echo json_encode(["code" => 0, "msg" => '操作成功']);
        } catch (Throwable $e) {
            Log::info("roominfopersoncheck:error:" . $e->getMessage());
            echo json_encode(["code" => -1, "msg" => '操作失败']);
        }
    }

    /**
     * @return mixed
     * 用户或者房间审核
     */
    public function userroomcheckList()
    {
        $user_id = (int) Request::param('user_id', 0); //用户ID
        $room_id = (int) Request::param('room_id', 0); //房间ID
        $status = (int) Request::param('status', 0); //状态
        $page = $this->request->param('page', 1);
        $date_b = $this->request->param('date_b', '');
        $date_e = $this->request->param('date_e', '');
        $date_type = $this->request->param('date_type', 1);
        $export = $this->request->param('export', '');
        $offset = ($page - 1) * $page;

        $where = [];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }

        if ($room_id > 0) {
            $where[] = ['room_id', '=', $room_id];
        }
        $where[] = ['status', '=', $status];

        if (!empty($date_b) && !empty($date_e)) {
            if ($date_type == 1) {
                //审核日期
                $where[] = ['create_time', ">=", strtotime($date_b)];
                $where[] = ['create_time', "<", strtotime($date_e)];
            }

            if ($date_type == 2) {
                $where[] = ['update_time', ">=", strtotime($date_b)];
                $where[] = ['update_time', "<", strtotime($date_e)];
            }

        }

        $callfunc = function ($items) {
            $adminids = [];
            foreach ($items as $item) {
                if (isset($item['admin_user_name']) && is_numeric($item['admin_user_name'])) {
                    $adminids[] = $item['admin_user_name'];
                }
            }
            $adminuserList = AdminUserModel::getInstance()->getModel()->where([["id", "in", $adminids]])->field("id,username")->select()->toArray();
            $adminuserListById = array_column($adminuserList, null, "id");
            foreach ($items as &$formatItem) {
                if (isset($formatItem['admin_user_name']) && is_numeric($formatItem['admin_user_name'])) {
                    $formatItem['admin_user_name'] = $adminuserListById[$formatItem['admin_user_name']]['username'] ?? '';
                }
            }

            return $items;
        };

        $typeMap = AdminCommonConfig::MEMBER_ACTION;
        $statusMap = AdminCommonConfig::STATUS_MAP;

        if ($this->request->param("isRequest") == 1) {

            if ($export == 1) {
                $field = "user_id,room_id,content,
                          case status when 0 then '待审核'
                            when 1 then '审核通过'
                            when 2 then '审核拒绝' else '' end as status,
                          case action when '用户头像' then '用户头像'
                            when 'nickname' then '用户昵称'
                            when 'intro' then '用户信息'
                            when 'wall' then '用户墙'
                            when 'voice' then  '用户语音'
                            when 'roomName' then '房间名称'
                            when 'roomWelcomes' then '房间欢迎语'
                            when 'roomDesc' then '房间公告' else '' end as action,
                            FROM_UNIXTIME(create_time) as create_time,
                            FROM_UNIXTIME(update_time) as update_time,
                            admin_user_name";
                $daochuDataSource = MemberDetailAuditModel::getInstance()->getModel()->field($field)
                    ->where($where);
                $headerArray = [
                    "user_id" => "用户ID",
                    "room_id" => "房间ID",
                    "content" => "内容",
                    "status" => "状态",
                    "action" => "类型",
                    "create_time" => "提审时间",
                    "update_time" => "审核时间",
                    "admin_user_name" => "处理人ID",
                ];
                ExportExcelService::getInstance()->dataExpormetCsvByFormat($daochuDataSource, $headerArray, $callfunc);
                exit;
            }

            //统计用户条数
            $count = MemberDetailAuditModel::getInstance()->getModel()->where($where)->count();
            $data = [];
            $data = MemberDetailAuditModel::getInstance()->getModel()->where($where)->limit($offset, self::LIMIT)->order('update_time desc,create_time desc')->select()->toArray();
            $roomids = array_column($data, "room_id");
            $roomList = LanguageroomModel::getInstance()->getWhereAllData([["id", "in", $roomids]], "id,room_name,guild_id");

            $uids = array_column($data, "user_id");

            $memberList = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], "id,lv_dengji");

            $member_map = array_column($memberList, null, 'id');

            $roomTypeList = array_column($roomList, 'guild_id', 'id');
            foreach ($data as $key => &$item) {
                //处理内容
                if (in_array($item['action'], ['avatar', 'wall'])) {
                    $imgs = explode(',', $item['content']);
                    $item['content'] = array_map(function ($img) {
                        if (strpos($img, 'http') === false) {
                            return config('config.APP_URL_image') . $img;
                        } else {
                            return $img;
                        }
                    }, $imgs);
                } elseif ($item['action'] == 'voice') {
                    $voice_content = json_decode($item['content'], true);
                    $item['content'] = config('config.APP_URL_image') . $voice_content['voiceIntro'];
                }
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                $item['lv_dengji'] = $member_map[$item['user_id']]['lv_dengji'] ?? '';
                $item['update_time'] = $item['update_time'] == 0 ? '' : date('Y-m-d H:i:s', $item['update_time']);
                //房间类型
                $item['room_type'] = ($item['room_id'] == 0) ? '' : (isset($roomTypeList[$item['room_id']]) && $roomTypeList[$item['room_id']] > 0 ? '工会房' : '个人房');
                if ($item['status'] == 0) {
                    $item['admin_user_name'] = "";
                }

                $item['action_info'] = $typeMap[$item['action']] ?? '';
                $item['status_info'] = $statusMap[$item['status']] ?? '';
            }

            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $callfunc($data)];
            echo json_encode($data);
        } else {
            $personroom_isopen = RoomcheckSwitchModel::getInstance()->getModel()->where('type', 'person_room')->value('is_open');
            $guildroom_isopen = RoomcheckSwitchModel::getInstance()->getModel()->where('type', 'guild_room')->value('is_open');
            View::assign('user_role_menu', $this->user_role_menu);
            View::assign('token', $this->request->param('token'));
            View::assign('date_type', $date_type);
            View::assign('personroom_isopen', $personroom_isopen);
            View::assign('guildroom_isopen', $guildroom_isopen);
            return View::fetch('member/infocheck');
        }

    }

}

<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\AdminCommonConfig;
use app\admin\model\AdminUserModel;
use app\admin\model\BlackDataModel;
use app\admin\model\BlackLogModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\MemberModel;
use app\admin\service\CurlApiService;
use app\admin\service\ExportExcelService;
use app\admin\service\HandleRedisService;
use app\admin\service\MemberBlackService;
use app\admin\service\MemberGuildService;
use app\admin\service\MemberService;
use app\common\FormaterExportDataCommon;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class MemberBlackController extends AdminBaseController
{
    private $key = 'member_black_';

    /**查出封号人信息
     * @param token token值
     * @param page page值
     * @param pagenum 条数值
     * @return mixed
     */
    public function blackList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = [];
        $user_id = Request::param('user_id'); //用户id
        $status = Request::param('status', 1); //封禁状态
        $blackinfo = Request::param('blackinfo'); //封禁状态
        $demo = $this->request->param('demo', $this->default_date);
        list($start_time, $end_time) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu', 0);
        $reason = $this->request->param('reason', '');

        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }

        if ($blackinfo) {
            $where[] = ['blackinfo', '=', $blackinfo];
        }

        if ($demo) {
            $where[] = ['blacks_time', '>=', strtotime($start_time)];
            $where[] = ['blacks_time', '<', strtotime($end_time)];
        }

        if ($status == 1) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '<>', 1];
        }

        if(trim($reason)){
            $where[] = ['reason', 'like', "%$reason%"];
        }

        $count = BlackDataModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = BlackDataModel::getInstance()->getList($where, $page, $pagenum);
            if ($daochu) {
                $columns = [
                    'user_id' => '用户ID',
                    'lv_dengji' => '用户等级',
                    'guild_nickname' => '所属公会',
                    'info' => '封禁天数',
                    'type_desc' => '封禁类型',
                    'blackinfo' => '封禁参数',
                    'status' => '封禁状态',
                    'create_time' => '创建时间',
                    'update_time' => '封禁时间',
                    'reason' => '封禁理由',
                    'admin_user' => '操作人员',
                ];
                ExportExcelService::getInstance()->dataExpormetCsvByFormat(BlackDataModel::getInstance()->getModel()->where($where)->order('update_time', 'desc'),
                    $columns,[FormaterExportDataCommon::getInstance(),"formatterBlackDataList"]);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('封禁列表获取成功:操作人:' . $this->token['username'], 'blackList');
        View::assign('page', $page_array);
        View::assign('data', FormaterExportDataCommon::getInstance()->formatterBlackDataList($data));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $user_id);
        View::assign('status', $status);
        View::assign('demo', $demo);
        View::assign('blackinfo', $blackinfo);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('reason',$reason);
        return View::fetch('black/index');
    }

    //用户解封
    public function memberUnsealings()
    {
        $id = $this->request->param('id');
        $desc = $this->request->param('desc', '管理员操作');
        $type = $this->request->param('type');
        $blackinfo = $this->request->param('blackinfo');

        if (!$blackinfo || !$desc || !$type) {
            echo json_encode(['code' => 500, 'msg' => '参数错误']);
            die;
        }

        $where = [];
        if ($blackinfo) {
            $where['blackinfo'] = $blackinfo;
        }
        $where['status'] = 1;
        $where['type'] = $type;

        $data = [];
        $data['time'] = 0;
        $data['status'] = 0;
        $data['admin_id'] = $this->token['id'];
        $data['update_time'] = time();
        $data['reason'] = $desc;
        $data['end_time'] = 0;

        $forbid_data = $data;
        $forbid_data['type'] = $type;
        $forbid_data['forbid_type'] = 1;
        $forbid_data['user_id'] = $blackinfo;
        $forbid_data['blackinfo'] = $blackinfo;
        $forbid_data['create_time'] = time();

        $ok = Db::transaction(function () use ($where, $data, $forbid_data) {
            BlackDataModel::getInstance()->updateBlackData($where, $data);
            return BlackLogModel::getInstance()->getModel()->insert($forbid_data);
        });
        if ($ok) {
            $redis = $this->getRedis();
            $redis->del($this->key . $blackinfo);
            HandleRedisService::getInstance()->delUserCache($blackinfo);
            //解封通知API
            CurlApiService::getInstance()->blockUserNotice($blackinfo, 0);
            Log::record('用户解封成功:操作人:' . $this->token['username'] . '@条件:' . json_encode($where) . ':内容:' . json_encode($data), 'memberUnsealings');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }
        Log::record('用户解封失败:操作人:' . $this->token['username'] . '@条件:' . json_encode($where) . ':内容:' . json_encode($data), 'memberUnsealings');
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }

    //用户封号
    public function memberBlacks()
    {
        $uid = $this->request->param('id');
        $time = (int) $this->request->param('time');
        $desc = $this->request->param('desc');

        if (!$uid || !$time || !$desc) {
            echo json_encode(['code' => 200, 'msg' => '参数错误']);
            die;
        }
        try {
            MemberBlackService::getInstance()->memberBlacks($uid, $time, $desc, $this->token);
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } catch (\Throwable $e) {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    /**
     * @添加用户黑名单
     * @dongbozhao
     * @2020-12-14 15:06
     */
    public function deviceIpAdd()
    {
        try {
            $uid = Request::param('uid');
            $type = Request::param('type');
            $reason = Request::param('reason');
            $time = Request::param('time');
            $blackinfo = trim(Request::param('blackinfo'));

            if ($uid && empty($blackinfo)) {
                Log::debug(sprintf('>>>>>封禁用户开始>>>>>'));
                MemberBlackService::getInstance()->deviceIpAdd($uid, $type, $reason, $time, $this->token);
            } else {
                Log::debug(sprintf('>>>>>封禁设备开始>>>>>'));
                MemberBlackService::getInstance()->memberBlacksAdd($type, $reason, $time, $blackinfo, $this->token);
            }

            Log::record('用户封号成功:操作人:' . $this->token['username'] . ':内容:' . json_encode($uid), 'memberBlacks');
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } catch (\Exception $e) {
            Log::record('用户封号失败:操作人:' . $this->token['username'] . ':内容:' . json_encode($uid), 'memberBlacks');
            $msg = empty($e->getMessage()) ? '操作失败' : $e->getMessage();
            echo json_encode(['code' => 500, 'msg' => $msg]);die;
        }
    }

    /**
     * @return mixed
     * @用户ip黑名单列表
     * @dongbozhao
     * @2020-12-14 15:07
     */
    public function deviceIpList()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $blackinfo = $this->request->param('blackinfo');
        $status = Request::param('status');
        $status = $status ? $status : 1;
        if ($status == 1) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '<>', 1];
        }
        if ($blackinfo) {
            $where[] = ['blackinfo', '=', $blackinfo];
        }
        $where[] = ['type', '=', 1];
        $count = BlackDataModel::getInstance()->getModel()->where($where)->count();
        $data = BlackDataModel::getInstance()->getModel()->where($where)->order('create_time desc')->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            if ($v['create_time']) {
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            //1:ip 2:设备 3:身份证号码
            if ($v['type'] == 1) {
                $data[$k]['types'] = 'ip';
            }
            if ($v['status'] == 1) {
                $data[$k]['statuss'] = '封禁';
            } else {
                $data[$k]['statuss'] = '解禁';
            }
            $data[$k]['end_time'] = "永久";
            if ($v['admin_id']) {
                $data[$k]['admin'] = AdminUserModel::getInstance()->getModel()->where('id', $v['admin_id'])->value('username');
            } else {
                $data[$k]['admin'] = '系统';
            }
            $data[$k]['admin_user'] = AdminUserModel::getInstance()->getModel()->where(array("id" => $v['admin_id']))->value('username');
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('data', $data);
        View::assign('status', $status);
        View::assign('blackinfo', $blackinfo);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/deviceIpList');
    }

    /**
     * @return mixed
     * @用户设备黑名单列表
     * @dongbozhao
     * @2020-12-14 15:07
     */
    public function deviceDeviceidList()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = $this->request->param('uid');
        $ip = $this->request->param('ip');
        $status = Request::param('status');
        $type = Request::param('type');
        $blackinfo = Request::param('blackinfo');
        if ($blackinfo) {
            $where[] = ['blackinfo', '=', $blackinfo];
        }
        $status = $status ? $status : 1;
        if ($status == 1) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '<>', 1];
        }
        if ($uid) {
            $where[] = ['user_id', '=', $uid];
        }
        if ($ip) {
            $where[] = ['blackinfo', '=', $ip];
        }
        if ($type) {
            $where[] = ['type', '=', $type];
        } else {
            $where[] = ['type', 'in', '2,5'];
        }
        $count = BlackDataModel::getInstance()->getModel()->where($where)->count();
        $data = BlackDataModel::getInstance()->getModel()->where($where)->order('create_time desc')->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            if ($v['create_time']) {
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            $data[$k]['types'] = '设备';
            if ($v['status'] == 1) {
                $data[$k]['statuss'] = '封禁';
            } else {
                $data[$k]['statuss'] = '解禁';
            }

            if ($v['type'] == 2) {
                $data[$k]['type_desc'] = '设备ID';
            } elseif ($v['type'] == 5) {
                $data[$k]['type_desc'] = '设备唯一标识';
            }

            $data[$k]['end_time'] = "永久";
            if ($v['admin_id']) {
                $data[$k]['admin_user'] = AdminUserModel::getInstance()->getModel()->where('id', $v['admin_id'])->value('username');
            } else {
                $data[$k]['admin_user'] = '系统';
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('data', $data);
        View::assign('status', $status);
        View::assign('type', $type);
        View::assign('blackinfo', $blackinfo);
        View::assign('uid', $uid);
        View::assign('ip', $ip);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/deviceDeviceidList');
    }

    /**
     * @return mixed
     * @用户身份证黑名单列表
     * @dongbozhao
     * @2020-12-14 15:07
     */
    public function deviceIdcardList()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $blackinfo = $this->request->param('blackinfo');
        $status = Request::param('status', 1);
        if ($status == 1) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '<>', 1];
        }
        if ($blackinfo) {
            $where[] = ['blackinfo', '=', $blackinfo];
        }
        $where[] = ['type', '=', 3];
        $count = BlackDataModel::getInstance()->getModel()->where($where)->count();
        $data = BlackDataModel::getInstance()->getModel()->where($where)->order('create_time desc')->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            if ($v['create_time']) {
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
            //1:ip 2:设备 3:身份证号码
            if ($v['type'] == 1) {
                $data[$k]['types'] = 'ip';
            }
            if ($v['status'] == 1) {
                $data[$k]['statuss'] = '封禁';
            } else {
                $data[$k]['statuss'] = '解禁';
            }
            $data[$k]['end_time'] = "永久";
            if ($v['admin_id']) {
                $data[$k]['admin_user'] = AdminUserModel::getInstance()->getModel()->where('id', $v['admin_id'])->value('username');
            } else {
                $data[$k]['admin_user'] = '系统';
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('data', $data);
        View::assign('status', $status);
        View::assign('blackinfo', $blackinfo);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('member/deviceIdcardList');
    }

    /**
     * @更新设备状态
     * @dongbozhao
     * @2020-12-15 10:55
     */
    public function deviceIpSave()
    {
        $id = $this->request->param('id');

        $is = BlackDataModel::getInstance()->getModel()->where('id', $id)->save(['status' => 0]);

        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']); //php编译join
        } else {
            echo json_encode(['code' => 200, 'msg' => '修改成功']); //php编译join
        }
    }

}

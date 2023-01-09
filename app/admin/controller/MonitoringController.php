<?php
namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\MemberModel;
use app\admin\model\MonitoringModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class MonitoringController extends AdminBaseController
{

    /*
     * 监控列表
     */
    public function monitoringList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $id = Request::param('user_id');
        $where = [];
        if ($id) {
            $where[] = ['user_id', '=', $id];
        }

        $count = MonitoringModel::getInstance()->getModel()->where($where)->count();
        $data = MonitoringModel::getInstance()->monitoringList($where, $page, $pagenum);
        foreach ($data as $key => $val) {
            $field = 'nickname';
            $nickname = MemberModel::getInstance()->getOneById($val['user_id'], $field);
            if ($nickname == "") {
                $data[$key]['nickname'] = '用户_' . $val['user_id'];
            } else {
                $data[$key]['nickname'] = $nickname['nickname'];
            }
            $data[$key]['pwd'] = $data[$key]['pwd'] == '' ? '未知' : $data[$key]['pwd'];
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('公告列表:操作人:' . $this->token['username'], 'noticeList');
        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('uid', $id);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('monitoring/index');
    }

    public function delMonitoring()
    {
        $id = Request::param('user_id');
        if (!$id) {
            echo json_encode(['code' => 500, 'msg' => '参数错误']);die;
        }
        $user_id = MonitoringModel::getInstance()->getModel()->where('user_id', $id)->select()->toArray();
        if (count($user_id) == 0) {
            echo json_encode(['code' => 500, 'msg' => '参数错误']);die;
        }
        $is = MonitoringModel::getInstance()->getModel()->where('user_id', $id)->delete();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '参数错误']);die;
        }
    }

    public function exitMonitoring()
    {
        $id = Request::param('user_id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $field = 'id,pretty_id';
        $user = MemberModel::getInstance()->getWhereInfo(array('id' => $id), $field); //查询用户是否存在
        if (empty($user)) {
            $pretty = MemberModel::getInstance()->prettyUser($id);
            if (empty($pretty)) {
                echo $this->return_json(\constant\CodeConstant::CODE_用户不存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户不存在]);
                die;
            } else {
                $id = $pretty['id'];
            }
        }
        $where = [['user_id', '=', $id], ['status', '<>', 0]];
        $search = MonitoringModel::getInstance()->searchUser($where);
        if (empty($search)) {
            echo $this->return_json(\constant\CodeConstant::CODE_此用户未申请解锁, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此用户未申请解锁]);
            die;
        } else {
            $data = [
                'monitoring_pwd' => "",
                'parents_pwd' => "",
                'monitoring_status' => 0,
                'parents_status' => 0,
                'monitoring_time' => "",
                'lock_time' => "",
                'status' => "2",
            ];
            $res = MonitoringModel::getInstance()->monitoringEdit($where, $data);
            $redis = $this->getRedis();
            $clearindex = $redis->del("monitoring_" . $id);
            if ($res) {
                Log::record('修改用户监控数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitMonitoring');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            } else {
                Log::record('修改用户监控数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitMonitoring');
                echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
                die;
            }
        }
    }

    public function noLock()
    {
        $id = Request::param('user_id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where = ['user_id' => $id];
        $search = MonitoringModel::getInstance()->searchUser($where);
        if (empty($search)) {
            echo $this->return_json(\constant\CodeConstant::CODE_此用户未开启过监控模式, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此用户未开启过监控模式]);
            die;
        }
        $data = ['status' => 0];
        $ress = MonitoringModel::getInstance()->monitoringEdit($where, $data);
        $redis = $this->getRedis();
        $clearindex = $redis->del("monitoring_" . $id);
        if ($ress) {
            Log::record('拒绝修改监控状态成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'noLock');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('拒绝修改监控状态失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'noLock');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }
}

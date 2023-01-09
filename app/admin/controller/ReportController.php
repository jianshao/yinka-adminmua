<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\MemberReportModel;
use app\admin\service\ReportService;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ReportController extends AdminBaseController
{
    //配置
    public function reportAudit()
    {
        $page = Request::param('page', 1);
        $demo = $this->request->param('demo', $this->default_date);
        list($start_date, $end_date) = getBetweenDate($demo);

        $report_tag_list = ReportService::getInstance()->getReportTagList();

        //获取举报数据
        $report_info = ReportService::getInstance()->getReportInfo($this->token['id']);
        if ($report_info && isset($report_info['create_time'])) {
            $start_date = date('Y-m-d', $report_info['create_time']);
            $end_date = date('Y-m-d', $report_info['create_time'] + 24 * 60 * 60);
        }

        $unaudit_count = MemberReportModel::getInstance()->getModel()->where('status', ReportService::REPORT_STATUS_未审核)->where('audio_status', '<>', 5)->count();
        $audit_count_now = MemberReportModel::getInstance()->getModel()->where('status', ReportService::REPORT_STATUS_审核中)->where('admin_id', $this->token['id'])->count();
        $audit_count = MemberReportModel::getInstance()->getModel()->whereIn('status', [ReportService::REPORT_STATUS_审核完成并处罚, ReportService::REPORT_STATUS_未处罚])->where('admin_id', $this->token['id'])->count();
        $audit_info = ['unaudit_count' => ($unaudit_count + $audit_count_now), 'audit_count' => $audit_count];

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('admin_info', $this->token);
        View::assign('report_info', $report_info);
        View::assign('audit_info', $audit_info);
        View::assign('start_date', $start_date);
        View::assign('end_date', $end_date);
        View::assign('report_tag_list', $report_tag_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('report/index');
    }

    public function reportStartAudit()
    {
        try {
            $admin_id = Request::param('admin_id', 0);

            ReportService::getInstance()->startReport($admin_id);
            return rjson([], 200, '开始审核');
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function reportPunish()
    {
        try {
            $params = Request::param();
            if (!isset($params['punish_level']) || empty($params['punish_level']) || !isset($params['punish_id']) || empty($params['punish_id'])) {
                throw new \Exception('请选择处罚等级', 500);
            }

            if (!isset($params['report_id']) || empty($params['report_id']) || !isset($params['reported_content']) || empty($params['reported_content']) || !isset($params['report_content']) || empty($params['report_content']) || !isset($params['reason']) || empty($params['reason'])) {
                throw new \Exception('参数错误', 500);
            }

            ReportService::getInstance()->punish($params, $this->token);
            return rjson([], 200, '审核成功');
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function execPunish()
    {
        try {
            $params = Request::param();
            if (!isset($params['report_id']) || empty($params['report_id']) || !isset($params['report_tag_id']) || empty($params['report_tag_id'])) {
                throw new \Exception('参数错误', 500);
            }

            ReportService::getInstance()->execPunish($params, $this->token);
            return rjson([], 200, '审核成功');
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function noPunish()
    {
        try {
            $params = Request::param();
            if (!isset($params['report_id']) || empty($params['report_id'])) {
                throw new \Exception('参数错误', 500);
            }

            ReportService::getInstance()->noPunish($params, $this->token);
            return rjson([], 200, '审核成功');
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}
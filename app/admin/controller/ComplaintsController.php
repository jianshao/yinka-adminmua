<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ComplaintsModel;
use app\admin\model\FeedbackModel;
use app\admin\model\LoginFeedbackModel;
use app\admin\model\MemberModel;
use app\admin\service\ComplaintsService;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class ComplaintsController extends AdminBaseController
{
    /*举报用户列表
     * @param string $token token值
     * @param string $page  分页
     * @param int $pagenum  条数
     * @return mixed
     */
    public function complaintsList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id'); //用户id
        //统计用户条数
        $where = [];
        if ($user_id) {
            $where = ['id' => $user_id];
        }
        $count = ComplaintsModel::getInstance()->getModel()->where($where)->count(); //数据总条数
        $data = [];
        if ($count > 0) {
            $data = ComplaintsService::getInstance()->getComplaintsListPage($where, $page, $pagenum);
            foreach ($data as $key => $vo) {
                $field = 'nickname';
                $where['id'] = $data[$key]['to_uid'];
                if (MemberModel::getInstance()->getOneById($vo['to_uid'], $field) == null) {
                    $data[$key]['to_uid_nickname'] = '用户_' . $vo['to_uid'];
                } else {
                    $data[$key]['to_uid_nickname'] = MemberModel::getInstance()->getOneById($vo['to_uid'], $field)->toarray()['nickname'];
                }
                if (MemberModel::getInstance()->getOneById($vo['user_id'], $field) == null) {
                    $data[$key]['user_nickname'] = '用户_' . $vo['user_id'];
                } else {
                    $data[$key]['user_nickname'] = MemberModel::getInstance()->getOneById($vo['user_id'], $field)->toarray()['nickname'];
                }
                $data[$key]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('举报用户列表:操作人:' . $this->token['username'], 'complaintsList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('complaints/index');
    }

    /*反馈用户列表
     * @param string $token token值
     * @param string $page  分页
     * @param int $pagenum  条数
     * @return mixed
     */
    public function feedbackList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id'); //用户id
        //统计用户条数
        $where = [];
        $where[] = ['content', 'not like', '私聊-房间%'];
        if ($user_id) {
            $where[] = ['id', '=', $user_id];
        }
        $count = FeedbackModel::getInstance()->getModel()->where($where)->count(); //数据总条数
        $data = [];
        if ($count > 0) {
            $data = FeedbackModel::getInstance()->getList($where, '*', array($page, $pagenum));
            foreach ($data as $k => $v) {
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('反馈用户列表:操作人:' . $this->token['username'], 'feedbackList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('complaints/feedback');
    }

    //用户登录反馈反馈
    public function loginFeedbackList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 30);
        $phone = $this->request->param('phone', '', 'trim');
        $date_b = $this->request->param('date_b', date('Y-m-d', strtotime("-30days")), 'trim');
        $date_e = $this->request->param('date_e', date('Y-m-d', strtotime("+1days")), 'trim');
        //统计用户条数
        $where = [];
        if ($date_b && $date_e) {
            $where[] = ["addtime", ">=", $date_b];
            $where[] = ["addtime", "<", $date_e];
        }

        if ($phone) {
            $where[] = ["phone", "=", $phone];
        }

        if ($this->request->param("isRequest") == 1) {
            $res = LoginFeedbackModel::getInstance()->getModel()->where($where)->page($page, $limit)->order("id desc")->select()->toArray();
            $count = LoginFeedbackModel::getInstance()->getModel()->where($where)->count();
            foreach ($res as $key => $item) {
                $record = ""; //跟进记录
                $problem = $item['problem'] ?? '';
                $readinfo = json_decode($item['readinfo'], true);
                if ($readinfo) {
                    foreach ($readinfo as $info) {
                        $record .= "时间: " . $info['create_time'] . "<br>";
                        $record .= "操作人: " . $info['operator'] . "<br>";
                        $record .= "内容: " . $info['readme'] . "<hr><br>";
                    }
                }
                $maxshow = 30;
                if (mb_strlen($problem) > $maxshow) {
                    $res[$key]['problem_part'] = mb_substr($problem, 0, $maxshow) . "..";
                } else {
                    $res[$key]['problem_part'] = $problem;
                }

                $res[$key]['record'] = $record;

                if (mb_strlen($record) > $maxshow) {
                    $res[$key]['record_part'] = mb_substr($record, 0, $maxshow) . "..";
                } else {
                    $res[$key]['record_part'] = $record;
                }

            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('date_b', $date_b);
            View::assign('date_e', $date_e);
            return View::fetch('complaints/loginfeedback');
        }
    }

    public function loginFeedbackUpdate()
    {
        $readme = $this->request->param('readme', '', 'trim');
        $id = $this->request->param('id', 0, 'trim');
        try {
            $content = LoginFeedbackModel::getInstance()->getModel()->where("id", $id)->value("readinfo");
            $parsedata = json_decode($content, true);
            $currentDate = date('Y-m-d H:i:s');
            $operator = $this->token['username'] ?? '';
            if ($readme) {
                if (empty($parsedata)) {
                    $parsedata = [];
                }
                array_unshift($parsedata, ["readme" => $readme, "create_time" => $currentDate, "operator" => $operator]);
                LoginFeedbackModel::getInstance()->getModel()->where("id", $id)->save(["readinfo" => json_encode($parsedata)]);
                echo 1;
                exit;
            }
        } catch (Throwable $e) {
            dd($e->getMessage());
            Log::error("loginFeedbackUpdate:error" . $e->getMessage());
            echo 0;
            exit;
        }

    }

}

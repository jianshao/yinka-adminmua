<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\ApiUrlConfig;
use app\admin\model\ForumModel;
use app\admin\model\ForumReplyModel;
use app\admin\model\ForumTopicModel;
use app\admin\model\MemberModel;
use app\admin\service\ApiService;
use app\admin\service\ForumReplyService;
use app\admin\service\ForumService;
use app\exceptions\ApiExceptionHandle;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class ForumController extends AdminBaseController
{
    /*动态列表
     * @param string $token token值
     * @param string $page  分页
     * @param int $pagenum  条数
     * @return mixed
     */
    public function getForumList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $uid = (int) $this->request->param('uid');
        $forum_id = (int) $this->request->param('forum_id');
        $where = [];

        if ($forum_id) {
            $where[] = ['id', '=', $forum_id];
        } else {
            $where[] = ['createtime', '>=', strtotime($start)];
            $where[] = ['createtime', '<', strtotime($end)];
        }

        $where_cal = $where;
        $where[] = ['forum_status', 'not in', '3,4'];

        if ($uid) {
            $where[] = ['forum_uid', '=', $uid];
            $where_cal[] = ['forum_uid', '=', $uid];
        }
        $count = ForumService::getInstance()->getForumCountNum($where);
        //发帖子统计数量
        $forum_count = ForumModel::getInstance()->getModel()->field('count(0) count,forum_status status')->where($where_cal)->group('forum_status')->select()->toArray();

        $total_forum = 0;
        $total_users = 0;
        $agree_forum = 0;
        if ($forum_count) {
            $forum_res = array_column($forum_count, 'count', 'status');
            //发帖子数
            $total_forum = array_sum(array_values($forum_res));
            //正常帖子数
            $agree_forum = $forum_res[1] ?? 0;
        }

        $users = ForumModel::getInstance()->getModel()->field('distinct(forum_uid)')->where($where_cal)->select()->toArray();
        if ($users) {
            $total_users = count($users);
        }

        $list = ForumService::getInstance()->getList($page, $pagenum, $where);
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $userinfo = MemberModel::getInstance()->getOneById($value['forum_uid'], 'nickname,lv_dengji')->toArray();
                $list[$key]['nickname'] = $userinfo['nickname'];
                $list[$key]['lv_dengji'] = $userinfo['lv_dengji'];
            }
        }
        $topic_map = ForumTopicModel::getInstance()->getModel()->column('topic_name', 'id');
        Log::record('动态列表:操作人:' . $this->token['username'], 'getForumList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('list', $list);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('topic_map', $topic_map);
        View::assign('total_forum', $total_forum);
        View::assign('agree_forum', $agree_forum);
        View::assign('total_users', $total_users);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('forum/index');
    }

    /*删除动态操作
     * @param string $token   token值
     * @param int $forum_id   删除动态id
     * @return mixed
     */
    public function delForum()
    {
        try {
            $forum_id = Request::param('id');
            if (!$forum_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_动态ID不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_动态ID不能为空]);
                die;
            }

            // $res = ForumModel::getInstance()->getOneById([['id', '=', $forum_id], ['forum_status', '<>', 4]]);
            // if ($res) {
            //     $where['id'] = $forum_id;
            //     $data = [
            //         "forum_status" => 4,
            //         "forum_deluid" => $this->token['id'],
            //         "forum_deltime" => time(),
            //     ];
            //     $ok = ForumModel::getInstance()->setForum($where, $data);
            // }

            $params = [
                'token' => $this->token['admin_token'],
                'forumId' => $forum_id,
                'operatorId' => $this->token['id'],
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$del_forum, $params);

            Log::record('动态删除成功:操作人:' . $this->token['username'] . '@' . json_encode($forum_id), 'delForum');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());
            die;
        } catch (Throwable $th) {
            Log::record('动态删除失败:操作人:' . $this->token['username'] . '@' . json_encode($forum_id), 'delForum');
            echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除失败]);
            die;
        }
    }

    /*
     * 帖子评论列表
     */
    public function replyList()
    {
        $pagenum = 999999;
        $page = !empty($this->request->param('page')) ? ($this->request->param('page') - 1) * $pagenum : 0;
        $forum_id = Request::param('forum_id');
        if (!$forum_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }

        $list = ForumReplyService::getInstance()->getList($forum_id, $page, $pagenum);
        $count = 0;
        if (!empty($list)) {
//            foreach ($list as $key => $value) {
            //                $field = "nickname";
            //                $list[$key]['nickname'] = MemberModel::getInstance()->getOneById($value['reply_uid'], $field)->toarray()['nickname'];
            //            }
            $count = ForumReplyModel::getInstance()->getModel()->where([['reply_status', '<>', 3], ['forum_id', '=', $forum_id]])->count();
        }
        Log::record('动态评论列表:操作人:' . $this->token['username'], 'replyList');
        $data = [];
        $data['list'] = $list;
//        $data['limit']['page'] = $master_page;
        //        $data['limit']['total_page'] = ceil($count / $pagenum);
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /*评论删除操作
     * @return mixed
     */
    public function delReply()
    {
        try {
            $reply_id = Request::param('reply_id');
            if (!$reply_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_动态评论ID不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_动态评论ID不能为空]);
                die;
            }

            $params = [
                'token' => $this->token['admin_token'],
                'replyId' => $reply_id,
                'operatorId' => $this->token['id'],
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$del_reply, $params);

            Log::record('动态评论删除成功:操作人:' . $this->token['username'] . '@' . json_encode($reply_id), 'delReply');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('动态评论删除失败:操作人:' . $this->token['username'] . '@' . json_encode($reply_id), 'delReply');
            echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_删除失败]);
            die;
        }

    }

    /*动态审核列表
     * @param string $token token值
     * @param string $page  分页
     * @param int $pagenum  条数
     * @return mixed
     */
    public function getForumAuditList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $list = ForumService::getInstance()->getAuditList($page, $pagenum);
        $forum_num = 0;
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $member_info = MemberModel::getInstance()->getOneById($value['forum_uid'], 'nickname,lv_dengji');
                $nickname = '';
                $lv_dengji = '';
                if ($member_info) {
                    $nickname = $member_info->nickname;
                    $lv_dengji = $member_info->lv_dengji;
                }
                $list[$key]['nickname'] = $nickname;
                $list[$key]['lv_dengji'] = $lv_dengji;
                if ($list[$key]['forum_status'] == 3) {
                    $list[$key]['forum_status'] = '未审核';
                }
            }
            $forum_num = ForumService::getInstance()->getForumCountNum([['forum_status', '=', '3']]);
        }
        Log::record('动态列表:操作人:' . $this->token['username'], 'getForumList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($forum_num / $pagenum);
        View::assign('list', $list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('forum/auditList');
    }

    /*
     * 通过审核
     */
    public function forumAuditYes()
    {
        try {
            $forum_id = Request::param('reply_id');
            if (!$forum_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_动态ID不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_动态ID不能为空]);
                die;
            }
            // $where['id'] = $forum_id;
            // $data = ['forum_status' => 1, 'examined_time' => time()];
            // $res = ForumModel::getInstance()->updateById($where, $data);

            // $forum_info = ForumModel::getInstance()->getModel()->where('id', $forum_id)->findOrEmpty()->toArray();
            // Log::info('forumAuditYes:{data}', ['data' => json_encode($forum_info)]);

            // $params = [
            //     'userId' => $forum_info['forum_uid'],
            //     'tid' => $forum_info['tid'],
            //     'forumId' => $forum_id,
            // ];
            // Log::info('forumAuditYes:{params}', ['params' => json_encode($forum_info)]);

            // $socket_url = config('config.app_api_url') . 'api/inner/forum/checkPass';
            // curlData($socket_url, $params);

            $params = [
                'token' => $this->token['admin_token'],
                'forumId' => $forum_id,
                'type' => 1,
                'operatorId' => $this->token['id'],
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$check_forum, $params);

            Log::record('审核通过帖子:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'forumAuditYes');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('审核通过帖子:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'forumAuditYes');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /*
     * 未通过审核
     */
    public function forumAuditNo()
    {
        try {
            $forum_id = Request::param('reply_id');

            if (!$forum_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_动态ID不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_动态ID不能为空]);die;
            }

            // $where['id'] = $forum_id;
            // $forumInfo = ForumModel::getInstance()->getModel()->field('forum_uid')->where($where)->find();
            // $data = ['forum_status' => 4];
            // $res = ForumModel::getInstance()->updateById($where, $data);
            // if ($res) {
            //     //云信消息
            //     $msg = ["msg" => "您的动态因不符合平台相关规定，未能通过审核，不合规动态已被删除。"];
            //     YunxinModel::getInstance()->sendMsg(config('config.fq_assistant'), 0, $forumInfo['forum_uid'], 0, $msg);
            //     Log::record('拒绝审核通过帖子:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'forumAuditYes');
            //     echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            //     die;
            // } else {
            //     Log::record('拒绝审核通过帖子:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'forumAuditYes');
            //     echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, \constant\CodeConstant::CODE_INSIDE_ERR_MAP[\constant\CodeConstant::CODE_更新失败]);
            //     die;
            // }

            $params = [
                'token' => $this->token['admin_token'],
                'forumId' => $forum_id,
                'type' => 2,
                'operatorId' => $this->token['id'],
            ];
            ApiService::getInstance()->curlApi(ApiUrlConfig::$check_forum, $params);

            Log::record('拒绝审核通过帖子:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'forumAuditYes');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('拒绝审核通过帖子:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'forumAuditYes');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    public function getForumListByWhere()
    {
        $forum_uid = $this->request->param('forum_uid');
        $where = [];
        if ($forum_uid) {
            $where[] = ['forum_uid', '=', $forum_uid];
        }
        $where[] = ['forum_status', 'not in', '3,4'];

        $list = ForumService::getInstance()->getForumListByWhere($where, 'id,createtime,forum_content,forum_image,forum_voice,forum_uid');
        if (!empty($list)) {
            $nickname = MemberModel::getInstance()->getOneById($forum_uid, 'nickname')->toarray()['nickname'];
            $url = config('config.APP_URL_image');
            foreach ($list as $key => $value) {
                if ($value['forum_image']) {
                    $imageArr = explode(',', $value['forum_image']);
                    foreach ($imageArr as $k => &$v) {
                        $v = $v ? $url . '/' . $v : '';
                    }
                    $list[$key]['forum_image'] = $imageArr;
                }
                if ($value['forum_voice']) {
                    $list[$key]['forum_voice'] = $value['forum_voice'] ? $url . '/' . $value['forum_voice'] : '';
                }
                $list[$key]['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
                $list[$key]['nickname'] = $nickname;
            }
        }
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;

    }

}
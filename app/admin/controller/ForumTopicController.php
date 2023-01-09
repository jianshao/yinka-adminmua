<?php
namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ForumTopicModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ForumTopicController extends AdminBaseController
{
    private $forum_topic_num_ = 'forum_topic_num_';
/*
 * 标签列表
 */
    public function labelList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = ['pid' => 0];
        $count = ForumTopicModel::getInstance()->getCount($where);
        $data = ForumTopicModel::getInstance()->tagList($where, $page, $pagenum);
        if (!empty($data)) {
            foreach ($data as $key => $vo) {
                $data[$key]['count'] = ForumTopicModel::getInstance()->getCount(array('pid' => $vo['id']));
            }
        }
        Log::record('标签列表:操作人:' . $this->token['username'], 'labelList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('labelList', $data);
        return View::fetch('topic/index');
    }
/*
 * 话题列表
 */
    public function topicList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = [['pid', '<>', 0]];
        $count = ForumTopicModel::getInstance()->getCount($where);
        $data = ForumTopicModel::getInstance()->tagList($where, $page, $pagenum);
        $redis = $this->getRedis();
        if (!empty($data)) {
            foreach ($data as $key => $vo) {
                $pidName = ForumTopicModel::getInstance()->getBYwhere(array('id' => $vo['pid']));
                $data[$key]['label'] = $pidName['topic_name'];
                $data[$key]['label_id'] = $pidName['id'];
                $count = $redis->get($this->forum_topic_num_ . $data[$key]['id']);
                if (empty($count)) {
                    $data[$key]['topic_count'] = "暂无动态";
                } else {
                    $data[$key]['topic_count'] = $count;
                }
            }
        }

        $label = ForumTopicModel::getInstance()->getLabel();
        foreach ($label as $key => $val) {
            $val = join('-', $val);
            $labelType[] = $val;
        }
        $labelType = implode(',', $labelType);
        Log::record('话题列表:操作人:' . $this->token['username'], 'topicList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('topicList', $data);
        View::assign('label', $label);
        View::assign('labelType', $labelType);
        return View::fetch('topic/topic');
    }

/*
 * 添加标签
 */
    public function addLabel()
    {
        if (Request::param('topic_name') && Request::param('topic_status')) {
            $data = [
                'topic_name' => Request::param('topic_name'),
                'topic_status' => Request::param('topic_status'),
                'create_time' => time(),
                'create_user' => $this->token['username'],
            ];
            $res = ForumTopicModel::getInstance()->addLabel($data);
            if ($res) {
                Log::record('添加标签成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addLabel');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                die;
            } else {
                Log::record('添加标签失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addLabel');
                echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
                die;
            }
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
    }

/*
 * 添加话题
 */
    public function addTopic()
    {
        $topic_name = Request::param('topic_name');
        $topic_status = Request::param('topic_status');
        $topic_hot = Request::param('topic_hot');
        $pid = Request::param('pid');
        if (!empty($topic_name) || !empty($topic_status) || !empty($topic_hot) || !empty($pid)) {
            $data = [
                'topic_name' => $topic_name,
                'topic_status' => $topic_status,
                'topic_hot' => $topic_hot,
                'pid' => $pid,
                'create_time' => time(),
                'create_user' => $this->token['username'],
            ];
            $res = ForumTopicModel::getInstance()->addLabel($data);
            if ($res) {
                Log::record('添加标签成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addLabel');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                die;
            } else {
                Log::record('添加标签失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addLabel');
                echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
                die;
            }
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
    }

/*
 * 修改标签上下架状态
 */
    public function exitLabel()
    {
        $id = Request::param('id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $field = Request::param('field'); //修改字段
        if (Request::param('value') == 1) {
            $value = 0;
        } else {
            $value = 1;
        }
        $data['update_time'] = time();
        $data['update_user'] = $this->token['username'];
        $data[$field] = $value;
        $where = ['id' => $id]; //条件
        $res = ForumTopicModel::getInstance()->exitLabel($where, $data);
        if ($res) {
            Log::record('修改标签状态成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitLabel');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改标签状态失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitLabel');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

/*
 * 修改话题标签
 */
    public function exitTopicTag()
    {
        $id = Request::param('id');
        $field = Request::param('field');
        $value = Request::param('value');
        if ($id && $field && $value) {
            $data['update_time'] = time();
            $data['update_user'] = $this->token['username'];
            $data[$field] = $value;
            $where = ['id' => $id]; //条件
            $res = ForumTopicModel::getInstance()->exitLabel($where, $data);
            if ($res) {
                Log::record('修改话题所属标签成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitTopicTag');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            } else {
                Log::record('修改话题所属标签失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitTopicTag');
                echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
                die;
            }
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
    }

/*
 * 修改话题序号
 */
    public function exitTopicNum()
    {
        $id = Request::param('id');
        $field = Request::param('field');
        $value = Request::param('value');
        if (empty($id || empty($field)) || empty($value)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        } else {
            $data['update_time'] = time();
            $data['update_user'] = $this->token['username'];
            $data[$field] = $value;
            $where = ['id' => $id]; //条件
            $res = ForumTopicModel::getInstance()->exitLabel($where, $data);
            if ($res) {
                Log::record('修改话题热门序号成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitTopicNum');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            } else {
                Log::record('修改话题热门序号失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitTopicNum');
                echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
                die;
            }
        }
    }

/*
 * 修改话题上下架状态
 */
    public function exitTopicStatus()
    {
        $id = Request::param('id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $field = Request::param('field'); //修改字段
        if (Request::param('value') == 1) {
            $value = 0;
        } else {
            $value = 1;
        }
        $data['update_time'] = time();
        $data['update_user'] = $this->token['username'];
        $data[$field] = $value;
        $where = ['id' => $id]; //条件
        $res = ForumTopicModel::getInstance()->exitLabel($where, $data);
        if ($res) {
            Log::record('修改话题状态成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitLabel');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改话题状态失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitLabel');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

/*
 * 改话题是否为热门
 */
    public function exitTopicHot()
    {
        $id = Request::param('id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $field = Request::param('field'); //修改字段
        if (Request::param('value') == 1) {
            $value = 0;
        } else {
            $value = 1;
        }
        $data['update_time'] = time();
        $data['update_user'] = $this->token['username'];
        $data[$field] = $value;
        $where = ['id' => $id]; //条件
        $res = ForumTopicModel::getInstance()->exitLabel($where, $data);
        if ($res) {
            Log::record('修改热门状态成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitLabel');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改热门状态失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitLabel');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

}

<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\MusicModel;
use app\admin\model\YunxinModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class MusicController extends AdminBaseController
{
    public function delMusic()
    {
        $id = Request::param('id'); //用户ID
        $is = MusicModel::getInstance()->getModel()->where('id', $id)->save(['status' => 3]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    /**用户音乐列表
     * @param string $token token值
     * @param string $id 用户id
     * @return mixed
     */
    public function musicList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = [];
        $user_id = Request::param('user_id'); //用户ID
        $where[] = ['status', '<>', 3];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        $song_name = Request::param('song_name'); //用户ID
        if ($song_name) {
            $where[] = ['song_name', 'like', "%$song_name%"];
        }
        $status = Request::param('status'); //审核状态
        if ($status) {
            if ($status < 3) {
                $where[] = ['status', '=', $status];
            }
        }
        //统计
        $count = MusicModel::getInstance()->getModel()->where($where)->count();
        //获取数据
        $list = MusicModel::getInstance()->getList($where, $page, $pagenum);
        $image_url = config('config.APP_URL_image');
        foreach ($list as $key => $value) {
            // $list[$key]['song_url'] = $image_url.substr($value['song_url'],strpos($value['song_url'],'/')+1);
            $list[$key]['song_url'] = $image_url . $value['song_url'];
            if ($value['type'] == 1) {
                $list[$key]['type'] = '伴奏';
            } else {
                $list[$key]['type'] = '原唱';
            }
            if ($value['status'] == 0) {
                $list[$key]['status'] = '未审核';
            } else if ($value['status'] == 1) {
                $list[$key]['status'] = '通过';
            } else if ($value['status'] == 2) {
                $list[$key]['status'] = '未通过';
            }
            $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
            $list[$key]['examine_time'] = date('Y-m-d H:i:s', $value['examine_time']);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('music列表获取成功:操作人:' . $this->token['username'], 'musicList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('songName', $song_name);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $user_id);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('status', $status);
        return View::fetch('member/usermusic');
    }

    /*
     * 通过审核
     */
    public function musicYes()
    {
        $music_id = Request::param('music_id');
        if (!$music_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_音乐ID不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_音乐ID不能为空]);
            die;
        }
        $where['id'] = $music_id;
        $musicInfo = MusicModel::getInstance()->getModel()->where($where)->find();
        $data = ['status' => 1, 'examine_time' => time(), 'examine_uid' => $this->token['username']];
        $res = MusicModel::getInstance()->getModel()->where($where)->update($data);
        if ($res) {
            //云信消息
            $msg = ["msg" => "您上传的音乐《" . $musicInfo['song_name'] . "》已通过平台的审核，您可在房间内添加收听，感谢您的分享！"];
            YunxinModel::getInstance()->sendMsg(config('config.fq_assistant'), 0, $musicInfo['user_id'], 0, $msg);
            Log::record('审核通过音乐:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'musicYes');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('审核通过音乐:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'musicYes');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     * 未通过审核
     */
    public function musicNo()
    {
        $music_id = Request::param('music_id');
        $desc = Request::param('desc');
        if (!$music_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_音乐ID不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_音乐ID不能为空]);
            die;
        }
        $where['id'] = $music_id;
        $musicInfo = MusicModel::getInstance()->getModel()->where($where)->find();
        $data = ['status' => 2, 'examine_time' => time(), 'examine_uid' => $this->token['username'], 'desc' => $desc];
        $res = MusicModel::getInstance()->getModel()->where($where)->update($data);
        if ($res) {
            //云信消息
            $msg = ["msg" => "非常抱歉，您上传的音乐《" . $musicInfo['song_name'] . "》未能通过平台的审核。"];
            YunxinModel::getInstance()->sendMsg(config('config.fq_assistant'), 0, $musicInfo['user_id'], 0, $msg);
            Log::record('拒绝审核通过音乐:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'musicNo');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('拒绝审核通过音乐:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'musicNo');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

}

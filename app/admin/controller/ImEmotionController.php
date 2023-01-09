<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ImEmotionModel;
use app\admin\service\ImEmotionService;
use app\admin\service\WeShineService;
use app\common\RedisCommon;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ImEmotionController extends AdminBaseController
{
    //配置
    function list() {
        $page = Request::param('page', 1);

        $where = [];
        $data = ImEmotionService::getInstance()->getList($page, $where);
        foreach ($data['list'] as $_ => &$item) {
            if ($item['emotion_list']) {
                $emotion_list = json_decode($item['emotion_list'], true, 512);
                $images = [];
                $emotion_ids = [];
                $item['count'] = 0;
                if ($emotion_list) {
                    foreach ($emotion_list as $_ => $emotion) {
                        $emotion_info = json_decode($emotion, true);
                        $images[] = $emotion_info['thumb']['gif'];
                        $emotion_ids[] = $emotion_info['id'];
                    }
                    $item['count'] = count($emotion_list);
                }
                $item['images'] = $images;
                $item['emotion_ids'] = $emotion_ids;
                $item['image_urls'] = implode(',', $images);

                $item['emotion_info'] = WeShineService::getInstance()->shineAlbumSearch($item['title']);
            }
        }

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $data['page_array']);
        View::assign('list', $data['list']);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('imBg/emotion');
    }

    //添加
    public function add()
    {
        try {
            $status = (int) Request::param('status');
            $emotion_list = Request::param('emotion_list', '');
            $title = Request::param('title', '');
            $seq = (int) Request::param('seq', 0);

            if (empty($title)) {
                throw new \Exception("专辑名称不能为空", 403);
            }

            $data = [];
            $data[0]['status'] = $status;
            $data[0]['seq'] = $seq;
            $data[0]['title'] = $title;
            $data[0]['emotion_list'] = json_encode($emotion_list);
            // $data[0]['intro'] = $intro;
            $data[0]['create_time'] = time();
            $data[0]['update_time'] = time();

            ImEmotionModel::getInstance()->getModel()->insertAll($data);
            return rjson([], 200, '添加成功');
        } catch (Exception $e) {
            return rjson([], 403, $e->getMessage());
        }
    }

    //编辑
    public function save()
    {
        try {
            $action = Request::param('action', '');
            $status = Request::param('status');
            $emotion_list = Request::param('emotion_list', '');
            $title = Request::param('title', '');
            $seq = Request::param('seq');
            $id = Request::param('id');

            // $intro = Request::param('intro', '');
            // if (strlen($intro) > 60) {
            //     throw new \Exception("介绍超出范围", 403);
            // }

            if ($action == 'delete') {
                ImEmotionModel::getInstance()->getModel()->where('id', $id)->delete();
            } else {
                $data = ['status' => $status, 'seq' => $seq, 'emotion_list' => json_encode($emotion_list), 'title' => $title];
                ImEmotionModel::getInstance()->getModel()->where('id', $id)->update($data);
            }
            return rjson([], 200, '成功');
        } catch (Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function online()
    {
        try {
            $list = ImEmotionModel::getInstance()->getModel()
                ->field('id,title,emotion_list')
                ->where('status', 1)
                ->order('id desc')
                ->order('seq desc')
                ->select()
                ->toArray();

            $data = [];
            if ($list) {
                foreach ($list as $key => $item) {
                    $emotion_list = json_decode($item['emotion_list'], true);
                    if ($emotion_list) {
                        $arr = [];
                        $arr['id'] = $item['id'];
                        $arr['title'] = $item['title'];

                        foreach ($emotion_list as $emotion_index => $emotion) {
                            $emotion_info = json_decode($emotion, true);

                            $arr['emotion_list'][$emotion_index]['id'] = $emotion_info['id'];
                            $arr['emotion_list'][$emotion_index]['name'] = $emotion_info['name'];
                            $arr['emotion_list'][$emotion_index]['emotion_url'] = $emotion_info['origin']['gif'];
                            $arr['emotion_list'][$emotion_index]['width'] = $emotion_info['origin']['w'];
                            $arr['emotion_list'][$emotion_index]['height'] = $emotion_info['origin']['h'];
                            $data[$key] = $arr;
                        }
                    }
                }
            }

            RedisCommon::getInstance()->getRedis(['select' => 3])->set('im_emotion_conf', json_encode(array_values($data)));
            return rjson([], 200, '成功');
        } catch (\Throwable $th) {
            return rjson([], 403, '失败');
        }
    }
}

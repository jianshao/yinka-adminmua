<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ConfigModel;
use app\admin\model\EmoticonModel;
use OSS\Core\OssException;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class EmoticonController extends AdminBaseController
{
    /*
     * 阿里OSS
     */
    public function ossEmoticonFile()
    {
        $gift_id = Request::param('id');
        $where['id'] = $gift_id;
        $savePath = '/gift';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $gift_image = request()->file('gift_image');
        $animation = request()->file('animation');
        $gift_animation = request()->file('gift_animation');
        if ($gift_image != "") {
            $gift_imageSavename = \think\facade\Filesystem::putFile($savePath, $gift_image);
            $gift_imageObject = str_replace("\\", "/", $gift_imageSavename);
            $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $gift_imageSavename);
        }
        if ($animation != "") {
            $animationSavename = \think\facade\Filesystem::putFile($savePath, $animation);
            $animationObject = str_replace("\\", "/", $animationSavename);
            $animationFile = STORAGE_PATH . str_replace("\\", "/", $animationSavename);
        }
        if ($gift_animation != "") {
            $gift_animationSavename = \think\facade\Filesystem::putFile($savePath, $gift_animation);
            $gift_animationObject = str_replace("\\", "/", $gift_animationSavename);
            $gift_animationFile = STORAGE_PATH . str_replace("\\", "/", $gift_animationSavename);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($gift_image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ['face_image' => '/' . $gift_imageObject, 'mold_icon' => '/' . $gift_imageObject];
                $res = EmoticonModel::getInstance()->getModel()->where($where)->save($data);
            }
            if ($animation != "") {
                $animationResult = $ossClient->uploadFile($bucket, $animationObject, $animationFile); //上传成功
                $data = ['animation' => '/' . $animationObject];
                $res = EmoticonModel::getInstance()->getModel()->where($where)->save($data);
            }
//            if($gift_animation != ""){
            //                $gift_animationResult = $ossClient->uploadFile($bucket, $gift_animationObject, $gift_animationFile);//上传成功
            //                $data = ['gift_animation' => '/' . $gift_animationObject];
            //                $res = EmoticonModel::getInstance()->getModel()->where($where)->save($data);
            //            }
            Log::record('添加礼物/装备图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加礼物/装备图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            return;
        }
    }

    public function listEmoticon()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $mold = Request::param('mold');
        $type = Request::param('type');
        $where = [];
        if ($mold) {
            $where[] = ['mold', '=', $mold];
        }

        $count = EmoticonModel::getInstance()->getModel()->where($where)->count();
        $list = EmoticonModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();

        foreach ($list as $k => $v) {
            if (empty($v['animation'])) {
                $list[$k]['animation'] = '';
            } else {
                $list[$k]['animation'] = config('config.APP_URL_image') . $v['animation'];

            }
            $list[$k]['face_image'] = config('config.APP_URL_image') . $v['face_image'];
            $list[$k]['mold_icon'] = config('config.APP_URL_image') . $v['mold_icon'];
            $list[$k]['game_image'] = config('config.APP_URL_image') . $v['game_image'];
        }

        Log::record('房间类型列表:操作人:' . $this->token['username'], 'roomTypeList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('type', $type);
        View::assign('mold', $mold);
        return View::fetch('emoticon/index');
    }

    public function addEmoticon()
    {
        $data = [
            'face_name' => Request::param('face_name'),
            'face_image' => Request::param('face_image'),
            'animation' => empty(Request::param('animation')) ? 0 : Request::param('animation'),
            'is_sort' => Request::param('is_sort'),
            'type' => Request::param('type'),
            'is_lock' => Request::param('is_lock'),
            'is_vip' => Request::param('is_vip'),
            'mold' => Request::param('mold'),
            'mold_icon' => Request::param('face_image'),
            'game_image' => '',
            'addtime' => '',
        ];
        $is = EmoticonModel::getInstance()->getModel()->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败！']);
            die;
        }
    }

    public function delEmoticon()
    {
        $where[] = ['id', '=', Request::param('id')];
        $is = EmoticonModel::getInstance()->getModel()->where($where)->delete();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败！']);
            die;
        }
    }

    public function saveEmoticon()
    {
        $where[] = ['id', '=', Request::param('id')];

        $data = [
            'face_name' => Request::param('face_name'),
            'is_sort' => Request::param('is_sort'),
            'type' => Request::param('type'),
            'is_lock' => Request::param('is_lock'),
            'is_vip' => Request::param('is_vip'),
            'mold' => Request::param('mold'),
            'mold_icon' => '',
            'game_image' => '',
            'addtime' => '',
        ];
        $is = EmoticonModel::getInstance()->getModel()->where($where)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败！']);
            die;
        }
    }

    /**
     * 清除缓存redis
     */
    public function clearGoldEmoti()
    {
        //获取表情包
        $emotion_list = EmoticonModel::field('id,face_name name,face_image image,is_vip vipLevel,type,is_lock isLock,animation,game_image gameImages,mold')->order('is_sort asc')->select()->toArray();
        $emotion_panel = [];
        $emotions = [];
        foreach ($emotion_list as $key => &$emotion) {
            $emotions[$key]['id'] = $emotion['id'];
            $emotions[$key]['name'] = $emotion['name'];
            $emotions[$key]['image'] = $emotion['image'];
            $emotions[$key]['vipLevel'] = $emotion['vipLevel'];
            $emotions[$key]['type'] = $emotion['type'];
            $emotions[$key]['isLock'] = $emotion['isLock'];
            $emotions[$key]['animation'] = $emotion['animation'];
            $emotions[$key]['gameImages'] = [];
            if (!empty($emotion['gameImages'])) {
                $emotions[$key]['gameImages'] = explode(';', $emotion['gameImages']);
            }

            $mold = $emotion['mold'];
            if ($mold === 1) {
                if (!isset($emotion_panel[0]['emoticons'])) {
                    $emotion_panel[0]['name'] = 'normal';
                    $emotion_panel[0]['icon'] = '';
                    $emotion_panel[0]['mold'] = $mold;
                    $emotion_panel[0]['emoticons'] = [];
                }

                if (!in_array($emotion['id'], $emotion_panel[0]['emoticons'])) {
                    array_push($emotion_panel[0]['emoticons'], $emotion['id']);
                }

            }

            if ($mold === 2) {
                if (!isset($emotion_panel[1]['emoticons'])) {
                    $emotion_panel[1]['name'] = 'special';
                    $emotion_panel[1]['icon'] = '';
                    $emotion_panel[1]['mold'] = $mold;
                    $emotion_panel[1]['emoticons'] = [];
                }

                if (!in_array($emotion['id'], $emotion_panel[1]['emoticons'])) {
                    array_push($emotion_panel[1]['emoticons'], $emotion['id']);
                }
            }
        }

        //更新配置
        ConfigModel::getInstance()->getModel()->where('name', 'emoticon_conf')->update(['json' => json_encode($emotions)]);
        ConfigModel::getInstance()->getModel()->where('name', 'emoticon_panels_conf')->update(['json' => json_encode($emotion_panel)]);

        $this->updateRedisConfig('emoticon_conf');
        $this->updateRedisConfig('emoticon_panels_conf');
    }
}

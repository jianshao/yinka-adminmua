<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\CategoryModel;
use app\admin\model\ConfigModel;
use OSS\Core\OssException;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class MallController extends AdminBaseController
{
    /*
     * 阿里OSS
     */
    public function ossCategoryFile()
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
                $res = CategoryModel::getInstance()->getModel()->where($where)->save($data);
            }
            if ($animation != "") {
                $animationResult = $ossClient->uploadFile($bucket, $animationObject, $animationFile); //上传成功
                $data = ['animation' => '/' . $animationObject];
                $res = CategoryModel::getInstance()->getModel()->where($where)->save($data);
            }
//            if($gift_animation != ""){
            //                $gift_animationResult = $ossClient->uploadFile($bucket, $gift_animationObject, $gift_animationFile);//上传成功
            //                $data = ['gift_animation' => '/' . $gift_animationObject];
            //                $res = CategoryModel::getInstance()->getModel()->where($where)->save($data);
            //            }
            Log::record('添加礼物/装备图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加礼物/装备图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    public function pidToGetTree($list, &$data, $pk = 'id', $pid = 'pid', $child_key = "_child", $sort_id = 'sort', $sort_type = SORT_ASC, $start_pid = 0)
    {
        if ($data === null) {
            return;
        }
        if (count($data) == $start_pid) {
            //初始化根列表
            foreach ($list as $key => &$value) {
                if ($value[$pid] == 0) {
                    $data[] = $value;
                    unset($list[$key]);
                }
            }
            $sort_root_arr = array_column($data, $sort_id);
            array_multisort($sort_root_arr, $sort_type, $data);
        }
        foreach ($data as $key => &$value) {
            foreach ($list as $key2 => $value2) {
                if ($value2[$pid] == $value[$pk]) {
                    $value[$child_key][] = $value2;
                    unset($list[$key2]);
                }
            }
            if (empty($value[$child_key])) {continue;}
            $sort_arr = array_column($value[$child_key], $sort_id);
            array_multisort($sort_arr, $sort_type, $value[$child_key]);
            $this->pidToGetTree($list, $value[$child_key], $pk, $pid, $child_key, $sort_id, $sort_type);
        }
    }

    public function listCategory()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $status = Request::param('status', 1);
        $where = [];
        if ($status) {
            $where[] = ['status', '=', $status];
        }

        $count = CategoryModel::getInstance()->getModel()->where($where)->count();
        $list = CategoryModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        $all = CategoryModel::getInstance()->getModel()->order('childs asc')->select()->toArray();

        $app_url = config('config.APP_URL_image');
        Log::record('房间类型列表:操作人:' . $this->token['username'], 'roomTypeList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('all', $all);
        View::assign('status', $status);
        View::assign('app_url', $app_url);
        return View::fetch('mall/index');
    }

    public function addCategory()
    {
        $data = [
            'category_name' => Request::param('category_name'),
            'img_url' => Request::param('img_url'),
            'sort' => (int) Request::param('sort', 0),
            'pid' => (int) Request::param('pid'),
            'status' => (int) Request::param('status', 1),
            'is_show' => (int) Request::param('is_show'),
        ];

        $level = CategoryModel::getInstance()->getModel()->where('id', $data['pid'])->value('level');
        if (empty($level)) {
            $level = 1;
        } else {
            $level += 1;
        }
        $data['level'] = $level;
        $id = CategoryModel::getInstance()->getModel()->insertGetId($data);

        $childs = CategoryModel::getInstance()->getModel()->where('id', $id)->value('childs');

        $top_childs = CategoryModel::getInstance()->getModel()->where('id', $data['pid'])->value('childs');

        if ($top_childs) {
            $childs = "{$top_childs}-{$id}";
        } else {
            $childs = "{$data['pid']}-{$id}";
        }
        $data['childs'] = $childs;

        CategoryModel::getInstance()->getModel()->where('id', $id)->update(['childs' => $childs]);

        if ($id) {
            echo json_encode(['code' => 200, 'msg' => '添加成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败！']);
            die;
        }
    }

    public function delCategory()
    {
        $id = (int) Request::param('id');
        $childs = "%{$id}%";
        $is = CategoryModel::getInstance()->getModel()->where([['childs', 'like', $childs]])->update(['status' => 0]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败！']);
            die;
        }
    }

    public function saveCategory()
    {
        $id = (int) Request::param('id');
        $sort = (int) Request::param('sort');
        $is_show = (int) Request::param('is_show');
        $pid = (int) Request::param('pid');
        $category_name = Request::param('category_name');
        $img_url = Request::param('img_url');
        $where[] = ['id', '=', $id];

        $data = [
            'category_name' => $category_name,
            'sort' => $sort,
            'is_show' => $is_show,
            // 'pid' => $pid,
            'img_url' => $img_url,
        ];
        $is = CategoryModel::getInstance()->getModel()->where('id', $id)->save($data);
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
        $emotion_list = CategoryModel::field('id,face_name name,face_image image,is_vip vipLevel,type,is_lock isLock,animation,game_image gameImages,mold')->select()->toArray();
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

            $mold = $emotion['mold'];
            if ($mold === 1) {
                if (!isset($emotion_panel[0]['Categorys'])) {
                    $emotion_panel[0]['name'] = 'normal';
                    $emotion_panel[0]['icon'] = '';
                    $emotion_panel[0]['mold'] = $mold;
                    $emotion_panel[0]['Categorys'] = [];
                }

                if (!in_array($emotion['id'], $emotion_panel[0]['Categorys'])) {
                    array_push($emotion_panel[0]['Categorys'], $emotion['id']);
                }

            }

            if ($mold === 2) {
                if (!isset($emotion_panel[1]['Categorys'])) {
                    $emotion_panel[1]['name'] = 'normal';
                    $emotion_panel[1]['icon'] = '';
                    $emotion_panel[1]['mold'] = $mold;
                    $emotion_panel[1]['Categorys'] = [];
                }

                if (!in_array($emotion['id'], $emotion_panel[1]['Categorys'])) {
                    array_push($emotion_panel[1]['Categorys'], $emotion['id']);
                }
            }
        }

        //更新配置
        ConfigModel::getInstance()->getModel()->where('name', 'Category_conf')->update(['json' => json_encode($emotions)]);
        ConfigModel::getInstance()->getModel()->where('name', 'Category_panels_conf')->update(['json' => json_encode(['panels' => $emotion_panel])]);

        $this->updateRedisConfig('Category_conf');
        $this->updateRedisConfig('Category_panels_conf');
    }

}

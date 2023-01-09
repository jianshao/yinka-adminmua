<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ConfigModel;
use app\admin\model\ScrollerModel;
use MQ\Config;
use OSS\Core\OssException;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ScrollerController extends AdminBaseController
{
    /*
     * 阿里OSS
     */
    public function ossFile()
    {
        $gift_id = Request::param('id');
        $where['id'] = $gift_id;
        $savePath = '/scroller';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $img_top = request()->file('img_top');
        $img_end = request()->file('img_end');
        if ($img_top != "") {
            $img_topSavename = \think\facade\Filesystem::putFile($savePath, $img_top);
            $img_topObject = str_replace("\\", "/", $img_topSavename);
            $img_topFile = STORAGE_PATH . str_replace("\\", "/", $img_topSavename);
        }
        if ($img_end != "") {
            $img_endSavename = \think\facade\Filesystem::putFile($savePath, $img_end);
            $img_endObject = str_replace("\\", "/", $img_endSavename);
            $img_endFile = STORAGE_PATH . str_replace("\\", "/", $img_endSavename);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($img_top != "") {
                $img_topResult = $ossClient->uploadFile($bucket, $img_topObject, $img_topFile); //上传成功
                $data = ['img_top' => '/' . $img_topObject];
                $res = ScrollerModel::getInstance()->getModel()->where($where)->save($data);
            }
            if ($img_end != "") {
                $img_endResult = $ossClient->uploadFile($bucket, $img_endObject, $img_endFile); //上传成功
                $data = ['img_end' => '/' . $img_endObject];
                $res = ScrollerModel::getInstance()->getModel()->where($where)->save($data);
            }
            Log::record('添加礼物/装备图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加礼物/装备图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    public function listInfo()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;

        $count = ScrollerModel::getInstance()->getModel()->count();
        $list = ScrollerModel::getInstance()->getModel()->limit($page, $pagenum)->select()->toArray();

        foreach ($list as $k => $v) {
            $list[$k]['img_top'] = !empty($v['img_top']) ? $this->img_url . $v['img_top'] : '';
            $list[$k]['img_end'] = !empty($v['img_end']) ? $this->img_url . $v['img_end'] : '';
        }

        Log::record('列表:操作人:' . $this->token['username']);
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('list', $list);
        return View::fetch('scroller/index');
    }

    public function add()
    {
        $data = [
            'type' => Request::param('type'),
            'img_top' => Request::param('img_top'),
            'img_end' => Request::param('img_end'),
            'color' => Request::param('color'),
            'border_color' => Request::param('border_color'),
            'description' => Request::param('description'),
        ];
        $is = ScrollerModel::getInstance()->getModel()->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败！']);
            die;
        }
    }

    public function del()
    {
        $where[] = ['id', '=', Request::param('id')];
        $is = ScrollerModel::getInstance()->getModel()->where($where)->delete();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败！']);
            die;
        }
    }

    public function save()
    {
        $where[] = ['id', '=', Request::param('id')];

        $data = [
            // 'type' => Request::param('type'),
            'color' => Request::param('color'),
            'border_color' => Request::param('border_color'),
            'description' => Request::param('description'),
        ];
        $is = ScrollerModel::getInstance()->getModel()->where($where)->save($data);
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
    public function clear()
    {
        //获取表情包
        $list = ScrollerModel::field('*')->getModel()->select()->toArray();
        $data = [];
        foreach ($list as $_ => &$scroller) {
            $type = $scroller['type'];
            $data[$type]['imgTop'] = $scroller['img_top'];
            $data[$type]['imgEnd'] = $scroller['img_end'];
            $data[$type]['color'] = $scroller['color'];
            $data[$type]['borderColor'] = $scroller['border_color'];
        }

        //更新配置
        ConfigModel::getInstance()->getModel()->where('name', 'led_conf')->update(['json' => json_encode($data)]);
        $this->updateRedisConfig('led_conf');
    }
}

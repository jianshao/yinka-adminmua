<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use think\facade\Request;
use think\facade\View;

class TaskController extends AdminBaseController
{
    /**
     * 任务展示列表
     */
    public function TaskList()
    {

        $pagenum = 20;
        $page = !empty($this->request->param('page')) ? ($this->request->param('page') - 1) * $pagenum : 0;
        $master_page = !empty($this->request->param('page')) ? $this->request->param('page') : 1;

        $attire_id = Request::param('attire_id'); //礼物ID
        $status = Request::param('status'); //任务的状态
        $is_show = Request::param('is_show');
        $is_show = empty($is_show) ? 2 : $is_show;
        $where = [];

        if (!empty($status)) {
            if ($status == 1) {
                $where['status'] = 1;
            } else if ($status == 3) {
                $where['status'] = 0;
            }
        }

        if ($attire_id) {
            $where['id'] = $attire_id;
        }

        if ($is_show != 2) {
            if ($is_show == 1) {
                $where['is_show'] = 1;
            } else if ($is_show == 3) {
                $where['is_show'] = 0;
            }
        }
        $count = AttireModel::getInstance()->getModel()->where($where)->count();
        $list = AttireService::getInstance()->AttierList($where, $page, $pagenum);
        $attiretype = AttireTypeModel::getInstance()->getModel()->select()->toArray();
        foreach ($attiretype as $k => $v) {
            $type[] = $v;
        }
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['attire_price'] = $array = json_decode($value['attire_price'], true);
                $list[$key]['attire_image'] = $array = config('config.APP_URL_image') . $value['attire_image'];

                foreach ($type as $k => $v) {
                    if ($list[$key]['son_id'] == $type[$k]['id']) {
                        $list[$key]['son_id_name'] = $type[$k]['name'];
                    }
                    if ($list[$key]['pid'] == $type[$k]['id']) {
                        $list[$key]['pid_name'] = $type[$k]['name'];
                    }
                }

                foreach ($list[$key]['attire_price'] as $k => $v) {
                    $list[$key]['price'][$k] = $v['day'] . '天' . $v['price'] . '豆 | ';
                }
                $list[$key]['price'] = rtrim(rtrim(implode($list[$key]['price']), ' '), '|');
            }

        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('list', $list);
        View::assign('attire_id', $attire_id);
        View::assign('status', $status);
        View::assign('type', $type);
        View::assign('is_show', $is_show);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('attire/index');
    }

    /**
     * 任务添加展示
     */
    public function TaskAdd()
    {
        foreach (Request::param('day') as $key => $value) {
            $price[$key]['day'] = intval($value);
        }
        foreach (Request::param('price') as $key => $value) {
            $price[$key]['price'] = intval($value);
        }
        $data = [
            'pid' => Request::param('typeid'),
            'son_id' => Request::param('typepid'),
            'attire_describe' => Request::param('desc'),
            'attire_price' => json_encode($this->sortArrByField($price, 'day')),
            'attire_name' => Request::param('attire_name'),
            'status' => Request::param('status'),
            'is_buy' => Request::param('is_buy'),
            'is_vip' => Request::param('is_vip'),
            'get_type' => Request::param('get_type'),
            'is_show' => Request::param('is_show'),
            'attire_image' => Request::param('image'),
            'created_time' => time(),
        ];
        $res = AttireStartModel::getInstance()->getModel()->save($data);
        if ($res) {
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }

    }

    /**
     * 修改
     */
    public function TaskSave()
    {
        $id = Request::param('attire_id'); //修改ID
        $day = Request::param('day');
        $price = Request::param('price');
        $image = Request::param('image');
        $pid = Request::param('typeid1');
        $son_id = Request::param('typepid');
        $data = [];
        if (!empty($son_id)) {
            $data['son_id'] = $son_id;
        }
        if (!empty($pid)) {
            $data['pid'] = $pid;
        }
        if (!empty($price) && !empty($day)) {
            foreach ($day as $key => $value) {
                $price_arr[$key]['day'] = intval($value);
            }
            foreach ($price as $key => $value) {
                $price_arr[$key]['price'] = intval($value);
            }
            $data['attire_price'] = json_encode($this->sortArrByField($price_arr, 'day'));
        }
        if (!empty($image)) {
            $data['attire_image'] = $image;
        }

        $where['id'] = $id;
        $data += [
            'get_type' => Request::param('get_type'),
            'attire_describe' => Request::param('desc'),
            'attire_name' => Request::param('attire_name'),
            'status' => Request::param('status'),
            'is_buy' => Request::param('is_buy'),
            'is_show' => Request::param('is_show'),
            'updated_time' => time(),
        ];
        $res = AttireModel::getInstance()->setAttire($where, $data);
        if ($res) {
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**
     * 任务上下架切换
     */
    public function statusTask()
    {
        $id = Request::param('attireType_id');
        $status = Request::param('status');
        if ($status == 1) {
            $data['status'] = 0;
        } else {
            $data['status'] = 1;
        }
        $where['id'] = $id;
        $res = AttireModel::getInstance()->setAttire($where, $data);
        if ($res) {
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     * 阿里OSS
     */
    public function ossTaskFile()
    {
        $savePath = '/attire';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $image = request()->file('image');
        if ($image != "") {
            $gift_imageSavename = \think\facade\Filesystem::putFile($savePath, $image);
            $gift_imageObject = str_replace("\\", "/", $gift_imageSavename);
            $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $gift_imageSavename);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ['image' => '/' . $gift_imageObject];
            }
            return ['status' => 1, 'msg' => '上传成功', 'image' => '/' . $gift_imageObject];die;
        } catch (OssException $e) {
            return ['status' => 1, 'msg' => '上传失败'];die;
        }
    }
}
<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AdvertModel;
use app\admin\model\DoappModel;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class AdertController extends AdminBaseController
{

    /**启动广告列表
     * @param string $token token值
     * @param string $id 广告id
     * @return mixed
     */
    public function advertList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = [];
        $name = Request::param('search_name'); //广告标题
        if ($name) {
            $where['name'] = $name;
        }
        //统计
        $count = AdvertModel::getInstance()->getModel()->where($where)->count();
        //获取数据
        $data = AdvertModel::getInstance()->getList($where, $page, $pagenum);
        foreach ($data as $key => $value) {
            $data[$key]['start_time'] = date('Y-m-d H:i:s', $value['start_time']);
            $data[$key]['end_time'] = date('Y-m-d H:i:s', $value['end_time']);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('advert列表获取成功:操作人:' . $this->token['username'], 'advertList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_name', $name);
        return View::fetch('active/advertindex');
    }

    /*
     * 添加广告位
     * @param string $token token值
     * @param string $title 标题
     * @param string $image 图片地址
     * @param string $linkurl 链接地址
     * @param string $type 类型
     * @param string $status 上下架状态
     */
    public function addAdvert()
    {
        $name = Request::param("name");
        $linkurl = Request::param("linkurl");
        $start_time = Request::param("start_time");
        $end_time = Request::param("end_time");
        $display_time = Request::param("display_time");
        if ($name && $start_time && $end_time && $display_time) {
            $data = [
                'name' => $name,
                'linkurl' => $linkurl,
                'start_time' => strtotime($start_time),
                'end_time' => strtotime($end_time),
                'display_time' => $display_time,
            ];
            $res = AdvertModel::getInstance()->getModel()->save($data);
            if ($res) {
                Log::record('advert添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addAdvert');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                die;
            } else {
                Log::record('advert添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addAdvert');
                echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
                die;
            }
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }

    }

    /*
     * 修改启动广告信息
     * @param string $token token值
     * @param string $id    id
     * @param string $name 字段名称
     * @param string $value 字段值
     */
    public function editAdvert()
    {
        $id = Request::param('id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $start_time = Request::param('start_time');
        if (!empty($start_time)) {
            $start_time = strtotime($start_time);
        }
        $end_time = Request::param('end_time');
        if (!empty($end_time)) {
            $end_time = strtotime($end_time);
        }
        $where['id'] = $id;
        $data = [
            'name' => Request::param('name'),
            'linkurl' => Request::param('linkurl'),
            'start_time' => $start_time,
            'end_time' => $end_time,
            'display_time' => Request::param('display_time'),
        ];
        $res = AdvertModel::getInstance()->getModel()->where($where)->save($data);
        if ($res) {
            Log::record('修改advert数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editAdvert');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改advert数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editAdvert');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     * 图片
     */
    public function editAdvertImg()
    {
        $id = Request::param('id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where['id'] = $id;
        $data['image'] = $url = config('config.APP_URL_image') . '/' . Request::param('img');
        $res = AdvertModel::getInstance()->getModel()->where($where)->save($data);
        if ($res) {
            Log::record('修改advert数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editAdvertImg');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改advert数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editAdvertImg');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**推广列表
     * @param string $token token值
     * @param string $id 广告id
     * @return mixed
     */
    public function doappList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = [];
        //统计
        $count = DoappModel::getInstance()->getModel()->where($where)->count();
        //获取数据
        $data = DoappModel::getInstance()->getList($where, $page, $pagenum);
        foreach ($data as $key => $value) {
            $data[$key]['ctime'] = date('Y-m-d', $value['ctime']);
            $data[$key]['downcount'] = $value['adowncount'] + $value['pdowncount'];
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('doapp列表获取成功:操作人:' . $this->token['username'], 'doappList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('active/tgindex');
    }
}

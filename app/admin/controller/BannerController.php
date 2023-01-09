<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\BannerModel;
use app\admin\model\ChannelModel;
use app\admin\service\BannerService;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class BannerController extends AdminBaseController
{
    /**
     * Banner自动化时间
     */
    public function bannerOpen()
    {
        $id = Request::param('id');
        $datetimeStart = Request::param('datetimeStart');
        $datetimeEnd = Request::param('datetimeEnd');
        $is = BannerModel::getInstance()->getModel()->where('id', $id)->save(['start_time' => $datetimeStart, 'end_time' => $datetimeEnd]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']); //php编译join
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']); //php编译join
        }
    }

    /**轮播图列表
     * @param string $token token值
     * @param string $id 广告id
     * @return mixed
     */
    public function bannerList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = [];
        $id = Request::param('id'); //广告ID
        if ($id) {
            $where['id'] = $id;
        }
        $channels_id = Request::param('channels_id');
        //房间类型
        if (!empty($channels_id)) {
            if (!is_numeric($channels_id)) {
                echo $this->return_json(\constant\CodeConstant::CODE_房间关联渠道错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_房间关联渠道错误]);
                die;
            }
        }
        $where_row = '';
        if ($channels_id) {
            $where_row = 'banner_channel & ' . $channels_id . ' > 0';
        }

        $count = BannerModel::getInstance()->count($where, $where_row);

        $data = BannerModel::getInstance()->BannerList($where, $page, $pagenum, $where_row);
        if (!empty($data)) {
            foreach ($data as $k => $vo) {
                $channel_name = '';
                $channel_id = '';
                if ($vo['banner_channel'] != 0) {
                    $whereIn = $this->bitSplit($vo['banner_channel']);
                    $channel = ChannelModel::getInstance()->getModel()->field('name,id')->where([['id', 'in', $whereIn]])->select()->toArray();
                    if (!empty($channel)) {
                        $channel_name = implode(',', array_column($channel, 'name'));
                        $channel_id = implode(',', array_column($channel, 'id'));
                    }
                }
                $data[$k]['channel_name'] = $channel_name;
                $data[$k]['channel_id'] = $channel_id;
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $vo['create_time']);
            }
        }
        $channel_array = ChannelModel::getInstance()->getModel()->field('id,name')->select()->toArray();
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('Banner列表获取成功:操作人:' . $this->token['username'], 'giftList');
        View::assign('page', $page_array);
        View::assign('channel_array',$channel_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $id);
        View::assign('channels_id', $channels_id);
        View::assign('start_time', date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") - date("w") + 1 + 7, date("Y"))));
        View::assign('end_time', date("Y-m-d H:i:s", mktime(23, 59, 59, date("m"), date("d") - date("w") + 1 + 13, date("Y"))));
        return View::fetch('banner/index');
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
    public function saveBanner()
    {
        $title = Request::param("title");
        $linkurl = Request::param("linkurl");
        $type = Request::param("type");
        $bannerType = Request::param("bannerType");
        $location = Request::param("location");
        if ($title && $linkurl && $type) {
            $data = [
                'title' => $title,
                'linkurl' => $linkurl,
                'type' => $type,
                'bannerType' => $bannerType,
                'location' => $location,
                'create_time' => time(),
                'status' => 0,
            ];
            $res = BannerModel::getInstance()->addBanner($data);
            if ($res) {
                Log::record('Banner添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'bannerAdd');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                die;
            } else {
                Log::record('Banner添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'bannerAdd');
                echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
                die;
            }
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }

    }

    /*
     * 修改广告信息
     * @param string $token token值
     * @param string $id    id
     * @param string $name 字段名称
     * @param string $value 字段值
     */
    public function exitBanner()
    {
        $id = Request::param('id');
        $show_type = Request::param('val');
        $type = Request::param('type');
        $linkurl = Request::param('linkurl');
        $title = Request::param('title');
        $bannerType = Request::param('bannerType');
        $location = Request::param('location');
        $where['id'] = $id;
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        if (empty($type)) {
            $data = ['show_type' => $show_type];
        } else {
            $data = [
                'title' => $title,
                'linkurl' => $linkurl,
                'type' => $type,
                'location' => $location,
                'bannerType' => $bannerType,
            ];
        }
        $res = BannerModel::getInstance()->getModel()->where($where)->save($data);
        if ($res) {
            Log::record('修改Banner数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'bannerEdit');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改Banner数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'bannerEdit');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     * 图片
     */
    public function exitBannerImg()
    {
        $id = Request::param('id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where['id'] = $id;
        $data['image'] = $url = config('config.APP_URL_image') . '/' . Request::param('img');
        $res = BannerModel::getInstance()->setBanner($where, $data);
        if ($res) {
            Log::record('修改Banner数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitBannerImg');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改Banner数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitBannerImg');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**
     * 清除缓存redis
     * @param string $token token值
     */
    public function clearCache()
    {
        BannerService::getInstance()->start_time();
        BannerService::getInstance()->end_time();
        BannerService::getInstance()->endTime();
        echo json_encode(['code' => 200, 'msg' => '修改成功']);die;
    }

    public function exitBannerChannel()
    {
        $id = $this->request->param('id');
        $check_id = $this->request->param('check_id');
        if (!$id || !$check_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $ok = BannerModel::getInstance()->getModel()->where(array('id' => $id))->save(array('banner_channel' => $check_id));
        if ($ok) {
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }
}

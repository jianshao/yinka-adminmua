<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\GiftModel;
use app\admin\model\GiftPropModel;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class PackController extends AdminBaseController
{
    //装备列表
    public function equipList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $type = Request::param('type'); //礼物类型
        $id = Request::param('gift_id'); //礼物ID
        $status = Request::param('status'); //礼物状态
        if ($type && $id && $status) {
            $where = ['id' => $id, 'status' => $status, 'type' => $type];
        } else if ($type && $status) {
            $where = ['status' => $status, 'type' => $type];
        } else if ($type && $id) {
            $where = ['id' => $id, 'type' => $type];
        } else if ($status && $id) {
            $where = [['status', '=', $status], ['id', '=', $id], ['type', '<>', 0]];
        } else if ($type) {
            $where = ['type' => $type];
        } else if ($status) {
            $where = [['status', '=', $status], ['type', '<>', 0]];
        } else if ($id) {
            $where = [['id', '=', $id], ['type', '<>', 0]];
        } else {
            $where = [['type', '<>', 0]];
        }
        $count = GiftModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = GiftModel::getInstance()->giftList($where, $page, $pagenum);
            foreach ($data as $key => $val) {
                if ($data[$key]['animation'] != "") {
                    $data[$key]['animation'] = $url . $val['animation'];
                }
                if ($data[$key]['gift_animation'] != "") {
                    $data[$key]['gift_animation'] = $url . $val['gift_animation'];
                }
                if ($data[$key]['gift_image'] != "") {
                    $data[$key]['gift_image'] = $url . $val['gift_image'];
                }
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('装备列表获取成功:操作人:' . $this->token['username'], 'equipList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $id);
        View::assign('search_type', $type);
        View::assign('search_status', $status);
        return View::fetch('pack/index');
    }
    //添加装备
    public function equipAdd()
    {
        $color = Request::param('color');
        $types = Request::param('types');
        if ($color && $types) {
            $arr = [
                'type' => $types,
                'color' => $color,
            ];
            $data['prop_info'] = json_encode($arr);
        }
        $data['gift_name'] = Request::param('gift_name');
        $data['type'] = Request::param('type');
        $data['gift_coin'] = Request::param('gift_coin');
        $data['status'] = Request::param('status');
        $data['creat_time'] = date('Y-m-d H:i:s', time());
        $res = GiftModel::getInstance()->addGifts($data);
        if ($res) {
            Log::record('装备添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'equipAdd');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('装备添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'equipAdd');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }
    //修改装备
    public function equipExid()
    {
        $id = Request::param('gift_id'); //礼物ID
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where['id'] = $id;
        $color = Request::param('color');
        $types = Request::param('types');
        if ($color && $types) {
            $arr = [
                'type' => $types,
                'color' => $color,
            ];
            $data['prop_info'] = json_encode($arr);
        }
        $data['gift_name'] = Request::param('gift_name');
        $data['gift_coin'] = Request::param('gift_coin');
        $data['type'] = Request::param('type');
        $data['status'] = Request::param('status');
        $res = GiftModel::getInstance()->setGift($where, $data);
        if ($res) {
            Log::record('装备数据修改成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'equipExid');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('装备数据修改失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'equipExid');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }
    //装备详情
    public function equipDetails()
    {
        $id = Request::param('gift_id'); //礼物ID'
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where['gift_id'] = $id;
        $list = GiftPropModel::getInstance()->propByWhere($where);
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $list[$key]['prop_discount_times'] = $this->secsToStrs($val['prop_discount_time']);
                $list[$key]['prop_discount_time'] = floor($val['prop_discount_time'] / 86400);
                $list[$key]['prop_times'] = $this->secsToStr($val['prop_time']);
                $list[$key]['prop_time'] = floor($val['prop_time'] / 86400);
                if ($val['updatetime'] == 0) {
                    $list[$key]['updatetimes'] = '无';
                } else {
                    $list[$key]['updatetimes'] = date('Y-m-d H:i:s', $val['updatetime']);
                }
                if ($val['prop_status'] == 1) {
                    $list[$key]['prop_statuss'] = "正常";
                } else {
                    $list[$key]['prop_statuss'] = "删除";
                }
                if ($val['prop_discount'] == 0) {
                    $list[$key]['prop_discounts'] = "无";
                }
                $list[$key]['createtime'] = date('Y-m-d H:i:s', $val['createtime']);
            }
        }
        Log::record('装备详细列表:操作人:' . $this->token['username'], 'equipDetails');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    //添加装备配置
    public function equipAddProp()
    {
        $gift_id = Request::param('gift_id');
        if (!$gift_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }

        $data = [
            'gift_id' => Request::param('gift_id'),
            'prop_price' => Request::param('prop_price'),
            'prop_discount' => Request::param('prop_discount'),
            'prop_discount_time' => Request::param('prop_discount_time') * 86400,
            'prop_time' => Request::param('prop_time') * 86400,
            'prop_status' => Request::param('prop_status'),
            'createtime' => time(),
            'create_user' => $this->token['username'],
        ];
        $res = GiftPropModel::getInstance()->propAdd($data);
        if ($res) {
            Log::record('装备配置添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'equipAddProp');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('装备配置添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'equipAddProp');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }
    //修改装备配置
    public function equipExidProp()
    {
        $id = Request::param('id'); //装备配置ID
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where['id'] = $id;
        $data['prop_price'] = Request::param('prop_price');
        $data['prop_discount'] = Request::param('prop_discount');
        $data['prop_discount_time'] = Request::param('prop_discount_time') * 86400;
        $data['prop_time'] = Request::param('prop_time') * 86400;
        $data['prop_status'] = Request::param('prop_status');
        $data['updatetime'] = time();
        $data['update_user'] = $this->token['username'];
        $res = GiftPropModel::getInstance()->propExid($where, $data);
        if ($res) {
            Log::record('装备数据修改成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'equipExid');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('装备数据修改失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'equipExid');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }
    public function secsToStr($secs)
    {
        if ($secs == 0) {
            $r = '永久有效';
        } else if ($secs >= 86400) {
            $days = floor($secs / 86400);
            $r = $days . '天';
        } else {
            $r = '时间小于一天';
        }
        return $r;
    }
    public function secsToStrs($secs)
    {
        if ($secs == 0) {
            $r = '无';
        } else if ($secs >= 86400) {
            $days = floor($secs / 86400);
            $r = $days . '天';
        } else {
            $r = '时间小于一天';
        }
        return $r;
    }

}

<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AttireTypeModel;
use app\admin\model\AttireTypeStartModel;
use app\admin\service\AttireTypeService;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class AttireTypeController extends AdminBaseController
{
    /**
     * 装饰展示列表
     * @return string
     * @throws \Exception
     */
    public function getAttireTypeList()
    {
        $id = Request::param('attireType_id'); //礼物ID
        $status = Request::param('status'); //装扮的状态
        $is_show = Request::param('is_show');
        $is_show = empty($is_show) ? 2 : $is_show;
        $where = [];

        if (!empty($status) && $status == 1) {
            $where['status'] = 1;
        }if (!empty($status) && $status == 2) {
            $where['status'] = 0;
        }
        if ($id) {
            $where['id'] = $id;
        }
        if ($is_show != 2 && !empty($is_show)) {
            if ($is_show == 3) {
                $where['is_show'] = 0;
            } else if ($is_show == 1) {
                $where['is_show'] = 1;
            }
        }
        $attiretype = AttireTypeModel::getInstance()->getModel()->select()->toArray();
        $count = AttireTypeModel::getInstance()->getModel()->where($where)->count();
        $list = AttireTypeService::getInstance()->AttierTypeList($where);
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['created_time'] = date("Y-m-d H:i:s", $list[$key]['created_time']);
            }
        }

        $page_array = [];
        View::assign('list', $list);
        View::assign('attiretype', $attiretype);
        View::assign('attireType_id', $id);
        View::assign('status', $status);
        View::assign('is_show', $is_show);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('attiretype/index');
    }

    /**装扮分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAttireType()
    {
        $attiretype = AttireTypeModel::getInstance()->getModel()->select()->toArray();
        foreach ($attiretype as $k => $v) {
            $type[] = $v;
        }
        return json_encode($type);
    }

    /**
     * 装扮分类添加展示
     * status: 1 ：上下架状态（0：上架，1：下架）
     * is_buy: 1：可否购买（1：不可，0：可）
     *   `id` int(11) NOT NULL AUTO_INCREMENT,
     *`pid` int(11) NOT NULL DEFAULT '0' COMMENT '父id',
     *`order` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
     *`name` varchar(255) NOT NULL COMMENT '名称',
     *`is_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否显示 0不显示 1显示',
     *`status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '上下架 0下架 1上架',
     *`created_time` int(11) NOT NULL COMMENT '创建时间',
     */
    public function addAttireType()
    {
        $typeid = Request::param('typeid');
        $data = [
            'name' => Request::param('typeName'),
            'status' => Request::param('status'),
            'is_show' => Request::param('is_show'),
            'created_time' => time(),
        ];
        if ($typeid != 0) {
            $data['pid'] = $typeid;
        } else {
            $data['pid'] = 0;
        }
        $is = AttireTypeStartModel::getInstance()->getModel()->where($data)->value('id');
        if (!$is) {
            $res = AttireTypeStartModel::getInstance()->getModel()->insert($data);
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
        if ($res) {
            Log::record('装扮添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('装扮添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }

    }

    /**
     * 修改
     */
    public function updateAttireType()
    {
        $id = Request::param('type_id');
        $pid = Request::param('typeid');
        $data = [];
        if (!empty($pid)) {
            $data['pid'] = $pid;
        }

        $where['id'] = $id;
        $data += [
            'name' => Request::param('typeName'),
            'status' => Request::param('status'),
            'is_show' => Request::param('is_show'),
//            'updated_time'=>time(),
        ];
        $res = AttireTypeModel::getInstance()->setAttireType($where, $data);
        if ($res) {
            Log::record('修改礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**
     * 上下架
     */
    public function statusAttireType()
    {
        $id = Request::param('attireType_id');
        $status = Request::param('status');
        if ($status == 1) {
            $data['status'] = 0;
        } else {
            $data['status'] = 1;
        }
        $where['id'] = $id;
        $res = AttireTypeModel::getInstance()->setAttireType($where, $data);
        if ($res) {
            Log::record('修改礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }
}

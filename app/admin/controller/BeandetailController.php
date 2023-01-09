<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\BeandetailModel;
use think\facade\Request;

class BeandetailController extends AdminBaseController
{
    /**
     * @param $token    token值
     * @param $page     分页
     * @param $pagenum  条数
     * @return mixed    返回
     */
    public function incomeList()
    {
        $page = Request::param('page'); //分页
        $limit = Request::param('pagenum'); //条数
        if (!$page || !$limit) {
            return $this->return_json(\constant\CodeConstant::CODE_分页或条数不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_分页或条数不能为空]);
        }
        //搜索筛选
        $user_id = Request::param('user_id'); //用户ID
        $addtime = Request::param('addtime'); //时间搜索
        if ($user_id && $addtime) {
            $where['uid'] = $user_id;
            $where['addtime'] = array("LIKE", '%' . $addtime . '%');
        } else if ($addtime) {
            $where['addtime'] = array("LIKE", '%' . $addtime . '%');
        } else if ($user_id) {
            $where = ['uid' => $user_id];
        } else {
            $where = [];
        }
        $offset = ($page - 1) * $limit;
        $count = BeandetailModel::getInstance()->getModel()->where($where)->count();
        $totalPage = ceil($count / $limit);
        $pageInfo = array("page" => $page, "pageNum" => $limit, "totalPage" => $totalPage, "count" => $count);
        $data = BeandetailModel::getInstance()->getList($where, $offset, $limit);
        if ($data) {
            $result = [
                "list" => $data,
                "pageInfo" => $pageInfo,
            ];
            return $this->return_json(\constant\CodeConstant::CODE_成功, $result, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        } else {
            return $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_暂无数据]);
        }
    }

}

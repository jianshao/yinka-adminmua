<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ReportTagModel;
use think\Exception;
use think\facade\Request;

class ReportTagController extends AdminBaseController
{
    //添加
    public function add()
    {
        try {
            $title = Request::param('title', '');
            $report_content = Request::param('report_content');
            $reported_content = Request::param('reported_content');
            $punish_level = Request::param('punish_level', 0);

            if (empty($title)) {
                throw new \Exception("标签名称不能为空", 500);
            }

            if (empty($report_content)) {
                throw new \Exception("举报者话术不能为空", 500);
            }

            if (empty($reported_content)) {
                throw new \Exception("被举报者话术不能为空", 500);
            }

            if (empty($punish_level)) {
                throw new \Exception("处罚等级不能为空", 500);
            }

            $data = [];
            $data[0]['type'] = 0;
            $data[0]['punish_type_id'] = 0;
            $data[0]['title'] = $title;
            $data[0]['punish_level'] = $punish_level;
            $data[0]['report_content'] = $report_content;
            $data[0]['reported_content'] = $reported_content;
            $data[0]['create_time'] = time();

            ReportTagModel::getInstance()->getModel()->insertAll($data);
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
            $id = (int) Request::param('id');
            if ($action == 'delete') {
                ReportTagModel::getInstance()->getModel()->where('id', $id)->delete();
            } else {
                $data = [];
                ReportTagModel::getInstance()->getModel()->where('id', $id)->update($data);
            }
            return rjson([], 200, '成功');
        } catch (Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}

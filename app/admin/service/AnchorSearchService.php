<?php

namespace app\admin\service;

use app\admin\model\AnchorSearchModel;

class AnchorSearchService
{
    protected static $instance;

    protected $pagenum = 20;
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addOrUpdate($data, $where = [])
    {
        if (empty($where)) {
            $res = AnchorSearchModel::getInstance()->getModel()->insertAll($data);
        } else {
            $res = AnchorSearchModel::getInstance()->getModel()->where([$where])->update($data);
        }
        return $res;
    }

    //推广渠道列表
    public function getList($page, $where)
    {
        $offset = ($page - 1) * $this->pagenum;
        $count = AnchorSearchModel::getInstance()->getModel()->where($where)->count();
        $list = AnchorSearchModel::getInstance()->getModel()
            ->where($where)
            ->order('id desc')
            ->limit($offset, $this->pagenum)
            ->select()
            ->toArray();
        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $this->pagenum);
        return ['page_array' => $page_array, 'list' => $list];
    }

    public function checkAnchorExists($where, $is_throw = false)
    {
        //校验用户是否存在
        $res = AnchorSearchModel::getInstance()->getModel()->where($where)->findorEmpty()->toArray();

        if ($res && $is_throw) {
            throw new \Exception("主播已存在", 500);
        }

        return $res;
    }

    public function checkAnchorNotExists($where, $is_throw = false)
    {
        //校验用户是否存在
        $res = AnchorSearchModel::getInstance()->getModel()->where($where)->find();
        if ($is_throw && !$res) {
            throw new \Exception("主播不存在", 500);
        }
        return $res;
    }
}

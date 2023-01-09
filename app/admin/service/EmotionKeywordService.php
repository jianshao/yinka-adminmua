<?php

namespace app\admin\service;

use app\admin\model\AdminUserModel;
use app\admin\model\EmotionKeywordModel;

class EmotionKeywordService
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
            $res = EmotionKeywordModel::getInstance()->getModel()->insertAll($data);
        } else {
            $res = EmotionKeywordModel::getInstance()->getModel()->where([$where])->update($data);
        }
        return $res;
    }

    //推广渠道列表
    public function getList($page, $where)
    {
        $offset = ($page - 1) * $this->pagenum;
        $count = EmotionKeywordModel::getInstance()->getModel()->where($where)->count();
        $list = EmotionKeywordModel::getInstance()->getModel()
            ->where($where)
            ->order('id desc')
            ->limit($offset, $this->pagenum)
            ->select()
            ->toArray();

        if ($list) {

            $admin_user_info = AdminUserModel::getInstance()->getAdminList([['id', 'in', array_column($list, 'admin_id')]], 'id,username');

            $admin_user_map = array_column($admin_user_info, null, 'id');

            foreach ($list as $_ => &$item) {
                $item['admin_username'] = isset($admin_user_map[$item['admin_id']]) ? $admin_user_map[$item['admin_id']]['username'] : '';
            }
        }

        $page_array['page'] = $page;
        $page_array['total_page'] = ceil($count / $this->pagenum);
        return ['page_array' => $page_array, 'list' => $list];
    }
}

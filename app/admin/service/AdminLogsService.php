<?php

namespace app\admin\service;

use app\admin\model\AdminLogsModel;

class AdminLogsService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AdminLogsService();
        }
        return self::$instance;
    }

    public function addAdminLogs(array $data)
    {
        return AdminLogsModel::getInstance()->getModel()->insert($data);
    }

    public function getAdminLogsList(array $where, $field = '*', array $limit)
    {
        return AdminLogsModel::getInstance()->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->select()->toArray();
    }

    public function getAdminLogsCountNum(array $where)
    {
        return AdminLogsModel::getInstance()->getModel()->where($where)->count();
    }
}

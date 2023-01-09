<?php

namespace app\admin\service;

use app\admin\model\UserAssetLogModel;

class UserAssetLogService
{
    /**根据时间获取表
     * @param $id
     * @param $field
     * @return mixed
     */
    public function getUserAssetLogTable()
    {
        $table = $this->getTable();
        return UserAssetLogModel::getInstance()->setTable($table);
    }

    public function getTable()
    {
        return;
    }
}
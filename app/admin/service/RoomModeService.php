<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */
namespace app\admin\service;

use app\admin\model\RoomModeModel;
use think\Log;
use think\Exception;


class RoomModeService extends RoomModeModel{

    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new RoomModeService();
        }
        return self::$instance;
    }

    /**根据id获取字段值
     * @return mixed
     */
    public function getList($type){
        $res = RoomModeModel::getInstance()->getList($type);
        return $res;
    }

    


}
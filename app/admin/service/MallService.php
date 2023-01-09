<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */
namespace app\admin\service;

use app\admin\model\GiftModel;

class MallService extends GiftModel
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}

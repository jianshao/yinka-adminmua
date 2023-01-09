<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */
namespace app\admin\service;

use app\admin\model\GiftModel;
use app\admin\script\analysis\GiftsCommon;

class GiftService extends GiftModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GiftService();
        }
        return self::$instance;
    }

    /**根据id获取字段值
     * @param $id
     * @param $field
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $res = GiftModel::getInstance()->getOneById($id, $field);
        return $res;
    }

    public function checkGiftExists($gift_id)
    {
        $gifts = GiftsCommon::getInstance()->getGifts();

        if (!isset($gifts[$gift_id])) {
            throw new \Exception("礼物ID不存在", 500);
        }
    }
}
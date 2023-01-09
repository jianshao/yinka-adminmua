<?php
namespace app\admin\script\analysis;

use app\admin\model\ConfigModel;

class GiftsCommon
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

    public function getGiftMap()
    {
        $gift_conf = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json');
        $data = json_decode($gift_conf, true);
        foreach ($data as $k => $v) {
            $gift['id'] = $v['giftId'];
            $gift['gift_name'] = $v['name'];
            $gift["gift_image"] = $v['image'];
            $gift["gift_animation"] = $v['animation'];
            $gift['gift_coin'] = (int) $v['price']['count'];
            $res[$k] = $gift;
        }
        return $res;
    }

    public function getGifts()
    {
        return array_column($this->getGiftMap(), 'gift_name', 'id');
    }

    public function giftMapList()
    {
        return array_column($this->getGiftMap(), null, 'id');
    }

    public function checkGiftIdExists($giftId)
    {
        $gifts = array_keys($this->getGifts());

        if (in_array($giftId, $gifts)) {
            return true;
        }
        return false;
    }
}
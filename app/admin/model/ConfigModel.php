<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class ConfigModel extends ModelDao
{
    protected $table = 'zb_config';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ConfigModel();
        }
        return self::$instance;
    }

    public function getConf($key)
    {
        return $this->getModel()->where('name', $key)->value('json');
    }

    public function getGiftList()
    {
        $res = [];
        $data = json_decode($this->getConf('gift_conf'), true);
        foreach ($data as $k => $gift) {
            $arr['id'] = $gift['giftId'];
            $arr['gift_name'] = $gift['name'];
            $arr["gift_image"] = $gift['image'];
            $arr["gift_animation"] = $gift['animation'];
            $arr['gift_coin'] = (int) $gift['price']['count'];
            $res[$k] = $arr;
        }
        return $res;
    }

    public function getPropList()
    {
        $res = [];
        $data = json_decode($this->getConf('prop_conf'), true);
        foreach ($data as $k => $prop) {
            $arr['id'] = $prop['kindId'];
            $arr['name'] = $prop['name'];
            $arr["image"] = $prop['image'];
            $arr["type"] = $prop['type'];
            $res[$k] = $arr;
        }
        return $res;
    }

    public function getPropMap()
    {
        return array_column($this->getPropList(), 'name', 'id');
    }

    public function getPropTypeList()
    {
        $res = [];
        $data = $this->getPropList();

        foreach ($data as $key => $prop) {
            $res[$prop['type']][] = $prop;
        }
        return $res;
    }

    public function getMallTypes()
    {
        $type = array_keys($this->getPropTypeList());
        return [
            "avatar" => '头像框',
            "bubble" => '气泡框',
            "mount" => '可穿戴',
            "voiceprint" => '麦位光圈',
        ];
    }
}

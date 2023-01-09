<?php

namespace app\admin\service;

use app\admin\model\ConfigModel;
use app\common\RedisCommon;

class HomePageConfService
{
    protected static $instance;
    protected $conf_key = 'bottom_menu_conf';
    protected $bottom_menu_conf = [];
    protected $poolsMap = [];

    public function __construct()
    {
        $this->bottom_menu_conf = $this->getConfObj();

        $pools = $this->checkObjKey($this->bottom_menu_conf, 'pools');

        $this->poolsMap = array_column($pools, null, 'poolId');
    }

    public function checkObjKey(object $conf, $key)
    {
        return isset($conf->$key) ? $conf->$key : [];
    }

    public function checkArrKey(array $conf, $key)
    {
        return isset($conf[$key]) ? $conf[$key] : [];
    }

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    protected function getConfObj()
    {
        $this->bottom_menu_conf = ConfigModel::getInstance()->getModel()->where('name', $this->conf_key)->value('json');
        return json_decode($this->bottom_menu_conf);
    }

    public function saveConf($json)
    {
        return ConfigModel::getInstance()->getModel()->where('name', $this->conf_key)->update(['json' => $json]);
    }

    public function getConf()
    {
        return $this->bottom_menu_conf;
    }

    /*
     *配置图片
     */
    public function update(array $data)
    {
        $app = $data['app'];
        $type_name = $data['type_name'];

        foreach ($data as $column => $v) {
            if (in_array($column, ["desc", "click_icon", "default_icon", "click_lott", "default_lott", 'default_font_color', 'click_font_color']) && $v != null) {
                $this->bottom_menu_conf->$app->$type_name->$column = $v;
            }
        }
        return $this->saveConf(json_encode($this->getConf()));
    }

    /*
     * 新增图片
     */
    public function add(array $data)
    {
        $app = $data['app'];
        $type_name = $data['type_name'];

        if (isset($this->bottom_menu_conf->$app->$type_name)) {
            throw new \Exception("当前类型已存在，请进行修改");
        }

        foreach ($data as $column => $v) {
            $this->bottom_menu_conf->$app->$type_name->$column = $v;
        }
        return $this->saveConf(json_encode($this->getConf()));
    }

    public function clearCache($operatorId)
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', $this->conf_key)->value('json');

        $data = ['conf' => $json, 'operatorId' => $operatorId];
        return $this->setRedis($json);
    }

    public function setRedis($json)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        return $redis->set($this->conf_key, urldecode($json));
    }
}

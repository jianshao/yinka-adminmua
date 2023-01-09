<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class CategoryModel extends ModelDao
{
    protected $table = 'zb_mall_category';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例s
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getFirstCategory()
    {
        return $this->getModel()->where('status', 1)->where('pid', 0)->select()->toArray();
    }
    public function getTwoCategory()
    {
        $res = $this->getModel()->where('status', 1)->where('pid', '>', 0)->select()->toArray();

        $data = [];
        foreach ($res as $key => $category) {
            $data[$category['pid']][] = $category;
        }
        return $data;
    }
}

<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class WeeksConfigModel extends ModelDao
{
    protected $table = 'zb_weeks_config';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new WeeksConfigModel();
        }
        return self::$instance;
    }

    public function insertWeeksConfig($data)
    {
        return $this->getModel()->insert($data);
    }

    public function saveWeeksConfig($where, $data)
    {
        return $this->getModel()->where($where)->save($data);
    }
}

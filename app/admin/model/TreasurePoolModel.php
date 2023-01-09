<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class TreasurePoolModel extends ModelDao
{
    protected $table = 'zb_treasure_pool';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TreasurePoolModel();
        }
        return self::$instance;
    }
    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setTask($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
}

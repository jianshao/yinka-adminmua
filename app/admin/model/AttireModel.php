<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class AttireModel extends ModelDao
{
    protected $table = 'zb_attire';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AttireModel();
        }
        return self::$instance;
    }
    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setAttire($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }
    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setGift($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }
}

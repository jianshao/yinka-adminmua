<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class GivePackModel extends ModelDao
{
    protected $table = 'yyht_give_pack';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GivePackModel();
        }
        return self::$instance;
    }

    public function givePropByWhere($where)
    {
        return $this->getModel()->where($where)->select()->toArray();
    }
    public function givePropAdd($data)
    {
        return $this->getModel()->save($data);
    }
    public function givePropExid($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
}

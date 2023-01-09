<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BoxGiftModel extends ModelDao
{
    protected $table = 'zb_box_gift';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BoxGiftModel();
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
    /*
     * 查询礼物列表
     */
    public function BoxGift($where)
    {
        return $this->alias('a')->field('a.*,b.gift_name')->join('zb_gift b', 'a.giftid = b.id')->where($where)->order('a.id', 'asc')->count();
    }
}

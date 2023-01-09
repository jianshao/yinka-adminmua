<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class GiftModel extends ModelDao
{
    protected $table = 'zb_gift';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GiftModel();
        }
        return self::$instance;
    }

    /*
     * 查询礼物列表
     */
    public function giftList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }

    /*
     * 根据id获取该字段值
     * @param $where
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $where['id'] = $id;
        return $this->getModel()->where($where)->field($field)->find();
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
    /*
     * 添加方法
     */
    public function addGifts($data)
    {
        return $this->getModel()->save($data);
    }

    /*
     * 查询礼物列表
     */
    public static function giftKeyList()
    {
        $data = self::select()->toArray();
        return array_column($data, null, 'id');
    }

}

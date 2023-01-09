<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */

namespace app\admin\model;

use app\core\mysql\ModelDao;

class BannerModel extends ModelDao
{
    protected $table = 'zb_banner';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BannerModel();
        }
        return self::$instance;
    }

    /**获取广告列表
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed
     */
    public function BannerList($where, $offset, $limit, string $where_row)
    {
        if ($where_row) {
            return $this->getModel()->where($where)->whereRaw($where_row)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
        }
        return $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }

    /*
     * 总条数
     */
    public function count($where, string $where_row)
    {
        if ($where_row) {
            return $this->getModel()->where($where)->whereRaw($where_row)->count();
        }
        return $this->getModel()->where($where)->count();
    }

    /**根据id获取该字段值
     * @param $where
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $where['id'] = $id;
        return $this->getModel()->where($where)->value($field);
    }

    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setBanner($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }

    /*
     * 添加
     */
    public function addBanner($data)
    {
        return $this->getModel()->save($data);
    }

}

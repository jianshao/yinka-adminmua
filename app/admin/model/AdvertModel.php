<?php
/**
 * User: li
 * Date: 2019
 * 广告表
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class AdvertModel extends ModelDao
{

    protected $table = 'zb_advert';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AdvertModel();
        }
        return self::$instance;
    }

    /**获取所有广告位数据
     * @return array
     */
    public function getList($where, $offset, $limit)
    {
        $res = $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

}

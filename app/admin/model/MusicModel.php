<?php
/**
 * @author ly
 * 音乐上传
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class MusicModel extends ModelDao
{
    protected $table = 'zb_member_song';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MusicModel();
        }
        return self::$instance;
    }

    /**查询上传音乐列表
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回值
     */
    public function getList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }

}

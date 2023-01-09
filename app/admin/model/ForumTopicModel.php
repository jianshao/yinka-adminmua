<?php
/**
 * @author ly
 * 公告表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class ForumTopicModel extends ModelDao
{
    protected $table = 'zb_forum_topic';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ForumTopicModel();
        }
        return self::$instance;
    }
    /*
     * 查询总条数
     */
    public function getCount($where)
    {
        return $this->getModel()->where($where)->count();
    }
    /*
     * 查询话题列表
     */
    public function tagList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->limit($offset, $limit)->order('pid', 'desc')->select()->toArray();
    }
    /*
     * 根据条件查询一条
     */
    public function getBYwhere($where)
    {
        return $this->getModel()->where($where)->find();
    }
    /*
     * 根据条件查询多条
     */
    public function getLabel()
    {
        return $this->getModel()->where(array('pid' => 0))->field('id,topic_name')->select()->toArray();
    }
    /*
     * 修改方法
     */
    public function exitLabel($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
    /*
     * 添加方法
     */
    public function addLabel($data)
    {
        return $this->getModel()->save($data);
    }

}

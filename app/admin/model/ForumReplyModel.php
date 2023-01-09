<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class ForumReplyModel extends ModelDao
{
    protected $table = 'zb_forum_reply';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ForumReplyModel();
        }
        return self::$instance;
    }

    /**
     * 查询某条贴子的所有评论
     * @param $where where条件
     * @param $limit limit条数
     * @return array
     */
    public function getForumReplayList($where, $field, $limit, $order)
    {
        $res = $this->getModel()->where($where)->field($field)->limit($limit[0], $limit[1])->order($order[0], $order[1])->select()->toArray();
        if (empty($res)) {
            return [];
        }
        return $res;
    }

    public function getOneById($where)
    {
        $res = $this->getModel()->where($where)->find();
        if ($res) {
            return $res->toArray();
        } else {
            return array();
        }
    }

    public function setForumReply($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }

}

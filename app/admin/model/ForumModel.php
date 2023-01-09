<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */

namespace app\admin\model;

use app\core\mysql\ModelDao;

class ForumModel extends ModelDao
{
    protected $table = 'zb_forum';
    protected $pk = 'admin_id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ForumModel();
        }
        return self::$instance;
    }

    /**
     * 查询贴
     * @param $admin_id array
     * @return array
     */
    public function getForumListPage($where, $field, $limit, $order)
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

    public function setForum($where, $data)
    {
        return $this->getModel()->where($where)->save($data);
    }

    public function updateById($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }

}

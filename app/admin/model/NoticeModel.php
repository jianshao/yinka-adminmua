<?php
/**
 * @author ly
 * 公告表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class NoticeModel extends ModelDao
{
    protected $table = 'yyht_notice';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new NoticeModel();
        }
        return self::$instance;
    }

    //详细列表
    public function noticeListOne($where)
    {
        return $this->getModel()->where($where)->findOrEmpty()->toArray();
    }
    //列表
    public function noticeList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }
    /*
     * 添加方法
     */
    public function addNotice($data)
    {
        return $this->getModel()->save($data);
    }

    public function insertNoticeGetId($data)
    {
        return $this->getModel()->insertGetId($data);
    }

    public function editNotice(array $where, array $data)
    {
        return $this->getModel()->where($where)->save($data);
    }
    /*
     * 修改方法
     */
    public function exitNotice($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
    /*
     * 根据ID查询字段
     */
    public function getById($where)
    {
        return $this->getModel()->where($where)->findOrEmpty()->toArray();
    }
}
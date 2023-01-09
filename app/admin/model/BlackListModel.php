<?php
/**
 * Created by PhpStorm.
 * User: sh
 * Date: 2019/7/24
 * Time: 14:37
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class BlackListModel extends ModelDao
{

    protected $table = 'zb_black_list';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BlackListModel();
        }
        return self::$instance;
    }

    /**列表
     * @param $where    where条件
     * @param $offset   分页
     * @param $limit    条数
     * @return mixed
     */
    public function getList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }
    /*
     * 根据条件查询用户存在
     */
    public function selectBan($uid)
    {
        $where = ['uid' => $uid];
        return $this->getModel()->where($where)->find();
    }
    /*
     * 查询用户ID和声网ID是否存在
     */
    public function checkSw($uid, $kick_id)
    {
        $where = ['uid' => $uid, 'kick_id' => $kick_id];
        return $this->getModel()->where($where)->find();
    }
    /**
     * 查询封禁列表
     * @param $admin_id array
     * @return array
     */
    public function getBlackListPage($offset, $limit)
    {
//        $where['status'] = array('in','1,2');
        return $this->order('id', 'desc')->limit($offset, $limit)->select()->toArray();
    }

}

<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */

namespace app\admin\model;

use app\core\mysql\ModelDao;

class MemberModel extends ModelDao
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'userSlave';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberModel();
        }
        return self::$instance;
    }

    /*统计当前数据
     * @param $where
     */
    public function count($where)
    {
        return $this->getModel()->where($where)->count();
    }

    public function getCount($where, $uid)
    {
        return $this->getModel($uid)->where($where, $uid)->count();
    }

    /*获取所有的用户列表接口
     * @param $where    where条件
     * @param $limit    limit条数
     * @return mixed    返回类型
     */
    public function getList($where, $limit)
    {
        $field = "id,user_id,nickname,phone,proportionally,diamond,free_diamond";
        return $this->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->select()->toArray();
//        echo $this->getModel()->_Sql(); die();
    }

    /*根据id获取该字段值
     * @param $where
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $where['id'] = $id;
        return $this->getModel($id)->where($where)->field($field)->find();
    }

    public function getFieldValueById($id, $field)
    {
        $where['id'] = $id;
        return $this->getModel($id)->where($where)->value($field);
    }

    /*
     * 查询部分用户信息
     */
    public function fieldFind($id, $field)
    {
        $where = ['id' => $id];
        $res = $this->getModel()->field($field)->where($where)->find();
        if (empty($res)) {
            $where = ['pretty_id' => $id];
            $res = $this->getModel()->field($field)->where($where)->find();
        }
        return $res;
    }

    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setMember($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }

    /*
     * 查询用户角色信息
     */
    public function userRole($id)
    {
        $where = ['id' => $id];
        return $this->getModel()->field('id,role')->where($where)->find();
    }

    /*获取用户列表数据
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回值
     */
    public function getMembberListPage($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->limit($offset, $limit)->select()->toArray();
    }

    /*
     * 查询用户的靓号信息
     */
    public function getPretty($uid)
    {
        $where = ['id' => $uid];
        return $this->getModel()->field('pretty_id')->where($where)->find();
    }

    /*
     * 根据靓号查看用户的ID
     */
    public function prettyUser($user_id)
    {
        $where = ['pretty_id' => $user_id];
        return $this->getModel()->field('id,pretty_id')->where($where)->find();
    }

    /*
     * 根据条件查询用户信息
     */
    public function getWhereInfo($where, $field)
    {
        return $this->getModel()->where($where)->field($field)->find();
    }

    public function getMemberList(array $where = [], string $field = '*')
    {
        return $this->getModel()->field($field)->where($where)->select()->toArray();
    }

    public function memberQuery($sql = '')
    {
        return $this->getModel()->query($sql);
    }

    //获取配置来获取所有的model
    public function getallModel()
    {
        $shareconfig = config("shard");
        $count = count($shareconfig['userSlave']);
        $key = [];
        for ($i = 0; $i < $count; $i++) {
            $key[] = $i;
        }
        return $this->getModels($key);
    }

    public function getMemberListPage($where, $offset, $limit, $uid = 0)
    {
        $data = [];
        if ($where) {
            $models = $this->getAllModels();

            foreach ($models as $model) {
                $res = $model->getModel($uid)->where($where)->select()->toArray();
                $data = array_merge($res, $data);
            }
        }
        return $data;
    }

    public function getAllModels()
    {
        $shareconfig = config("shard");
        $count = count($shareconfig[$this->serviceName]);
        $key = [];
        for ($i = 0; $i < $count; $i++) {
            $key[] = $i;
        }
        return $this->getModels($key);
    }
}
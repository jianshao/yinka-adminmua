<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class MemberSocityModel extends ModelDao
{
    protected $table = 'zb_member_socity';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberSocityModel();
        }
        return self::$instance;
    }

    /**统计分会成员当前数据
     * @param $where
     */
    public function count($where)
    {
        return $this->getModel()->where($where)->count();
    }

    public function getListNew($where, $limit)
    {
        return $this->getModel()->alias('s')
            ->field(['s.user_id', 's.guild_id', 's.socity', 's.addtime'])
            ->where($where)
            ->limit($limit[0], $limit[1])
            ->select()
            ->toArray();
    }

    /**通过id获取某一个值
     * @param $user_id  用户id
     * @return mixed
     */
    public function find($user_id)
    {
        $where['user_id'] = $user_id;
        $where['status'] = 1;
        return $this->getModel()->where($where)->find();
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

    /*
     * 根据条件查询成员是否存在
     */
    public function getUser($id)
    {
        $where[] = ['user_id', '=', $id];
        $where[] = ['status', '=', 1];
        return $this->getModel()->field('user_id')->where($where)->find();
    }
    /*
     * 根据公会ID查询公会人员
     */
    public function getSocityUser($guild_id)
    {
        $where = ['guild_id' => $guild_id];
        return $this->getModel()->where($where)->select()->toArray();
    }

    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setSocity($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }
    /*
     * 根据条件查询
     */
    public function getByWhere($where, $field)
    {
        return $this->getModel()->where($where)->field($field)->find();
    }

}

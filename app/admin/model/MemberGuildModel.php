<?php
/**
 * @author ly
 * 帖子表
 * $date 2019
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class MemberGuildModel extends ModelDao
{
    protected $table = 'zb_member_guild';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberGuildModel();
        }
        return self::$instance;
    }

    /**统计当前数据
     * @param $where
     */
    public function getCount($where)
    {
        return $this->getModel()->where($where)->count();
    }

    /**查询公会数据
     * @param $where
     */
    public function getghData($where, $field = "*")
    {
        $res = $this->getModel()->field($field)->where($where)->select();
        if (!empty($res)) {
            return $res->toArray();
        }
        return [];
    }

    /**通过用户id去查找
     * @param $user_id
     * @return mixed
     */
    public function find($user_id)
    {
        $where['user_id'] = $user_id;
        return $this->getModel()->where($where)->find();
    }

    /**获取所有的公会列表接口
     * @param $where    where条件
     * @param $limit    limit条数
     * @return mixed    返回类型
     */
    public function getList($where, $limit)
    {
        $field = "id,user_id,nickname,phone,proportionally,diamond,free_diamond,status,logo_url";
        return $this->getModel()->field($field)->where($where)->limit($limit[0], $limit[1])->order('id desc')->select()->toArray();
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

    public function getOne($id)
    {
        $where['id'] = $id;
        return $this->getModel()->where($where)->find();
    }
    /*
     * 查询用户是否存在
     */
    public function isUser($id)
    {
        $where = ['user_id' => $id];
        return $this->getModel()->field('id,user_id')->where($where)->find();
    }
    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setGuild($where, $data)
    {
        return $this->getModel()->where($where)->update($data);

    }
    /*
     * 查询公会分成比例信息
     */
    public function proportionallyInfo($guild_id)
    {
        $where = ['id' => $guild_id];
        return $this->getModel()->field('proportionally')->where($where)->findOrEmpty()->toArray();
    }

    public function getGuildListMap()
    {
        $guild_list = MemberGuildModel::getInstance()->getWhereAllData([['id', '>', 0]], 'id,nickname');
        return array_column($guild_list, null, 'id');
    }
}

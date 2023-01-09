<?php
/**
 * @author yond
 * 用户表
 * $date 2019
 */
namespace app\common\model;

use think\Model;


class MemberModel extends Model
{
    protected $table = 'zb_member';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MemberModel();
        }
        return self::$instance;
    }

    //查询单条
    public function getOne($where)
    {
        $res = $this->where($where)->find();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

    //查询多条
    public function getList($where)
    {
        $res = $this->where($where)->select();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }



    /**统计当前数据
     * @param $where
     */
    public function count($where){
        return $this->where($where)->count();
    }

    /**获取所有的用户列表id为索引
     * @param $where    where条件
     */
    public function getMemberListNoPage($where,$field='*'){
        $ret = $this->where($where)->field($field)->select();
    	if($ret){
            $res = [];
            foreach ($ret->toArray() as $key => $value) {
            	$res[$value['id']] = $value['avatar'];
            }
            return $res;
        }else{
            return array();
        }
    }

    /**根据id获取该字段值
     * @param $where
     * @return mixed
     */
    public function getOneById($id,$field)
    {
        $where['id'] = $id;
        $res = $this->where($where)->field($field)->find();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }

    /*
     * 查询部分用户信息
     */
    public function fieldFind($id,$field){
        $where = ['id'=>$id];
        $res = $this->field($field)->where($where)->find();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }

    /**更新方法
     * @param $where    where条件
     * @param $data     更新的数据值
     * @return mixed
     */
    public function setMember($where,$data)
    {
        return $this->where($where)->update($data);

    }

    /*
    * 查询用户角色信息
    */
    public function userRole($id){
        $where = ['id'=>$id];
        $res = $this->field('id,role')->where($where)->find();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }

    /**获取用户列表数据
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回值
     */
    public function getMembberListPage($where,$offset,$limit){
        $res = $this->where($where)->limit($offset,$limit)->select();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }

    /**根据where条件去查询对应字段的信息数据
     * @param $where    where条件
     * @param $field    数据表字段值
     * @return mixed    返回类型
     */
    public function getTypeInfo($where,$field){
        return $this->field($field)->where($where)->find();
        /*if(!$res){
            return [];
        }
        return $res->toArray();*/
    }

    /**根据用户查询所有用户信息数据
     * @param $user_id  用户id
     * @return mixed    返回类型
     */
    public function getIdInfo($user_id){
        $where['id'] = $user_id;
        $res = $this->where($where)->find();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }

    /*
     * 查询用户信息
     */
    public function find($where){
        $res = $this->where($where)->find();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }





}
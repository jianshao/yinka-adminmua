<?php
/**
 * User: li
 * Date: 2019
 * 动态标签及话题数据表
 */
namespace app\common\model;
use think\Model;

class ForumTopicModel extends Model{

    protected $table = 'zb_forum_topic';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ForumTopicModel();
        }
        return self::$instance;
    }

    /**获取所有动态数据
     * @return array
     */
	public function getList($where){
        $field = "id,pid,topic_name as tag_name,topic_status as tag_status,topic_order as tag_order,topic_hot as tag_hot,topic_recommend as tag_recommend";
		$res = $this->field($field)->where($where)->order('id desc')->select();
		if(!$res){
			return [];
		}
		return $res->toArray();
	}

    /**获取所有动态话题标签数据
     * @return array
     */
    public function getTagList($where){
        $res = $this->where($where)->select();
        if(!$res){
            return [];
        }
        return $res->toArray();
    }




}
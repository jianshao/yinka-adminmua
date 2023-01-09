<?php
/**
 * @author ly
 * 帖子操作
 * $date 2019
 */

namespace app\admin\service;

use app\admin\model\ForumReplyModel;

class ForumReplyService extends ForumReplyModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ForumReplyService();
        }
        return self::$instance;
    }

    /*根据帖子id获取评论
     * @param $where    where条件
     * @param $limit    limit条件
     * @return mixed
     */
    public function getList($forum_id, $page, $pagenum)
    {
        $limit = [$page, $pagenum];
        $where = [['reply_status', '<>', 3], ['forum_id', '=', $forum_id]];
        $field = 'id as reply_id,forum_id,reply_content,reply_uid,reply_type,createtime';
        $order = ['createtime', 'desc'];
        $res = ForumReplyModel::getInstance()->getForumReplayList($where, $field, $limit, $order);
        if ($res) {
            foreach ($res as $key => $value) {
                $res[$key]['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
            }
        }
        return $res;
    }

    /*统计当前帖子的评论数
     * @param $where    where条件
     * @return mixed    返回值
     */
    public function getCount($where)
    {
        $res = ForumReplyModel::getInstance()->getModel()->where($where)->count();
        return $res;
    }

}

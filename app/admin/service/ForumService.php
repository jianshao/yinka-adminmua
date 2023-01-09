<?php
/**
 * @author ly
 * 帖子操作
 * $date 2019
 */

namespace app\admin\service;

use app\admin\model\ForumModel;
use think\facade\Config;

class ForumService extends ForumModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ForumService();
        }
        return self::$instance;
    }

    public function getList($page, $pagenum, $where)
    {
        $url = config('config.APP_URL_image');
        $limit = [$page, $pagenum];
        // $where = [['forum_status', 'not in', '3,4']];
        $field = 'id as forum_id,forum_uid,createtime,updatetime,examined_time,forum_content,forum_image,forum_voice,tid';
        $order = ['examined_time', 'desc'];
        $res = ForumModel::getInstance()->getForumListPage($where, $field, $limit, $order);
        if ($res) {
            foreach ($res as $key => $value) {
                if ($value['forum_image']) {
                    $imageArr = explode(',', $value['forum_image']);
                    foreach ($imageArr as $k => &$v) {
                        $v = $v ? $url . '/' . $v : '';
                    }
                    $res[$key]['forum_image'] = $imageArr;
                }
                if ($value['forum_voice']) {
                    $res[$key]['forum_voice'] = $value['forum_voice'] ? $url . '/' . $value['forum_voice'] : '';
                }
                $res[$key]['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
                $res[$key]['updatetime'] = date('Y-m-d H:i:s', $value['updatetime']);
                $res[$key]['examined_time'] = $value['examined_time'] == 0 ? '' : date('Y-m-d H:i:s', $value['examined_time']);
            }
        }
        return $res;
    }

    public function getAuditList($page, $pagenum)
    {
        $url = config('config.APP_URL_image');
        $limit = [$page, $pagenum];
        $where = [['forum_status', '=', '3']];
        $field = 'id as forum_id,forum_uid,createtime,forum_content,forum_image,forum_voice,forum_status';
        $order = ['createtime', 'desc'];
        $res = ForumModel::getInstance()->getForumListPage($where, $field, $limit, $order);
        if ($res) {
            foreach ($res as $key => $value) {
                if ($value['forum_image']) {
                    $imageArr = explode(',', $value['forum_image']);
                    foreach ($imageArr as $k => &$v) {
                        $v = $v ? $url . '/' . $v : '';
                    }
                    $res[$key]['forum_image'] = $imageArr;
                }
                if ($value['forum_voice']) {
                    $res[$key]['forum_voice'] = $value['forum_voice'] ? $url . '/' . $value['forum_voice'] : '';
                }
                $res[$key]['createtime'] = date('Y-m-d H:i:s', $value['createtime']);
            }
        }
        return $res;
    }

    public function getForumCountNum($where)
    {
        $res = ForumModel::getInstance()->getModel()->where($where)->count();
        return $res;
    }

    public function getForumListByWhere(array $where, $field = '*')
    {
        return ForumModel::getInstance()->getModel()->field($field)->where($where)->select()->toArray();
    }

}

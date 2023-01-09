<?php
/**
 * User: li
 * Date: 2019
 * 金币
 */

namespace app\admin\model;

use app\core\mysql\ModelDao;

class CheckImMessageModel extends ModelDao
{

    //protected $table = 'zb_check_im_message';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    const TYPEMAP = [
        "0" => "文本消息",
        "1" => "图片消息",
        "2" => "语音消息",
        "3" => "视频消息",
        "4" => "位置消息",
        "5" => "文件消息",
        "6" => "提示消息",
        "7" => "自定义消息",
        "8" => "闪萌表情",
    ];

    const STATUSMAP = [
        "1" => "发送成功",
        "2" => "检测失败",
        "3" => "信息限制",
        "4" => "撤回",
    ];

    //单例
    public static function getInstance($part = '')
    {
        if ($part) {
            $table = "zb_check_im_message_" . $part;
        } else {
            $table = "zb_check_im_message";
        }

        if (!isset(self::$instance) || self::$instance->table != $table) {
            self::$instance = new self();
            self::$instance->table = $table;
        }
        return self::$instance;
    }

}

<?php
/**
 * User: li
 * Date: 2019
 * 金币
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiUserRoomChatModel extends ModelDao
{

    protected $table = 'bi_user_room_chat';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    //单例
    public static function getInstance($part = '')
    {
        if ($part) {
            $table = "bi_user_room_chat_" . $part;
        } else {
            $table = "bi_user_room_chat";
        }

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->table = $table;
        return self::$instance;
    }

}

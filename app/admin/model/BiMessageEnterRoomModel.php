<?php
/**
 * User: li
 * Date: 2019
 * 金币
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class BiMessageEnterRoomModel extends ModelDao
{

    protected $table = 'bi_message_enter_room';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    //单例
    public static function getInstance($part = '')
    {
        if ($part) {
            $table = "bi_message_enter_room_" . $part;
        } else {
            $table = "bi_message_enter_room";
        }

        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->table = $table;
        return self::$instance;
    }

}

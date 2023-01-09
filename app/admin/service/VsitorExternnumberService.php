<?php

namespace app\admin\service;

use app\admin\model\VsitorExternnumberModel;
use app\common\RedisCommon;
use think\facade\Log;

class VsitorExternnumberService extends VsitorExternnumberModel
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new VsitorExternnumberService();
        }
        return self::$instance;
    }

    public function getLists(array $where, $field = '')
    {
        return VsitorExternnumberModel::getInstance()->getModel()->field($field)->where($where)->select()->toArray();
    }

    public function addVsitorExternnumber(array $data)
    {
        return VsitorExternnumberModel::getInstance()->getModel()->insertGetId($data);
    }

    public function editVsitorExternnumber(array $where, array $data)
    {
        return VsitorExternnumberModel::getInstance()->getModel()->where($where)->save($data);
    }

    /**判断当前房间是C还是派对,更新热门值(且手动添加的热度值也在变化)
     * @param $room_id      房间id
     * @param $number       房间热度值
     */
    public function saveRoomNumber($room_id, $number)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $guildRedisKey = 'guild_room_hot:' . $room_id;
        $redis->hset($guildRedisKey, 'orignal', (int) $number);
        //发送消息
        $str = ['msgId' => 2031, 'VisitorNum' => 0];
        $msg['msg'] = json_encode($str);
        $msg['roomId'] = (int) $room_id;
        $msg['toUserId'] = '0';
        $socket_url = config('config.socket_url');
        $msgData = json_encode($msg);
        $res = curlData($socket_url, $msgData, 'POST', 'json');
        Log::record("房间热度值消息发送参数-----" . $msgData, "info");
        Log::record("房间热度值消息发送-----" . $res, "info");
    }
}

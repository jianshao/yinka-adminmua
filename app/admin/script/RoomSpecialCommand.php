<?php


namespace app\admin\script;

use app\admin\model\LanguageroomModel;
use app\admin\model\RoomHideModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Lang;
use think\facade\Log;

ini_set('set_time_limit', 0);

class RoomSpecialCommand extends Command
{

    private $room_key = "regist_roomid";
    private $room_key_save = "regist_roomid_push";
    private $handler;
    //asa相关信息
    const ASAROOMTEMPSAVE = "puton_room_come_temp";
    const ASAROOMSAVE = "puton:come_room:type:";
    const CACHE_KEY = "%s:%s";


    //渠道包相关信息
    const CHANNELROOMTEMPSAVE = "channel_puton_room_come_temp";
    const CHANNELROOMSAVE = "channel_puton:come_room:type:";


    protected function configure()
    {
        $this->setName('RoomSpecialCommand')->setDescription('RoomSpecialCommand');

    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        try {

            $this->roomSpecial();
            $this->asaRoomCome();
            $this->channelRoomCome();

        } catch (\Throwable $e) {
            Lang::error("roomspecialcommand:error:" . $e->getMessage());
        }

    }


    //房间推荐老的
    public function roomSpecial()
    {
        $this->getRedis();
        $res = $this->handler->hGetAll($this->room_key_save);
        Log::info("RoomSpecialCommand:res=" . json_encode($res));
        $currentTimestamp = time();
        foreach ($res as $key => $items) {
            $params = json_decode($items, true);
            if (strtotime($params['end_time']) < $currentTimestamp) {
                $this->handler->hDel($this->room_key_save, $key);
                if ($this->handler->sIsMember($this->room_key, $key)) {
                    $this->handler->sRem($this->room_key, $key);
                }
            } elseif (strtotime($params['end_time']) > $currentTimestamp && strtotime($params['begin_time']) < $currentTimestamp) {
                if (!$this->handler->sIsMember($this->room_key, $key)) {
                    $this->handler->sAdd($this->room_key, $key);
                }
            }
        }
    }


    //asa或者其他买量推广进入房间
    public function asaRoomCome()
    {
        $this->getRedis();
        $res = $this->handler->hGetAll(SELF::ASAROOMTEMPSAVE);
        Log::info("RoomSpecialCommand:asa:res=" . json_encode($res));
        $currentTimestamp = time();
        foreach ($res as $items) {
            $params = json_decode($items, true);
            $room_id = $params["room_id"];
            $type = $params["type"];
            if (strtotime($params['end_time']) < $currentTimestamp) {
                $haskey = sprintf("%s:%s:%s:%s", $room_id, $type,strtotime($params['begin_time']),strtotime($params['end_time']));
                $this->handler->hDel(self::ASAROOMTEMPSAVE, $haskey);
                if ($this->handler->sIsMember(self::ASAROOMSAVE . $type, $room_id)) {
                    $this->handler->sRem(self::ASAROOMSAVE . $type, $room_id);
                }
            } elseif (strtotime($params['end_time']) > $currentTimestamp && strtotime($params['begin_time']) < $currentTimestamp) {
                if (!$this->handler->sIsMember(self::ASAROOMSAVE . $type, $room_id)) {
                    $this->handler->sAdd(self::ASAROOMSAVE . $type, $room_id);
                }
            }
        }
    }


    //渠道包推广进入房间
    public function channelRoomCome()
    {
        $this->getRedis();
        $res = $this->handler->hGetAll(SELF::CHANNELROOMTEMPSAVE);
        Log::info("roomspecialcommand:channel:res=" . json_encode($res));
        $currentTimestamp = time();
        foreach ($res as $items) {
            $params = json_decode($items, true);
            $room_id = $params["room_id"];
            $type = $params["type"];
            if (strtotime($params['end_time']) < $currentTimestamp) {
                $haskey = sprintf(self::CACHE_KEY, $room_id, $type);
                $this->handler->hDel(self::CHANNELROOMTEMPSAVE, $haskey);
                if ($this->handler->sIsMember(self::CHANNELROOMSAVE . $type, $room_id)) {
                    $this->handler->sRem(self::CHANNELROOMSAVE . $type, $room_id);
                }
            } elseif (strtotime($params['end_time']) > $currentTimestamp && strtotime($params['begin_time']) < $currentTimestamp) {
                if (!$this->handler->sIsMember(self::CHANNELROOMSAVE . $type, $room_id)) {
                    $this->handler->sAdd(self::CHANNELROOMSAVE . $type, $room_id);
                }
            }
        }
    }

    protected function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.redis');
        $param['host'] = $redis_result['host'];
        $param['port'] = $redis_result['port'];
        $param['password'] = $redis_result['password'];
        $param['select'] = 0;
        if (!empty($arr)) {
            foreach ($arr as $v => $v) {
                $param[$v] = $v;
            }
        }

        $this->handler = new \Redis;
        $this->handler->connect($param['host'], $param['port'], 0);
        if ('' != $param['password']) {
            $this->handler->auth($param['password']);
        }

        if (0 != $param['select']) {
            $this->handler->select($param['select']);
        }
        return $this->handler;
    }


}

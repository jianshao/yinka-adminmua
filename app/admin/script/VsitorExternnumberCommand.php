<?php

namespace app\admin\script;

use app\admin\model\LanguageroomModel;
use app\admin\model\VsitorExternnumberModel;
use app\admin\service\VsitorExternnumberService;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);

class VsitorExternnumberCommand extends Command
{
    protected function configure()
    {
        $this->setName('VsitorExternnumberCommand')->setDescription('VsitorExternnumberCommand');
    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        $list = VsitorExternnumberModel::getInstance()->getModel()->field('id,room_id,visitor_externnumber,created_user,status,start_time,end_time')->where('status', 'in', [1, 2])->select()->toArray();
        if (!empty($list)) {
            Log::info('房间热度值脚本开始:');
            foreach ($list as $v) {
                $id = $v['id']; //热度id
                $room_id = $v['room_id']; //房间id
                $visitor_externnumber = (int) $v['visitor_externnumber']; //热度值

                $start_time = $v['start_time'];
                $end_time = $v['end_time'];

                try {
                    //VsitorExternnumberService::getInstance()->getModel()->startTrans();

                    if ($end_time < time()) {
                        VsitorExternnumberService::getInstance()->editVsitorExternnumber(array(['id', '=', $id], ['status', '<>', 3]), ['status' => 3]);
                        LanguageroomModel::getInstance()->getModel($room_id)->where(array('id' => $room_id))->update(array('visitor_externnumber' => 0));
                        //LanguageroomModel::getInstance()->setRoom(array('id' => $room_id), array('visitor_externnumber' => 0));
                        VsitorExternnumberService::getInstance()->saveRoomNumber($room_id, 0);
                    } else if ($end_time >= time() && $start_time < time()) {
                        VsitorExternnumberService::getInstance()->editVsitorExternnumber(array(['id', '=', $id], ['status', '<>', 3]), ['status' => 2]);
                        VsitorExternnumberService::getInstance()->saveRoomNumber($room_id, $visitor_externnumber);
                    }

                    //VsitorExternnumberService::getInstance()->getModel()->commit();
                } catch (\Exception $e) {
                    echo $e->getMessage().$e->getLine();
                    Log::info("vsitorexternnumbercommand:error:".$e->getMessage());
                    //VsitorExternnumberService::getInstance()->rollback();
                }
            }
        }
    }

    /**判断当前房间是C还是派对,更新热门值(且手动添加的热度值也在变化)
     * @param $room_id      房间id
     * @param $number       房间热度值
     */
    private function _saveRoomNumber($room_id, $number)
    {
        VsitorExternnumberService::getInstance()->saveRoomNumber($room_id, $number);
    }

    public function getVisitorNumber($room_id, $visitor_externnumber)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $guildRedisKey = 'guild_room_hot:' . $room_id;
        $nowNumber = $redis->hGet($guildRedisKey, 'orignal');
        if ($nowNumber > $visitor_externnumber) {
            $number = -$visitor_externnumber;
        } else {
            $number = -$nowNumber;
        }
        return $number;
    }
}

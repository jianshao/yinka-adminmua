<?php

namespace app\admin\script;

use app\admin\model\BlackDataModel;
use app\admin\service\CurlApiService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class MemberBlackCommand extends Command
{
    protected function configure()
    {
        $this->setName('MemberBlackCommand')->setDescription('MemberBlackCommand');
    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        Log::record('用户解封开始:', 'MemberBlackCommand');
        $time = time();
        $where[] = ['type', '=', 4];
        $where[] = ['status', '=', 1];
        $where[] = ['time', '>', 0];
        $arr = BlackDataModel::getInstance()->memberBlackList($where);
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                if ($v['update_time'] + $v['time'] <= $time) { //解封
                    $res = BlackDataModel::getInstance()->updateBlackData(['blackinfo' => $v['user_id'], 'status' => 1], ['status' => 0, 'time' => 0, 'end_time' => 0, 'blacks_time' => 0, 'reason' => '封停时间结束']);
                    CurlApiService::getInstance()->blockUserNotice($v['user_id'], 0);
                    if ($res) {
                        Log::record('用户解封:' . json_encode([['blackinfo' => $v['user_id'], 'status' => 1], ['status' => 0, 'time' => 0, 'reason' => '封停时间结束']]), 'MemberBlackCommand');
                    }
                }
            }
        }

    }
}

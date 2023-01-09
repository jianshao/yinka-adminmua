<?php
/**
 * 三人夺宝
 */

namespace app\admin\script;

use app\admin\model\ActiveModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class ActivityCommand extends Command
{
    /**
     * 入库表
     */
    protected const TABLE_INSERT = '';

    protected function configure()
    {
        $this->setName('ActivityCommand')
            ->setDescription('ActivityCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $activity_list = ActiveModel::getInstance()->getModel()
            ->whereIn('active_status', [0, 1])
            ->select()
            ->toArray();

        if ($activity_list) {
            foreach ($activity_list as $_ => $activity) {
                $data = [];
                if ($activity['end_time'] < time()) {
                    //活动结束
                    $data = ['updated_user' => 'admin', 'updated_time' => time(), 'active_status' => 2];
                } elseif ($activity['active_status'] == 0 && $activity['start_time'] > time()) {
                    //活动开始
                    $data = ['updated_user' => 'admin', 'updated_time' => time(), 'active_status' => 1];
                }

                if ($data) {
                    ActiveModel::getInstance()->getModel()->where('id', $activity['id'])->update($data);
                }
            }
        }
    }
}

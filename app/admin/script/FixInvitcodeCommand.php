<?php
/**
 * 三人夺宝
 */

namespace app\admin\script;

use app\admin\model\BiUserStats1DayModel;
use app\admin\model\BiUserStats5MinsModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\admin\script\analysis\AnalysisCommon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use Throwable;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class FixInvitcodeCommand extends Command
{
    protected $date;
    protected $time;
    protected $next_day = [2, 3, 4];

    protected $invitcodes_map = [];
    /**
     * 入库表
     */
    protected const TABLE_INSERT = '';

    protected function configure()
    {
        $this->setName('FixInvitcodeCommand')
            ->setDescription('FixInvitcodeCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '');
        $marketchannelModel = MarketChannelModel::getInstance()->getModel();
        $count = $marketchannelModel->count();
        if (count($this->invitcodes_map) != $count) {
            $this->invitcodes_map = $marketchannelModel->where('invitcode', '<>', '')->column('id', 'invitcode');
        }

        $this->date = date("Y-m-d");
        $this->time = date("Y-m-d H:i:s");
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $start = trim($input->getArgument('start'));
        $end = trim($input->getArgument('end'));

        list($start, $end, $days) = AnalysisCommon::getInstance()->getStartEndDate($start, $end, $this->next_day);

        for ($i = 0; $i < $days; $i++) {
            $start_date = date("Y-m-d", strtotime("{$start} + {$i}days"));
            $end_date = date("Y-m-d", strtotime("{$start_date} + 1days"));
            echo "开始日期：{$start_date}-----结束日期：{$end_date}" . PHP_EOL;

            $this->dealTodayData($start_date, $end_date, $end);
        }

        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;
    }

    /**
     *处理每日推广数据
     */
    protected function dealTodayData($start, $end)
    {
        $userData = $this->getBindInvitcodeUsers($start, $end);
        foreach ($userData as $uid) {
            $this->calData($uid);
        }
    }

    /*
     * 登陆数据
     */
    protected function getUserInfo($uid)
    {
        return MemberModel::getInstance()->getModel($uid)->where('id', $uid)->value('invitcode');
    }

    protected function calData($uid)
    {
        try {
            echo "处理用户ID:" . $uid . PHP_EOL;
            $invitcode = $this->getUserInfo($uid);
            $invitcodes_map = $this->invitcodes_map;
            //更新基础数据渠道信息
            if (in_array($invitcode, array_keys($invitcodes_map))) {
                BiUserStats5MinsModel::getInstance()->getModel()->where('uid', $uid)->update(['promote_channel' => $invitcodes_map[$invitcode]]);
                BiUserStats1DayModel::getInstance()->getModel()->where('uid', $uid)->update(['promote_channel' => $invitcodes_map[$invitcode]]);
            }
        } catch (Throwable $e) {
            Log::error(sprintf('VipHandler::calUserDiamond ex=%d:%s trace=%s', $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
    }

    public function getFiveMinutesUsers($start, $end)
    {
        return BiUserStats1DayModel::getInstance()->getModel()
            ->where('date', '>=', $start)
            ->where('date', '<', $end)
            ->where('promote_channel', 0)
            ->distinct('uid', true)
            ->column('uid');
    }

    public function getBindInvitcodeUsers($start, $end)
    {
        $models = MemberModel::getInstance()->getallModel();
        $res = [];
        foreach ($models as $model) {
            $data = $model->getModel()->where('register_time', '>=', $start)
                ->where('register_time', '<', $end)
                ->where('invitcode', 'in', array_keys($this->invitcodes_map))
                ->column('id');

            $res = array_merge($data, $res);
        }
        return $res;
    }
}

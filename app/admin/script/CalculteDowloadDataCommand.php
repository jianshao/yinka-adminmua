<?php
/**
 * 三人夺宝
 */

namespace app\admin\script;

use app\admin\model\AdServingModel;
use app\admin\model\BiDaysDownloadStatsByDealerModel;
use app\admin\model\BiUserStats1DayModel;
use app\admin\model\MemberModel;
use app\admin\script\analysis\AnalysisCommon;
use app\admin\script\analysis\InsertOrUpdateStats;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;
use Throwable;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class CalculteDowloadDataCommand extends Command
{
    protected $date;
    protected $time;
    protected $next_day = [2];

    /**
     * 入库表
     */
    protected const TABLE_INSERT = 'bi_days_download_stats_by_dealer';

    protected function configure()
    {
        $this->setName('CalcultePromoteCodeRoomDataCommand')
            ->setDescription('CalcultePromoteCodeRoomDataCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '');

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

    protected function calData($start, $end, $dealer)
    {
        try {
            //下载量
            $downloads = $this->getDownload($start, $end, $dealer);
            $where = $condition = [];
            $where[] = ["register_time",">=",$start];
            $where[] = ["register_time","<",$end];
            $where[] = ["idfa","<>",""];
            $where[] = ["idfa","<>","00000000-0000-0000-0000-000000000000"];
            $memberList  = MemberModel::getInstance()->getWhereAllData($where,"idfa,id");
            $idfas = array_column($memberList,"idfa");
            $condition[]=["idfa","in",$idfas];
            $condition[]=["source","=",$dealer];
            $condition[]=["status","=",1];
            $reg_count = AdServingModel::getInstance()->getWhereCount($condition);
            $insert_data = [
                'date' => $start,
                'dealer' => $dealer,
                'download_count' => count($downloads),
                'reg_count' => $reg_count,
            ];
            $model = BiDaysDownloadStatsByDealerModel::getInstance()->getModel();
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($model,[$insert_data],['date','dealer','id']);
        } catch (Throwable $e) {
            Log::error(sprintf('VipHandler::calUserDiamond ex=%d:%s trace=%s', $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
    }

    /**
     *处理每日推广数据
     */
    protected function dealTodayData($start, $end)
    {
        $sourceData = $this->getSource($start, $end);
        foreach ($sourceData as $source) {
            $this->calData($start, $end, $source);
        }
    }

    public function getDownload($start, $end, $dealer)
    {
        return AdServingModel::getInstance()->getModel()
            ->where('created_time', '>=', strtotime($start))
            ->where('created_time', '<', strtotime($end))
            ->where('source', $dealer)
            ->column('idfa');
    }

    public function getSource($start, $end)
    {
        return AdServingModel::getInstance()->getModel()
            ->where('created_time', '>=', strtotime($start))
            ->where('created_time', '<', strtotime($end))
            ->distinct('source', true)
            ->column('source');
    }
}
<?php
/**
 * 老的砸蛋日统计
 */

namespace app\admin\script;

use app\admin\model\UserAssetLogModel;
use app\admin\service\ElasticsearchService;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);

class SyncLogToEsCommand extends Command
{
    protected $date;
    protected $time;
    protected $sync_id;

    const LIMIT = 10000;
    const SYNCKEY = 27;
    const TABLE = 'es_zb_user_asset_log';

    const SECONDS_MINUTE_5 = 5 * 60;
    const CALCULATE_NEXT_DAY = [3, 4]; //隔天统计昨天的数据

    protected function configure()
    {
        $this->setName('SyncLogToEsCommand')
            ->setDescription('SyncLogToEsCommand')
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
        try {
            $this->dealTodayData();
        } catch (\Throwable $th) {
            echo "start======>" . $this->time . PHP_EOL;
            echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;
            Log::info("start======>" . $this->time);
            Log::info("finish======>" . date("Y-m-d H:i:s"));
        }
    }

    /**
     *按照时间处理每日数据
     */
    protected function dealTodayData()
    {
        //获取处理的时间节点
        $max_id = UserAssetLogModel::getInstance()->getModel()->max('id');

        //更新初始ID
        $this->sync_id = Db::table('sync_data_conf')->where('id', SELF::SYNCKEY)->value('sync_id');

        if (!$this->sync_id) {
            $this->sync_id = UserAssetLogModel::getInstance()->getModel()->min('id');
            Db::table('sync_data_conf')->where('id', SELF::SYNCKEY)->update(['sync_id' => (int) $this->sync_id]);
        }

        while ($max_id >= $this->sync_id) {
            echo "start======>" . microtime() . PHP_EOL;

            $sync_info = Db::table('sync_data_conf')->where('id', SELF::SYNCKEY)->field('sync_id,id,ext_1')->find();
            $this->sync_id = Db::table('sync_data_conf')->where('id', SELF::SYNCKEY)->value('sync_id');

            //处理ES数据
            $this->dealEsData($this->sync_id, $sync_info);
            echo "finish======>" . microtime() . PHP_EOL;
        }
    }

    public function dealEsData($sync_id, $sync_info)
    {
        echo '>>>>>开始处理ID：' . $sync_id . PHP_EOL;
        //获取数据
        $data = $this->getAssetLog($sync_id);

        if ($data) {
            $max_id = max(array_column($data, 'id'));
        }

        $body = [];
        foreach ($data as $item) {
            $body[] = ['index' => ['_index' => self::TABLE, '_id' => md5(md5(json_encode($item))) . '-' . $item['id']]];
            $body[] = $item;
        }

        $res = ElasticsearchService::getInstance()->bulkData(self::TABLE, $body);

        if ($res['errors'] !== false) {
            throw new \Exception("写入ES失败========>写入ID：{$item['id']}", 500);
        }
        $update_data = ['sync_id' => ($max_id + 1)];
        Log::debug('>>>>>更新同步数据' . json_encode($update_data));
        Db::table('sync_data_conf')->where('id', SELF::SYNCKEY)->update($update_data);
    }

    /*
     * 获取每日金流分页数据
     */
    public function getAssetLog($sync_id, $offset = self::LIMIT)
    {
        Log::debug('>>>>>获取查询数据条件' . json_encode([['id', '>=', $sync_id], ['id', '<', $sync_id + $offset]]));
        //消耗
        return UserAssetLogModel::getInstance()->getModel()
            ->where(
                [
                    ['id', '>=', $sync_id],
                    ['id', '<', $sync_id + $offset],
                ]
            )
            ->order('id asc')->select()->toArray();
    }

    /*
     * 获取每日金流分页数据
     */
    public function getAssetLogCount($start, $end)
    {
        $table = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);
        //消耗
        return UserAssetLogModel::getInstance($table)->getModel()
            ->where(
                [
                    ['success_time', '>=', $start],
                    ['success_time', '<', $end],
                ]
            )
            ->field('*')
            ->order('id asc')
            ->select()
            ->toArray();
    }
}
<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\model\BiDaysUserGiftDatasModel;
use app\admin\model\UserAssetLogModel;
use app\admin\script\analysis\AnalysisCommon;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class CalculateUserGetAndSendGiftsCommand extends Command
{
    protected $date;
    protected $time;
    protected $table_name;

    const LIMIT = 3000;
    const BOX = ['consumer_amount' => 4, 'reward_amount' => 3];
    const BOX_PRICE = ['consumer_amount' => false, 'reward_amount' => true];

    protected $duke_user_value = 'duke_user_value'; //祈福奖励榜前缀

    protected function configure()
    {
        $this->setName('CalculateUserGetAndSendGiftsCommand')
            ->setDescription('CalculateUserGetAndSendGiftsCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '')
            ->addArgument('status', Argument::OPTIONAL, "status", '');

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
        $status = trim($input->getArgument('status'));

        $end_time = $end;

        list($start, $end) = AnalysisCommon::getInstance()->getStartEndDate($start, $end, [3, 6]);

        $days = ceil(round((strtotime($end) - strtotime($start)) / 3600 / 24));

        $days = max($days, 1);
        for ($i = 0; $i < $days; $i++) {
            $start_date = date("Y-m-d", strtotime("{$start} + {$i}days"));
            $end_date = date("Y-m-d", strtotime("{$start_date} + 1days"));
            echo "开始日期：{$start_date}-----结束日期：{$end_date}" . PHP_EOL;
            $this->date = $start_date;
            if (empty($status)) {
                $this->table_name = getTable($start_date, $end_date);
                $this->dealTodayData($start_date, $end_date);
            } else {
                $this->table_name = getTable($start_date, $end_time);
                $this->dealTodayData($start_date, $end_time);
            }

        }
        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;

    }

    /**
     *处理每日数据
     */
    protected function dealTodayData($start, $end)
    {
        //财富榜
        $this->dealUserAssetSendGiftLog($start, $end);

        //魅力榜
        $this->dealUserAssetGetGiftLog($start, $end);
    }

    public function insert($data, $date, $type)
    {
        $res = [];
        foreach ($data as $uid => $item) {
            foreach ($item as $gift_id => $columns) {
                $amount = $columns['amount'];
                $count = $columns['count'];

                echo "uid=>{$uid}date=>{$date},gift_id=>{$gift_id},type=>{$type},amount=>{$amount},count=>{$count}" . PHP_EOL;
                $insertArr = [
                    'uid' => $uid,
                    'gift_id' => $gift_id,
                    'date' => $date,
                    'type' => $type,
                    'amount' => $amount,
                    'count' => $count,
                ];
                array_push($res, $insertArr);
            }
        }

        foreach ($res as $item) {
            $this->insertOrUpdate($item);
        }

    }
    public function getStartEndDate($start = '', $end = '')
    {
        if ($start && $end) {
            $start_date = date("Y-m-d", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m-d", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m-d", strtotime($start));
            $end_date = date("Y-m-d", strtotime("{$start} + 1days"));
        } elseif (time() - strtotime(date("Y-m-d")) == 30 * 60) {
            //每天凌晨30分钟统计前一天的数值
            $start_date = date("Y-m-d", strtotime("-1days"));
            $end_date = date("Y-m-d");
        } else {
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d", strtotime("+1days"));
        }

        return [$start_date, $end_date];
    }

    protected function calData($data, &$res = [])
    {
        if ($data) {
            foreach ($data as $key => $item) {
                echo "calData=====>{$item['uid']},gift_id=====>{$item['gift_id']},change_amount=====>{$item['change_amount']}" . PHP_EOL;
                if (!isset($res[$item['uid']]["{$item['gift_id']}:{$item['room_id']}"])) {
                    $res[$item['uid']]["{$item['gift_id']}:{$item['room_id']}"]['amount'] = (int) $item['change_amount'];
                    $res[$item['uid']]["{$item['gift_id']}:{$item['room_id']}"]['count'] = (int) $item['count'];
                } else {
                    $res[$item['uid']]["{$item['gift_id']}:{$item['room_id']}"]['amount'] += (int) $item['change_amount'];
                    $res[$item['uid']]["{$item['gift_id']}:{$item['room_id']}"]['count'] += (int) $item['count'];
                }
            }
        }
    }

    //财富榜
    public function dealUserAssetSendGiftLog($start, $end)
    {
        // $field = 'uid,room_id,case when type = 4 then abs(change_amount) else ext_4 end change_amount,ext_1 as gift_id,ext_3 as count';
        $field = 'uid,room_id,ext_4 as change_amount,ext_2 as gift_id,ext_3 as count';
        $where = [
            ['event_id', '=', 10002],
            ['type', 'in', '3,4'],
            ['success_time', '>=', strtotime($start)],
            ['success_time', '<', strtotime($end)],
        ];
        $data = [];
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start,$end);
        $this->calculate($where, $field, $data,$instance);

        $this->insert($data, $start, 1);
    }

    //魅力榜
    public function dealUserAssetGetGiftLog($start, $end, &$data = [])
    {
        $field = 'uid,room_id,abs(ext_4) as change_amount,ext_2 as gift_id,ext_3 as count';
        $where = [
            ['event_id', '=', 10003],
            ['success_time', '>=', strtotime($start)],
            ['success_time', '<', strtotime($end)],
        ];

        $data = [];
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start,$end);
        $this->calculate($where, $field, $data,$instance);
        $this->insert($data, $start, 2);
    }

    public function calculate($where, $field, &$data,$instance='')
    {
        $uids = UserAssetLogModel::getInstance($instance)->getuids($where);
        $models = UserAssetLogModel::getInstance($instance)->getModels($uids);
        foreach($models as $model){
            $count = $model->getModel()->where($where)->where("uid","in",$model->getList())->count();
            $page = ceil($count / self::LIMIT);
            for ($i = 0; $i < $page; $i++) {
                $offset = $i * self::LIMIT;
                $res = $model->getModel()->where($where)->where("uid","in",$model->getList())->field($field)->limit($offset, self::LIMIT)
                    ->select()->toArray();
                $this->calData($res, $data);
            }
        }

    }


    /**
     *更新同步表数据ID
     */
    protected function insertOrUpdate($data)
    {
        $date = $data['date'];
        $uid = (int) $data['uid'];
        $type = $data['type'];
        $amount = (int) $data['amount'];
        $count = (int) $data['count'];
        $arr = explode(':', $data['gift_id']);
        $gift_id = (int) $arr[0];
        $room_id = (int) $arr[1];
        $updateData  = [
            'date' => $date,
            'uid' => $uid,
            'type' => $type,
            'amount' => $amount,
            'count' => $count,
            'gift_id' => $gift_id,
            'room_id' => $room_id,
        ];

        $model = BiDaysUserGiftDatasModel::getInstance()->getModel();
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($model,[$updateData],['date','uid','type','gift_id','room_id']);
    }

}

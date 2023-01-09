<?php

namespace app\admin\script;

use app\admin\model\BiDaysUserTurntableDatasModel;
use app\admin\model\ConfigModel;
use app\admin\model\GiftModel;
use app\admin\model\UserAssetLogModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 265);

class TurntableOutputCommand extends Command
{
    protected $date;
    protected $time;
    protected static $event_id = 10009;
    protected static $type = ['产出' => 3, '消耗' => 2];
    protected static $ext_1 = 'turntable';
    protected static $ext_2 = ['小转盘' => 1, '大转盘' => 2];

    const LIMIT = 1000;

    protected function configure()
    {
        $this->setName('TurntableOutputCommand')
            ->setDescription('TurntableOutputCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '');

        $this->date = date("Y-m-d");
        $this->time = date("Y-m-d H:i:s");

        $giftconf = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json');
        $gifts = json_decode($giftconf, true);
        $this->gift_map = array_column($gifts, 'gift_coin', 'id');
    }

    /**
     *执行
     */
    protected function execute(Input $input, Output $output)
    {
        $start = trim($input->getArgument('start'));
        $end = trim($input->getArgument('end'));

        list($start, $end) = $this->getStartEndDate($start, $end);

        $days = round((strtotime($end) - strtotime($start)) / 3600 / 24);

        for ($i = 0; $i < $days; $i++) {
            $start_date = date("Y-m-d", strtotime("{$start} + {$i}days"));
            $end_date = date("Y-m-d", strtotime("{$start_date} + 1days"));
            echo "开始日期：{$start_date}-----结束日期：{$end_date}" . PHP_EOL;
            $this->date = $start_date;
            $this->dealTodayData($start_date, $end_date, $end);
        }
        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;

    }

    /**
     *处理每日数据
     */
    protected function dealTodayData($start, $end)
    {
        $users = $this->getTurntableUsers($start, $end);

        foreach ($users as $item) {

            $uid = $item['uid'];
            $turntable_data['date'] = $start;
            $turntable_data['uid'] = $uid;
            $small_consume_amount = $this->dealUserTurntableConsumeLog($start, $end, self::$ext_2['小转盘'], $uid);
            $in_consume_amount = $this->dealUserTurntableConsumeLog($start, $end, self::$ext_2['大转盘'], $uid);

            $turntable_data['in_consume_amount'] = $in_consume_amount;
            $turntable_data['small_consume_amount'] = $small_consume_amount;

            $in_output_amount = $small_output_amount = 0;

            $this->dealUserTurntableOutputLog($start, $end, $uid, self::$ext_2['大转盘'], $in_output_amount);
            $turntable_data['in_output_amount'] = $in_output_amount;

            $this->dealUserTurntableOutputLog($start, $end, $uid, self::$ext_2['小转盘'], $small_output_amount);
            $turntable_data['small_output_amount'] = $small_output_amount;

            echo "date=>{$start},uid=>{$item['uid']}" . PHP_EOL;

            $this->updateOrInsertSyncId($turntable_data);
        }
    }

    public function getStartEndDate($start = '', $end = '')
    {
        $date = time() - strtotime(date("Y-m-d"));

        if ($start && $end) {
            $start_date = date("Y-m-d", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m-d", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m-d", strtotime($start));
            $end_date = date("Y-m-d", strtotime("{$start} + 1days"));
        } elseif ($date >= 0 && $date <= 1 * 30) {
            //每天凌晨30分钟统计前一天的数值
            $start_date = date("Y-m-d", strtotime("-1days"));
            $end_date = date("Y-m-d");
        } else {
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d", strtotime("+1days"));
        }
        return [$start_date, $end_date];
    }

    protected function calData($data, &$output)
    {
        if ($data) {
            foreach ($data as $key => $item) {
                $output += (int)$item['ext_4'];
            }
        }
    }

    /*
     * 当日砸蛋的用户
     */
    public function getTurntableUsers($start, $end)
    {
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start,$end);
        $assetLogModel = UserAssetLogModel::getInstance($instance);
        //获取所有的模型
        $models = $assetLogModel->getallModel();
        $returnRes = [];
        foreach($models as $model){
           $data =  $model->getModel()->where([
                ['event_id', '=', self::$event_id],
                ['type', '=', self::$type['消耗']],
                ['ext_1', '=', self::$ext_1],
                ['success_time', '>=', strtotime($start)],
                ['success_time', '<', strtotime($end)],
            ])->field('distinct(uid) as uid')->select()->toArray();
            $returnRes = array_merge($data,$returnRes);
        }
        return $returnRes;
    }

    public function dealUserTurntableConsumeLog($start, $end, $ext_2, $uid)
    {
        $where = [
            ['event_id', '=', self::$event_id],
            ['type', '=', self::$type['消耗']],
            ['uid', '=', $uid],
            ['ext_1', '=', self::$ext_1],
            ['ext_2', '=', $ext_2],
            ['success_time', '>=', strtotime($start)],
            ['success_time', '<', strtotime($end)],
        ];
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start,$end);
        $res = UserAssetLogModel::getInstance($instance)->getModel($uid)->where($where)->value('sum(abs(change_amount))');
        return $res;
    }

    public function dealUserTurntableOutputLog($start, $end, $uid, $ext_2, &$data)
    {
        $where = [
            ['event_id', '=', self::$event_id],
            ['type', '=', self::$type['产出']],
            ['uid', '=', $uid],
            ['ext_1', '=', self::$ext_1],
            ['ext_2', '=', $ext_2],
            ['success_time', '>=', strtotime($start)],
            ['success_time', '<', strtotime($end)],
        ];
        $instance = "";

        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start,$end);
        $count = UserAssetLogModel::getInstance($instance)->getModel($uid)->where($where)->count();

        $page = ceil($count / self::LIMIT);

        for ($i = 0; $i < $page; $i++) {
            $offset = $i * self::LIMIT;
            $gold_reward = UserAssetLogModel::getInstance($instance)->getModel($uid)->where($where)
                ->field('uid, ext_4')
                ->limit($offset, self::LIMIT)->select()->toArray();
            $this->calData($gold_reward, $data);
        }
    }

    /**
     *更新同步表数据ID
     */
    protected function updateOrInsertSyncId($data)
    {
        $date = $data['date'];
        $uid = $data['uid'];
        $in_consume_amount = (int)$data['in_consume_amount'];
        $in_output_amount = (int)$data['in_output_amount'];
        $small_consume_amount = (int)$data['small_consume_amount'];
        $small_output_amount = (int)$data['small_output_amount'];
        $data = [
            "in_consume_amount" => $in_consume_amount,
            "in_output_amount" => $in_output_amount,
            "small_consume_amount" => $small_consume_amount,
            "small_output_amount" => $small_output_amount,
            "uid" => $uid,
            "date" => $date,
        ];
        $model = BiDaysUserTurntableDatasModel::getInstance()->getModel();
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($model,[$data],["date","id","uid"]);
        //$sql = "insert into bi_days_user_turntable_datas (date, uid, in_consume_amount, in_output_amount, small_consume_amount, small_output_amount) values ('{$date}',{$uid},{$in_consume_amount},{$in_output_amount},{$small_consume_amount},{$small_output_amount}) on duplicate key update in_consume_amount = {$in_consume_amount},in_output_amount = {$in_output_amount},small_consume_amount = {$small_consume_amount},small_output_amount = {$small_output_amount};";
        //Db::execute($sql);
    }

}

<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\model\BiDaysRoomDatasBysendTypeModel;
use app\admin\model\BiDaysUserGiftDatasBysendTypeModel;
use app\admin\model\BiDaysUserSendgiftModel;
use app\admin\model\ConfigModel;
use app\admin\model\GiftModel;
use app\admin\model\UserAssetLogModel;
use app\admin\script\analysis\AnalysisCommon;
use app\admin\service\ConfigService;
use app\admin\service\RoomConsumeService;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use http\Client\Curl\User;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class CalculateUserGetAndSendGiftsByGiftTypeCommand extends Command
{
    protected $date;
    protected $time;
    protected $gift_map;
    protected $table_name;

    const LIMIT = 3000;

    protected $sendgift_map = [
        'SENDGIFT_其他' => 0,
        'SENDGIFT_直送' => 1,
        'SENDGIFT_背包送礼' => 2,
        'SENDGIFT_礼物盒子' => 3,
        'SENDGIFT_小火锅' => 4,
    ];
    protected $duke_user_value = 'duke_user_value'; //祈福奖励榜前缀

    protected function configure()
    {
        $this->setName('CalculateUserGetAndSendGiftsByGiftTypeCommand')
            ->setDescription('CalculateUserGetAndSendGiftsByGiftTypeCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '')
            ->addArgument('status', Argument::OPTIONAL, "status", '');

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
        $status = trim($input->getArgument('status'));

        $end_time = $end;

        list($start, $end) = AnalysisCommon::getInstance()->getStartEndDate($start, $end, [3, 6]);
        $days = ceil(round((strtotime($end) - strtotime($start)) / 3600 / 24));
        $days = max($days, 1);


        try {
            for ($i = 0; $i < $days; $i++) {
                $start_date = date("Y-m-d", strtotime("{$start} + {$i}days"));
                $end_date = date("Y-m-d", strtotime("{$start_date} + 1days"));
                //echo "开始日期：{$start_date}-----结束日期：{$end_date}" . PHP_EOL;
                $this->date = $start_date;
                $this->table_name = getTable($start_date, $end_date);
                if (empty($status)) {
                    $this->dealTodayData($start_date, $end_date);
                } else {
                    $this->dealTodayData($start_date, $end_time);
                }

                //房间消费汇总
                RoomConsumeService::getInstance()->handler($start_date,$end_date);

            }
            echo "start======>" . $this->time . PHP_EOL;
            echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;
        } catch (\Throwable $e) {
            dump($e->getMessage().$e->getFile().$e->getLine());
            Log::info("calculateusergetandsendgiftsbygifttypecommand:error:".$e->getMessage().":getline=".$e->getLine());
        }


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
        $room_data = [];
        foreach ($data as $uid => $item) {
            foreach ($item as $gift_id => $columns) {
                $reward_amount = $columns['reward_amount'];
                $consume_amount = $columns['consume_amount'];
                $count = $columns['count'];

                echo "uid=>{$uid}date=>{$date},gift_id=>{$gift_id},type=>{$type},amount=>{$reward_amount},count=>{$count}" . PHP_EOL;
                $insertArr = [
                    'uid' => $uid,
                    'gift_id' => $gift_id,
                    'date' => $date,
                    'type' => $type,
                    'consume_amount' => $consume_amount,
                    'reward_amount' => $reward_amount,
                    'count' => $count,
                ];
                array_push($res, $insertArr);

                $arr = explode(':', $gift_id);
                $room_id = (int)$arr[1];
                $send_type = (int)$arr[2];
                $room_key = $room_id . ':' . $send_type;

                if (!isset($room_data[$room_key])) {
                    $room_data[$room_key]['consume_amount'] = $consume_amount;
                    $room_data[$room_key]['reward_amount'] = $reward_amount;
                    $room_data[$room_key]['count'] = $count;
                } else {
                    $room_data[$room_key]['consume_amount'] += $consume_amount;
                    $room_data[$room_key]['reward_amount'] += $reward_amount;
                    $room_data[$room_key]['count'] += $count;
                }
            }
        }

        foreach ($res as $item) {
            $this->insertOrUpdate($item);
        }

        foreach ($room_data as $room_key => $item) {
            $this->insertOrUpdateRoomData($room_key, $item, $date, $type);
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
                $send_type = $this->sendgift_map['SENDGIFT_其他'];
                if ($item['ext_1'] == '395') {
                    //小火锅
                    $send_type = $this->sendgift_map['SENDGIFT_小火锅'];
                } elseif ($item['ext_1'] == '376') {
                    //礼物盒子
                    $send_type = $this->sendgift_map['SENDGIFT_礼物盒子'];
                } else {
                    if ($item['type'] == 4) {
                        //直送
                        $send_type = $this->sendgift_map['SENDGIFT_直送'];
                    } elseif ($item['type'] == 3) {
                        //背包送礼
                        $send_type = $this->sendgift_map['SENDGIFT_背包送礼'];
                    }
                }
                echo "uid=====>{$item['uid']},ext_1=====>{$item['ext_1']},ext_2=====>{$item['ext_2']},send_type=====>{$send_type},gift_id=====>{$item['gift_id']},reward_amount=====>{$item['reward_amount']}" . PHP_EOL;
                $this->getData($send_type, $item, $res);
            }
        }
    }

    protected function getData($send_type, $item, &$res)
    {
        $cal_key = "{$item['gift_id']}:{$item['room_id']}:{$send_type}";
        if (!isset($res[$item['uid']][$cal_key])) {
            $res[$item['uid']][$cal_key]['reward_amount'] = (int)$item['reward_amount'];
            $res[$item['uid']][$cal_key]['consume_amount'] = (int)$item['consume_amount'];
            $res[$item['uid']][$cal_key]['count'] = (int)$item['count'];
        } else {
            $res[$item['uid']][$cal_key]['reward_amount'] += (int)$item['reward_amount'];
            $res[$item['uid']][$cal_key]['consume_amount'] += (int)$item['consume_amount'];
            $res[$item['uid']][$cal_key]['count'] += (int)$item['count'];
        }
    }

    //财富榜
    public function dealUserAssetSendGiftLog($start, $end)
    {
        // $field = 'uid,room_id,case when type = 4 then abs(change_amount) else ext_4 end reward_amount,ext_1 as gift_id,ext_3 as count';
        $field = 'uid,room_id,type,ext_1,ext_2,case when type = 4 then abs(change_amount) else ext_4 end as consume_amount,ext_4 as reward_amount,ext_2 as gift_id,ext_3 as count,touid,success_time';
        $where = [
            ['event_id', '=', 10002],
            ['type', 'in', '3,4'],
            ['success_time', '>=', strtotime($start)],
            ['success_time', '<', strtotime($end)],
        ];
        $data = [];
        $usertouser = [];
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);
        $this->calculate($where, $field, $data,$instance,$usertouser);
        $this->insert($data, $start, 1);
        $usertouserChunk = array_chunk($usertouser,500);
        $usersendgiftModel = BiDaysUserSendgiftModel::getInstance()->getModel();
        foreach($usertouserChunk as $chunkitem){
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($usersendgiftModel,$chunkitem,["date","uid","touid","id"]);
        }

    }

    //魅力榜
    public function dealUserAssetGetGiftLog($start, $end, &$data = [])
    {
        $field = 'touid as uid,room_id,type,ext_1,ext_2,abs(change_amount) as consume_amount,abs(ext_4) as reward_amount,ext_2 as gift_id,ext_3 as count';
        $where = [
            ['event_id', '=', 10002],
            ['type', '<>', 7],
            ['success_time', '>=', strtotime($start)],
            ['success_time', '<', strtotime($end)],
        ];

        $data = [];
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);
        $this->calculates($where, $field, $data, $instance);
        $this->insert($data, $start, 2);
    }

    public function calculate($where, $field, &$data,$instance = '',&$usertouser=[])
    {
        $uids = UserAssetLogModel::getInstance($instance)->getuids($where);
        $models = UserAssetLogModel::getInstance($instance)->getModels($uids);
        foreach ($models as $model) {
            $count = $model->getModel()->where($where)->where("uid","in",$model->getList())->count();
            $page = ceil($count / self::LIMIT);
            for ($i = 0; $i < $page; $i++) {
                $offset = $i * self::LIMIT;
                $res = $model->getModel()->where($where)->where("uid","in",$model->getList())->field($field)->limit($offset, self::LIMIT)
                    ->select()->toArray();
                $this->calData($res, $data);
                if(is_array($usertouser)){
                    //用户给用户送礼  点对点
                    $this->sendGiftUserToUser($res,$usertouser);
                }
            }
        }
    }


    public function calculates($where, $field, &$data,$instance = '')
    {
        $uids = UserAssetLogModel::getInstance($instance)->getuids($where);
        $models = UserAssetLogModel::getInstance($instance)->getModels($uids);
        foreach ($models as $model) {
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
    protected function insertOrUpdateRoomData($room_key, $data, $date, $type)
    {
        $reward_amount = (int)$data['reward_amount'];
        $consume_amount = (int)$data['consume_amount'];
        $count = (int)$data['count'];
        $arr = explode(':', $room_key);
        $room_id = (int)$arr[0];
        $send_type = (int)$arr[1];

        $updateData = [
            'date' => $date,
            'type' => $type,
            'send_type' => $send_type,
            'consume_amount' => $consume_amount,
            'reward_amount' => $reward_amount,
            'count' => $count,
            'room_id' => $room_id,
        ];
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiDaysRoomDatasBysendTypeModel::getInstance()->getModel(), [$updateData], ['date', 'type', 'room_id', 'send_type', 'id']);
    }

    protected function insertOrUpdate($data)
    {
        $date = $data['date'];
        $uid = (int)$data['uid'];
        $type = $data['type'];
        $reward_amount = (int)$data['reward_amount'];
        $consume_amount = (int)$data['consume_amount'];
        $count = (int)$data['count'];
        $arr = explode(':', $data['gift_id']);
        $gift_id = (int)$arr[0];
        $room_id = (int)$arr[1];
        $send_type = (int)$arr[2];
        $updateData = [
            'date' => $date,
            'uid' => $uid,
            'type' => $type,
            'send_type' => $send_type,
            'consume_amount' => $consume_amount,
            'reward_amount' => $reward_amount,
            'count' => $count,
            'gift_id' => $gift_id,
            'room_id' => $room_id,
        ];

        //$sql = "insert into bi_days_user_gift_datas_bysend_type () values ('{$date}',{$uid},'{$type}', {$send_type}, {$consume_amount},{$reward_amount},{$count},{$gift_id},{$room_id}) on duplicate key update consume_amount = {$consume_amount},reward_amount = {$reward_amount},count = {$count};";
        //Db::execute($sql);
        $model = BiDaysUserGiftDatasBysendTypeModel::getInstance()->getModel();
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($model, [$updateData], ['date', 'uid', 'type', 'gift_id', 'room_id', 'send_type', 'id']);
    }


    //点对点用户送礼记录
    protected function sendGiftUserToUser($data,&$sendRes){
        foreach($data as $item){
            $date = date('Y-m-d',$item['success_time']);
            $mark = "{$item['uid']}:{$item['touid']}:".$date;
            if (!isset($sendRes[$mark])) {
                $sendRes[$mark]['reward_amount'] = (int)$item['reward_amount'];
                $sendRes[$mark]['consume_amount'] = (int)$item['consume_amount'];
                $sendRes[$mark]['uid'] = $item['uid'];
                $sendRes[$mark]['touid'] = $item['touid'];
                $sendRes[$mark]['date'] = $date;
            } else {
                $sendRes[$mark]['reward_amount'] += (int)$item['reward_amount'];
                $sendRes[$mark]['consume_amount'] += (int)$item['consume_amount'];
            }
        }
    }

}

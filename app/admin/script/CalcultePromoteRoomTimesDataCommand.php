<?php
/*
 * 渠道推广进厅统计
 */

namespace app\admin\script;

use app\admin\model\BiDaysRoomPromotionStatsByTimesModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MemberModel;
use app\admin\model\PromotionRoomConfModel;
use app\admin\model\PromotionRoomTimesConfModel;
use app\admin\model\UserAssetLogModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class CalcultePromoteRoomTimesDataCommand extends Command
{
    protected $date;
    protected $time;
    protected $table_name;

    const INTERVAL_TIME = [
        30 * 60,
        35 * 60,
    ];

    protected function configure()
    {
        $this->setName('CalcultePromoteRoomTimesDataCommand')
            ->setDescription('CalcultePromoteRoomTimesDataCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '');

        $this->date = date("Y-m-d");
        $this->time = date("Y-m-d H:i:s");
    }

    protected function getStartEndDate($start = '', $end = '')
    {
        $between = time() - strtotime(date("Y-m-d"));
        if ($start && $end) {
            $start_date = date("Y-m-d", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m-d", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m-d", strtotime($start));
            $end_date = date("Y-m-d", strtotime("{$start} + 1days"));
        } elseif ($between >= min(self::INTERVAL_TIME) && $between < max(self::INTERVAL_TIME)) {
            //每天凌晨30分钟统计前一天的数值
            $start_date = date("Y-m-d", strtotime("-1days"));
            $end_date = date("Y-m-d");
        } else {
            $start_date = date("Y-m-d");
            $end_date = date("Y-m-d", strtotime("+1days"));
        }
        return [$start_date, $end_date];
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
            $this->table_name = getTable($start_date, $end_date);
            $this->calPromotionData($start_date, $end_date);
        }
        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;
    }

    /*
     *获取注册用户
     */
    /*
     *获取推广日充值用户
     */

    protected function getRegUsers($start, $end, $promote_code = 0)
    {
        $where = [];
        $where[] = ['register_time', '>=', $start];
        $where[] = ['register_time', '<=', $end];
        if (!empty($promote_code)) {
            $where[] = ['invitcode', '=', $promote_code];
        }
        $res = [];
        $models = MemberModel::getInstance()->getallModel();
        foreach ($models as $model) {
            $data = $model->getModel()->field('id')->where($where)->column("id");
            $res = array_merge($res, $data);
        }
        return $res;
    }

    protected function getPromoteList($id)
    {
        return BiDaysRoomPromotionStatsByTimesModel::getInstance()->getModel()->where('promotion_id', $id)->order('date asc')->limit(1)->find();
    }

    /*
     *充值
     */
    protected function getCharge($start, $end, $users, $is_count = false)
    {
        $query = ChargedetailModel::getInstance()->getModel()
            ->where('addtime', '>=', $start)
            ->where('addtime', '<=', $end)
            ->where('status', 'in', [1, 2])
            ->where('uid', 'in', $users);

        if ($is_count) {
            $res = $query->field('distinct(uid) as uid')->select()->toArray();
        } else {
            $res = $query->sum('rmb');
        }

        return $res;
    }

    /*
     *代充
     */
    protected function getMemberChargeNew($start, $end, $users, $is_count = false)
    {
        $query = BiDaysUserChargeModel::getInstance()->getModel()
            ->where('type', 2)
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->where('uid', 'in', $users);

        if ($is_count) {
            $res = $query->field('distinct(uid) as uid')->select()->toArray();
        } else {
            $res = $query->sum('amount');
        }

        return $res;
    }

    /*
     *豆消费
     */
    protected function getMemberConsume($start, $end, $users, $room_id)
    {
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);
        $res = [];
        $models = UserAssetLogModel::getInstance($instance)->getModels($users);
        foreach ($models as $model) {
            $data = $model->getModel()
                ->field('ifnull(sum(abs(change_amount)),0) as change_amount')
                ->where('event_id', 10002)
                ->where('type', 'in', [4])
                ->where('room_id', $room_id)
                ->where('success_time', '>=', strtotime($start))
                ->where('success_time', '<=', strtotime($end))
                ->where('uid', 'in', $model->getList())->select()->toArray();
            $res = array_merge($res, $data);
        }
        return $res;
    }

    /*
     *背包消费
     */
    protected function getMemberBagConsume($start, $end, $users, $room_id)
    {
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);
        $res = [];
        $models = UserAssetLogModel::getInstance($instance)->getModels($users);
        foreach ($models as $model) {
            $data = $model->getModel()
                ->field('ifnull(sum(abs(ext_4)),0) as change_amount')
                ->where('event_id', 10002)
                ->where('type', 'in', [3])
                ->where('ext_4', '<>', '')
                ->where('room_id', $room_id)
                ->where('success_time', '>=', strtotime($start))
                ->where('success_time', '<=', strtotime($end))
                ->where('uid', 'in', $model->getList())->select()->toArray();
            $res = array_merge($res, $data);
        }
        return $res;
    }

    /*
     *消费人数
     */
    protected function getConsumeCount($start, $end, $users, $room_id)
    {
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);
        $res = [];
        $models = UserAssetLogModel::getInstance($instance)->getModels($users);
        foreach ($models as $model) {
            $data = $model->getModel()
                ->field('distinct(uid) as uid')
                ->where('event_id', 10002)
                ->where('type', 'in', [3, 4])
                ->where('room_id', $room_id)
                ->where('ext_4', '<>', '')
                ->where('success_time', '>=', strtotime($start))
                ->where('success_time', '<=', strtotime($end))
                ->where('uid', 'in', $model->getList())->select()->toArray();
            $res = array_merge($res, $data);
        }
        return $res;
    }

    /*
     *登陆
     */
    protected function getLoginStats($start, $end, $users)
    {
        $models = LogindetailModel::getInstance()->getModels($users);
        $res = [];
        foreach ($models as $model) {
            $data = $model->getModel()
                ->field('user_id')
                ->where('ctime', '>=', strtotime($start))
                ->where('ctime', '<', strtotime($end))
                ->where('user_id', 'in', $model->getList())
                ->select()->toArray();
            $res = array_merge($res, $data);
        }

        return array_unique(array_column($res, "user_id"));
    }

    protected function calPromotionData($start, $end)
    {
        $promotions = $this->getRoomPromotions();
        foreach ($promotions as $promotion) {
            list(
                $promote_code, $promotion_id, $price, $begin, $finish
            ) = [
                $promotion['promote_code'], $promotion['id'], $promotion['rmb'], $promotion['start_time'], $promotion['end_time'],
            ];
            $start_date = date("Y-m-d", strtotime($begin));

            $room_id = PromotionRoomConfModel::getInstance()->getModel()->where('id', $promotion['promote_code'])->value('room_id');
            if ($start >= $start_date) {
                //当前引流时间段注册用户
                $reg_users = $this->getRegUsers($begin, $finish);

                $reg_count = count($reg_users);

                //获取推广用户
                $reg_invitcode_users = $this->getRegUsers($begin, $finish, $promote_code);

                if (!empty($reg_invitcode_users)) {
                    //引流时间段充值数据
                    $promote_pay_amount = $this->getCharge($begin, $finish, $reg_invitcode_users);
                    $promote_pay_users = $this->getCharge($begin, $finish, $reg_invitcode_users, true);
                    $promote_pay_users = array_column($promote_pay_users, 'uid');
                    $promote_pay_count = count($promote_pay_users);

                    //充值金额
                    $pay_amount = $this->getCharge($start, $end, $reg_invitcode_users);
                    $pay_users = $this->getCharge($start, $end, $reg_invitcode_users, true);
                    $pay_users = array_column($pay_users, 'uid');
                    $pay_count = count($pay_users);

                    echo "pay======>" . date("Y-m-d H:i:s") . PHP_EOL;

                    //历史充值人数 历史充值金额
                    $total_pay_amount = $this->getCharge($begin, $end, $reg_invitcode_users);
                    $total_pay_users = $this->getCharge($begin, $end, $reg_invitcode_users, true);
                    $total_pay_users = array_column($total_pay_users, 'uid');
                    $total_pay_count = count($total_pay_users);

                    echo "total_pay_amount======>" . date("Y-m-d H:i:s") . PHP_EOL;

                    //代充
                    $member_pay_amount = $this->getMemberChargeNew($start, $end, $reg_invitcode_users) / 10;
                    $member_pay_users = $this->getMemberChargeNew($start, $end, $reg_invitcode_users, true);
                    $member_payusers = array_column($member_pay_users, 'uid');
                    $member_pay_count = count($member_pay_users);

                    echo "member_pay_amount======>" . date("Y-m-d H:i:s") . PHP_EOL;
                    //厅消费
                    $bean_consumer_amount = $this->getMemberConsume($start, $end, $reg_invitcode_users, $room_id);
                    $bag_consumer_amount = $this->getMemberBagConsume($start, $end, $reg_invitcode_users, $room_id);

                    //厅消费人数
                    $consume_users = $this->getConsumeCount($start, $end, $reg_invitcode_users, $room_id);

                    // dump(array_column($consume_users, 'uid'));die;
                    $consume_count = count($consume_users);

                    //历史
                    $total_member_pay_amount = $this->getMemberChargeNew($begin, $end, $reg_invitcode_users) / 10;
                    $total_member_pay_users = $this->getMemberChargeNew($begin, $end, $reg_invitcode_users, true);
                    $total_member_pay_users = array_column($total_member_pay_users, 'uid');
                    $total_member_pay_count = count($total_member_pay_users);

                    echo "total_member_pay_count======>" . date("Y-m-d H:i:s") . PHP_EOL;

                    //登陆
                    $login_users = $this->getLoginStats($start, $end, $reg_invitcode_users);
                    $loginusers = $login_users;
                    $login_count = count($loginusers);
                    echo "login_count======>" . date("Y-m-d H:i:s") . PHP_EOL;

                    $roi = round(($total_member_pay_amount + $total_pay_amount) / $this->getPrice($price), 2);

                    $insert_data = [
                        'date' => $start,
                        'promotion_id' => $promotion_id,
                        'promote_code' => $promote_code,
                        'reg_count' => $reg_count,
                        'login_count' => $login_count,
                        'login_users' => implode(',', array_values($loginusers)),
                        'enter_users' => implode(',', array_values($reg_invitcode_users)),
                        'enter_count' => count($reg_invitcode_users),
                        'promote_pay_amount' => $promote_pay_amount,
                        'promote_pay_count' => $promote_pay_count,
                        'pay_amount' => $pay_amount,
                        'total_pay_amount' => $total_pay_amount,
                        'pay_users' => implode(',', $pay_users),
                        'pay_count' => $pay_count,
                        'total_pay_users' => implode(',', array_values($total_pay_users)),
                        'total_pay_count' => $total_pay_count,
                        'member_pay_users' => implode(',', $member_payusers),
                        'member_pay_amount' => $member_pay_amount,
                        'member_pay_count' => $member_pay_count,
                        'total_member_pay_amount' => $total_member_pay_amount,
                        'total_member_pay_count' => $total_member_pay_count,
                        'pay_login_count' => 0,
                        'pay_login_users' => '',
                        'consume_users' => implode(',', array_column($consume_users, 'uid')),
                        'room_consume_amount' => (int) $bean_consumer_amount[0]['change_amount'],
                        'room_bagconsume_amount' => (int) $bag_consumer_amount[0]['change_amount'],
                        'consume_count' => (int) $consume_count,
                        'roi' => $roi,
                    ];

                    ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(BiDaysRoomPromotionStatsByTimesModel::getInstance()->getModel(), [$insert_data], ['date', 'promotion_id', 'id']);
                }
            }
            if ($start > $start_date) {
                //当前引流时间段注册用户
                $promotion_info = $this->getPromoteList($promotion['id']);
                if (!empty($promotion_info)) {
                    $promote_pay_users = $promotion_info['pay_users'];
                    $promote_member_pay_users = $promotion_info['member_pay_users'];
                    $consume_users = explode(',', $promotion_info['consume_users']);

                    //厅消费人数
                    $consume_res = $this->getConsumeCount($start, $end, $consume_users, $room_id);

                    $pay_users = array_unique(array_merge(explode(',', $promote_pay_users), explode(',', $promote_member_pay_users)));
                    //引流充值用户登录留存
                    $login_users = $this->getLoginStats($start, $end, $pay_users, true);

                    $loginusers = array_column($login_users, 'uid');
                    $login_count = count($loginusers);
                    echo "login_count======>" . date("Y-m-d H:i:s") . PHP_EOL;

                    $insert_data = [
                        'pay_login_count' => $login_count,
                        'pay_login_users' => implode(',', array_values($loginusers)),
                        'consume_count' => count($consume_res),
                        'consume_users' => implode(',', array_column($consume_res, 'uid')),
                    ];
                    BiDaysRoomPromotionStatsByTimesModel::getInstance()->getModel()->where('date', $start)->where('promotion_id', $promotion_id)->update($insert_data);
                }
            }
        }
    }

    protected function getPrice($price)
    {
        if ($price == 0.00) {
            $price = 1;
        }
        return $price;
    }

    /*
     * 获取倒量房间
     */
    protected function getRoomPromotions()
    {
        return PromotionRoomTimesConfModel::getInstance()->getModel()->where('status', 0)->select()->toArray();
    }
}

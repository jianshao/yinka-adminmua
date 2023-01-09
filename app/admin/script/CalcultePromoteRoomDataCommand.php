<?php
/**
 * 进厅分场次统计
 */

namespace app\admin\script;

use app\admin\model\BiDaysRoomPromotionStatsModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MemberModel;
use app\admin\model\RoomPromotionConfModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

ini_set('set_time_limit', 0);

class CalcultePromoteRoomDataCommand extends Command
{
    protected $date;
    protected $time;
    protected $url = "http://182.92.189.66:180/room_query";
    protected $table_name;

    protected function configure()
    {
        $this->setName('CalcultePromoteRoomDataCommand')
            ->setDescription('CalcultePromoteRoomDataCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '');

        $this->url = config('config.enter_room_url');
        $this->date = date("Y-m-d");
        $this->time = date("Y-m-d H:i:s");
    }

    protected function getStartEndDate($start = '', $end = '')
    {
        if ($start && $end) {
            $start_date = date("Y-m-d", min(strtotime($start), strtotime($end)));
            $end_date = date("Y-m-d", max(strtotime($start), strtotime($end)));
        } elseif ($start && !$end) {
            $start_date = date("Y-m-d", strtotime($start));
            $end_date = date("Y-m-d", strtotime("{$start} + 1days"));
        } elseif (time() - strtotime(date("Y-m-d")) <= 30 * 60) {
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
            $this->table_name = getTable($start, $end);
            $this->dealTodayData($start_date, $end_date, $end);
        }
        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;
    }

    /*
     *获取注册用户
     */
    protected function getRegUsers($start, $end)
    {
        $where = [];
        $where[] = ['register_time', '>=', $start];
        $where[] = ['register_time', '<=', $end];
        $res = [];
        $models = MemberModel::getInstance()->getallModel();
        foreach ($models as $model) {
            $data = $model->getModel()->field('id')->where($where)->column("id");
            $res = array_merge($res, $data);
        }
        return $res;
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
        $query = BiDaysUserChargeModel::getInstance()->getModel()->where('type', 2)
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

    public function getEnterUsers($item)
    {
        return ParseUserStateDataCommmon::getInstance()->getEnterRoomUsers($item['start_time'], $item['end_time'], $item['room_id']);
    }


    protected function calPromotionData($start, $end)
    {
        $promotions = $this->getRoomPromotions($start, $end);

        foreach ($promotions as $promotion) {
            list(
                $room_id,
                $promotion_id,
                $price,
                $begin,
                $finish
            ) = [
                $promotion['room_id'],
                $promotion['id'],
                $promotion['rmb'],
                $promotion['start_time'],
                $promotion['end_time'],
            ];

            $start_date = date("Y-m-d", strtotime($begin));
            if ($start == $start_date) {
                //当前引流时间段注册用户
                $reg_users = $this->getRegUsers($begin, $finish);

                $reg_count = count($reg_users);

                //获取进厅用户
                $enter_users = $this->getEnterUsers($promotion) ?: [];


                //注册并进厅用户
                $reg_enterroom_users = array_intersect($enter_users, $reg_users);

                if (!empty($reg_enterroom_users)) {
                    //引流时间段充值数据
                    $promote_pay_amount = $this->getCharge($begin, $finish, $reg_enterroom_users);
                    $promote_pay_users = $this->getCharge($begin, $finish, $reg_enterroom_users, true);
                    $promote_pay_users = array_column($promote_pay_users, 'uid');
                    $promote_pay_count = count($promote_pay_users);
                    $promote_pay_users = json_encode($promote_pay_users);

                    //充值金额
                    $pay_amount = $this->getCharge($start, $end, $reg_enterroom_users);
                    $pay_users = $this->getCharge($start, $end, $reg_enterroom_users, true);
                    $pay_users = array_column($pay_users, 'uid');
                    $pay_count = count($pay_users);

                    //历史充值人数 历史充值金额
                    $total_pay_amount = $this->getCharge($begin, $end, $reg_enterroom_users);
                    $total_pay_users = $this->getCharge($begin, $end, $reg_enterroom_users, true);
                    $total_pay_users = array_column($total_pay_users, 'uid');
                    $total_pay_count = count($total_pay_users);
                    $total_pay_users = json_encode($total_pay_users);

                    //代充
                    $member_pay_amount = $this->getMemberChargeNew($start, $end, $reg_enterroom_users) / 10;
                    $member_pay_users = $this->getMemberChargeNew($start, $end, $reg_enterroom_users, true);
                    $member_payusers = array_column($member_pay_users, 'uid');
                    $member_pay_count = count($member_pay_users);

                    //历史
                    $total_member_pay_amount = $this->getMemberChargeNew($begin, $end, $reg_enterroom_users) / 10;
                    $total_member_pay_users = $this->getMemberChargeNew($begin, $end, $reg_enterroom_users, true);
                    $total_member_pay_users = array_column($total_member_pay_users, 'uid');
                    $total_member_pay_count = count($total_member_pay_users);
                    $total_member_pay_users = json_encode($total_member_pay_users);

                    //登陆
                    $login_info = $this->getLoginStats($start, $end, $reg_enterroom_users);
                    $login_users = $login_info;
                    $login_count = count($login_users);

                    $roi = round(($total_member_pay_amount + $total_pay_amount) / $this->getPrice($price), 2);
                    $insertData = [
                        'date' => $start,
                        'promotion_id' => $promotion_id,
                        'room_id' => $room_id,
                        // 'reg_users' => implode(',', $reg_users),
                        'reg_count' => $reg_count,
                        'login_count' => $login_count,
                        'login_users' => implode(',', array_values($login_users)),
                        'enter_users' => implode(',', array_values($reg_enterroom_users)), //进厅注册用户
                        'enter_count' => count($reg_enterroom_users),
                        'promote_pay_amount' => $promote_pay_amount,
                        'promote_pay_count' => $promote_pay_count,
                        'promote_pay_users' => $promote_pay_users,
                        'pay_amount' => $pay_amount,
                        'total_pay_amount' => $total_pay_amount,
                        'pay_users' => implode(',', $pay_users),
                        'pay_count' => $pay_count,
                        'total_pay_count' => $total_pay_count,
                        'total_pay_users' => $total_pay_users,
                        'member_pay_users' => implode(',', $member_payusers),
                        'member_pay_amount' => $member_pay_amount,
                        'member_pay_count' => $member_pay_count,
                        'total_member_pay_amount' => $total_member_pay_amount,
                        'total_member_pay_count' => $total_member_pay_count,
                        'total_member_pay_users' => $total_member_pay_users,
                        'roi' => $roi,
                    ];

                    $this->insertOrUpdate($insertData);
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

    /**
     *处理每日数据
     */
    protected function dealTodayData($start, $end)
    {
        $this->calPromotionData($start, $end);
    }

    /**
     *更新同步表数据ID
     */
    protected function insertOrUpdate($data)
    {
        //bi_days_room_promotion_stats
        $daysroompromotionstatsModel = BiDaysRoomPromotionStatsModel::getInstance()->getModel();
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($daysroompromotionstatsModel, [$data], ['date', 'promotion_id', 'id']);

    }
    /*
     * 获取倒量房间
     * zb_room_promotion_conf
     */
    protected function getRoomPromotions($start, $end)
    {
        return RoomPromotionConfModel::getInstance()->getModel()
            ->where('start_time', '>=', $start)
            ->where('start_time', '<', $end)
            ->where('status', 0)
            ->select()->toArray();
    }
}

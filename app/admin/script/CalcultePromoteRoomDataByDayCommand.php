<?php
/**
 * 进厅分场次统计
 */

namespace app\admin\script;

use app\admin\model\BiDaysRoomPromotionStateByDayModel;
use app\admin\model\BiDaysRoomPromotionStatsModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\LogindetailModel;
use app\admin\model\RoomPromotionConfModel;
use app\common\ParseUserStateByUniqkey;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

ini_set('set_time_limit', 0);

class CalcultePromoteRoomDataByDayCommand extends Command
{
    protected $date;
    protected $time;
    protected $start_date = '2021-06-01';
    protected $table_name;

    protected function configure()
    {
        $this->setName('CalcultePromoteRoomDataCommand')
            ->setDescription('CalcultePromoteRoomDataCommand')
            ->addArgument('start', Argument::OPTIONAL, "start", '')
            ->addArgument('end', Argument::OPTIONAL, "end", '');

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
            $this->table_name = getTable($start_date, $end_date);
            $this->dealTodayData($start_date, $end_date, $end);
        }
        echo "start======>" . $this->time . PHP_EOL;
        echo "finish======>" . date("Y-m-d H:i:s") . PHP_EOL;
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
     *登陆
     */
    protected function getLoginStats($start, $end, $users, $is_count = false)
    {
        $models = LogindetailModel::getInstance()->getallModel();
        $res = [];
        if ($is_count) {
            foreach ($models as $model) {
                $data = $model->getModel()->where('ctime', '>=', strtotime($start))
                    ->where('ctime', '<', strtotime($end))
                    ->where('user_id', 'in', $users)
                    ->field('distinct(user_id) as uid')
                    ->select()->toArray();
                $res = array_merge($res, $data);
            }
        } else {
            //登陆次数
            foreach ($models as $model) {
                $data = $model->getModel()->where('ctime', '>=', strtotime($start))
                    ->where('ctime', '<', strtotime($end))
                    ->where('user_id', 'in', $users)
                    ->field('user_id as uid')
                    ->select()->toArray();
                $res = array_merge($res, $data);
            }
        }
        return $res;
    }

    protected function calPromotionTodayData($start, $end)
    {
        //统计当日的所有进厅数据
        $promotions = $this->getRoomPromotionsByDay($start, $end);
        $promotion_ids = array_values(array_column($promotions, 'id'));
        $price = array_sum(array_column($promotions, 'rmb'));

        Db::execute('SET SESSION group_concat_max_len = 1024000');
        $promotions = BiDaysRoomPromotionStatsModel::getInstance()->getModel()
            ->field('GROUP_CONCAT(enter_users) enter_users,date promotion_date,sum(reg_count) reg_count,sum(enter_count) enter_count')
            ->where('promotion_id', 'in', $promotion_ids)
            ->where('date', $start)
            ->select()
            ->toArray();

        //获取当天的用户数据
        if ($promotions) {
            foreach ($promotions as $promotion) {
                $reg_enterroom_users = array_unique(array_filter(explode(',', $promotion['enter_users'])));

                if (empty($reg_enterroom_users)) {
                    continue;
                }
                $promotion['price'] = $price;
                $this->updateData($start, $end, $promotion);
            }
        }
    }

    protected function calPromotionHistoryData($start, $end)
    {
        $promotions = $this->getPromotionsByDay($start, $end);

        //获取当天的用户数据
        foreach ($promotions as $promotion) {
            $promotion_date = $promotion['promotion_date'];
            $reg_enterroom_users = array_unique(array_filter(explode(',', $promotion['enter_users'])));

            if ($start < $promotion_date || empty($reg_enterroom_users)) {
                continue;
            }

            $this->updateData($start, $end, $promotion);
        }
    }

    public function updateData($start, $end, $promotion)
    {
        $reg_count = $promotion['reg_count'];
        $enter_count = $promotion['enter_count'];
        $promotion_date = $promotion['promotion_date'];
        $price = $promotion['price'];

        $promotion_end_date = date('Y-m-d', strtotime("$promotion_date +1days"));

        $reg_enterroom_users = array_unique(array_filter(explode(',', $promotion['enter_users'])));

        //引流时间段充值数据
        $promote_pay_amount = $this->getCharge($promotion_date, $promotion_end_date, $reg_enterroom_users);
        $promote_pay_users = $this->getCharge($promotion_date, $promotion_end_date, $reg_enterroom_users, true);
        $promote_pay_users = array_column($promote_pay_users, 'uid');
        $promote_pay_count = count($promote_pay_users);
        $promote_pay_users = json_encode($promote_pay_users);

        //充值金额
        $pay_amount = $this->getCharge($start, $end, $reg_enterroom_users);
        $pay_users = $this->getCharge($start, $end, $reg_enterroom_users, true);
        $pay_users = array_column($pay_users, 'uid');
        $pay_count = count($pay_users);

        //历史充值人数 历史充值金额
        $total_pay_amount = $this->getCharge($promotion_date, $end, $reg_enterroom_users);
        $total_pay_users = $this->getCharge($promotion_date, $end, $reg_enterroom_users, true);
        $total_pay_users = array_column($total_pay_users, 'uid');
        $total_pay_count = count($total_pay_users);
        $total_pay_users = json_encode($total_pay_users);

        //代充
        $member_pay_amount = $this->getMemberChargeNew($start, $end, $reg_enterroom_users) / 10;
        $member_pay_users = $this->getMemberChargeNew($start, $end, $reg_enterroom_users, true);
        $member_payusers = array_column($member_pay_users, 'uid');
        $member_pay_count = count($member_pay_users);

        //历史
        $total_member_pay_amount = $this->getMemberChargeNew($promotion_date, $end, $reg_enterroom_users) / 10;
        $total_member_pay_users = $this->getMemberChargeNew($promotion_date, $end, $reg_enterroom_users, true);
        $total_member_pay_users = array_column($total_member_pay_users, 'uid');
        $total_member_pay_count = count($total_member_pay_users);
        $total_member_pay_users = json_encode($total_member_pay_users);

        //登陆
        $login_info = $this->getLoginStats($start, $end, $reg_enterroom_users, true);
        $login_users = array_values(array_column($login_info, 'uid'));
        $login_count = count($login_users);

        $promote_info = $this->getRoomPromotionsByDay($promotion_date, $promotion_end_date);
        $price = array_sum(array_column($promote_info, 'rmb'));

        $roi = round(($total_member_pay_amount + $total_pay_amount) / $this->getPrice($price), 2);

        $insertData = [
            'date' => $start,
            'promotion_date' => $promotion_date,
            'reg_count' => $reg_count,
            'login_count' => $login_count,
            'login_users' => implode(',', $login_users),
            'enter_users' => implode(',', $reg_enterroom_users),
            'enter_count' => $enter_count,
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
            'price' => $price,
            'roi' => $roi,
        ];

        $this->insertOrUpdate($insertData);
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
        $this->calPromotionTodayData($start, $end);
        $this->calPromotionHistoryData($start, $end);

    }

    /**
     *更新同步表数据ID
     */
    protected function insertOrUpdate($data)
    {
        $model = BiDaysRoomPromotionStateByDayModel::getInstance()->getModel();
        ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($model, [$data], ['date', 'promotion_date', 'id']);
    }

    /*
     * 获取每天的倒量房间
     */
    protected function getRoomPromotionsByDay($start, $end)
    {
        $start = max($start, $this->start_date);
        return RoomPromotionConfModel::getInstance()->getModel()
            ->where('status', 0)
            ->where('start_time', '>=', $start)
            ->where('start_time', '<', $end)
            ->select()
            ->toArray();
    }

    /*
     * 获取每天的倒量数据
     */
    protected function getPromotionsByDay($start, $end)
    {

        return BiDaysRoomPromotionStateByDayModel::getInstance()->getModel()
            ->where('date', '<', $start)
            ->where('date = promotion_date')
            ->select()
            ->toArray();
    }
}

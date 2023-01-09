<?php
/**
 * 同步脚本
 */

namespace app\admin\script;

use app\admin\model\BiDaysUserGiftDatasBysendTypeModel;
use app\admin\model\InviteGuildAnchorsModel;
use app\admin\model\InvitePointHistoryModel;
use app\common\ParseUserStateByUniqkey;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

//白名单设置的陪陪的有效积分
//每日凌晨12点以后执行 每日一次
class AnchorPromotePointCommand extends Command
{
    const COMMAND_NAME = "AnchorPromotePointCommand";
    const MAXLIMIT = 2000; //最大执行的条数
    private $uids = []; //主播陪陪的用户id
    private $uidPointList = []; //主播陪陪的积分值
    //获取积分的规则
    const POINTRULE = [
        'day' => ['0.5-0.8' => 1, '0.8-0.95' => 2, '0.95-1' => 3], //日规则
        'week' => ['0.5-0.8' => 5, '0.8-0.95' => 10, '0.95-1' => 15], //周规则
        'month' => ['0.5-0.8' => 20, '0.8-0.95' => 40, '0.95-1' => 60], //月规则
    ];

    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->setDescription(SELF::COMMAND_NAME);
    }

    public function execute(Input $input, Output $output)
    {
        //获取所有的设置的陪陪的用户ID stauts==0 标识无效的主播
        $this->uids = InviteGuildAnchorsModel::getInstance()->getModel()->where(['status'=>1])->column("uid", "id");
        try {
            $this->dayReceiveGiftTotal();//每日的数据
            if (date('w') == 1) { //判断是否每周的第一天
                $this->weekReviceGiftTotal();
            }
            if ((int)date("d") == 1) { //判断是否是每月的第一天
                $this->monthReviceGiftTotal();
            }
            $this->updateAnchorPoint();
        } catch (\Throwable $e) {
            Log::info(self::COMMAND_NAME . ":error:" . $e->getMessage());
        }

    }

    //每个用户日的收礼排名
    private function dayReceiveGiftTotal()
    {
        $where_date = date("Y-m-d", strtotime("-1days"));
        $where = [];
        $where[] = ["date", "=", $where_date];
        $where[] = ['uid', "in", $this->uids];
        $receiveRes = $this->getReceiveData($where);
        $totalAmount = array_sum($receiveRes);
        $handRes = [];
        foreach ($receiveRes as $uid => $item) {
            $handRes[] = [
                "uid" => $uid,
                "reward_amount" => $item,
                "rank" => $this->divedFunc($item, $totalAmount, 1)
            ];
        }
        $this->uidPointList = $this->getPoint($handRes, self::POINTRULE['day']);
        $this->handleUserPoint(1);
    }


    //每个用户周的收礼排名
    private function weekReviceGiftTotal()
    {
        $params = $this->getLastWeekBeginEnd();
        $where[] = ["date", ">=", $params[0]];
        $where[] = ["date", "<=", $params[1]];
        $receiveRes = $this->getReceiveData($where);
        $totalAmount = array_sum($receiveRes);
        $handRes = [];
        foreach ($receiveRes as $uid => $item) {
            $handRes[] = [
                "uid" => $uid,
                "reward_amount" => $item,
                "rank" => $this->divedFunc($item, $totalAmount, 1)
            ];
        }
        $this->uidPointList = $this->getPoint($handRes, self::POINTRULE['week']);
        $this->handleUserPoint(2);
    }

    //每个用户月的收礼排名
    private function monthReviceGiftTotal()
    {
        $params = $this->getLastWeekBeginEnd();
        $where[] = ["date", ">=", $params[0]];
        $where[] = ["date", "<=", $params[1]];
        $receiveRes = $this->getReceiveData($where);
        $totalAmount = array_sum($receiveRes);
        $handRes = [];
        foreach ($receiveRes as $uid => $item) {
            $handRes[] = [
                "uid" => $uid,
                "reward_amount" => $item,
                "rank" => $this->divedFunc($item, $totalAmount, 1)
            ];
        }
        $this->uidPointList = $this->getPoint($handRes, self::POINTRULE['month']);
        $this->handleUserPoint(3);
    }


    //相除
    private function divedFunc($param1, $param2, $decimal = 2)
    {
        if ($param2 == 0 || $param1 == false) {
            return 0;
        }
        return round($param1 / $param2, $decimal);
    }

    /**
     * 获取用户的分数值
     * @param $res
     * @param $conf
     * @return array
     */
    private function getPoint($res, $conf)
    {
        foreach ($res as $index => $item) {
            $getpoint = 0;
            foreach ($conf as $key => $point) {
                $range = explode("-", $key);
                $min = $range[0];
                $max = $range[1];
                if (($item['rank'] > $min) && ($item['rank']) <= $max) {
                    $getpoint = $point;
                }
            }
            $res[$index]['point'] = $getpoint;
        }
        return $res;
    }


    /**
     * 获取上周的开始与结束时间
     * @return array
     */
    private function getLastWeekBeginEnd()
    {
        //获取上周的开始时间与结束时间
        $w = date("w");
        $w = $w + 7 - 1;
        $begin = date('Y-m-d', strtotime("-$w days"));
        $end = date('Y-m-d', (strtotime("+6days" . $begin)));
        return [$begin, $end];
    }


    /**
     * 获取上个月的开始与结束
     */
    private function getLastMonthBeginEnd()
    {
        $begin = date('Y-m-01', strtotime('-1 month'));
        $end = date("Y-m-d", strtotime(-date('d') . 'day'));
        return [$begin, $end];
    }


    /**
     * 获取收送礼的数据源数据
     * @param $where
     * @return array
     */
    private function getReceiveData($where)
    {
        $page = 1;
        $returnData = [];
        $source = BiDaysUserGiftDatasBysendTypeModel::getInstance()->getModel()
            ->where("type", "=", 2) //送礼
            ->where($where)
            ->field("uid,reward_amount");
        $res = $source->page($page, self::MAXLIMIT)->select()->toArray();
        while ($res) {
            foreach ($res as $item) {
                if (isset($returnData[$item['uid']])) {
                    $returnData[$item['uid']] += $item['reward_amount'];
                } else {
                    $returnData[$item['uid']] = $item['reward_amount'];
                }
            }
            $page++;
            $res = $source->page($page, self::MAXLIMIT)->select()->toArray();
        }
        return $returnData;
    }


    /**
     * 主播用户的积分入库
     * @param $type
     */
    private function handleUserPoint($type)
    {
        $invitepointhistoryModel = InvitePointHistoryModel::getInstance()->getModel();
        foreach ($this->uidPointList as $item) {
            $item['type'] = $type;
            //ParseUserStateByUniqkey::getInstance()->insertOrUpdate($item, 'invite_point_history', ['id', 'type', 'uid']);
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel($invitepointhistoryModel, [$item], ['id', 'type', 'uid']);
        }
    }

    /**
     * 更新主播的积分到数据库
     */
    public function updateAnchorPoint()
    {
        $res = InvitePointHistoryModel::getInstance()->getModel()->field("uid,sum(point) as points")->group("uid")->select()->toArray();
        $inviteguildanchorsModel = InviteGuildAnchorsModel::getInstance()->getModel();
        $inviteguildanchorsModel->startTrans();
        try {
            $inviteguildanchorsModel->where("id",">",0)->update(["point" => 0]);//主播积分先全部清空下
            foreach ($res as $item) {
                $inviteguildanchorsModel->where("uid", "=", $item['uid'])->update(["point" => $item['points']]);
            }
            $inviteguildanchorsModel->commit();
        } catch (\Throwable $e) {
            Log::info(SELF::COMMAND_NAME . ":updateanchorpoint" . $e->getMessage());
            $inviteguildanchorsModel->rollback();
        }
    }


}

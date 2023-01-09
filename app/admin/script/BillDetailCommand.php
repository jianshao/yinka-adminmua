<?php

namespace app\admin\script;

use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BillDetailModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberMoneyModel;
use app\admin\model\MemberWithdrawalModel;
use app\admin\model\PackModel;
use app\admin\model\UserAssetLogModel;
use app\admin\script\analysis\GiftsCommon;
use app\common\ParseUserStateDataCommmon;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class BillDetailCommand extends Command
{
    protected function configure()
    {
        $this->setName('BillDetailCommand')->setDescription('BillDetailCommand');
    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        //时间条件
        try{
            $start_time = date('Y-m-d 00:00:00');
            $end_time = date('Y-m-d H:i:s', time());
            $stime = strtotime($start_time);
            $etime = strtotime($end_time);
            $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start_time, $end_time);
            $searchwhere=[];
            $searchwhere[] = ["success_time",">=",$stime];
            $searchwhere[] = ["success_time","<",$etime];
            //因为分库userassetlog之前的用户数据没有删除 所以先计算出来用户 来对应模型
            $uids  = UserAssetLogModel::getInstance($instance)->getuids($searchwhere);
            $userassetlogModels = UserAssetLogModel::getInstance($instance)->getModels($uids);
            //1.统计当日所有账户的充值
            $chargeWhere = [
                ['addtime', '>=', $start_time],
                ['addtime', '<', $end_time],
                ['status', 'in', '1,2'],
                ['type', '=', '1'],
            ];
            $totailCoin = ChargedetailModel::getInstance()->getModel()->field('sum(coin) as chargecoin')->where($chargeWhere)->select()->toArray();
            //2.后台代充总豆
            $adminWhere = [
                ['status', '=', 1],
                ['type', '=', 2],
                ['created_time', '>=', $stime],
                ['created_time', '<', $etime],
            ];
            $adminCoin = MemberMoneyModel::getInstance()->getModel()->field('sum(money) as admincoin')->where($adminWhere)->select()->toArray();

            //3.公会代充总豆
            /*    $UserAssetLogWhere = [
                    ['asset_id', '=', 'bean'],
                    ['event_id', '=', 10014],
                    ['success_time', '>=', $stime],
                    ['success_time', '<', $etime],
                ];
                $guildCoin = UserAssetLogModel::getInstance($instance)
                    ->field('sum(change_amount) as guildcoin')
                    ->where($UserAssetLogWhere)->select()->toArray();*/
            $agentchargeWhere = [
                ['type', '=', 2],
                ['date', '>=', date('Y-m-d', $stime)],
                ['date', '<=', date('Y-m-d', $etime)],
            ];
            $guildCoin = BiDaysUserChargeModel::getInstance()->getModel()->where($agentchargeWhere)
                ->field('sum(amount) as guildcoin')->select()->toArray();

            //4.送礼收到钻石
            $beanWhere = [
                ['event_id', '=', 10003],
                ['type', '=', 5],
                ['asset_id', '=', 'diamond'],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];
            /*     $giftBean = UserAssetLogModel::getInstance($instance)
                     ->field('sum(abs(change_amount/1000)) as giftbean')
                     ->where($beanWhere)->select()->toArray();*/
            $giftBean = 0;
            foreach ($userassetlogModels as $assetModel) {
                $giftBeanRes = $assetModel->getModel()->where($beanWhere)->where("uid","in",$assetModel->getList())
                    ->field('sum(abs(change_amount/1000)) as giftbean')->find();
                $giftBean += $giftBeanRes['giftbean'];
            }


            //5.送出礼物35%
            $giftThirtyWhere = [
                ['event_id', '=', 10002],
                ['ext_1', '<>', '395'],
                ['type', 'in', '3,4'],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];
            /*   $adminIncome = UserAssetLogModel::getInstance($instance)
                   ->field('sum(abs(ext_4)) as thirtycoin')
                   ->where($giftThirtyWhere)->select()->toArray();*/
            $adminIncome = 0;
            foreach ($userassetlogModels as $assetModel) {
                $adminIncomeRes = $assetModel->getModel()->where($giftThirtyWhere)
                    ->where("uid","in",$assetModel->getList())
                    ->field('sum(abs(ext_4)) as thirtycoin')->find();
                $adminIncome += $adminIncomeRes['thirtycoin'];
            }


            //6.钻石兑豆
            $exchargeWhere = [
                ['event_id', '=', 10004],
                ['type', '=', 5],
                ['asset_id', '=', 'diamond'],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];
            /*  $convertCoin = UserAssetLogModel::getInstance($instance)
                  ->field('sum(abs(change_amount/1000)) as convertcoin')
                  ->where($exchargeWhere)->select()->toArray();*/
            $convertCoin = 0;
            foreach ($userassetlogModels as $assetModel) {
                $convertCoinRes = $assetModel->getModel()->where($exchargeWhere)
                    ->where("uid","in",$assetModel->getList())
                    ->field('sum(abs(change_amount/1000)) as convertcoin')->find();
                $convertCoin += $convertCoinRes['convertcoin'];
            }


            //7.直送礼物
            $giftWhere = [
                ['event_id', '=', 10002],
                ['type', '=', 4],
                ['ext_1', 'not in', [395]],
                ['asset_id', '=', 'bean'],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];
            /*   $giftCoin = UserAssetLogModel::getInstance($instance)
                   ->field('sum(abs(change_amount)) as giftcoin')
                   ->where($giftWhere)->select()->toArray();*/
            $giftCoin = 0;
            foreach ($userassetlogModels as $assetModel) {
                $giftCoinRes = $assetModel->getModel()->where($giftWhere)
                    ->where("uid","in",$assetModel->getList())
                    ->field('sum(abs(change_amount)) as giftcoin')->find();
                $giftCoin += $giftCoinRes['giftcoin'];
            }


            //8.背包赠送礼物
            $packgiftWhere = [
                ['event_id', '=', 10002],
                ['type', '=', 3],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];
            /*   $packgift_Coin = UserAssetLogModel::getInstance($instance)
                   ->field('sum(ext_4) as packgiftcoin')
                   ->where($packgiftWhere)
                   ->select()->toArray();*/

            $packgift_Coin = 0;
            foreach ($userassetlogModels as $assetModel) {
                $packgift_CoinRes = $assetModel->getModel()->where($packgiftWhere)
                    ->field('sum(ext_4) as packgiftcoin')
                    ->find();
                $packgift_Coin += $packgift_CoinRes['packgiftcoin'];
            }

            //9.开箱子
            //金宝箱
            $Where2 = [
                ['event_id', '=', 10009],
                ['type', '=', 4],
                ['asset_id', '=', 'bean'],
                ['ext_1', '=', 'box'],
                ['ext_2', '=', 'gold'],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];
            /*$big = UserAssetLogModel::getInstance($instance)
                ->field('sum(abs(change_amount)) sum')
                ->where($Where2)
                ->select()->toArray();*/
            $big = 0;
            foreach ($userassetlogModels as $assetModel) {
                $bigRes = $assetModel->getModel()->where($Where2)
                    ->where("uid","in",$assetModel->getList())
                    ->field('sum(abs(change_amount)) sum')
                    ->find();
                $big += $bigRes['sum'];
            }


            //银宝箱
            $Where1 = [
                ['event_id', '=', 10009],
                ['type', '=', 4],
                ['asset_id', '=', 'bean'],
                ['ext_1', '=', 'box'],
                ['ext_2', '=', 'silver'],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];
            /*$small = UserAssetLogModel::getInstance($instance)
                ->field('sum(abs(change_amount)) sum')
                ->where($Where1)
                ->select()->toArray();*/

            $small = 0;
            foreach ($userassetlogModels as $assetModel) {
                $smallRes = $assetModel->getModel()
                    ->where("uid","in",$assetModel->getList())
                    ->field('sum(abs(change_amount)) sum')
                    ->where($Where1)
                    ->find();
                $small += $smallRes['sum'];
            }

            //20200722--新增装扮消耗
            $attireWhere = [
                ['event_id', '=', 10005],
                ['type', '=', 4],
                ['asset_id', '=', 'bean'],
                ['ext_4', '=', 'attire'],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];
            /*    $attireCoin = UserAssetLogModel::getInstance($instance)
                    ->field('sum(abs(change_amount)) as attirecoin')
                    ->where($attireWhere)->select()->toArray();*/

            $attireCoin = 0;
            foreach ($userassetlogModels as $assetModel) {
                $attireCoinRes = $assetModel->getModel()
                    ->field('sum(abs(change_amount)) as attirecoin')
                    ->where("uid","in",$assetModel->getList())
                    ->where($attireWhere)
                    ->find();
                $attireCoin += $attireCoinRes['attirecoin'];
            }

            //10.提现钻石(成功)
            $cashWhere = [
                ['created_time', '>=', $stime],
                ['created_time', '<', $etime],
                ['status', 'in', '1,3'],
            ];
            $cashDiamond = MemberWithdrawalModel::getInstance()->getModel()
                ->field('sum(diamond) as cashdiamond')
                ->where($cashWhere)->select()->toArray();

            //11.开箱子特殊礼物
            $gift = array_column(GiftsCommon::getInstance()->getGiftMap(), 'gift_coin', 'id');


            //12.后台减M豆
            $adminReduceWhere = [
                ['created_time', '>=', $stime],
                ['created_time', '<', $etime],
                ['status', '=', 2],
                ['type', '=', 2],
            ];
            $reduceCoin = MemberMoneyModel::getInstance()->getModel()->field('sum(money) as reducecoin')->where($adminReduceWhere)->select()->toArray();

            //13.历史背包剩余
            $packCount = 0;
            /*   $splsq2 = "select sum(pack_num) as num,gift_id from zb_pack GROUP BY gift_id";
                 $pack = Db::query($splsq2);
                 $packCount = 0;
                 foreach ($pack as $key => $value) {
                     $packCount += ($gift[$value['gift_id']] * $value['num']);
                 }*/

            $packModels = PackModel::getInstance()->getallModel();
            foreach ($packModels as $packmodel) {
                $packcountRes = $packmodel->getModel()->field("sum(pack_num) as num,gift_id")
                    ->group("gift_id")->select()->toArray();
                foreach ($packcountRes as $key => $value) {
                    $packCount += ($gift[$value['gift_id']] * $value['num']);
                }
            }

            //14.许愿石剩余
            //大
            //$smasheggs = FirstpayHammersModel::getInstance()->getModel()->where([['createTime', '>=', $stime], ['createTime', '<', $etime], ['status', '=', 1], ['status', '=', 1]])->select()->count();
            //小
            //$hammers = FirstpayHammersModel::getInstance()->getModel()->where([['createTime', '>=', $stime], ['createTime', '<', $etime], ['status', '=', 0], ['status', '=', 1]])->select()->count();
            //$newuserhammers = FirstpayHammersModel::getInstance()->getModel()->where([['createTime', '>=', $stime], ['createTime', '<', $etime]])->select()->count();

            /*    $member = MemberModel::getInstance()
                    ->field('sum(totalcoin) as totalcoin,sum(freecoin) as freecoin,sum(diamond) as diamond,sum(exchange_diamond) as exchange_diamond,sum(free_diamond) as free_diamond')
                    ->select()
                    ->toArray();*/
            $totalcoin = 0;
            $freecoin = 0;
            //16.未提现钻石
            $diamond = 0;
            $exchange_diamond = 0;
            $free_diamond = 0;
            $memberModels = MemberModel::getInstance()->getallModel();
            foreach ($memberModels as $membermodel) {
                $memberextension = $membermodel->getModel()
                    ->field('sum(totalcoin) as totalcoin,sum(freecoin) as freecoin,sum(diamond) as diamond,sum(exchange_diamond) as exchange_diamond,sum(free_diamond) as free_diamond')
                    ->find();
                $totalcoin += ($memberextension['totalcoin'] ?? 0);
                $freecoin += ($memberextension['freecoin'] ?? 0);
                $diamond += ($memberextension['diamond'] ?? 0);
                $exchange_diamond += ($memberextension['exchange_diamond'] ?? 0);
                $free_diamond += ($memberextension['free_diamond'] ?? 0);
            }

            $nocashWhere = [
                ['created_time', '>=', $stime],
                ['created_time', '<', $etime],
                ['status', '<', '1'],
            ];
            $nocashDiamond = MemberWithdrawalModel::getInstance()->getModel()
                ->field('sum(diamond) as cashdiamond')
                ->where($nocashWhere)->select()->toArray();


            //17.开箱子礼物到礼物未送出(包括历史和今天的礼物)
            // $spl3 = "select giftid,sum(coin) as giftcount from zb_coindetail where action in ('BreakEggGetGiftj,BreakEggGetGifty') and addtime >= '" . $start_time . "' and addtime < '" . $end_time . "'group by giftid";
            // $boxdata = Db::query($spl3);

            $boxwhere = [
                ['event_id', '=', 10009],
                ['type', '=', 3],
                ['ext_1', '=', 'box'],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];

            /*        $boxdata = UserAssetLogModel::getInstance($instance)
                        ->field('asset_id as giftid,sum(change_amount) as giftcount')
                        ->where($boxwhere)
                        ->group('asset_id')
                        ->select()
                        ->toArray();*/
            $boxGift = 0;
            $eggGift = [];

            foreach ($userassetlogModels as $boxmodel) {
                $boxdata = $boxmodel->getModel()->field("asset_id as giftid,sum(change_amount) as giftcount")
                    ->where($boxwhere)
                    ->where("uid","in",$assetModel->getList())
                    ->group('asset_id')
                    ->select()->toArray();
                foreach ($boxdata as $key => $value) {
                    $boxGift += ($gift[$value['giftid']] * (int)$value['giftcount']);
                    $eggGift[$value['giftid']] = (int)$value['giftcount'];
                }
            }

            //18.开箱子得到礼物送出(开箱子得到礼物 - 背包(在今天开的箱子里的)赠送礼物ID)
            // $spl4 = "select giftid,sum(giftcount) as giftcount from zb_coindetail where action = 'sendgiftFromBag' and addtime >= '" . $start_time . "' and addtime < '" . $end_time . "'group by giftid";
            // $boxGiftData = Db::query($spl4);

//            $boxGiftData = UserAssetLogModel::getInstance($instance)
//                ->field('asset_id as giftid,sum(abs(change_amount)) as giftcount')
//                ->where(
//                    [
//                        ['event_id', '=', 10002],
//                        ['type', '=', 3],
//                        ['success_time', '>=', $stime],
//                        ['success_time', '<', $etime],
//                    ]
//                )
//                ->group('asset_id')
//                ->select()
//                ->toArray();

            $boxGiftWhere = [
                ['event_id', '=', 10002],
                ['type', '=', 3],
                ['success_time', '>=', $stime],
                ['success_time', '<', $etime],
            ];

            $packGift = [];
            foreach ($userassetlogModels as $boxgiftmodel) {
                $boxGiftData = $boxgiftmodel->getModel()
                    ->field('asset_id as giftid,sum(abs(change_amount)) as giftcount')
                    ->where("uid","in",$assetModel->getList())
                    ->where($boxGiftWhere)
                    ->group('asset_id')
                    ->select()->toArray();
                foreach ($boxGiftData as $key => $value) {
                    if (isset($packGift[$value['giftid']])) {
                        $packGift[$value['giftid']] += $value['giftcount'];
                    } else {
                        $packGift[$value['giftid']] = $value['giftcount'];
                    }
                }
            }


            $packResult = array_udiff_uassoc($packGift, $eggGift, "myfunction_diff", "myfunction_diff");
            $packCoin = 0;
            foreach ($packResult as $key => $value) {
                $packCoin += ($gift[$key] * $value);
            }

            //vip
            $where1[] = ['status', 'in', '1,2'];
            $where1[] = ['type', 'in', '2,3'];
            $where1[] = ['addtime', '>=', $start_time];
            $where1[] = ['addtime', '<', $end_time];
            $vip = ChargedetailModel::getInstance()->getModel()->where($where1)->field('sum(rmb) rmb')->select()->toArray();

            //取得上一次所有数据
            $data['vip'] = floor($vip[0]['rmb']);
            $data['charge_coin'] = floor($totailCoin[0]['chargecoin']); //充值总豆
            $data['attire_coin'] = floor($attireCoin); //装扮消耗
            $data['admin_coin'] = floor($adminCoin[0]['admincoin']); //后台代充总豆
            $data['guild_coin'] = floor($guildCoin[0]['guildcoin']); //公会代充总豆
            $data['gift_diamond'] = floor($giftBean); //送礼收到(钻石)
            $data['admin_income'] = floor($adminIncome * 0.35); //送出礼物30%平台收益
            $data['convert_coin'] = floor($convertCoin); //钻石兑换豆
            $data['gift_coin'] = floor($giftCoin); //直送礼物
            $data['packgift_coin'] = floor($packgift_Coin); //背包赠送礼物
            $data['hammer_coin'] = floor($big + $small); //开金银宝箱
            $data['cash_diamond'] = floor($cashDiamond[0]['cashdiamond']); //提现(钻石)
            //        $data['special_gift'] = $specialGift; //开箱特殊礼物
            $data['special_gift'] = 0; //开箱特殊礼物
            $data['reduce_coin'] = floor($reduceCoin[0]['reducecoin']); //后台减M豆
            $data['history_pack'] = $packCount; //历史背包剩余
            //$data['keys'] = abs($newuserhammers / 3); //首冲人数
            //$data['gem_coin'] = $hammers; //许愿石剩余数量
            $data['free_coin'] = floor($totalcoin) - floor($freecoin); //未消耗M豆
            $data['nocash_diamond'] = floor($diamond) - floor($exchange_diamond) - floor($free_diamond); //未提现钻石
            $data['box_gift'] = abs($boxGift - $packCoin); //开箱子礼物到礼物未送出
            $data['boxsend_gift'] = $boxGift; //开箱子礼物到礼物送出
            $data['finger_coin'] = 0; //发起猜拳
            $data['finger_meet_coin'] = 0; //猜拳迎战
            $data['withdrawing'] = floor($nocashDiamond[0]['cashdiamond']) / 10000; //未提现的钻石
            $data['ctime'] = date('Y-m-d H:i:s', time());
            BillDetailModel::getInstance()->getModel()->insert($data);

        }catch (\Throwable $e){
            Log::error("billdetailcommand:error:".$e->getMessage());
        }

    }

}

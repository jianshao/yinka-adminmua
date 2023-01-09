<?php
/*
 * bI充值率脚本
 */

namespace app\admin\script;

use app\admin\model\BiChargeModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\MemberModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);
//数据应该用不到 暂时不做修改了
class BIChargeCommand extends Command
{
    protected function configure()
    {
        $this->setName('BIChargeCommand')->setDescription('BIChargeCommand');
    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        $day7 = date('Y-m-d', strtotime('-7 day'));
        $day8 = date('Y-m-d', strtotime('-8 day'));
        $day6 = date('Y-m-d', strtotime('-6 day'));
        $day5 = date('Y-m-d', strtotime('-5 day'));
        $day4 = date('Y-m-d', strtotime('-4 day'));
        $day3 = date('Y-m-d', strtotime('-3 day'));
        $day2 = date('Y-m-d', strtotime('-2 day'));
        $day1 = date('Y-m-d', strtotime('-1 day'));
        $day = date('Y-m-d');

        //新增加一条数据
        $data = [
            "riq" => strtotime($day1), //日期
            "cinczl" => 0,
            "sannczl" => 0,
            "qinczl" => 0,
            "ciczl" => 0,
            "sanczl" => 0,
            "qiczl" => 0,
        ];
        BiChargeModel::getInstance()->getModel()->save($data);
        //新增用户次日 注册用户
        $res1 = MemberModel::getInstance()->getMemberList([['register_time', '>=', $day2], ['register_time', '<', $day1]], 'id');
        //新增用户三日 注册用户
        $res3 = MemberModel::getInstance()->getMemberList([['register_time', '>=', $day4], ['register_time', '<', $day3]], 'id');
        //新增用户七日 注册用户
        $res7 = MemberModel::getInstance()->getMemberList([['register_time', '>=', $day8], ['register_time', '<', $day7]], 'id');

        $count1 = count($res1);
        if ($count1) {
            //次日充值的用户
            $res1_user_string = array_column($res1, 'id');
            $where1[] = ['uid', 'in', $res1_user_string];
            $where1[] = ['status', 'in', [1, 2]];
            $where1[] = ['addtime', '>=', $day1];
            $where1[] = ['addtime', '<', $day];
            $chargeres1 = ChargedetailModel::getInstance()->getModel()->field('uid as user_id,date_format(addtime,"%Y-%m-%d") as a')->where($where1)->group('uid')->select()->toArray();
            //次日新增充值率
            $tmp1 = [];
            $cinczl = 0;
            foreach ($chargeres1 as $key => $value) {
                $tmp1[$value['user_id']][] = $value['a'];
            }
            foreach ($tmp1 as $key => $value) {
                if (in_array($key, $res1_user_string)) {
                    if (in_array($day1, $value)) {
                        $cinczl += 1;
                    }
                }
            }
            //修改充值率
            $update1['cinczl'] = round($cinczl / $count1 * 100, 2);
            BiChargeModel::getInstance()->getModel()->where(array("riq" => strtotime($day2)))->save($update1);
        }
        //新增三日充值率
        $count3 = count($res3);
        if ($count3) {
            //次日到三日充值的用户
            $res3_user_string = array_column($res3, 'id');
            $where3[] = ['uid', 'in', $res3_user_string];
            $where3[] = ['status', 'in', [1, 2]];
            $where3[] = ['addtime', '>=', $day3];
            $where3[] = ['addtime', '<', $day];
            $chargeres3 = ChargedetailModel::getInstance()->getModel()->field('uid as user_id,date_format(addtime,"%Y-%m-%d") as a')->where($where3)->group('uid')->select()->toArray();
            //三日新增充值率
            $tmp3 = [];
            $sannczl = 0;
            foreach ($chargeres3 as $key => $value) {
                $tmp3[$value['user_id']][] = $value['a'];
            }
            foreach ($tmp3 as $key => $value) {
                if (in_array($key, $res3_user_string)) {
                    if (in_array($day1, $value) && in_array($day2, $value) && in_array($day3, $value)) {
                        $sannczl += 1;
                    }
                }
            }
            //修改充值率
            $update3['sannczl'] = round($sannczl / $count3 * 100, 2);
            BiChargeModel::getInstance()->getModel()->where(array("riq" => strtotime($day4)))->save($update3);
        }
        //新增七日充值率
        $count7 = count($res7);
        if ($count7) {
            //次日到七日充值的用户
            $res7_user_string = array_column($res7, 'id');
            $where7[] = ['uid', 'in', $res7_user_string];
            $where7[] = ['status', 'in', [1, 2]];
            $where7[] = ['addtime', '>=', $day7];
            $where7[] = ['addtime', '<', $day];
            $chargeres7 = ChargedetailModel::getInstance()->getModel()->field('uid as user_id,date_format(addtime,"%Y-%m-%d") as a')->where($where7)->group('uid')->select()->toArray();
            //七日新增充值率
            $tmp7 = [];
            $qinczl = 0;
            foreach ($chargeres7 as $key => $value) {
                $tmp7[$value['user_id']][] = $value['a'];
            }
            foreach ($tmp7 as $key => $value) {
                if (in_array($key, $res7_user_string)) {
                    if (in_array($day1, $value) && in_array($day2, $value) && in_array($day3, $value) && in_array($day4, $value) && in_array($day5, $value) && in_array($day6, $value) && in_array($day7, $value)) {
                        $qinczl += 1;
                    }
                }
            }
            //修改充值率
            $update7['qinczl'] = round($qinczl / $count7 * 100, 2) * 100;
            BiChargeModel::getInstance()->getModel()->where(array("riq" => strtotime($day8)))->save($update7);
        }
        //次日充值率(前一天充值人数/第二天充值人数)
        $ciczl = 0;
        $ciwhere[] = ['status', 'in', [1, 2]];
        $ciwhere[] = ['addtime', '>=', $day2];
        $ciwhere[] = ['addtime', '<', $day1];
        $count_ci_res = ChargedetailModel::getInstance()->getModel()->field('uid')->where($ciwhere)->group('uid')->select()->toArray();
        $count_ci = count($count_ci_res);
        if ($count_ci) {
            $ci_user_string = array_column($count_ci_res, 'uid');
            $ciwhere1[] = ['uid', 'in', $ci_user_string];
            $ciwhere1[] = ['status', 'in', [1, 2]];
            $ciwhere1[] = ['addtime', '>=', $day1];
            $ciwhere1[] = ['addtime', '<', $day];
            $chargeres_ci = ChargedetailModel::getInstance()->getModel()->field('uid as user_id,date_format(addtime,"%Y-%m-%d") as a')->where($ciwhere1)->group('uid')->select()->toArray();
            //次日充值率
            $tmp_ci = [];
            foreach ($chargeres_ci as $key => $value) {
                $tmp_ci[$value['user_id']][] = $value['a'];
            }
            foreach ($tmp_ci as $key => $value) {
                if (in_array($key, $ci_user_string)) {
                    if (in_array($day1, $value)) {
                        $ciczl += 1;
                    }
                }
            }
            //修改充值率
            $update1['ciczl'] = round($ciczl / $count_ci * 100, 2) * 100;
            BiChargeModel::getInstance()->getModel()->where(array("riq" => strtotime($day2)))->save($update1);
        }
        //三日充值率(前一天充值人数/第二三天充值人数)
        $sanczl = 0;
        $sanwhere[] = ['status', 'in', [1, 2]];
        $sanwhere[] = ['addtime', '>=', $day4];
        $sanwhere[] = ['addtime', '<', $day3];
        $count_san_res = ChargedetailModel::getInstance()->getModel()->field('uid')->where($sanwhere)->group('uid')->select()->toArray();
        $count_san = count($count_san_res);
        if ($count_san) {
            $san_user_string = array_column($count_san_res, 'uid');
            $sanwhere3[] = ['uid', 'in', $san_user_string];
            $sanwhere3[] = ['status', 'in', [1, 2]];
            $sanwhere3[] = ['addtime', '>=', $day3];
            $sanwhere3[] = ['addtime', '<', $day];
            $chargeres_san = ChargedetailModel::getInstance()->getModel()->field('uid as user_id,date_format(addtime,"%Y-%m-%d") as a')->where($sanwhere3)->group('uid')->select()->toArray();
            //三日充值率
            $tmp_san = [];
            foreach ($chargeres_san as $key => $value) {
                $tmp_san[$value['user_id']][] = $value['a'];
            }
            foreach ($tmp_san as $key => $value) {
                if (in_array($key, $san_user_string)) {
                    if (in_array($day1, $value) && in_array($day2, $value) && in_array($day3, $value)) {
                        $sanczl += 1;
                    }
                }
            }
            //修改充值率
            $update3['sanczl'] = round($sanczl / $count_san * 100, 2) * 100;
            BiChargeModel::getInstance()->getModel()->where(array("riq" => strtotime($day4)))->save($update3);
        }
        //七日充值率(前一天充值人数/第二七天充值人数)
        $qiczl = 0;
        $qiwhere[] = ['status', 'in', [1, 2]];
        $qiwhere[] = ['addtime', '>=', $day8];
        $qiwhere[] = ['addtime', '<', $day7];
        $count_qi_res = ChargedetailModel::getInstance()->getModel()->field('uid')->where($qiwhere)->group('uid')->select()->toArray();
        $count_qi = count($count_qi_res);
        if ($count_qi) {
            $qi_user_string = array_column($count_qi_res, 'uid');
            $qiwhere7[] = ['uid', 'in', $qi_user_string];
            $qiwhere7[] = ['status', 'in', [1, 2]];
            $qiwhere7[] = ['addtime', '>=', $day7];
            $qiwhere7[] = ['addtime', '<', $day];
            $chargeres_qi = ChargedetailModel::getInstance()->getModel()->field('uid as user_id,date_format(addtime,"%Y-%m-%d") as a')->where($qiwhere7)->group('uid')->select()->toArray();
            //七日充值率
            $tmp_qi = [];
            foreach ($chargeres_qi as $key => $value) {
                $tmp_qi[$value['user_id']][] = $value['a'];
            }
            foreach ($tmp_qi as $key => $value) {
                if (in_array($key, $qi_user_string)) {
                    if (in_array($day1, $value) && in_array($day2, $value) && in_array($day3, $value) && in_array($day4, $value) && in_array($day5, $value) && in_array($day6, $value) && in_array($day7, $value)) {
                        $qiczl += 1;
                    }
                }
            }
            //修改充值率
            $update7['qiczl'] = round($qiczl / $count_qi * 100, 2) * 100;
            BiChargeModel::getInstance()->getModel()->where(array("riq" => strtotime($day8)))->save($update7);
        }
        die(1);

    }

}

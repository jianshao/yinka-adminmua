<?php

namespace app\admin\script;

use app\common\ParseUserStateByUniqkey;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

//未使用的脚本 jobby没此任务脚本
//统计每天的日常数据量 日活 新增 充值人数 充值总金额
class NewCheckCommand extends Command
{
    // const  UPDATE_LIMIT = 1000;
    const TABLENAME_1DAY = 'bi_user_stats_1day'; //1天的数据表


    protected function configure()
    {
        // 指令配置
        $this->setName('NewCheckCommand')
            ->setDescription('NewCheckCommand')
            ->addArgument('start_time', Argument::OPTIONAL, "start_time", date('Y-m-d', strtotime("-1 days")))
            ->addArgument('end_time', Argument::OPTIONAL, "end_time", date('Y-m-d'));
    }


    protected function execute(Input $input, Output $output)
    {
        try {
            $start_time = $input->getArgument('start_time');
            $end_time = $input->getArgument('end_time');
            $condition = [];
            $condition[] = ['date', '>=', $start_time];
            $condition[] = ['date', '<=', $end_time];
            $uids = Db::table(self::TABLENAME_1DAY)->field('uid')->where($condition)->select()->toArray();
            $handRes = [];
            foreach ($uids as $uidinfo) {
                //根据条件来获取某个用户的合并json
                $uid = $uidinfo['uid'];
                $res = $this->baseExecute($start_time, $end_time, $uid);
                if (empty($res)) {
                    continue;
                }

                $res['taojin'] && $handRes['taojin'][$uid] = $res['taojin'];
                $res['box2'] && $handRes['box2'][$uid] = $res['box2'];
                $res['turntable'] && $handRes['turntable'][$uid] = $res['turntable'];
            }



            if (isset($handRes['box2']) && count($handRes['box2']) > 0 ) {
                $box2Info = $handRes['box2'];
                krsort($box2Info);
                $showBox2Res = [];
                foreach ($box2Info as $key => $items) {
                    foreach ($items as $mapid => $item) {
                        $showBox2Res['reward'][$mapid][] = [$key . "-" . $item['reward'],$item['reward']];
                        $showBox2Res['consume'][$mapid][] = [$key . "-" . $item['consume'],$item['consume']];
                    }
                }

                foreach ($showBox2Res['reward'] as $key => $itemre) {
                    echo PHP_EOL . "========START:产出========box2-mapid:{$key}" . PHP_EOL . PHP_EOL;
                    $totalnumber=0;
                    foreach ($itemre as $sdata) {
                        echo $sdata[0] . PHP_EOL;
                        $totalnumber+=$sdata[1];

                    }
                    echo PHP_EOL . "box2-mapid:{$key} 总产出:{$totalnumber}" . PHP_EOL;
                    echo PHP_EOL . "========END==============================" . PHP_EOL;
                }

                foreach ($showBox2Res['consume'] as $key => $itemre) {
                    echo PHP_EOL . "========START:消耗========box2-mapid:{$key}" . PHP_EOL . PHP_EOL;
                    $totalnumber=0;
                    foreach ($itemre as $sdata) {
                        echo $sdata[0] . PHP_EOL;
                        $totalnumber+=$sdata[1];
                    }
                    echo PHP_EOL . "box2-mapid:{$key} 总消耗:{$totalnumber}" . PHP_EOL;
                    echo PHP_EOL . "========END==============================" . PHP_EOL;
                }

            }




            if (isset($handRes['taojin']) && count($handRes['taojin'])>0) {
                $taojinInfo = $handRes['taojin'];
                krsort($taojinInfo);
                $showTaoJinRes = [];
                foreach ($taojinInfo as $key => $items) {
                    foreach ($items as $mapid => $item) {
                        $showTaoJinRes['reward'][$mapid][] = [$key . "-" . $item['reward'],$item['reward']];
                        $showTaoJinRes['consume'][$mapid][] = [$key . "-" . $item['consume'],$item['consume']];
                    }
                }

                foreach ($showTaoJinRes['reward'] as $key => $itemre) {
                    echo PHP_EOL . "========START:产出========taojin-mapid:{$key}" . PHP_EOL . PHP_EOL;
                    $totalnumber=0;
                    foreach ($itemre as $sdata) {
                        echo $sdata[0] . PHP_EOL;
                        $totalnumber+=$sdata[1];
                    }
                    echo PHP_EOL . "taojin-mapid:{$key} 总产出:{$totalnumber}" . PHP_EOL;
                    echo PHP_EOL . "========END==============================" . PHP_EOL;
                }

                foreach ($showTaoJinRes['consume'] as $key => $itemre) {
                    echo PHP_EOL . "========START:消耗========taojin-mapid:{$key}" . PHP_EOL . PHP_EOL;
                    $totalnumber=0;
                    foreach ($itemre as $sdata) {
                        echo $sdata[0] . PHP_EOL;
                        $totalnumber+=$sdata[1];
                    }
                    echo PHP_EOL . "taojin-mapid:{$key} 总消耗:{$totalnumber}" . PHP_EOL;
                    echo PHP_EOL . "========END==============================" . PHP_EOL;
                }

            }




            if (isset($handRes['turntable']) && count($handRes['turntable'])>0) {
                $turntableInfo = $handRes['turntable'];
                krsort($turntableInfo);
                $showTurntableRes = [];
                foreach ($turntableInfo as $key => $items) {
                    foreach ($items as $mapid => $item) {
                        $showTurntableRes['reward'][$mapid][] = [$key . "-" . $item['reward'],$item['reward']];
                        $showTurntableRes['consume'][$mapid][] = [$key . "-" . $item['consume'],$item['consume']];
                    }
                }

                foreach ($showTurntableRes['reward'] as $key => $itemre) {
                    echo PHP_EOL . "========START:产出========turntable-mapid:{$key}" . PHP_EOL . PHP_EOL;
                    $totalnumber=0;
                    foreach ($itemre as $sdata) {
                        echo $sdata[0] . PHP_EOL;
                        $totalnumber+=$sdata[1];
                    }
                    echo PHP_EOL . "turntable-mapid:{$key} 总产出:{$totalnumber}" . PHP_EOL;
                    echo PHP_EOL . "========END==============================" . PHP_EOL;
                }

                foreach ($showTurntableRes['consume'] as $key => $itemre) {
                    echo PHP_EOL . "========START:消耗========turntable-mapid:{$key}" . PHP_EOL . PHP_EOL;
                    $totalnumber=0;
                    foreach ($itemre as $sdata) {
                        echo $sdata[0] . PHP_EOL;
                        $totalnumber+=$sdata[1];
                    }
                    echo PHP_EOL . "turntable-mapid:{$key} 总消耗:{$totalnumber}" . PHP_EOL;
                    echo PHP_EOL . "========END==============================" . PHP_EOL;
                }

            }

        } catch (\Throwable $e) {
            dump($e->getMessage(), $e->getLine(), $e->getFile());
        }


    }

    public  function baseExecute($start_time, $end_time, $uid)
    {
        $returnData = [];
        $condition = [];
        $condition[] = ['date', '>=', $start_time];
        $condition[] = ['date', '<=', $end_time];
        $condition[] = ['uid', '=', $uid];
        $parseCustomData = ParseUserStateByUniqkey::getInstance()->parseCustomData(self::TABLENAME_1DAY, $condition, "uid:$uid"); //所有的数据
        $agentchargeamount = ParseUserStateByUniqkey::getInstance()->getAgentChargeSum($parseCustomData, 'agentcharge');
        $vipamount = ParseUserStateByUniqkey::getInstance()->getChargeVipSum($parseCustomData, 'charge');
        $directamount = ParseUserStateByUniqkey::getInstance()->getChargeSum($parseCustomData, 'charge');
        $totalamount = $agentchargeamount + $directamount;
        $activityData = [];
        if (array_key_exists("activity", $parseCustomData)) {
            $activityData['activity'] = $parseCustomData['activity']['data'];
        }


        $box2info = ParseUserStateByUniqkey::getInstance()->getActivitySum($activityData, 'box2');
        $turntableinfo = ParseUserStateByUniqkey::getInstance()->getActivitySum($activityData, 'turntable');
        $taojininfo = ParseUserStateByUniqkey::getInstance()->getActivitySum($activityData, 'taojin');


        $returnData['agentcharge'] = $agentchargeamount;
        $returnData['dirctamount'] = $directamount;
        $returnData['totalamount'] = $totalamount;
        $returnData['vipamount'] = $vipamount;

        $returnData['box2'] = $box2info;
        $returnData['turntable'] = $turntableinfo;
        $returnData['taojin'] = $taojininfo;

        return $returnData;
    }


    /**
     * 获取时间节点列表
     * @param $start
     * @return array
     */
    public
    function getTimeNode($start, $enddate)
    {
        if (empty($start) || empty($enddate)) {
            return [];
        }
        if ($enddate) {
            $days = (strtotime($enddate) - strtotime($start)) / (24 * 3600);
        } else {
            $days = (strtotime(date('Y-m-d')) - strtotime($start)) / (24 * 3600);
        }

        $list = [];
        for ($i = 0; $i < $days; $i++) {
            $list[] = date('Y-m-d', strtotime($start . " +$i days"));
        }

        return $list;
    }


//相除
    public
    function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }


}

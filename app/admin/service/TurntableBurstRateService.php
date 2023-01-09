<?php

namespace app\admin\service;

use app\admin\model\ConfigModel;
use app\admin\model\DaysUserTurntableDatasModel;
use app\admin\model\TurntableReUserGiftModel;
use app\admin\model\UserAssetLogModel;
use app\common\ParseUserStateDataCommmon;
use think\facade\Db;

class TurntableBurstRateService
{
    protected static $instance;
    protected static $event_id = 10009;
    protected static $type = ['产出' => 3, '消耗' => 2];
    protected static $ext_1 = 'turntable';
    protected static $ext_2 = ['小转盘' => 1, '大转盘' => 2];
    protected static $turntable = [1 => '小转盘', 2 => '大转盘'];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TurntableBurstRateService();
        }
        return self::$instance;
    }

    public function RealTimeRate($array)
    {
        $pagenum = 5;
        $page = !empty($array['page']) ? ($array['page'] - 1) * $pagenum : 0;
        $master_page = !empty($array['page']) ? $array['page'] : 1;
        $data = [];
        $demo = array_key_exists('demo', $array) ? $array['demo'] : getDefaultDate();
        list($start, $end) = getBetweenDate($demo);

        $turntableId = array_key_exists('uid', $array) ? $array['turntableId'] : 1;
        $uid = array_key_exists('uid', $array) ? $array['uid'] : false;
        if ($uid) {
            $src = $this->getUserExplodeRate($uid, $turntableId, $start, $end);
            $data[] = ['uid' => $uid, 'turntableId' => $turntableId, 'output' => $src['output'], 'consumption' => $src['consumption'], 'explodeRate' => $src['explodeRate']];
            $count = 1;
        } else {
            $count = Db::table(getTable(formatDate($start), formatDate($end)))
                ->where(
                    [
                        ['event_id', '=', self::$event_id],
                        ['type', '=', self::$type['消耗']],
                        ['ext_1', '=', self::$ext_1],
                        ['ext_2', '=', $turntableId],
                        ['success_time', '>=', strtotime($start)],
                        ['success_time', '<', strtotime($end)],
                    ]
                )
                ->group('uid')
                ->count();
            $uidArray = $this->getTurntableUsers($start, $end, $pagenum, $page, $turntableId);
            foreach ($uidArray as $k => $v) {
                $src = $this->getUserExplodeRate($v['uid'], $turntableId, $start, $end);
                $data[] = [
                    'uid' => $v['uid'],
                    'turntableId' => $turntableId,
                    'output' => $src['output'],
                    'consumption' => $src['consumption'],
                    'explodeRate' => $src['explodeRate'],
                ];
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $explodeRate = $this->getTurntableUsersTheTotal($start, $end, $turntableId);
        return ['data' => $data, 'page' => $page_array, 'turntableId' => $turntableId, 'explodeRate' => $explodeRate, 'demo' => $demo];
    }

    public function getUserExplodeRate($uid, $turntableId, $start, $end)
    {
        $where[] = ['event_id', '=', self::$event_id];
        $where[] = ['type', '=', self::$type['消耗']];
        $where[] = ['uid', '=', $uid];
        $where[] = ['ext_1', '=', self::$ext_1];
        $where[] = ['ext_2', '=', $turntableId];
        $where[] = ['created_time', '>=', $start];
        $where[] = ['created_time', '<', $end];
        $instance = ParseUserStateDataCommmon::getInstance()->getMonthTableName(date('Y-m-d H:i:s', $start), date('Y-m-d H:i:s', $end));
        $consumption = abs(UserAssetLogModel::getInstance($instance)->where($where)->sum('change_amount'));
        $where[1][2] = self::$type['产出'];
        $output = UserAssetLogModel::getInstance($instance)->where($where)->sum('ext_4');
        return ['output' => $output, 'consumption' => $consumption, 'explodeRate' => empty($output) || empty($consumption) ? 0 : round($output / $consumption, 2)];
    }

    public function getTurntableUsers($start, $end, $pagenum, $page, $turntableId)
    {
        $query = Db::table(getTable(formatDate($start), formatDate($end)))
            ->where(
                [
                    ['event_id', '=', self::$event_id],
                    ['type', '=', self::$type['消耗']],
                    ['ext_1', '=', self::$ext_1],
                    ['ext_2', '=', $turntableId],
                    ['created_time', '>=', $start],
                    ['created_time', '<', $end],
                ]
            )
            ->field('distinct(uid) as uid,sum(abs(change_amount)) amount')
            ->group('uid')
            ->order('amount desc')
            ->limit($page, $pagenum)
//            ->fetchSql(true)
            ->select()
            ->toArray();

        return $query;
    }

    public function getTurntableUsersTheTotal($start, $end, $turntableId)
    {
        $consumption = abs(Db::table(getTable(formatDate($start), formatDate($end)))
            ->where(
                [
                    ['event_id', '=', self::$event_id],
                    ['type', '=', self::$type['消耗']],
                    ['ext_1', '=', self::$ext_1],
                    ['ext_2', '=', $turntableId],
                    ['created_time', '>=', $start],
                    ['created_time', '<', $end],
                ]
            )
            ->sum('change_amount'));
        $output = Db::table(getTable(formatDate($start), formatDate($end)))
            ->where(
                [
                    ['event_id', '=', self::$event_id],
                    ['type', '=', self::$type['产出']],
                    ['ext_1', '=', self::$ext_1],
                    ['ext_2', '=', $turntableId],
                    ['created_time', '>=', $start],
                    ['created_time', '<', $end],
                ]
            )
            ->sum('ext_4');
        return ['consumption' => $consumption, 'output' => $output, 'explodeRate' => empty($output) || empty($consumption) ? 0 : round($output / $consumption, 2)];
    }

    public function cancelTheSpecifiedTurntableGift($array)
    {
        $is = TurntableReUserGiftModel::getInstance()->getModel()->where('id', $array['id'])->save(['state' => 4]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    public function addTheSpecifiedTurntableGift($array, $username)
    {
        $user_id = $array['user_id'];
        $gift_id = $array['gift_id'];
        if (empty($user_id) || empty($gift_id)) {
            echo json_encode(['code' => 500, 'msg' => '参数不可为空']);
            die;
        }
        $data = [
            'user_id' => $user_id,
            'turntable_id' => $array['turntable_id'],
            'gift_id' => $gift_id,
            'created' => time(),
            'create_user' => $username,
        ];
        $is = TurntableReUserGiftModel::getInstance()->getModel()->insert($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    public function TheSpecifiedTurntableGift($array)
    {
        $pagenum = 20;
        $page = !empty($array['page']) ? ($array['page'] - 1) * $pagenum : 0;
        $master_page = !empty($array['page']) ? $array['page'] : 1;
        $info = ['demo' => '', 'uid' => '', 'type' => ''];
        $uid = array_key_exists('uid', $array) ? $array['uid'] : false;
        if ($uid) {
            $where[] = ['user_id', '=', $uid];
            $info['user_id'] = $uid;
        }
        $gift_id = array_key_exists('gift_id', $array) ? $array['gift_id'] : false;
        if ($gift_id) {
            $where[] = ['gift_id', '=', $gift_id];
            $info['gift_id'] = $gift_id;
        }
        $turntableId = array_key_exists('turntableId', $array) ? $array['turntableId'] : false;
        if ($turntableId) {
            $where[] = ['turntable_id', '=', $turntableId];
            $info['turntable_id'] = $turntableId;
        }
        $state = array_key_exists('state', $array) ? $array['state'] : false;
        if ($state) {
            $where[] = ['state', '=', $state];
            $info['state'] = $state;
        }
        $demo = array_key_exists('demo', $array) ? $array['demo'] : getDefaultDate();
        list($start, $end) = getBetweenDate($demo);
        $where[] = ['created', '>=', strtotime($start)];
        $where[] = ['created', '<', strtotime($end)];
        $data = TurntableReUserGiftModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['name'] = $this->getGift($v['gift_id'], 'name');
            $data[$k]['turntable_id'] = self::$turntable[$v['turntable_id']];
            $data[$k]['gift_id'] = self::$turntable[$v['turntable_id']];
            $data[$k]['created'] = date('Y-m-d H:i:s', $v['created']);
            if ($v['state'] == 1) {
                $data[$k]['stateDesc'] = '未中奖';
            } elseif ($v['state'] == 3) {
                $data[$k]['stateDesc'] = '已发出';
            } else {
                $data[$k]['stateDesc'] = '已取消';
            }
        }
        $info['data'] = $data;
        $count = TurntableReUserGiftModel::getInstance()->getModel()->where($where)->count();
        $url = config('config.APP_URL_image');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $info['page_array'] = $page_array;
        $info['demo'] = $demo;
        return $info;
    }

    public function TurntableDetails($array)
    {
        $pagenum = 10;
        $page = !empty($array['page']) ? ($array['page'] - 1) * $pagenum : 0;
        $master_page = !empty($array['page']) ? $array['page'] : 1;

        $esparams = ElasticsearchService::getInstance()->searchWhere("es_zb_user_asset_log");
        $esparams['body']['from'] = $page;
        $esparams['body']['size'] = $pagenum;
        $daochu = array_key_exists('daochu', $array) ? $array['daochu'] : false;
        $esparams['body']['query']['bool']['must'][] = ['term' => ['event_id' => 10009]];
        $esparams['body']['query']['bool']['must'][] = ['term' => ['type' => 3]];
        $esparams['body']['query']['bool']['must'][] = ['term' => ['ext_1' => 'turntable']];
        $uid = array_key_exists('uid', $array) ? $array['uid'] : false;
        if ($uid) {
            $where[] = ['uid', '=', $array['uid']];
            $esparams['body']['query']['bool']['must'][] = ['term' => ['uid' => $array['uid']]];
        }
        $asset_id = array_key_exists('asset_id', $array) ? $array['asset_id'] : false;
        if ($asset_id) {
            //$where[] = ['asset_id', '=', "{$asset_id}"];
            $esparams['body']['query']['bool']['must'][] = ['term' => ['asset_id' => $asset_id]];
        }
        $type = array_key_exists('type', $array) ? $array['type'] : '小转盘';
        if ($type) {
            //$where[] = ['ext_2', '=', self::$ext_2[$type]];
            $esparams['body']['query']['bool']['must'][] = ['term' => ['ext_2' => self::$ext_2[$type]]];
        }

        $demo = array_key_exists('demo', $array) ? $array['demo'] : getDefaultDate();
        list($start, $end) = getBetweenDate($demo);

        $esparams['body']['query']['bool']['filter'] = [
            'range' => [
                'success_time' => ['gte' => strtotime($start), 'lt' => strtotime($end)],
            ],
        ];

        $esparams['body']['sort'] = ["success_time" => ["order" => "desc"]];
        $searchData = ElasticsearchService::getInstance()->search($esparams);
        $data = $searchData['data'] ?? [];
        $count = $searchData['total'] ?? 0;

        $callfunc = function ($data) {
            $url = config('config.APP_URL_image');
            foreach ($data as $k => $v) {
                $data[$k]['image'] = $url . $this->getGift($v['asset_id'], 'image');
                $data[$k]['name'] = $this->getGift($v['asset_id'], 'name');
                $data[$k]['price'] = $this->getGift($v['asset_id'], 'price')['count'];
                $data[$k]['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
                $data[$k]['change_amount'] = (int)$v['change_amount'];
            }
            return $data;
        };

        //聚合数据
        $aggsWhere = $esparams;
        $aggsWhere['size'] = 0 ;
        $aggsWhere['body']['aggs']['sum_change_amount']=['sum'=>["field"=>"change_amount"]];
        $sumsearchData = ElasticsearchService::getInstance()->searchAggs($aggsWhere);
        $change_amount = $sumsearchData['aggregations']['sum_change_amount']['value'] ?? 0 ;
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        return ['data' => $callfunc($data), 'uid' => $uid, 'demo' => $demo, 'type' => $type, 'page_array' => $page_array, 'asset_id' => $asset_id, 'change_amount' => $change_amount];
    }

    public function DaoChu($data)
    {
        $tilie = ['日期', '用户id', '礼物', '礼物', '个数', '价值', '总价值'];
        $string = implode(",", $tilie) . "\n";
        foreach ($data as $key => $value) {
            $outArray['created_time'] = $value['created_time']; //统计日期
            $outArray['uid'] = $value['uid']; //一级渠道
            $outArray['name'] = $value['name']; //一级渠道
            $outArray['asset_id'] = $value['asset_id']; //二级渠道
            $outArray['change_amount'] = $value['change_amount']; //三级渠道
            $outArray['price'] = $value['price']; //登录账号
            $outArray['ext_4'] = $value['ext_4']; //登录账号
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = '渠道分析导出时间：' . date('Y-m-d H:i:s') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    public function getGift($giftId, $keys)
    {
        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftId) {
                return $v[$keys];
            }
        }
    }

    public function TurntableBurstRate($array)
    {
        $pagenum = 20;
        $page = !empty($array['page']) ? ($array['page'] - 1) * $pagenum : 0;
        $master_page = !empty($array['page']) ? $array['page'] : 1;

        $demo = array_key_exists('demo', $array) ? $array['demo'] : getDefaultDate();
        list($start, $end) = getBetweenDate($demo);
        $where[] = ['date', '>=', $start];
        $where[] = ['date', '<', $end];

        $uid = array_key_exists('uid', $array) ? $array['uid'] : '';
        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }

        $type = array_key_exists('type', $array) ? $array['type'] : 1;
        if ($type == 1) {
            $where[] = ['small_output_amount', '>', 0];
        } elseif ($type == 2) {
            $where[] = ['in_output_amount', '>', 0];
        }

        // dump($where);die;

        $list = DaysUserTurntableDatasModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        $count = DaysUserTurntableDatasModel::getInstance()->getModel()->where($where)->count();
        $info = DaysUserTurntableDatasModel::getInstance()->getModel()
            ->where($where)
            ->field('sum(in_output_amount) in_output_amount, sum(in_consume_amount) in_consume_amount,sum(small_output_amount) small_output_amount, sum(small_consume_amount) small_consume_amount')
            ->select()
            ->toArray();

        $info = $info[0];
        $data = [];
        foreach ($list as $k => $v) {
            $data = array_merge($data, $this->TheAssembly($v, $type));
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $info['in_rate'] = empty($info['in_output_amount']) || empty($info['in_consume_amount']) ? 0 : round($info['in_output_amount'] / $info['in_consume_amount'], 3);
        $info['small_rate'] = empty($info['small_output_amount']) || empty($info['small_consume_amount']) ? 0 : round($info['small_output_amount'] / $info['small_consume_amount'], 3);
        return ['data' => $data, 'order' => 1, 'demo' => $demo, 'uid' => $uid, 'info' => $info, 'page_array' => $page_array, 'type' => $type];
    }

    public function TheAssembly($array, $type)
    {
        if ($type == 1) {
            $array = [
                [
                    'uid' => $array['uid'],
                    'turntablename' => '小转盘',
                    'consume' => $array['small_consume_amount'],
                    'output' => $array['small_output_amount'],
                    'burstrate' => empty($array['small_output_amount']) && empty($array['small_consume_amount']) ? 0 : round($array['small_output_amount'] / $array['small_consume_amount'], 3),
                ],
            ];
        } elseif ($type == 2) {
            $array = [
                [
                    'uid' => $array['uid'],
                    'turntablename' => '大转盘',
                    'consume' => $array['in_consume_amount'],
                    'output' => $array['in_output_amount'],
                    'burstrate' => empty($array['in_output_amount']) && empty($array['in_consume_amount']) ? 0 : round($array['in_output_amount'] / $array['in_consume_amount'], 3),
                ],
            ];
        }
        return $array;
    }

}
<?php

namespace app\admin\service;

use app\admin\model\Box2ReUserGiftModel;
use app\admin\model\ConfigModel;
use app\admin\model\DaysUserBoxDatasModel;
use app\admin\model\UserAssetLogModel;
use think\facade\Db;

class BoxBurstRateService
{
    protected static $instance;
    protected static $event_id = 10009;
    protected static $type = ['产出' => 3, '消耗' => 2];
    protected static $ext_1 = 'box2';
    protected static $ext_2 = ['莫提斯' => 1, '宙斯' => 2, '盖亚' => 3];
    protected static $box = [1 => '莫提斯', 2 => '宙斯', 3 => '盖亚'];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BoxBurstRateService();
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

        $boxId = array_key_exists('uid', $array) ? $array['boxId'] : 1;
        $uid = array_key_exists('uid', $array) ? $array['uid'] : false;
        if ($uid) {
            $src = $this->getUserExplodeRate($uid, $boxId, $start, $end);
            $data[] = ['uid' => $uid, 'boxId' => $boxId, 'output' => $src['output'], 'consumption' => $src['consumption'], 'explodeRate' => $src['explodeRate']];
            $count = 1;
        } else {
            $count = Db::table(getTable($start, $end))
                ->where(
                    [
                        ['event_id', '=', self::$event_id],
                        ['type', '=', self::$type['消耗']],
                        ['ext_1', '=', self::$ext_1],
                        ['ext_2', '=', $boxId],
                        ['success_time', '>=', strtotime($start)],
                        ['success_time', '<', strtotime($end)],
                    ]
                )
                ->group('uid')
                ->count();

            $uidArray = $this->getBoxUsers($start, $end, $pagenum, $page, $boxId);
            if ($uidArray) {
                foreach ($uidArray as $k => $v) {
                    $src = $this->getUserExplodeRate($v['uid'], $boxId, $start, $end);
                    $data[] = [
                        'uid' => $v['uid'],
                        'boxId' => $boxId,
                        'output' => $src['output'],
                        'consumption' => $src['consumption'],
                        'explodeRate' => $src['explodeRate'],
                    ];
                }
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $explodeRate = $this->getBoxUsersTheTotal($start, $end, $boxId);
        return ['data' => $data, 'page' => $page_array, 'boxId' => $boxId, 'explodeRate' => $explodeRate, 'demo' => $demo];
    }

    public function getUserExplodeRate($uid, $boxId, $start, $end)
    {
        $where[] = ['event_id', '=', self::$event_id];
        $where[] = ['type', '=', self::$type['消耗']];
        $where[] = ['uid', '=', $uid];
        $where[] = ['ext_1', '=', self::$ext_1];
        $where[] = ['ext_2', '=', $boxId];
        $where[] = ['created_time', '>=', $start];
        $where[] = ['created_time', '<', $end];
        $consumption = abs(UserAssetLogModel::getInstance()->getModel()->where($where)->sum('change_amount'));
        $where[1][2] = self::$type['产出'];
        $output = UserAssetLogModel::getInstance()->getModel()->where($where)->sum('ext_4');
        return ['output' => $output, 'consumption' => $consumption, 'explodeRate' => empty($output) || empty($consumption) ? 0 : round($output / $consumption, 2)];
    }

    public function getBoxUsers($start, $end, $pagenum, $page, $boxId)
    {
        $query = Db::table(getTable(formatDate($start), formatDate($end)))
            ->where(
                [
                    ['event_id', '=', self::$event_id],
                    ['type', '=', self::$type['消耗']],
                    ['ext_1', '=', self::$ext_1],
                    ['ext_2', '=', $boxId],
                    ['success_time', '>=', $start],
                    ['success_time', '<', $end],
                ]
            )
            ->field('distinct(uid) as uid,sum(abs(change_amount)) amount')
            ->group('uid')
            ->order('amount desc')
            ->limit($page, $pagenum)
        // ->fetchSql(true)
            ->select()
            ->toArray();

        return $query;
    }

    public function getBoxUsersTheTotal($start, $end, $boxId)
    {
        $consumption = abs(Db::table(getTable(formatDate($start), formatDate($end)))
                ->where(
                    [
                        ['event_id', '=', self::$event_id],
                        ['type', '=', self::$type['消耗']],
                        ['ext_1', '=', self::$ext_1],
                        ['ext_2', '=', $boxId],
                        ['success_time', '>=', $start],
                        ['success_time', '<', $end],
                    ]
                )
                ->sum('change_amount'));
        $output = Db::table(getTable(formatDate($start), formatDate($end)))
            ->where(
                [
                    ['event_id', '=', self::$event_id],
                    ['type', '=', self::$type['产出']],
                    ['ext_1', '=', self::$ext_1],
                    ['ext_2', '=', $boxId],
                    ['success_time', '>=', $start],
                    ['success_time', '<', $end],
                ]
            )
            ->sum('ext_4');
        return ['consumption' => $consumption, 'output' => $output, 'explodeRate' => empty($output) || empty($consumption) ? 0 : round($output / $consumption, 2)];
    }

    public function cancelTheSpecifiedBoxGift($array)
    {
        $is = Box2ReUserGiftModel::getInstance()->getModel()->where('id', $array['id'])->save(['state' => 4]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function addTheSpecifiedBoxGift($array, $username)
    {
        $user_id = $array['user_id'];
        $gift_id = $array['gift_id'];
        if (empty($user_id) || empty($gift_id)) {
            echo json_encode(['code' => 500, 'msg' => '参数不可为空']);die;
        }
        $data = [
            'user_id' => $user_id,
            'box_id' => $array['box_id'],
            'gift_id' => $gift_id,
            'created' => time(),
            'create_user' => $username,
        ];
        $is = Box2ReUserGiftModel::getInstance()->getModel()->insert($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function TheSpecifiedBoxGift($array)
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
        $boxId = array_key_exists('boxId', $array) ? $array['boxId'] : false;
        if ($boxId) {
            $where[] = ['box_id', '=', $boxId];
            $info['box_id'] = $boxId;
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

        $data = Box2ReUserGiftModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['name'] = $this->getGift($v['gift_id'], 'name');
            $data[$k]['box_id'] = self::$box[$v['box_id']];
            $data[$k]['gift_id'] = self::$box[$v['box_id']];
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
        $count = Box2ReUserGiftModel::getInstance()->getModel()->where($where)->count();
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $info['page_array'] = $page_array;
        $info['demo'] = $demo;
        return $info;
    }

    public function BoxDetails($array)
    {
        $pagenum = 10;
        $page = !empty($array['page']) ? ($array['page'] - 1) * $pagenum : 0;
        $master_page = !empty($array['page']) ? $array['page'] : 1;

        $daochu = array_key_exists('daochu', $array) ? $array['daochu'] : false;

        $where[] = ['event_id', '=', 10009];
        $where[] = ['type', '=', 3];
        $uid = array_key_exists('uid', $array) ? $array['uid'] : false;
        if ($uid) {
            $where[] = ['uid', '=', $array['uid']];
        }
        $where[] = ['ext_1', '=', 'box2'];

        $asset_id = array_key_exists('asset_id', $array) ? $array['asset_id'] : false;
        if ($asset_id) {
            $where[] = ['asset_id', '=', $asset_id];
        }
        $type = array_key_exists('type', $array) ? $array['type'] : '莫提斯';
        if ($type) {
            $where[] = ['ext_2', '=', self::$ext_2[$type]];
        }

        $demo = array_key_exists('demo', $array) ? $array['demo'] : getDefaultDate();
        list($start, $end) = getBetweenDate($demo);
        $where[] = ['success_time', '>=', strtotime($start)];
        $where[] = ['success_time', '<', strtotime($end)];

        if ($daochu) {
            $data = UserAssetLogModel::getInstance()->getModel()->where($where)->field('uid,asset_id,created_time,change_amount,ext_4')->select()->toArray();
        } else {
            $data = UserAssetLogModel::getInstance()->getModel()->where($where)->field('uid,asset_id,created_time,change_amount,ext_4')->limit($page, $pagenum)->select()->toArray();
        }
        $count = UserAssetLogModel::getInstance()->getModel()->where($where)->count();
        $url = config('config.APP_URL_image');
        foreach ($data as $k => $v) {
            $data[$k]['image'] = $url . $this->getGift($v['asset_id'], 'image');
            $data[$k]['name'] = $this->getGift($v['asset_id'], 'name');
            $data[$k]['price'] = $this->getGift($v['asset_id'], 'price')['count'];
            $data[$k]['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $data[$k]['change_amount'] = (int) $v['change_amount'];
        }
        $change_amount = UserAssetLogModel::getInstance()->getModel()->where($where)->sum('change_amount');

        if ($daochu) {
            $this->DaoChu($data);
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        return ['data' => $data, 'uid' => $uid, 'demo' => $demo, 'type' => $type, 'page_array' => $page_array, 'asset_id' => $asset_id, 'change_amount' => $change_amount];
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

    public function BoxBurstRate($array)
    {
        $pagenum = 4;
        $page = !empty($array['page']) ? ($array['page'] - 1) * $pagenum : 0;
        $master_page = !empty($array['page']) ? $array['page'] : 1;

        $demo = array_key_exists('demo', $array) ? $array['demo'] : getDefaultDate();
        list($start, $end) = getBetweenDate($demo);
        $where[0] = ['date', '=', $start];

        $uid = array_key_exists('uid', $array) ? $array['uid'] : '';
        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }

        $condition = $where;

        $type = array_key_exists('type', $array) ? $array['type'] : 1;
        if ($type == 1) {
            $where[] = ['small_output_amount', '>', 0];
        } elseif ($type == 2) {
            $where[] = ['in_output_amount', '>', 0];
        } elseif ($type == 3) {
            $where[] = ['big_output_amount', '>', 0];
        }
        $list = DaysUserBoxDatasModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        $count = DaysUserBoxDatasModel::getInstance()->getModel()->where($where)->count();
        $info = DaysUserBoxDatasModel::getInstance()->getModel()
            ->where($condition)
            ->field('sum(big_output_amount) big_output_amount,sum(big_consume_amount) big_consume_amount,sum(in_output_amount) in_output_amount, sum(in_consume_amount) in_consume_amount,sum(small_output_amount) small_output_amount, sum(small_consume_amount) small_consume_amount')
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
        $info['big_rate'] = empty($info['big_output_amount']) || empty($info['big_consume_amount']) ? 0 : round($info['big_output_amount'] / $info['big_consume_amount'], 3);
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
                    'boxname' => '莫提斯',
                    'consume' => $array['small_consume_amount'],
                    'output' => $array['small_output_amount'],
                    'burstrate' => empty($array['small_output_amount']) && empty($array['small_consume_amount']) ? 0 : round($array['small_output_amount'] / $array['small_consume_amount'], 3),
                ],
            ];
        } elseif ($type == 2) {
            $array = [
                [
                    'uid' => $array['uid'],
                    'boxname' => '宙斯',
                    'consume' => $array['in_consume_amount'],
                    'output' => $array['in_output_amount'],
                    'burstrate' => empty($array['in_output_amount']) && empty($array['in_consume_amount']) ? 0 : round($array['in_output_amount'] / $array['in_consume_amount'], 3),
                ],
            ];
        } elseif ($type == 3) {
            $array = [
                [
                    'uid' => $array['uid'],
                    'boxname' => '盖亚',
                    'consume' => $array['big_consume_amount'],
                    'output' => $array['big_output_amount'],
                    'burstrate' => empty($array['big_output_amount']) && empty($array['big_consume_amount']) ? 0 : round($array['big_output_amount'] / $array['big_consume_amount'], 3),
                ],
            ];
        }
        return $array;
    }

}
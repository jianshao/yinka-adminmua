<?php

namespace app\admin\service;

use app\admin\model\ConfigModel;
use app\admin\model\DaysUserBoxDatasModel;
use app\admin\model\TurntableReUserGiftModel;
use app\admin\model\UserAssetLogModel;
use think\facade\Db;

class TurntableBurstService
{
    protected static $instance;
    protected static $event_id = 10009;
    protected static $type = ['产出' => 3, '消耗' => 2];
    protected static $ext_1 = 'box2';
    protected static $ext_2 = ['莫提斯' => 1, '宙斯' => 2, '盖亚' => 3];
    protected static $box = [1 => '小转盘', 2 => '大转盘'];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new TurntableBurstService();
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

    public function getBoxUsersTheTotal($start, $end, $boxId)
    {
        $consumption = abs(Db::table(getTable(formatDate($start), formatDate($end)))
                ->where(
                    [
                        ['event_id', '=', self::$event_id],
                        ['type', '=', self::$type['消耗']],
                        ['ext_1', '=', self::$ext_1],
                        ['ext_2', '=', $boxId],
                        ['created_time', '>=', $start],
                        ['created_time', '<', $end],
                    ]
                )
                ->sum('change_amount'));
        $output = Db::table(Db::table(getTable(formatDate($start), formatDate($end))))
            ->where(
                [
                    ['event_id', '=', self::$event_id],
                    ['type', '=', self::$type['产出']],
                    ['ext_1', '=', self::$ext_1],
                    ['ext_2', '=', $boxId],
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
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function addTheSpecifiedTurntableGift($array, $username)
    {
        $user_id = $array['user_id'];
        $gift_id = $array['gift_id'];
        if (empty($user_id) || empty($gift_id)) {
            echo json_encode(['code' => 500, 'msg' => '参数不可为空']);die;
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
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
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
        $boxId = array_key_exists('boxId', $array) ? $array['boxId'] : false;
        if ($boxId) {
            $where[] = ['turntable_id', '=', $boxId];
            $info['turntable_id'] = $boxId;
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
        $data = TurntableReUserGiftModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->order('id desc')->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['name'] = $this->getGift($v['gift_id'], 'name');
            $data[$k]['turntable_id'] = self::$box[$v['turntable_id']];
            $data[$k]['gift_id'] = $v['gift_id'];
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

    public function BoxDetails($array)
    {
        $pagenum = 10;
        $page = !empty($array['page']) ? ($array['page'] - 1) * $pagenum : 0;
        $master_page = !empty($array['page']) ? $array['page'] : 1;

        $where[] = ['event_id', '=', 10009];
        $where[] = ['type', '=', 3];
        $where[] = ['uid', '=', $array['uid']];
        $where[] = ['ext_1', '=', 'box2'];

        $type = array_key_exists('type', $array) ? $array['type'] : '莫提斯';
        if ($type) {
            $where[] = ['ext_2', '=', self::$ext_2[$type]];
        }

        $demo = array_key_exists('demo', $array) ? $array['demo'] : getDefaultDate();
        list($start, $end) = getBetweenDate($demo);
        $where[] = ['success_time', '>=', strtotime($start)];
        $where[] = ['success_time', '<', strtotime($end)];

        $data = UserAssetLogModel::getInstance()->getModel()->where($where)->field('uid,asset_id,created_time,change_amount,ext_4')->limit($page, $pagenum)->select()->toArray();
        $count = UserAssetLogModel::getInstance()->getModel()->where($where)->count();
        $url = config('config.APP_URL_image');
        foreach ($data as $k => $v) {
            $data[$k]['image'] = $url . $this->getGift($v['asset_id'], 'image');
            $data[$k]['name'] = $this->getGift($v['asset_id'], 'name');
            $data[$k]['price'] = $this->getGift($v['asset_id'], 'price')['count'];
            $data[$k]['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $data[$k]['change_amount'] = (int) $v['change_amount'];
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        return ['data' => $data, 'uid' => $array['uid'], 'demo' => $array['demo'], 'type' => $type, 'page_array' => $page_array];
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
            ->where($where)
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
<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\Box3UserSpecialGiftModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\BreakBoxConfService;
use app\admin\service\ExportExcelService;
use think\facade\Db;
use think\facade\Request;
use think\facade\View;

class Box3ConfController extends AdminBaseController
{
    public function boxList()
    {
        $boxId = Request::param('boxId');
        $poolId = Request::param('poolId');
        $boxInstance = BreakBoxConfService::getInstance();
        $data = $boxInstance->getBoxList();
        // $public_value = $boxInstance->getPublicValue();
        // $flutter_value = $boxInstance->getFlutterValue();
        foreach ($data as $k => $item) {
            if (!isset($item->fullPublicGiftValue)) {
                $item->fullPublicGiftValue = 0;
            }
            if (!isset($item->fullFlutterGiftValue)) {
                $item->fullFlutterGiftValue = 0;
            }
            if (!isset($item->profitsBaolv)) {
                $item->profitsBaolv = 0;
            }
        }
        View::assign('data', $data);
        // View::assign('fullPublicGiftValue', $public_value);
        // View::assign('fullFlutterGiftValue', $flutter_value);
        View::assign('boxId', $boxId);
        View::assign('poolId', $poolId);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/index');
    }

    public function setBox()
    {
        try {
            $boxId = (int) Request::param('boxId');
            $name = (string) Request::param('name');
            $price = (int) Request::param('price');
            $isOpen = (int) Request::param('isOpen');
            $fullPublicGiftValue = (int) Request::param('fullPublicGiftValue');
            $fullFlutterGiftValue = (int) Request::param('fullFlutterGiftValue');
            $inLuckRankGiftValue = (int) Request::param('inLuckRankGiftValue');
            $profitsBaolv = (int) Request::param('profitsBaolv');

            $boxInstance = BreakBoxConfService::getInstance();
            $boxInstance->setBox($boxInstance->getBox($boxId), [
                'boxName' => $name,
                'price' => $price,
                'isOpen' => $isOpen,
                'fullPublicGiftValue' => $fullPublicGiftValue,
                'fullFlutterGiftValue' => $fullFlutterGiftValue,
                'inLuckRankGiftValue' => $inLuckRankGiftValue,
                'profitsBaolv' => $profitsBaolv / 100,
            ]);

            $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
            echo json_encode(['code' => 200, 'msg' => '更新成功']);die;
        } catch (\Throwable$th) {
            echo json_encode(['code' => 500, 'msg' => '更新成功']);die;
        }
    }

    public function getBoxPools()
    {
        $boxId = (int) Request::param('boxId');
        $boxInstance = BreakBoxConfService::getInstance();
        $data = array_column($boxInstance->getPools($boxId), null, 'poolId');
        $gifts = GiftsCommon::getInstance()->getGiftMap();

        View::assign('data', $data);
        View::assign('gifts', array_column($gifts, null, 'id'));
        View::assign('boxId', $boxId);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/pools');
    }

    public function setBoxPools()
    {
        try {
            $boxId = (int) Request::param('boxId');
            $poolId = (int) Request::param('poolId', 0);
            $name = (string) Request::param('name');
            $mode = (int) Request::param('mode');
            $giftsKey = (array) Request::param('giftsKey');
            $giftsVal = (array) Request::param('giftsVal');
            $action = (string) Request::param('action');

            if (count($giftsKey) != count($giftsVal)) {
                echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
            }

            //合并数组
            $gifts = [];
            for ($i = 0; $i < count($giftsVal); $i++) {
                $gifts[$i] = [(int) $giftsKey[$i], (int) $giftsVal[$i]];
            }

            $boxInstance = BreakBoxConfService::getInstance();
            $pool_map = $boxInstance->getPoolsMap();

            //添加
            if ($action === 'add') {
                if (!array_key_exists($boxId, $pool_map)) {
                    $pool_map[$boxId] = [];
                    $newPoolId = 1;
                } else {
                    $newPoolId = max(array_keys($pool_map[$boxId])) + 1;
                }
                $pools = $boxInstance->getPools($boxId);
                $newPool = (object) [];
                $newPool->poolId = $newPoolId;
                $newPool->name = $name;
                $newPool->mode = $mode;
                $newPool->gifts = $gifts;
                $pools[] = $newPool;

                $boxInstance->setPools($boxId, $pools);
                $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                echo json_encode(['code' => 200, 'msg' => '添加成功']);die;
            } elseif ($action === 'edit') {
                $pool = $pool_map[$boxId][$poolId];
                $pool->name = $name;
                $pool->gifts = $gifts;
                $pool->mode = $mode;
                $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                echo json_encode(['code' => 200, 'msg' => '更新成功']);die;
            } elseif ($action === 'delete') {
                unset($pool_map[$boxId][$poolId]);
                $pools = array_values($pool_map[$boxId]);
                $boxInstance->setPools($boxId, $pools);
                $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                echo json_encode(['code' => 200, 'msg' => '更新成功']);die;
            }
        } catch (\Throwable$th) {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
        }
    }

    public function getBoxRules()
    {
        $boxId = (int) Request::param('boxId');
        $boxInstance = BreakBoxConfService::getInstance();
        $rules = $boxInstance->getRules($boxId);
        $pools = $boxInstance->getPools($boxId);

        $poolIds = $boxInstance->getPoolsIds($pools);
        // dump($rules);die;
        View::assign('data', $rules);
        View::assign('poolIds', $poolIds);
        View::assign('boxId', $boxId);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/rules');
    }

    public function setBoxRules()
    {
        try {
            $boxId = (int) Request::param('boxId');
            $action = (string) Request::param('action');
            $poolId = (int) Request::param('poolId', 0);
            $poolValue = (int) Request::param('poolValue');
            $old_poolId = (int) Request::param('old_poolId');
            $old_poolValue = (int) Request::param('old_poolValue');

            if (!$poolId || !$boxId) {
                echo json_encode(['code' => 500, 'msg' => '池子ID为空']);die;
            }
            //合并数组
            $boxInstance = BreakBoxConfService::getInstance();
            $poolRules = $boxInstance->getRules($boxId);

            $newRule = [$poolValue, $poolId];
            if ($action === 'add') {
                $boxInstance->setRules($boxId, $poolRules, $newRule);
                $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                echo json_encode(['code' => 200, 'msg' => '添加成功']);die;

            } elseif ($action === 'edit') {
                $oldRule = [$old_poolValue, $old_poolId];
                $index = array_search($oldRule, $poolRules);

                $poolRules[$index] = $newRule;
                $boxInstance->setRules($boxId, $poolRules, $newRule);
                $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                echo json_encode(['code' => 200, 'msg' => '更新成功']);die;

            } elseif ($action === 'delete') {
                if (in_array($newRule, $poolRules)) {
                    $boxInstance->delRules($boxId, $poolRules, $newRule);
                    $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                    echo json_encode(['code' => 200, 'msg' => '删除成功']);die;
                } else {
                    echo json_encode(['code' => 500, 'msg' => '该配置不存在']);die;
                }
            }
        } catch (\Throwable$th) {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
        }
    }

    public function getBoxRate()
    {
        $boxId = (int) Request::param('boxId');
        $boxInstance = BreakBoxConfService::getInstance();
        $rates = $boxInstance->getRates($boxId);
        $pools = $boxInstance->getPools($boxId);

        $poolIds = $boxInstance->getPoolsIds($pools);

        foreach ($rates as $rate) {
            if (!isset($rate->whitePoolId)) {
                $rate->whitePoolId = 0;
            }
        }
        View::assign('data', $rates);
        View::assign('poolIds', $poolIds);
        View::assign('boxId', $boxId);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/rate');
    }

    public function setBoxRate()
    {
        try {
            $boxId = (int) Request::param('boxId');
            $action = (string) Request::param('action');
            $blackPoolId = (int) Request::param('blackPoolId', 0);
            $whitePoolId = (int) Request::param('whitePoolId', 0);
            $key = (int) Request::param('key', 0);

            $boxRange = (array) Request::param('boxRange');
            $whiteBaolv = (array) Request::param('whiteBaolv');
            $whiteHopeBaolv = (array) Request::param('whiteHopeBaolv');
            $blackBaolv = (array) Request::param('blackBaolv');

            if ((!$blackPoolId || !$whitePoolId || !$boxId || empty($boxRange) || empty($whiteBaolv) || empty($whiteHopeBaolv) || empty($blackBaolv)) && $action != 'delete') {
                echo json_encode(['code' => 500, 'msg' => '参数错误']);die;
            }

            for ($i = 0; $i < count($boxRange); $i++) {
                $boxRange[$i] = (int) $boxRange[$i];
                $whiteBaolv[$i] = (int) $whiteBaolv[$i] / 100;
                $whiteHopeBaolv[$i] = (int) $whiteHopeBaolv[$i] / 100;
                $blackBaolv[$i] = (int) $blackBaolv[$i] / 100;
            }
            $newRate = [
                'boxRange' => $boxRange,
                'whiteBaolv' => $whiteBaolv,
                'whiteHopeBaolv' => $whiteHopeBaolv,
                'blackBaolv' => $blackBaolv,
                'blackPoolId' => $blackPoolId,
                'whitePoolId' => $whitePoolId,
            ];

            //合并数组
            $boxInstance = BreakBoxConfService::getInstance();
            $poolRates = $boxInstance->getRates($boxId);

            if ($action === 'add') {
                $boxInstance->addRates($boxId, $poolRates, $newRate);
                $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                echo json_encode(['code' => 200, 'msg' => '添加成功']);die;
            } elseif ($action === 'edit') {
                $poolRate = $poolRates[$key - 1];
                $boxInstance->setRates($boxId, $poolRate, $newRate);
                $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                echo json_encode(['code' => 200, 'msg' => '更新成功']);die;
            } elseif ($action === 'delete') {
                $boxInstance->delRates($boxId, $key, $poolRates);
                $boxInstance->saveConf(json_encode($boxInstance->getBoxConf()));
                echo json_encode(['code' => 200, 'msg' => '删除成功']);die;
            }
        } catch (\Throwable$th) {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
        }
    }

    public function refreshAllPool()
    {
        return BreakBoxConfService::getInstance()->refreshAllPool(Request::param());
    }

    public function clearCacheBoxConf()
    {
        return BreakBoxConfService::getInstance()->clearCacheBoxConf();
    }

    public function getBoxPointUsers()
    {
        $boxId = (int) Request::param('boxId');
        $boxInstance = BreakBoxConfService::getInstance();
        $pools = array_column($boxInstance->getPools($boxId), null, 'poolId');

        $gifts = GiftsCommon::getInstance()->getGiftMap();
        $data = Db::table('zb_break_box_assign_pools')->where('box_id', $boxId)->select()->toArray();
        View::assign('data', $data);
        View::assign('pools', $pools);
        View::assign('gifts', array_column($gifts, null, 'id'));
        View::assign('boxId', $boxId);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/poolsusers');
    }

    public function getBoxPointPrize()
    {
        $boxId = (int) Request::param('boxId');
        $boxInstance = BreakBoxConfService::getInstance();
        $pools = array_column($boxInstance->getPools($boxId), null, 'poolId');

        $gifts = GiftsCommon::getInstance()->getGiftMap();

        View::assign('pools', $pools);
        View::assign('gifts', array_column($gifts, null, 'id'));
        View::assign('boxId', $boxId);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/prize');
    }

    public function addBoxPointUser()
    {
        try {
            $boxId = (int) Request::param('boxId');
            $poolId = (int) Request::param('poolId');
            $user_id = (int) Request::param('user_id');
            $start_time = (string) Request::param('start_time');
            $end_time = (string) Request::param('end_time');

            if (!$boxId || !$poolId || !$user_id || !$start_time || !$end_time) {
                echo json_encode(['code' => 500, 'msg' => '参数错误']);die;
            }
            $data = [
                'box_id' => $boxId,
                'pool_id' => $poolId,
                'user_id' => $user_id,
                'start_time' => strtotime($start_time),
                'end_time' => strtotime($end_time),
            ];

            $query = Db::table('zb_break_box_assign_pools')->where('box_id', $boxId)->where('pool_id', $poolId)->where('user_id', $user_id)->findOrEmpty();
            if (!empty($query)) {
                echo json_encode(['code' => 200, 'msg' => '该用户已存在']);die;
            }

            Db::table('zb_break_box_assign_pools')->insert($data);
            echo json_encode(['code' => 200, 'msg' => '添加成功']);die;
        } catch (\Throwable$th) {
            echo json_encode(['code' => 500, 'msg' => '添加失败']);die;
        }
    }

    public function editBoxPointUser()
    {
        try {
            $boxId = (int) Request::param('boxId');
            $poolId = (int) Request::param('poolId');
            $user_id = (int) Request::param('user_id');
            $start_time = (string) Request::param('start_time');
            $end_time = (string) Request::param('end_time');
            if (!$boxId || !$poolId || !$user_id || !$start_time || !$end_time) {
                echo json_encode(['code' => 500, 'msg' => '参数错误']);die;
            }
            $data = [
                'start_time' => strtotime($start_time),
                'end_time' => strtotime($end_time),
            ];
            Db::table('zb_break_box_assign_pools')->where('box_id', $boxId)->where('pool_id', $poolId)->where('user_id', $user_id)->update($data);
            echo json_encode(['code' => 200, 'msg' => '更新成功']);die;
        } catch (\Throwable$th) {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
        }
    }

    public function delBoxPointUser()
    {
        try {
            $boxId = (int) Request::param('boxId');
            $poolId = (int) Request::param('poolId');
            $user_id = (int) Request::param('user_id');

            if (!$boxId || !$poolId || !$user_id) {
                echo json_encode(['code' => 500, 'msg' => '参数错误']);die;
            }
            Db::table('zb_break_box_assign_pools')->where('box_id', $boxId)->where('pool_id', $poolId)->where('user_id', $user_id)->delete();
            echo json_encode(['code' => 200, 'msg' => '删除成功']);die;
        } catch (\Throwable$th) {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);die;
        }
    }

    public function getUserSpecialGift()
    {
        $pagenum = 20;
        $boxId = (int) Request::param('boxId');
        $gift_id = (int) Request::param('gift_id');
        $uid = (int) Request::param('uid');
        $state = (int) Request::param('state');
        $type = (int) Request::param('type');
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        $where[] = ['created', '>=', strtotime($start)];
        $where[] = ['created', '<', strtotime($end)];

        if ($uid) {
            $where[] = ['user_id', '=', $uid];
        }

        if ($gift_id) {
            $where[] = ['gift_id', '=', $gift_id];
        }

        if ($boxId) {
            $where[] = ['box_id', '=', $boxId];
        }

        if ($state) {
            $where[] = ['state', '=', $state];
        }

        $data = Box3UserSpecialGiftModel::getInstance()->getModel()->where($where)->limit($offset, $pagenum)->select()->toArray();

        $gifts = GiftsCommon::getInstance()->getGifts();

        foreach ($data as $k => $v) {
            $data[$k]['name'] = isset($gifts[$v['gift_id']]) ? $gifts[$v['gift_id']] : '';
            $data[$k]['box_id'] = $v['box_id'];
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
        $count = Box3UserSpecialGiftModel::getInstance()->getModel()->where($where)->count();
        $page_array = [];
        $page_array['page'] = $page;
        $page_array['total_page'] = (int) ceil($count / $pagenum);

        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('type', $type);
        View::assign('state', $state);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/specialgift');
    }

    public function addUserSpecialGift()
    {
        try {
            $username = $this->token['username'];
            $request = Request::param();
            $user_id = (int) $request['user_id'];
            $gift_id = (int) $request['gift_id'];
            if (empty($user_id) || empty($gift_id)) {
                echo json_encode(['code' => 500, 'msg' => '参数不可为空']);die;
            }
            BreakBoxConfService::getInstance()->addUserSpecialGift($request, $username);

            echo $this->return_json(200, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
        } catch (\Throwable$th) {
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
        }
    }

    public function cancelUserSpecialGift()
    {
        try {
            BreakBoxConfService::getInstance()->cancelUserSpecialGift(Request::param());
            echo $this->return_json(200, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除成功]);
        } catch (\Throwable$th) {
            echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_删除失败]);
        }
    }

    public function userBoxRates()
    {
        $pagenum = 20;
        $uid = (int) Request::param('uid');
        $consume_val = (int) Request::param('consume_val');
        $baolv_val = (int) Request::param('baolv_val');
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;
        $daochu = (int) Request::param('daochu', 0);
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        //渠道数据用户数据
        $conditions[] = [
            ['date', '>=', $start],
            ['date', '<', $end],
            ['json_data', 'like', '%box%'],
        ];
        if ($uid) {
            $conditions[] = ['uid', '=', $uid];
        }

        $page_info = ['page' => $page, 'total_page' => 1];

        $day_datas = Db::table('bi_user_stats_1day')->where($conditions)->select()->toArray();
        $count = count($day_datas);

        $page_info['page'] = $page;
        $page_info['total_page'] = (int) ceil($count / $pagenum);
        $res = BreakBoxConfService::getBoxData($day_datas);
        if ($consume_val > 0 && $baolv_val > 0) {
            foreach ($res['data'] as $key => $item) {
                if ($item['gold_consume_amount'] < $consume_val || $item['gold_explodeRate'] > $baolv_val) {
                    unset($res['data'][$key]);
                }
            }
        }
        $data = [];
        $data = array_slice($res['gold']['data'], $offset, $pagenum);

        if ($daochu == 1) {
            $columns = [
                'uid' => '用户Id',
                'gold_consume_amount' => '金宝箱消耗',
                'gold_output_amount' => '金宝箱产出',
                'gold_explodeRate' => '金宝箱爆率',
            ];
            ExportExcelService::getInstance()->export($res['data'], $columns);
        }

        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('consume_val', $consume_val);
        View::assign('baolv_val', $baolv_val);
        View::assign('explodeRate', $res['gold']);
        View::assign('page', $page_info);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/userGodlBoxRates');
    }

    public function userSilverBoxRates()
    {
        $pagenum = 20;
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $daochu = (int) Request::param('daochu', 0);
        $uid = (int) Request::param('uid');
        $consume_val = (int) Request::param('consume_val');
        $baolv_val = (int) Request::param('baolv_val');
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;

        //渠道数据用户数据
        $conditions[] = [
            ['date', '>=', $start],
            ['date', '<', $end],
            ['json_data', 'like', '%box%'],
        ];
        if ($uid) {
            $conditions[] = ['uid', '=', $uid];
        }

        $page_info = ['page' => $page, 'total_page' => 1];

        $day_datas = Db::table('bi_user_stats_1day')->where($conditions)->select()->toArray();
        $count = count($day_datas);
        $page_info['page'] = $page;
        $page_info['total_page'] = (int) ceil($count / $pagenum);

        $res = BreakBoxConfService::getBoxData($day_datas);

        if ($consume_val > 0 && $baolv_val > 0) {
            foreach ($res['data'] as $key => $item) {
                if ($item['silver_consume_amount'] < $consume_val || $item['silver_explodeRate'] > $baolv_val) {
                    unset($res['data'][$key]);
                }
            }
        }
        $data = [];
        $data = array_slice($res['silver']['data'], $offset, $pagenum);

        if ($daochu == 1) {
            $columns = [
                'uid' => '用户Id',
                'silver_consume_amount' => '银宝箱消耗',
                'silver_output_amount' => '银宝箱产出',
                'silver_explodeRate' => '银宝箱爆率',
            ];
            ExportExcelService::getInstance()->export($res['data'], $columns);
        }

        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('consume_val', $consume_val);
        View::assign('baolv_val', $baolv_val);
        View::assign('explodeRate', $res['silver']);
        View::assign('page', $page_info);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/userSilverBoxRates');
    }

    public function boxOuputDetails()
    {
        $pagenum = 20;
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $uid = (int) Request::param('uid');
        $daochu = (int) Request::param('daochu');
        $type = (int) Request::param('type');
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;

        $where[] = ['event_id', '=', 10009];
        $where[] = ['type', '=', 3];
        $where[] = ['ext_1', '=', 'box'];
        $where[] = ['success_time', '>=', strtotime($start)];
        $where[] = ['success_time', '<', strtotime($end)];

        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }

        if ($type) {
            $where[] = ['ext_2', '=', (string) $type];
        }

        $gifts = GiftsCommon::getInstance()->getGifts();

        $data = Db::table(getTable($start, $end))->field('*,FROM_UNIXTIME(success_time) as success_time')->where($where)->order('id', 'desc')->limit($offset, $pagenum)->select()->toArray();
        $count = Db::table(getTable($start, $end))->where($where)->count();
        $page_array = [];
        $page_array['page'] = $page;
        $page_array['total_page'] = (int) ceil($count / $pagenum);

        if ($daochu == 1) {
            $columns = [
                'uid' => '用户ID',
                'ext_2' => '宝箱ID',
                'asset_id' => '礼物ID',
                'asset_id' => '礼物名称',
                'success_time' => '创建时间',
            ];
            ExportExcelService::getInstance()->export($data, $columns);
        }

        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('type', $type);
        View::assign('gifts', $gifts);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box3/userBoxOutputDetail');
    }

    public function setBoxConf()
    {
        return BreakBoxConfService::getInstance()->setBoxConf(Request::param());
    }

    public function boxPoolsShow()
    {
        $data = BreakBoxConfService::getInstance()->boxPoolsShow();

        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('box3/boxPoolsShow');
    }
}

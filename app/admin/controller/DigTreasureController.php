<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\CommonConst;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\BoxService;
use app\admin\service\DigTreasureConfService;
use app\admin\service\ExportExcelService;
use constant\CodeConstant;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class DigTreasureController extends AdminBaseController
{
    public function getConfig()
    {
        $instance = DigTreasureConfService::getInstance();
        $data = $instance->getGameObj();
        $pools = $instance->getPoolsList();
        //获取池子数量
        $kingValue = $instance->getPoolNum();
        $isOpen = $instance->isOpen();

        View::assign('isOpen', $isOpen);
        View::assign('kingValue', $kingValue);
        View::assign('data', $data);
        View::assign('pools', $pools);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('treasure/index');
    }

    public function setConfig()
    {
        try {
            $data = Request::param();
            if (!isset($data['gameId']) || empty($data['gameId'])) {
                return rjson([], CodeConstant::CODE_参数错误, CodeConstant::CODE_PARAMETER_ERR_MAP[CodeConstant::CODE_参数错误]);die;
            }
            DigTreasureConfService::getInstance()->setBaseInfo($data);
            Log::record('设置基础信息:操作人:' . $this->token['username'] . '更新条件:' . json_encode($data));
            echo json_encode(['code' => 200, 'msg' => '更新成功']);die;
        } catch (\Throwable $th) {
            echo json_encode(['code' => 500, 'msg' => '更新成功']);die;
        }
    }

    public function getPools()
    {
        $gameId = Request::param('gameId');
        $instance = DigTreasureConfService::getInstance();
        $data = array_column($instance->getPools($gameId), null, 'poolId');
        $gifts = GiftsCommon::getInstance()->getGiftMap();
        View::assign('data', $data);
        View::assign('gifts', array_column($gifts, null, 'id'));
        View::assign('gameId', $gameId);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('treasure/pools');
    }

    public function setPools()
    {
        try {
            $gameId = (string) Request::param('gameId');
            $poolId = (int) Request::param('poolId', 0);
            $name = (string) Request::param('name');
            $type = (string) Request::param('type');
            $sort = (string) Request::param('sort');
            $giftsKey = (array) Request::param('giftsKey');
            $giftsVal = (array) Request::param('giftsVal');
            $conditionKey = (array) Request::param('conditionKey');
            $conditionVal = (array) Request::param('conditionVal');
            $action = (string) Request::param('action');

            if (count($giftsKey) != count($giftsVal)) {
                echo json_encode(['code' => 500, 'msg' => '参数有误']);die;
            }

            if (count($conditionKey) != count($conditionVal)) {
                echo json_encode(['code' => 500, 'msg' => '参数有误']);die;
            }

            //合并数组
            $gifts = [];
            for ($i = 0; $i < count($giftsVal); $i++) {
                $gifts[$i] = [(int) $giftsKey[$i], (int) $giftsVal[$i]];
            }

            //合并数组
            $condition = [];
            for ($i = 0; $i < count($conditionVal); $i++) {
                $condition[$i] = (object) [];
                $condition[$i]->consume = [(int) $conditionKey[$i], (int) $conditionVal[$i]];
            }

            $instance = DigTreasureConfService::getInstance();
            //添加

            if ($action === 'add') {
                DigTreasureConfService::getInstance()->addPools(
                    [
                        'gameId' => $gameId,
                        'poolId' => $poolId,
                        'name' => $name,
                        'poolType' => $type,
                        'condition' => $condition,
                        'items' => $gifts,
                    ]
                );
                echo json_encode(['code' => 200, 'msg' => '添加成功']);die;
            } elseif ($action === 'edit') {
                DigTreasureConfService::getInstance()->setPools(
                    [
                        'gameId' => $gameId,
                        'poolId' => $poolId,
                        'name' => $name,
                        'poolType' => $type,
                        'condition' => $condition,
                        'items' => $gifts,
                    ]
                );
                echo json_encode(['code' => 200, 'msg' => '更新成功']);die;
            } elseif ($action === 'delete') {
                DigTreasureConfService::getInstance()->delPools(
                    [
                        'gameId' => $gameId,
                        'poolId' => $poolId,
                        'name' => $name,
                        'poolType' => $type,
                        'condition' => $condition,
                        'items' => $gifts,
                    ]
                );
                echo json_encode(['code' => 200, 'msg' => '删除成功']);die;
            }
        } catch (\Throwable $th) {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
        }
    }

    public function getPoolInfo()
    {
        try {
            $gameId = (string) Request::param('gameId');
            $poolId = (string) Request::param('poolId');
            $res = DigTreasureConfService::getInstance()->getPoolInfo($gameId, $poolId, $this->token['id']);
            if ($res['code'] == 0) {
                return rjson($res['data']);
            } else {
                return rjson([], 500, '获取数据失败');
            }
        } catch (\Throwable $th) {
            return rjson([], 500, '获取数据失败');
        }
    }

    public function refreshPool()
    {
        try {
            $poolId = (string) Request::param('poolId');
            $gameId = (string) Request::param('gameId');

            $res = DigTreasureConfService::getInstance()->refreshPool($gameId, $poolId, $this->token['id']);
            if ($res && $res['code'] == 0) {
                $data = ['code' => 200, 'msg' => '更新成功'];
            } else {
                $data = ['code' => 500, 'msg' => $res['desc']];
            }
            echo json_encode($data);die;
        } catch (\Throwable $th) {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
        }

    }

    public function clearCache()
    {
        try {
            $res = DigTreasureConfService::getInstance()->clearCache($this->token['id']);
            if ($res && $res['code'] == 0) {
                $data = ['code' => 200, 'msg' => '更新成功'];
            } else {
                $data = ['code' => 500, 'msg' => $res['desc']];
            }
            echo json_encode($data);die;
        } catch (\Throwable $th) {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
        }
    }

    public function realTimeRate()
    {
        $pagenum = 20;
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;

        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $daochu = (int) Request::param('daochu', 0);
        $uid = (int) Request::param('uid');
        $gopherId = (string) Request::param('gopherId', '');

        //渠道数据用户数据
        $conditions[] = [
            ['date', '>=', $start],
            ['date', '<', $end],
            ['json_data', 'like', '%gopher%'],
        ];

        if ($uid) {
            $conditions[] = ['uid', '=', $uid];
        }

        $day_datas = Db::table('bi_user_stats_1day')->where($conditions)->select()->toArray();

        $res = BoxService::getGopherData($day_datas, 'gopher', $gopherId);

        $res_ = $res[$gopherId];
        $consumption = $res_["consumption"];
        $output = $res_["output_amount"];
        $explodeRate = $res_["explodeRate"];

        $count = count($res_['data']);

        $gopher_data = $res_['data'];

        $data = [];

        foreach ($gopher_data as $key => $gopher) {
            $data[$key]['date'] = $gopher['date'];
            $data[$key]['uid'] = $gopher['uid'];
            $data[$key]['output_amount_1'] = isset($gopher['1']) ? $gopher['1']['output_amount'] : 0;
            $data[$key]['consume_amount_1'] = isset($gopher['1']) ? $gopher['1']['consume_amount'] : 0;
            $data[$key]['explodeRate_1'] = isset($gopher['1']) ? $gopher['1']['explodeRate'] : 0;
            $data[$key]['output_amount_2'] = isset($gopher['2']) ? $gopher['2']['output_amount'] : 0;
            $data[$key]['consume_amount_2'] = isset($gopher['2']) ? $gopher['2']['consume_amount'] : 0;
            $data[$key]['explodeRate_2'] = isset($gopher['2']) ? $gopher['2']['explodeRate'] : 0;
            $data[$key]['output_amount_3'] = isset($gopher['3']) ? $gopher['3']['output_amount'] : 0;
            $data[$key]['consume_amount_3'] = isset($gopher['3']) ? $gopher['3']['consume_amount'] : 0;
            $data[$key]['explodeRate_3'] = isset($gopher['3']) ? $gopher['3']['explodeRate'] : 0;
            $data[$key]['output_amount_4'] = isset($gopher['4']) ? $gopher['4']['output_amount'] : 0;
            $data[$key]['consume_amount_4'] = isset($gopher['4']) ? $gopher['4']['consume_amount'] : 0;
            $data[$key]['explodeRate_4'] = isset($gopher['4']) ? $gopher['4']['explodeRate'] : 0;
            $data[$key]['output_amount_99'] = isset($gopher['99']) ? $gopher['99']['output_amount'] : 0;
            $data[$key]['consume_amount_99'] = isset($gopher['99']) ? $gopher['99']['consume_amount'] : 0;
            $data[$key]['explodeRate_99'] = isset($gopher['99']) ? $gopher['99']['explodeRate'] : 0;
            $data[$key]['output_amount_king'] = isset($gopher['king']) ? $gopher['king']['output_amount'] : 0;
            $data[$key]['consume_amount_king'] = isset($gopher['king']) ? $gopher['king']['consume_amount'] : 0;
            $data[$key]['explodeRate_king'] = isset($gopher['king']) ? $gopher['king']['explodeRate'] : 0;
            $data[$key]['consume_amount_sum_data'] = isset($gopher['sum_data']) ? $gopher['sum_data']['consume_amount'] : 0;
            $data[$key]['output_amount_sum_data'] = isset($gopher['sum_data']) ? $gopher['sum_data']['output_amount'] : 0;
            $data[$key]['explodeRate_sum_data'] = isset($gopher['sum_data']) ? $gopher['sum_data']['explodeRate'] : 0;
        }
        $page_data = array_slice($data, $offset, $pagenum);

        if ($daochu == 1) {
            $columns = [
                'date' => '日期',
                'uid' => '用户Id',
                'output_amount_1' => '4倍地鼠产出',
                'consume_amount_1' => '4倍地鼠消耗',
                'explodeRate_1' => '4倍地鼠爆率',
                'output_amount_2' => '8倍地鼠产出',
                'consume_amount_2' => '8倍地鼠消耗',
                'explodeRate_2' => '8倍地鼠爆率',
                'output_amount_3' => '16倍地鼠产出',
                'consume_amount_3' => '16倍地鼠消耗',
                'explodeRate_3' => '16倍地鼠爆率',
                'output_amount_4' => '32倍地鼠产出',
                'consume_amount_4' => '32倍地鼠消耗',
                'explodeRate_4' => '32倍地鼠爆率',
                'output_amount_99' => '4倍地鼠王产出',
                'consume_amount_99' => '4倍地鼠王消耗',
                'explodeRate_99' => '4倍地鼠王爆率',
                'output_amount_king' => '地鼠王产出',
                'consume_amount_king' => '地鼠王消耗',
                'explodeRate_king' => '地鼠王爆率',
                'consume_amount_sum_data' => '总消耗',
                'output_amount_sum_data' => '总产出',
                'explodeRate_sum_data' => '总爆率',
            ];
            ExportExcelService::getInstance()->export($data, $columns);
        }

        $page_info['page'] = $page;
        $page_info['total_page'] = (int) ceil($count / $pagenum);
        View::assign('page', $page_info);
        View::assign('gopher_map', CommonConst::GOPHER_MAP);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('gopherId', $gopherId);
        View::assign('consumption', $consumption);
        View::assign('output', $output);
        View::assign('explodeRate', $explodeRate);
        View::assign('data', $page_data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('treasure/RealTimeRate');
    }

    public function outputDetails()
    {
        $pagenum = 20;
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $uid = (int) Request::param('uid');
        $daochu = (int) Request::param('daochu');
        $type = (string) Request::param('type');
        $page = (int) Request::param('page', 1);
        $offset = ($page - 1) * $pagenum;

        $where[] = ['event_id', '=', 10009];
        $where[] = ['ext_1', '=', 'gopher'];
        $where[] = ['success_time', '>=', strtotime($start)];
        $where[] = ['success_time', '<', strtotime($end)];

        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }

        if ($type) {
            $where[] = ['ext_2', '=', (string) $type];
        }
        $data = Db::table(getTable($start, $end))->field('*,FROM_UNIXTIME(success_time) as success_time')->where($where)->order('id', 'desc')->limit($offset, $pagenum)->select()->toArray();
        $count = Db::table(getTable($start, $end))->where($where)->count();
        $page_array = [];
        $page_array['page'] = $page;
        $page_array['total_page'] = (int) ceil($count / $pagenum);

        if ($daochu == 1) {
            $columns = [
                'uid' => '用户ID',
                'ext_2' => '地鼠ID',
                'change_amount' => '消耗产出数量',
                'success_time' => '创建时间',
            ];
            ExportExcelService::getInstance()->export($data, $columns);
        }

        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('type', $type);
        View::assign('gopher_map', CommonConst::GOPHER_MAP);
        View::assign('page', $page_array);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('treasure/outputDetail');
    }
}

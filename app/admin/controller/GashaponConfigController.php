<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ConfigModel;
use app\admin\model\UserAssetLogModel;
use app\admin\script\analysis\AnalysisCommon;
use app\admin\service\ConfigService;
use app\common\ParseUserStateDataCommmon;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class GashaponConfigController extends AdminBaseController
{

    /*
     *
    $config = [
    "gashapon"=>[
    "price"=>["assetId"=>"user:coin","count"=>50],
    "count" => [1,10],
    "lotterys" =>[
    ["weight"=>5,"reward"=>["assetId"=>"prop:129","count"=>7]],
    ["weight"=>5,"reward"=>["assetId"=>"prop:128","count"=>4]],
    ["weight"=>5,"reward"=>["assetId"=>"prop:126","count"=>3]],
    ]
    ]];
     *
     * */

    public function getLotterys($lotterys, $propName, $propid = 0)
    {
        $popsRes = [];
        if ($propid == 0) {
            foreach ($lotterys as $key => $popsitem) {
                $assetId = $popsitem['reward']['assetId'];
                $assetArr = explode(":", $assetId);
                $kindid = $assetArr[1] ?? 0;
                $popsRes[$kindid]['id'] = $kindid;
                $popsRes[$kindid]['assetname'] = $propName[$kindid] ?? '';
                $popsRes[$kindid]['assetId'] = $popsitem['reward']['assetId'];
                $popsRes[$kindid]['count'] = $popsitem['reward']['count'];
                $popsRes[$kindid]['weight'] = $popsitem['weight'];
            }
            return array_values($popsRes);
        }
    }

    //扭蛋机配置列表
    public function gashaponConList()
    {
        $namekey = "gashapon_conf";
        $prop_id = $this->request->param('prop_id', 0, 'trim'); //道具ID
        $propList = ConfigService::getInstance()->JsonEscape("prop_conf");
        if (!is_array($propList)) {
            $propList = json_decode($propList, true);
        }
        $propName = array_column($propList, "name", "kindId");
        if ($this->request->param("isRequest") == 1) {
            $returnRes = [];
            $configRes = ConfigService::getInstance()->JsonEscape($namekey);
            if (!is_array($configRes)) {
                $configRes = json_decode($configRes, true);
            }

            $gashapon_price = $configRes['gashapon']['price']['count'] ?? 50;
            $gashapon_count = join(",", $configRes['gashapon']['count'] ?? []);

            if (isset($configRes['gashapon']['lotterys'])) {
                $lotterys = $configRes['gashapon']['lotterys'];
                $returnRes = $this->getLotterys($lotterys, $propName, $prop_id);
            }
            $hz = ["gashapon_count" => $gashapon_count, "gashapon_price" => $gashapon_price];
            echo json_encode(["msg" => '', "count" => count($returnRes), "code" => 0, "data" => $returnRes, "hz" => $hz]);
        } else {
            View::assign('propList', $propList);
            View::assign('token', $this->request->param('token'));
            return View::fetch('config/gashapon');
        }

    }

    //扭蛋机配置编辑
    public function gashaponConAdd()
    {
        $namekey = "gashapon_conf";
        $prop_id = $this->request->param('propid', 0, 'trim'); //道具ID
        $weight = $this->request->param('weight', 0, 'trim'); //权重
        $count = $this->request->param('count', 0, 'trim'); //数量
        $gashapon_count = $this->request->param('gashapon_count', 1, 'trim'); //数量
        $gashapon_price = $this->request->param('gashapon_price', 50, 'trim'); //数量
        $gashapon_edit = $this->request->param('gashapon_edit', '', 'trim'); //数量
        $configRes = ConfigService::getInstance()->JsonEscape($namekey);
        if (!is_array($configRes)) {
            $configRes = json_decode($configRes, true);
        }
        try {
            if ($gashapon_edit) {
                //修改公用的配置 扭蛋机的每次的价格
                $counts = explode(",", $gashapon_count);
                $configRes['gashapon']['count'] = array_map(function ($v) {
                    return intval($v);
                }, $counts);
                $configRes['gashapon']['price'] = ["assetId" => "user:coin", "count" => (int) $gashapon_price];
            } else {
                $is_edit = false;
                $lotterys = $configRes['gashapon']['lotterys'] ?? [];
                foreach ($lotterys as $key => $item) {
                    //存在
                    if ($item['reward']['assetId'] == "prop:" . $prop_id) {
                        $is_edit = $key;
                        break;
                    }
                }
                if ($is_edit !== false) {
                    //编辑
                    $lotterys[$is_edit] = ["weight" => (int) $weight, "reward" => ["assetId" => "prop:" . $prop_id, "count" => (int) $count]];
                } else {
                    //新增
                    $lotterys[] = ["weight" => (int) $weight, "reward" => ["assetId" => "prop:" . $prop_id, "count" => (int) $count]];
                }
                $configRes['gashapon']['lotterys'] = $lotterys;
            }

            if (ConfigModel::getInstance()->getModel()->where(["name" => $namekey])->find()) {
                ConfigModel::getInstance()->getModel()->where(["name" => $namekey])->update(["json" => json_encode($configRes)]);
            } else {
                ConfigModel::getInstance()->getModel()->insert(["json" => json_encode($configRes), 'name' => $namekey]);
            }

            echo json_encode(["code" => 0, "msg" => "设置成功,请点击更新上线"]);
        } catch (Throwable $e) {
            Log::error("gashaponconadd:" . $e->getMessage());
            echo json_encode(["code" => -1, "msg" => "操作异常"]);
        }
    }

    //扭蛋机的配置推送到redis中
    public function gashaponConPublishCache()
    {
        try {
            $socket_url = config('config.app_api_url') . 'api/inner/gashapon/setConf';
            $gashapon_conf = ConfigModel::getInstance()->getModel()->where(['name' => 'gashapon_conf'])->value('json');
            $operatorId = $this->token['id'] ?: 0;
            $redis = $this->getRedis();
            $token = $redis->get('admin_token_' . $operatorId);
            $params = [
                "operatorId" => $operatorId,
                "token" => $token,
                "conf" => $gashapon_conf,
            ];
            $res = curlData($socket_url, json_encode($params), 'POST', 'json');
            $parseres = json_decode($res, true);
            if (isset($parseres['code']) && $parseres['code'] == 200) {
                Log::info("gashaponConPublishCache:res" . $res);
                echo json_encode(["code" => 0, "msg" => "成功"]);
                exit;
            } else {
                Log::info("gashaponConPublishCache:res" . $res);
                echo json_encode(["code" => 0, "msg" => $parseres['desc'] ?? '操作异常']);
                exit;
            }

        } catch (Throwable $e) {
            Log::error("gashaponConPublishCache:error:" . $e->getMessage());
            echo json_encode(["code" => -1, "msg" => "操作异常"]);
        }
    }

    //刷新扭蛋机奖池
    public function gashaponRefreshPool()
    {
        try {
            $socket_url = config('config.app_api_url') . 'api/inner/gashapon/refreshPool';
            $operatorId = $this->token['id'] ?: 0;
            $redis = $this->getRedis();
            $token = $redis->get('admin_token_' . $operatorId);
            $params = [
                "operatorId" => $operatorId,
                "token" => $token,
            ];
            Log::info("gashaponrefreshpool:params" . json_encode($params));
            Log::info("gashaponrefreshpool:socketurl" . $socket_url);
            $res = curlData($socket_url, json_encode($params), 'POST', 'json');
            Log::info("gashaponrefreshpool:res" . $res);
            $parseRes = json_decode($res, true);
            if ($parseRes['code'] == 200) {
                echo json_encode(["code" => 0, "msg" => "刷新奖池成功"]);
            } else {
                echo json_encode(["code" => -1, "msg" => $parseRes['desc'] ?? '操作异常']);
            }

        } catch (Throwable $e) {
            Log::error("gashaponrefreshpool:error:" . $e->getMessage());
            echo json_encode(["code" => -1, "msg" => "操作异常"]);
        }
    }

    //查看运行中的扭蛋机奖池
    public function gashaponSeekRuning()
    {
        if ($this->request->param("isRequest") == 1) {
            $propList = ConfigService::getInstance()->JsonEscape("prop_conf");
            if (!is_array($propList)) {
                $propList = json_decode($propList, true);
            }
            $propName = array_column($propList, "name", "kindId");
            $returnRes = [];
            $socket_url = config('config.app_api_url') . 'api/inner/gashapon/getRunningPool';
            $operatorId = $this->token['id'] ?: 0;
            $redis = $this->getRedis();
            $token = $redis->get('admin_token_' . $operatorId);
            $params = [
                "operatorId" => $operatorId,
                "token" => $token,
            ];
            Log::info("gashaponseekruning:params" . json_encode($params));
            $res = curlData($socket_url, json_encode($params), 'POST', 'json');
            Log::info("gashaponseekruning:res" . $res);
            $parseRes = json_decode($res, true);
            $returnRes = [];
            if ($parseRes['code'] == 200 && isset($parseRes['data']['lotterys'])) {
                $lotterys = $parseRes['data']['lotterys'];
                foreach ($lotterys as $key => $item) {
                    $assetArr = explode(":", $key);
                    $kindid = $assetArr[1] ?? 0;
                    $assetname = $propName[$kindid] ?? '';
                    $returnRes[] = ["assetname" => $assetname, "assetId" => $key, "count" => $item, "id" => $kindid];
                }
            }
            echo json_encode(["msg" => '', "count" => count($returnRes), "code" => 0, "data" => $returnRes]);
        } else {
            View::assign('token', $this->request->param('token'));
            return View::fetch('config/gashaponRuning');
        }
    }

    //扭蛋机配置编辑
    public function gashaponConDel()
    {
        $prop_id = $this->request->param('prop_id', 0, 'trim'); //道具ID
        $configRes = ConfigService::getInstance()->JsonEscape("gashapon_conf");
        if (!is_array($configRes)) {
            $configRes = json_decode($configRes, true);
        }
        try {
            $lotterys = $configRes['gashapon']['lotterys'] ?? [];
            foreach ($lotterys as $key => $item) {
                //存在
                if ($item['reward']['assetId'] == "prop:" . $prop_id) {
                    unset($lotterys[$key]);
                }
            }
            $configRes['gashapon']['lotterys'] = array_values($lotterys);
            ConfigModel::getInstance()->getModel()->where(["name" => "gashapon_conf"])->update(["json" => json_encode($configRes)]);
            echo json_encode(["code" => 0, "msg" => "设置成功,请点击更新上线"]);
        } catch (Throwable $e) {
            Log::error("gashaponconadd:" . $e->getMessage());
            echo json_encode(["code" => -1, "msg" => "操作异常"]);
        }
    }

    /*
     * 扭蛋机数据
     */
    public function gashaponDetail()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $page = Request::param('page', 1);
        $offset = ($page - 1) * self::LIMIT;
        $uid = $this->request->param('uid', '');

        $getInstance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);

        $query = UserAssetLogModel::getInstance($getInstance)->getModel($uid)
            ->where('ext_1', 'gashapon')
            ->where('type', 1)
            ->where('success_time', '>=', strtotime($start))
            ->where('success_time', '<', strtotime($end));

        $coin_query = UserAssetLogModel::getInstance($getInstance)->getModel($uid)
            ->where('ext_1', 'gashapon')
            ->where('type', 6)
            ->where('change_amount', '<', 0)
            ->where('success_time', '>=', strtotime($start))
            ->where('success_time', '<', strtotime($end));

        $coin_sum = 0;
        if ($uid) {
            $query = $query->where('uid', $uid);
            $coin_query = $coin_query->where('uid', $uid);
        }

        $coin_sum = $coin_query->sum('change_amount');

        $count = $query->count();
        $list = $query->order('id desc')->limit($offset, self::LIMIT)->select()->toArray();

        $page_array['total_page'] = AnalysisCommon::getPage($count, self::LIMIT);
        $page_array['page'] = $page;

        $props = ConfigService::getInstance()->JsonEscape('prop_conf');

        $props_map = array_column($props, 'name', 'kindId');

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('uid', $uid);
        View::assign('props_map', $props_map);
        View::assign('coin_sum', abs($coin_sum));
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/gashapon/detail');
    }
}
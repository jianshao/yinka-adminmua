<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\CommonConst;
use app\admin\model\ActivityTimesModel;
use app\admin\model\BiActivityTimesModel;
use app\admin\model\ConfigModel;
use app\admin\model\DeliveryAddressModel;
use app\admin\model\GashaponRewardModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\RoomPkModel;
use app\admin\model\UserAssetLogModel;
use app\admin\script\analysis\AnalysisCommon;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ConfigService;
use app\admin\service\ExportExcelService;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use think\App;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class ActivityController extends AdminBaseController
{
    public $pagenum = 20;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    /*
     *淘金活动
     */
    public function taojinActivityList()
    {
        $page = Request::param('page', 1);
        $offset = ($page - 1) * $this->pagenum;
        $daochu = $this->request->param('daochu');

        $where[] = ['type', '=', 'taojin'];

        $count = AnalysisCommon::getStatsCount(BiActivityTimesModel::getInstance()->getModel(), $where);
        $list = AnalysisCommon::getStatsItems(BiActivityTimesModel::getInstance()->getModel(), $where, $offset, $this->pagenum);

        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/List');
    }

    /*
     *淘金详情
     */
    public function taojinDetail()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $id = Request::param('id', 0);

        $page = Request::param('page', 1);
        $offset = ($page - 1) * $this->pagenum;
        $uid = $this->request->param('uid', '');
        $game_id = $this->request->param('game_id', '');

        $taojin_conf = ConfigService::getInstance()->taojinConf('taojin_conf', 0);
        $goods_conf = ConfigService::getInstance()->goods('goods_conf');

        $game_asset_map = CommonConst::GAME_ASSET_MAP;
        $games = array_column($taojin_conf['list'], 'name', 'gameId');
        $query = Db::table('bi_days_user_taojin_stats');

        if ($id) {
            $query = $query->where('activity_id', $id);
        } else {
            $query = $query->where('date', '>=', $start)->where('date', '<', $end);
        }

        if ($uid) {
            $query = $query->where('uid', $uid);
        }

        if ($game_id) {
            $query = $query->where('game_id', $game_id);
        }

        $count = $query->count();
        $list = $query->limit($offset, $this->pagenum)->select()->toArray();
        // $list = $query->limit($offset, $this->pagenum)->fetchSql(true)->select();
        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('uid', $uid);
        View::assign('game_id', $game_id);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('games', $games);
        View::assign('goods_conf', $goods_conf);
        View::assign('game_asset_map', $game_asset_map);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/detail');
    }

    /*
     *淘金兑换记录
     */
    public function taojinExchangelog()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $page = Request::param('page', 1);
        $offset = ($page - 1) * $this->pagenum;
        $uid = $this->request->param('uid', '');
        $game_id = $this->request->param('game_id', '');

        $id = Request::param('id', 0);

        $taojin_conf = ConfigService::getInstance()->taojinConf('taojin_conf', 0);
        $goods_conf = ConfigService::getInstance()->goods('goods_conf');

        $game_asset_map = CommonConst::GAME_ASSET_MAP;
        $games = array_column($taojin_conf['list'], 'name', 'gameId');
        $query = UserAssetLogModel::getInstance()->getModel()->where('event_id', 10005)
            ->field('uid,asset_id,abs(change_amount) as consume_amount,ext_2 as goods_id,ext_3 as count,success_time,ext_4 as game_id')
            ->where('ext_1', 'ore')
            ->where('type', 8);

        if ($id) {
            $activity = ActivityTimesModel::getInstance()->getModel()->where('id', $id)->findOrEmpty()->toArray();
            $query = $query->where('success_time', '>=', strtotime($activity['start_time']))->where('success_time', '<=', strtotime($activity['end_time']));
        } else {
            $query = $query->where('success_time', '>=', strtotime($start))->where('success_time', '<', strtotime($end));
        }

        if ($uid) {
            $query = $query->where('uid', $uid);
        }

        if ($game_id) {
            $query = $query->where('ext_4', (string) $game_id);
        }

        $count = $query->count();
        $list = $query->limit($offset, $this->pagenum)->select()->toArray();
        // $list = $query->limit($offset, $this->pagenum)->fetchSql(true)->select();

        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('uid', $uid);
        View::assign('game_id', $game_id);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('games', $games);
        View::assign('goods_conf', $goods_conf);
        View::assign('game_asset_map', $game_asset_map);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/exchange');
    }

    /*
     *扭蛋机中奖列表
     */
    public function eggTwistedList()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        $page = Request::param('page', 1);
        $offset = ($page - 1) * $this->pagenum;
        $uid = $this->request->param('uid', '');

        $props_map = ConfigModel::getInstance()->getPropMap();
        $gift_map = GiftsCommon::getInstance()->getGifts();

        $game_asset_map = CommonConst::ASSET_TYPE_MAP;
        $query = GashaponRewardModel::getInstance()->getModel($uid)
            ->where('create_time', '>=', strtotime($start))
            ->where('create_time', '<', strtotime($end))
            ->where('uid', $uid);

        // if ($uid) {
        //     $query = $query->where('uid', $uid);
        // }

        $count = $query->count();
        $list = $query->limit($offset, $this->pagenum)->select()->toArray();

        foreach ($list as $_ => &$item) {
            $reward_type = explode(':', $item['reward_id']);
            $type = $reward_type[0];
            $reward_id = $reward_type[1];
            if ($type == 'user') {
                $type_name = CommonConst::USER_ASSET_MAP[$item['reward_id']];
                $item['reward_id'] = '';
            } else {
                $type_name = CommonConst::GAME_ASSET_MAP[$type];

                $reward_name = $type == 'prop' ? $props_map[$reward_id] : $gift_map[$reward_id];
                $item['reward_id'] = $reward_type[1] . "($reward_name)";
            }

            $item['reward_type'] = $type_name;
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }

        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('uid', $uid);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('game_asset_map', $game_asset_map);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/eggTwistedDetail');
    }

    /*
     *月饼中奖列表
     */
    public function zhongQiuList()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        $page = Request::param('page', 1);
        $daochu = $this->request->param('daochu', 0);

        $offset = ($page - 1) * $this->pagenum;
        $user_id = $this->request->param('user_id', '');

        $query = DeliveryAddressModel::getInstance()->getModel();

        if ($user_id) {
            $query = $query->where('user_id', $user_id);
        }
        $count = 0;
        if ($daochu != 1) {
            $query = $query->where('create_time', '>=', strtotime($start))
                ->where('create_time', '<', strtotime($end));
            $query2 = clone $query;
            $count = $query->count();
            $query = $query2->limit($offset, $this->pagenum);
        }

        $list = $query->select()->toArray();

        foreach ($list as $_ => &$item) {
            $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
        }
        if ($daochu == 1) {
            $columns = [
                'id' => 'ID',
                'user_id' => '用户ID',
                'name' => '用户名称',
                'mobile' => '手机号',
                'region' => '地址',
                'address' => '详细地址',
                'activity_type' => '活动类型',
                'reward' => '奖品kindId',
                'create_time' => '时间',
            ];
            ExportExcelService::getInstance()->export($list, $columns);
        }

        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;
        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('user_id', $user_id);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/addressDetail');
    }

    /*
     * 感恩节活动
     */
    public function thankGivingActivityList()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $game_id = $this->request->param('game_id', '');
        $daochu = $this->request->param('daochu', 0);

        $page = Request::param('page', 1);
        $offset = ($page - 1) * $this->pagenum;
        $uid = $this->request->param('uid', 0);

        $goods_conf = ConfigService::getInstance()->goods('goods_conf');

        $game_asset_map = CommonConst::GAME_ASSET_MAP;
        $games = ['1' => '1号车', '2' => '2号车', '3' => '3号车', '4' => '4号车', '5' => '5号车'];

        $getInstance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);

        //消耗
        $query = UserAssetLogModel::getInstance($getInstance)->getModel($uid)
            ->field("DATE_FORMAT( FROM_UNIXTIME( success_time ), '%Y-%m-%d' ) date,uid,sum( ext_5 ) amount,ext_2,CASE WHEN change_amount < 0 THEN 1 ELSE 2 END action")
            ->where('ext_1', 'car')
            ->where('success_time', '>=', strtotime($start))
            ->where('success_time', '<', strtotime($end));

        $round = UserAssetLogModel::getInstance($getInstance)->getModel($uid)
            ->field("count(distinct(ext_4)) count")
            ->where('ext_1', 'car')
            ->where('success_time', '>=', strtotime($start))
            ->where('success_time', '<', strtotime($end));

        if ($uid) {
            $query = $query->where('uid', $uid);
            $round = $round->where('uid', $uid);
        }

        if ($game_id) {
            $query = $query->where('ext_2', $game_id);
            $round = $round->where('ext_2', $game_id);
        }
        $build_sql = $query->group('uid,date,action')->order('date desc,amount desc')->buildSql();
        $round = $round->select()->toArray();

        //合计的数据
        $total_list = UserAssetLogModel::getInstance($getInstance)->getModel($uid)->table($build_sql)
            ->alias('A')
            ->field('A.date,A.uid,A.amount consume_amount,B.amount output_amount')
            ->leftJoin("$build_sql as B", 'A.uid = B.uid and A.date = B.date')
            ->where('A.action', 1)
            ->where('B.action', 2)
            // ->fetchSql(true)
            ->select()
            ->toArray();

        if ($daochu == 1) {
            $columns = ['date' => '日期', 'uid' => '用户ID', 'consume_amount' => '消耗', 'output_amount' => '产出'];
            ExportExcelService::getInstance()->export($total_list, $columns);
        }

        //合计数据
        $total_consume_amount = array_sum(array_column($total_list, 'consume_amount'));
        $total_output_amount = array_sum(array_column($total_list, 'output_amount'));
        $total_users_count = count(array_unique(array_column($total_list, 'uid')));
        $total_round_count = $round[0]['count'];
        $car_pool_value = RedisCommon::getInstance()->getRedis()->hGET('car_info', 'car_profits_pool');

        $count = count($total_list);
        $list = array_slice($total_list, $offset, $this->pagenum);

        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('uid', $uid);
        // View::assign('game_id', $game_id);
        View::assign('total_consume_amount', $total_consume_amount);
        View::assign('total_output_amount', $total_output_amount);
        View::assign('total_users_count', $total_users_count);
        View::assign('total_round_count', $total_round_count);
        View::assign('car_pool_value', $car_pool_value);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('games', $games);
        View::assign('goods_conf', $goods_conf);
        View::assign('game_asset_map', $game_asset_map);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/car/carList');
    }

    /*
     *淘金详情
     */
    public function thankGivingActivityDetail()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $page = Request::param('page', 1);
        $offset = ($page - 1) * $this->pagenum;
        $uid = $this->request->param('uid', '');
        $game_id = $this->request->param('game_id', '');
        $games = ['1' => '1号车', '2' => '2号车', '3' => '3号车', '4' => '4号车', '5' => '5号车'];

        $getInstance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);
        $query = UserAssetLogModel::getInstance($getInstance)->getModel($uid)
            ->where('ext_1', 'car')
            ->where('uid', $uid)
            ->where('success_time', '>=', strtotime($start))
            ->where('success_time', '<', strtotime($end));

        if ($game_id) {
            $query = $query->where('ext_2', $game_id);
        }

        $count = $query->count();
        $list = $query->order('id desc')->limit($offset, $this->pagenum)->select()->toArray();

        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('uid', $uid);
        View::assign('game_id', $game_id);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('games', $games);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/car/carDetail');
    }

    /*
     * 感恩节活动
     */
    public function pkActivityList()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $game_id = $this->request->param('game_id', '');
        $daochu = $this->request->param('daochu', 0);

        $page = Request::param('page', 1);
        $offset = ($page - 1) * $this->pagenum;
        $red_room_id = $this->request->param('red_room_id', 0);
        $blue_room_id = $this->request->param('blue_room_id', 0);
        $uid = $this->request->param('uid', 0);

        $goods_conf = ConfigService::getInstance()->goods('goods_conf');

        $game_asset_map = CommonConst::GAME_ASSET_MAP;
        $games = ['1' => '1号车', '2' => '2号车', '3' => '3号车', '4' => '4号车', '5' => '5号车'];

        //消耗
        $query = RoomPkModel::getInstance()->getModel()
            ->field("id,FROM_UNIXTIME( create_time ) start_time,FROM_UNIXTIME( end_time ) end_time,red_room_id,blue_room_id,pk_mode,punishment,win_team,red_pk_data,red_contribute_data,blue_pk_data,blue_contribute_data")
            ->where('create_time', '>=', strtotime($start))
            ->where('create_time', '<', strtotime($end));

        if ($red_room_id) {
            $query = $query->where('red_room_id', $red_room_id);
        }

        if ($blue_room_id) {
            $query = $query->where('blue_room_id', $blue_room_id);
        }

        if ($game_id >= 1) {
            $query = $query->where('pk_mode', $game_id);
        }

        $count = $query->count();
        $clone = clone $query;
        $total_list = $clone->select()->toArray();
        $list = $query->order('id desc')->limit($offset, $this->pagenum)->select()->toArray();

        $deal_func = function ($item) {
            $item['blue_room_name'] = LanguageroomModel::getInstance()->getOneById($item['blue_room_id'], 'room_name');
            $item['red_room_name'] = LanguageroomModel::getInstance()->getOneById($item['red_room_id'], 'room_name');
            $win_room_id = $item['win_team'] == 'red' ? $item['red_room_id'] : ($item['win_team'] == 'blue' ? $item['blue_room_id'] : '');
            $item['win_room_id'] = $win_room_id;
            $item['win_room_name'] = '';
            if ($win_room_id) {
                $item['win_room_name'] = LanguageroomModel::getInstance()->getOneById($win_room_id, 'room_name');
            }
            return $item;
        };

        foreach ($list as $_ => &$item) {
            $item = $deal_func($item);
        }
        foreach ($total_list as $_ => &$item) {
            $item = $deal_func($item);
        }
        $this->sumValues($list);
        $this->sumValues($total_list);

        if ($daochu == 1) {
            $columns = [
                'start_time' => '开始时间',
                'end_time' => '结束时间',
                'red_room_name' => '红队房间名',
                'red_room_id' => '红队房间ID',
                'sum_red_pk_data' => '红队魅力值',
                'count_red_contribute_data' => '红队贡献人数',
                'blue_room_name' => '蓝队房间名',
                'blue_room_id' => '蓝队房间ID',
                'sum_blue_pk_data' => '蓝队魅力值',
                'count_blue_contribute_data' => '蓝队贡献人数',
                'win_team' => '输赢',
                'win_room_id' => '赢房间ID',
                'win_room_name' => '赢房间名称',
            ];

            ExportExcelService::getInstance()->export($total_list, $columns);
        }
        //合计数据
        $sum_red_pk_data = array_sum(array_column($total_list, 'sum_red_pk_data'));
        $sum_red_contribute_data = array_sum(array_column($total_list, 'sum_red_contribute_data'));
        $count_red_contribute_data = array_sum(array_column($total_list, 'count_red_contribute_data'));
        $sum_blue_pk_data = array_sum(array_column($total_list, 'sum_blue_pk_data'));
        $sum_blue_contribute_data = array_sum(array_column($total_list, 'sum_blue_contribute_data'));
        $count_blue_pk_data = array_sum(array_column($total_list, 'count_blue_pk_data'));
        $count_blue_contribute_data = array_sum(array_column($total_list, 'count_blue_contribute_data'));

        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('game_id', $game_id);
        View::assign('red_room_id', $red_room_id);
        View::assign('blue_room_id', $blue_room_id);
        View::assign('sum_red_pk_data', $sum_red_pk_data);
        View::assign('sum_red_contribute_data', $sum_red_contribute_data);
        View::assign('count_red_contribute_data', $count_red_contribute_data);
        View::assign('sum_blue_pk_data', $sum_blue_pk_data);
        View::assign('sum_blue_contribute_data', $sum_blue_contribute_data);
        View::assign('count_blue_contribute_data', $count_blue_contribute_data);
        View::assign('count', $count);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('games', $games);
        View::assign('goods_conf', $goods_conf);
        View::assign('game_asset_map', $game_asset_map);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/pk/list');
    }

    public function sumValues(&$list)
    {
        foreach ($list as $_ => &$item) {
            $red_pk_data = json_decode($item['red_pk_data'], true);
            $item['red_pk_data'] = $red_pk_data;
            $item['sum_red_pk_data'] = array_sum(arrayValues($red_pk_data));
            $item['count_red_pk_data'] = count(arrayValues($red_pk_data));

            $red_contribute_data = json_decode($item['red_contribute_data'], true);
            if ($red_contribute_data) {
                arsort($red_contribute_data);
            }
            $item['red_contribute_data'] = $red_contribute_data;
            $item['sum_red_contribute_data'] = array_sum(arrayValues($red_contribute_data));
            $item['count_red_contribute_data'] = count(arrayValues($red_contribute_data));

            $blue_pk_data = json_decode($item['blue_pk_data'], true);
            if ($blue_pk_data) {
                arsort($blue_pk_data);
            }

            $item['blue_pk_data'] = $blue_pk_data;
            $item['sum_blue_pk_data'] = array_sum(arrayValues($blue_pk_data));
            $item['count_blue_pk_data'] = count(arrayValues($blue_pk_data));

            $blue_contribute_data = json_decode($item['blue_contribute_data'], true);
            if ($blue_contribute_data) {
                arsort($blue_contribute_data);
            }

            $item['blue_contribute_data'] = $blue_contribute_data;
            $item['sum_blue_contribute_data'] = array_sum(arrayValues($blue_contribute_data));
            $item['count_blue_contribute_data'] = count(arrayValues($blue_contribute_data));
        }
    }
    /*
     *淘金详情
     */
    public function pkkActivityDetail()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $page = Request::param('page', 1);
        $offset = ($page - 1) * $this->pagenum;
        $uid = $this->request->param('uid', '');
        $game_id = $this->request->param('game_id', '');
        $games = ['1' => '1号车', '2' => '2号车', '3' => '3号车', '4' => '4号车', '5' => '5号车'];

        $getInstance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start, $end);
        $query = UserAssetLogModel::getInstance($getInstance)->getModel($uid)
            ->where('ext_1', 'car')
            ->where('uid', $uid)
            ->where('success_time', '>=', strtotime($start))
            ->where('success_time', '<', strtotime($end));

        if ($game_id) {
            $query = $query->where('ext_2', $game_id);
        }

        $count = $query->count();
        $list = $query->order('id desc')->limit($offset, $this->pagenum)->select()->toArray();

        $page_array['total_page'] = AnalysisCommon::getPage($count, $this->pagenum);
        $page_array['page'] = $page;

        Log::record('跑量列表:操作人:' . $this->token['username'], 'memberList');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('uid', $uid);
        View::assign('game_id', $game_id);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('games', $games);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('activity/pk/detail');
    }
}

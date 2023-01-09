<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\CommonConfig;
use app\admin\common\CommonConst;
use app\admin\model\AdminUserModel;
use app\admin\model\BillDetailModel;
use app\admin\model\BillModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\ConfigModel;
use app\admin\model\MemberModel;
use app\admin\model\UserAssetLogModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ElasticsearchService;
use app\admin\service\ExportExcelService;
use app\common\ParseUserStateDataCommmon;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class CoindetailController extends AdminBaseController
{
    /**
     * M豆订单列表
     */
    public function MOrder()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = Request::param('uid', ''); //用户ID
        $callfunc = function($data){
            foreach ($data as $key => $val) {
                $data[$key]['giftcount'] = $data[$key]['ext_3'];
                $data[$key]['addtime'] = date('Y-m-d H:i:s',$data[$key]['success_time']);
                $data[$key]['coin'] = $data[$key]['ext_4'];
                switch ($data[$key]['type']) {
                    case '3':
                        $data[$key]['action'] = "背包赠送礼物";
                        break;
                    case '4':
                        $data[$key]['action'] = "直送礼物";
                        break;
                }

                if (isset($val['status']) && $val['status'] == 1) {
                    $data[$key]['status'] = "虚拟币";
                } elseif (isset($val['status']) && $val['status'] == 2) {
                    $data[$key]['status'] = '钻石';
                } else {
                    $data[$key]['status'] = 'VIP';
                }

                if (isset($val['change_type']) && $val['change_type'] == 1) {
                    $data[$key]['change_type'] = "语言";
                } elseif (isset($val['change_type']) && $val['change_type'] == 2) {
                    $data[$key]['change_type'] = '一对一';
                } else {
                    $data[$key]['change_type'] = '直播';
                }
                $giftname = $this->getGiftConf($data[$key]['ext_1']);
                if (!empty($giftname)) {
                    $data[$key]['giftid'] = $giftname;
                }
                $data[$key]['coin_after'] = $val['change_before'] - abs($val['change_amount']);
                $data[$key]['coin_before'] = (int) $val['change_before'];

            }
            return $data;
        };

        $esparams = ElasticsearchService::getInstance()->searchWhere("es_zb_user_asset_log");
        $esparams['body']['from'] = $page;
        $esparams['body']['size'] = $pagenum;

        if (!empty($uid)) {
            $esparams['body']['query']['bool']['must'][] = ['term' => ['uid' => $uid]];
        }
        $esparams['body']['query']['bool']['must'][] = ['term' => ['event_id' => 10002]];
        $esparams['body']['query']['bool']['must'][] = ['terms' => ['type' => [3,4]]];
        $searchData = ElasticsearchService::getInstance()->search($esparams);
        $data = $searchData['data'] ?? [];
        $count = $searchData['total'] ?? 0;
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('会员特权列表获取成功:操作人:' . $this->token['username'], 'vipPrivilegeList');
        View::assign('page', $page_array);
        View::assign('data', $callfunc($data));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('uid', $uid);
        return View::fetch('coindetail/morder');
    }

    /**用户(分页)收礼
     * @return mixed
     */
    public function userGetCoin()
    {
        $user_id = Request::get('user_id'); //用户id
        $limit = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $where = [];
        if (!empty($user_id)) {
            $where[] = ['touid', '=', $user_id];
        }
        $where[] = ['event_id', '=', '10002'];
        $where[] = ['type', 'in', '3,4'];
        $field = 'id,uid as touid,room_id,ext_2 as giftid,ext_3 as giftcount,FROM_UNIXTIME(success_time) as addtime,ext_1,ext_2,type';
        $data = UserAssetLogModel::getInstance()->getModel()->field($field)->where($where)->order('id desc')->limit($offset, $limit)->select()->toArray();

        $gifts_map = GiftsCommon::getInstance()->getGiftMap();
        $gifts = array_column($gifts_map, null, 'id');

        $totalPage = 0;
        $count = 0;
        if (!empty($data)) {
            foreach ($data as $key => &$value) {
                if ($value['ext_1'] == $value['ext_2'] && $value['type'] == 4 && $value['giftid'] != '395') {
                    $data[$key]['content'] = "赠送礼物";
                } elseif ($value['ext_1'] == $value['ext_2'] && $value['type'] == 3) {
                    $data[$key]['content'] = "背包赠送礼物";
                } elseif ($value['ext_1'] != $value['ext_2'] && $value['giftid'] == 'bean') {
                    $data[$key]['content'] = "礼物盒子";
                } elseif ($value['ext_1'] == $value['ext_2'] && $value['giftid'] == '395') {
                    $data[$key]['content'] = "游戏礼物";
                } else {
                    $data[$key]['content'] = "背包抵扣赠送礼物";
                }
                $value['gift_name'] = isset($gifts[$value['giftid']]) ? $gifts[$value['giftid']]['gift_name'] : '';
                $value['gift_coin'] = isset($gifts[$value['giftid']]) ? $gifts[$value['giftid']]['gift_coin'] : '';
            }
            $count = UserAssetLogModel::getInstance()->getModel()->where($where)->count();
        }
        $totalPage = ceil($count / $limit);
        Log::record('用户收礼明细列表:操作人:' . $this->token['username'], 'userGetCoin');
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        $admin_url = config('config.admin_url');
        View::assign('list', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('user_id', $user_id);
        View::assign('admin_url', $admin_url);
        return View::fetch('coindetail/userGetCoin');
    }

    public function getCoinDetailGivingList()
    {
        $size = 1000;
        $page = 0;
        $uid = $this->request->param('uid');
        $where = [];
        if ($uid) {
            $where['uid'] = $uid;
        }
        $admin_url = config('config.admin_url');
        // $where['action'] = 'sendgift';
        /*$where['action'] = ['in','sendgift','sendgiftFromBag'];
        $list = CoinDetailService::getInstance()->getCoinDetailByWhere($where, 'id,touid,room_id,giftid,giftcount,addtime,content',array($page, $size));
        //查询送礼人名称
        if (!empty($list)) {
        $list = array_reverse($list);
        foreach ($list as $k => $v) {
        $list[$k]['nickname'] = MemberService::getInstance()->getOneById($v['touid'],'nickname')->toArray()['nickname'];
        $list[$k]['gift_name'] = GiftService::getInstance()->getOneById($v['giftid'],'gift_name')->toArray()['gift_name'];
        }
        }
        $data = [];
        $data['list']=$list;
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);*/
        $token = $this->request->param('token');
        echo json_encode(array('code' => 200, 'msg' => "成功", 'admin_url' => $admin_url, 'uid' => $uid, 'token' => $token));
        die;
    }

    /**用户(分页)送礼
     * @return mixed
     */
    public function userCoin()
    {
        $user_id = Request::get('user_id'); //用户id
        $limit = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $where = [];
        if (!empty($user_id)) {
            $where[] = ['uid', '=', $user_id];
        }
        $where[] = ['event_id', '=', '10002'];
        $where[] = ['type', 'in', '3,4'];
        $field = 'id,touid,room_id,ext_1 as giftid,ext_3 as giftcount,FROM_UNIXTIME(success_time) as addtime,ext_1,ext_2,type';
        $data = UserAssetLogModel::getInstance()->getModel()->field($field)->where($where)->order('id desc')->limit($offset, $limit)->select()->toArray();

        $gifts_map = GiftsCommon::getInstance()->getGiftMap();
        $gifts = array_column($gifts_map, null, 'id');

        $totalPage = 0;
        $count = 0;
        if (!empty($data)) {
            foreach ($data as $key => &$value) {
                if ($value['ext_1'] == $value['ext_2'] && $value['type'] == 4 && $value['giftid'] != '395') {
                    $data[$key]['content'] = "赠送礼物";
                } elseif ($value['ext_1'] == $value['ext_2'] && $value['type'] == 3) {
                    $data[$key]['content'] = "背包赠送礼物";
                } elseif ($value['ext_1'] != $value['ext_2'] && $value['giftid'] == 'bean') {
                    $data[$key]['content'] = "礼物盒子";
                } elseif ($value['ext_1'] == $value['ext_2'] && $value['giftid'] == '395') {
                    $data[$key]['content'] = "游戏礼物";
                } else {
                    $data[$key]['content'] = "背包抵扣赠送礼物";
                }
                $value['gift_name'] = $gifts[$value['giftid']]['gift_name'];
                $value['gift_coin'] = $gifts[$value['giftid']]['gift_coin'];
            }
            $count = UserAssetLogModel::getInstance()->getModel()->where($where)->count();
        }
        $totalPage = ceil($count / $limit);
        Log::record('用户送礼明细列表:操作人:' . $this->token['username'], 'getCoinDetailGivingList');
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        $admin_url = config('config.admin_url');
        View::assign('list', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('user_id', $user_id);
        View::assign('admin_url', $admin_url);
        return View::fetch('coindetail/userCoin');
    }

    //获取礼物配置
    public function getGiftConf($type = 0)
    {
        $rsc = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json');
        $data = [];
        if ($rsc) {
            if ($type == 0) {
                foreach (json_decode($rsc, true) as $k => $v) {
                    $data[$v['giftId']] = $v['name'];
                }
            } elseif ($type == 1) {
                foreach (json_decode($rsc, true) as $k => $v) {
                    $data[$k] = [
                        'id' => $v['giftId'],
                        'gift_name' => $v['name'],
                        'gift_coin' => $v['price']['count'],
                    ];
                }
                $data = array_column($data, null, 'id');
            } else {
                foreach (json_decode($rsc, true) as $k => $v) {
                    if ($type == $v['giftId']) {
                        return $v['name'];
                    }
                }
            }
        }

        return $data;
    }

    /**所有消费明细
     * @return mixed
     */
    public function getConsumeList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $demo = Request::param('demo', $this->default_date); //开始时间
        list($start, $end) = getBetweenDate($demo);
        $room_id = Request::get('room_id'); //强制转换为整型类型
        $user_id = Request::get('user_id',0); //用户id
        $daochu = Request::get('daochu', 0); //用户id
        $type = Request::get('type/d'); //类型
        $is_show = Request::get('is_show', 0); //类型
        $event_id = Request::get('event_id/d'); //强制转换为整型类型
        $ext_1 = Request::get('ext_1','','trim');
        $elasticService = ElasticsearchService::getInstance()->index("es_zb_user_asset_log");
        $elasticService->page($page,$pagenum);

        if (!empty($user_id)) {
            $elasticService->must(['uid' => $user_id]);
        }

        if (!empty($room_id)) {
            $elasticService->must(['room_id' => $room_id]);
        }

        if (!empty($event_id)) {
            $elasticService->must(['event_id' => $event_id]);
        }

        if(!empty($ext_1)){
            $elasticService->must(['ext_1' => $ext_1]);
        }

        $elasticService->range("success_time",['gte' => strtotime($start), 'lt' => strtotime($end)]);
        $elasticService->order("success_time","desc");
        $conf_map = CommonConfig::getInstance()->getCommonConfig();
        $gifts_map = GiftsCommon::getInstance()->getGifts();
        //数据格式化
        $formatFunc = function($data) use($conf_map,$gifts_map){
            if (!empty($data)) {
                foreach ($data as $key => &$value) {
                    $event = $value['event_id'];
                    $value['action'] = CommonConst::EVENTS_MAP[$event] ??'';
                    $unit = CommonConst::TYPE_MAP[$value['type']] ?? '';

                    if ($value['type'] == 3) {
                        $value['asset_id'] = $value['asset_id'] . '(' . $gifts_map[$value['asset_id']] ?? '' . ')';
                    }
                    $value['type'] = $unit;

                    $conf_ext_1 = $conf_map[$event]['ext_1'] ?? '';

                    if (in_array($event, [10002, 10003])) {
                        $ext_1 = $gifts_map[$value['ext_1']] ?? '';
                    } else {
                        $ext_1 = isset($conf_ext_1['desc']) ? $conf_ext_1['desc'] : '';
                    }

                    $ext_2 = isset($conf_ext_1[$value['ext_1']]) && isset($conf_ext_1[$value['ext_1']]['list'][$value['ext_2']]) ? $conf_ext_1[$value['ext_1']]['list'][$value['ext_2']] : '';
                    $ext_3 = $conf_map[$event]['ext_3'] ?? '';
                    $ext_4 = $conf_map[$event]['ext_4'] ?? '';
                    if (!empty($ext_1)) {
                        $value['ext_1'] .= "(" . $ext_1 . ")";
                    }
                    if (!empty($ext_2)) {
                        $value['ext_2'] .= "(" . $ext_2 . ")";
                    }
                    if (!empty($ext_3)) {
                        $value['ext_3'] .= "(" . $ext_3 . ")";
                    }
                    if (!empty($ext_4)) {
                        $value['ext_4'] .= "(" . $ext_4 . ")";
                    }
                    $value['type'] = $unit;
                    $value['addtime'] = date('Y-m-d H:i:s',$value['success_time']);
                }
            }
            return $data;
        };

        if ($daochu == 1) {
            $columns = ['id' => 'ID', 'action' => '事件ID', 'type' => '资产类型',
                'asset_id' => '资产ID', 'room_id' => '房间ID', 'uid' => '用户ID', 'touid' => 'to用户ID',
                'ext_1' => 'ext_1', 'ext_2' => 'ext_2', 'ext_3' => 'ext_3', 'ext_4' => 'ext_4',
                'change_amount' => '变化量', 'change_before' => '变化前', 'change_after' => '变化后', 'addtime' => '时间',
            ];
            ExportExcelService::getInstance()->dataElasExportCsv($elasticService,$columns,$formatFunc);exit;

        } else {
            $searchData = $elasticService->select();
            $data = $searchData['data'] ?? [];
            $count = $searchData['total'] ?? 0;
        }

        if (!empty($data)) {
            $data =  $formatFunc($data);
        }
        Log::record('用户消费明细列表:操作人:' . $this->token['username'], 'getConsumeList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $admin_url = config('config.admin_url');
        View::assign('list', $data);
        View::assign('events', CommonConst::EVENTS_MAP);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('user_id', $user_id);
        View::assign('room_id', $room_id);
        View::assign('demo', $demo);
        View::assign('is_show', $is_show);
        View::assign('admin_url', $admin_url);
        View::assign('type', $type);
        View::assign('event_id', $event_id);
        View::assign('ext_1', $ext_1);
        return View::fetch('coindetail/index');
    }

    /**运营调整
     * @return mixed
     */
    public function getKeFuAdjust()
    {
        $start_time = Request::param('start_time', $this->start_time); //开始时间
        $end_time = Request::param('end_time', $this->end_time); //结束时间
        $room_id = Request::get('room_id'); //强制转换为整型类型
        $user_id = Request::get('user_id'); //用户id
        $daochu = Request::get('daochu', 0); //用户id
        $uid = Request::get('uid'); //类型 运营ID

        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $where = [];
        $where[] = ['event_id', '=', 10020];
        $where[] = ['type', '=', 4];

        if (!empty($uid)) {
            $where[] = ['uid', '=', (int) $uid];
        }

        if (!empty($user_id)) {
            $where[] = ['ext_1', '=', (string) $user_id];
        }

        $where[] = ['success_time', '>=', strtotime($start_time)];
        $where[] = ['success_time', '<=', strtotime($end_time)];

        $field = 'uid,ext_1,change_amount,FROM_UNIXTIME(success_time) as success_time';
        $query = UserAssetLogModel::getInstance()->getModel()->where($where);
        $clone = clone $query;
        $data = $query->field($field)->limit($page, $pagenum)->order('id desc')->select()->toArray();

        $admins = AdminUserModel::getInstance()->getModel()->column('username', 'id');
        if ($daochu == 1) {
            $data1 = $clone->field($field)->order('id desc')->select()->toArray();
            foreach ($data1 as $key => &$item) {
                $item['name'] = $admins[$item['ext_1']];
            }
            $columns = [
                'name' => '运营名称',
                'ext_1' => '运营ID',
                'uid' => '用户ID',
                'change_amount' => '金额',
                'success_time' => '时间',
            ];
            ExportExcelService::getInstance()->export($data1, $columns);
        }

        $sum = $clone->sum('change_amount');

        $count = UserAssetLogModel::getInstance()->getModel()->where($where)->count();

        Log::record('用户消费明细列表:操作人:' . $this->token['username'], 'getConsumeList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $admin_url = config('config.admin_url');
        View::assign('list', $data);
        View::assign('events', CommonConst::EVENTS_MAP);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('admins', $admins);
        View::assign('page', $page_array);
        View::assign('user_id', $user_id);
        View::assign('uid', $uid);
        View::assign('sum', $sum);
        View::assign('room_id', $room_id);
        View::assign('daytime', $start_time);
        View::assign('start_time', $start_time);
        View::assign('end_time', $end_time);
        View::assign('admin_url', $admin_url);
        return View::fetch('coindetail/adjustCoin');
    }

    /**运营调整
     * @return mixed
     */
    public function getleftBeanAndDiamond()
    {
        $uid = Request::get('uid'); //类型 运营ID

        $field = 'sum(totalcoin-freecoin) bean,sum(diamond-exchange_diamond-free_diamond) diamond';
        $data = MemberModel::getInstance()->getModel($uid)->field($field)->select()->toArray();

        View::assign('list', $data);
        View::assign('events', CommonConst::EVENTS_MAP);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('uid', $uid);
        return View::fetch('coindetail/leftDiamondAndBean');
    }

    /**
     * 对账脚本数据
     */
    public function bill()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $demo = $this->request->param('demo', $this->default_date);
        list($start_time, $end_time) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');
        $where = [];
        $where[] = ['createtime', '>=', $start_time];
        $where[] = ['createtime', '<', $end_time];

        //统计
        $count = BillModel::getInstance()->getModel()->where($where)->count();
        //获取数据
        if ($daochu == 1) {
            $data = BillModel::getInstance()->getModel()->where($where)->order('id desc')->select()->toArray();
        } else {
            $data = BillModel::getInstance()->getModel()->where($where)->order('id desc')->limit($page, $pagenum)->select()->toArray();
        }
        $yucoin = 0; //m豆
        $countes = count($data);
        for ($i = 0; $i < $countes; $i++) {
            $nowday = $data[$i];
            $yucoin = $nowday['totalcoin'] - $nowday['freecoin'] + $nowday['diamond'] - $nowday['exchange_diamond'] - $nowday['free_diamond'] + $nowday['pack'] + $nowday['keys'];
            $data[$i]['surplus'] = $nowday['charge'] + $nowday['admincharge'] + $nowday['specialgift'];
            if ($i == 0) {
                $data[$i]['surplusdesc'] = 0;
            } else {
                $yesday = $data[$i - 1];
                $yucoin -= $yesday['totalcoin'] - $yesday['freecoin'] + $yesday['diamond'] - $yesday['exchange_diamond'] - $yesday['free_diamond'] + $yesday['pack'] + $yesday['keys'];
                $data[$i]['surplusdesc'] = $yucoin + $nowday['cash'] + $nowday['sendGiftCount'] + $nowday['adminchargedesc'];
            }

        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('bill列表获取成功:操作人:' . $this->token['username'], 'bill');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        if ($daochu == 1) {
            $this->_billcsv($data);
        }
        return View::fetch('coindetail/bill');
    }

    //导出csv
    private function _billcsv($data)
    {
        $headerArray = ['总豆', '总消费豆', '总钻石', '总兑换钻石', '总消费钻石', '后台加豆', '后台减豆', '总充值', '总背包', '总钥匙', '总提现', '送出礼物30%', '总收益', '总消费', '特殊礼物豆', '时间'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['totalcoin'] = $value['totalcoin'];
            $outArray['freecoin'] = $value['freecoin'];
            $outArray['diamond'] = $value['diamond'];
            $outArray['exchange_diamond'] = $value['exchange_diamond'];
            $outArray['free_diamond'] = $value['free_diamond'];
            $outArray['admincharge'] = $value['admincharge'];
            $outArray['adminchargedesc'] = $value['adminchargedesc'];
            $outArray['charge'] = $value['charge'];
            $outArray['pack'] = $value['pack'];
            $outArray['keys'] = $value['keys'];
            $outArray['cash'] = $value['cash'];
            $outArray['sendGiftCount'] = $value['sendGiftCount'];
            $outArray['surplus'] = $value['surplus'];
            $outArray['surplusdesc'] = $value['surplusdesc'];
            $outArray['specialgift'] = $value['specialgift'];
            $outArray['createtime'] = $value['createtime'];
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = date('Ymd') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    /**
     * 对账明细列表数据
     */
    public function billDetail()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $demo = $this->request->param('demo', $this->default_date);
        list($start_time, $end_time) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');
        $where = [];
        $where[] = ['ctime', '>=', $start_time];
        $where[] = ['ctime', '<', $end_time];
        //统计
        $count = BillDetailModel::getInstance()->getModel()->where($where)->count();
        //获取数据
        if ($daochu == 1) {
            $data = BillDetailModel::getInstance()->getModel()->where($where)->order('id desc')->select()->toArray();
        } else {
            $data = BillDetailModel::getInstance()->getModel()->where($where)->order('id desc')->limit($page, $pagenum)->select()->toArray();
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('billDetail列表获取成功:操作人:' . $this->token['username'], 'billDetail');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        if ($daochu == 1) {
            $this->_billDetailcsv($data);
        }
        return View::fetch('coindetail/billdetail');
    }
    //数据导出
    private function _billDetailcsv($data)
    {
        $headerArray = ['充值总豆', '后充总豆', '公充总豆', '送礼收钻石', '送礼物30', '钻石兑豆', '直送礼物', '包送礼物', '购买钥匙', '提现钻石', '特殊礼物', '后台减豆', '背包剩余', '钥匙剩余', '剩余豆', '剩余钻石', '箱子送出', '发起猜拳', '猜拳应战', '提现审核中', '时间'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['charge_coin'] = $value['charge_coin']; //充值总豆
            $outArray['admin_coin'] = $value['admin_coin']; //后充总豆
            $outArray['guild_coin'] = $value['guild_coin']; //公充总豆
            $outArray['gift_diamond'] = $value['gift_diamond']; //送礼收钻石
            $outArray['admin_income'] = $value['admin_income']; //送礼物30平台收益
            $outArray['convert_coin'] = $value['convert_coin']; //钻石兑换豆
            $outArray['gift_coin'] = $value['gift_coin']; //直送礼物
            $outArray['packgift_coin'] = $value['packgift_coin']; //背包赠送礼物
            $outArray['hammer_coin'] = $value['hammer_coin']; //购买许愿石
            $outArray['cash_diamond'] = $value['cash_diamond']; //提现钻石
            $outArray['special_gift'] = $value['special_gift']; //开箱子特殊礼物
            $outArray['reduce_coin'] = $value['reduce_coin']; //后台减豆
            $outArray['history_pack'] = $value['history_pack']; //历史背包剩余
            $outArray['keys'] = $value['keys']; //钥匙剩余
            $outArray['free_coin'] = $value['free_coin']; //未消耗M豆
            $outArray['nocash_diamond'] = $value['nocash_diamond']; //未提现钻石
            $outArray['boxsend_gift'] = $value['boxsend_gift']; //开箱子礼物到礼物送出
            $outArray['finger_coin'] = abs($value['finger_coin']); //发起猜拳
            $outArray['finger_meet_coin'] = abs($value['finger_meet_coin']); //猜拳应战
            $outArray['withdrawing'] = $value['withdrawing']; //提现钻石中
            $outArray['ctime'] = $value['ctime']; //时间
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = date('Ymd') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    /**
     * 每日付费用户列表
     */
    public function payUser()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $start_time = date('Y-m-d 00:00:00');
        $end_time = date('Y-m-d 23:59:59');
        $daochu = $this->request->param('daochu');
        $where = [];
        $where[] = ['addtime', '>=', $start_time];
        $where[] = ['addtime', '<', $end_time];
        $where[] = ['status', 'in', '1,2'];
        //统计
        $count = ChargedetailModel::getInstance()->getModel()->where($where)->count();
        $rmb = ChargedetailModel::getInstance()->getModel()->where($where)->field('sum(rmb) rmb')->select()->toArray();
        //获取数据
        if ($daochu == 1) {
            $data = ChargedetailModel::getInstance()->getModel()->where($where)->order('id addtime')->field('id,uid,rmb,addtime')->select()->toArray();
        } else {
            $data = ChargedetailModel::getInstance()->getModel()->where($where)->order('id addtime')->limit($page, $pagenum)->field('id,uid,rmb,addtime')->select()->toArray();
        }

        $result = array();

        foreach ($data as $keys => $val) {
            $key = $val['uid'];
            if (!isset($result[$key])) {
                $result[$key] = $val;
            } else {
                $result[$key]['rmb'] += $val['rmb'];
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('bill列表获取成功:操作人:' . $this->token['username'], 'bill');
        View::assign('page', $page_array);
        View::assign('data', $result);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('start_time', $start_time);
        View::assign('end_time', $end_time);
        View::assign('rmb', $rmb[0]['rmb']);
        return View::fetch('coindetail/payuser');
    }

    /**
     * leftUser() 用户余额
     */
    public function leftUser()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id', ''); //用户id
        $pretty_id = Request::param('pretty_id'); //用户靓号id
        $mobile = Request::param('mobile'); //手机号
        $head_frame = Request::param('head_frame'); //头像框
        $head_frame = $head_frame ? $head_frame : 2;
        $where = [];
        if ($head_frame == 1) {
            $where[] = ['pretty_avatar', '<>', ''];
        }
        if ($user_id) {
            $where[] = ['id', '=', $user_id];
        }
        if ($pretty_id) {
            $where[] = ['pretty_id', '=', $pretty_id];
        }
        if ($mobile) {
            $where[] = ['username', '=', $mobile];
        }
        //统计用户条数
        $count = MemberModel::getInstance()->getModel($user_id)->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = MemberModel::getInstance()
                ->getModel($user_id)
                ->where($where)
                ->limit($page, $pagenum)
                ->field('id,avatar,nickname,sex,register_time,invitcode,totalcoin,freecoin')
                ->select()
                ->toArray();
            foreach ($data as $key => $vo) {
                $data[$key]['sex'] = $vo['sex'] == 1 ? '男' : '女';
                $data[$key]['avatar'] = getavatar($vo['avatar']);
                $data[$key]['dou'] = floor($vo['totalcoin']) - floor($vo['freecoin']);
            }
        }
        Log::record('用户列表查询:操作人:' . $this->token['username'], 'memberList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $user_id);
        View::assign('pretty_id', $pretty_id);
        View::assign('mobile', $mobile);
        View::assign('head_frame', $head_frame);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('coindetail/userdou');
    }
}
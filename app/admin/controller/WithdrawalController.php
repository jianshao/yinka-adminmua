<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\ApiUrlConfig;
use app\admin\model\MembercashModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberSocityModel;
use app\admin\model\MemberWithdrawalModel;
use app\admin\model\UserAssetLogModel;
use app\admin\model\WithdrawWhiteListModel;
use app\admin\service\ApiService;
use app\admin\service\ExportExcelService;
use app\admin\service\WithdrawalService;
use app\common\FormaterExportDataCommon;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class WithdrawalController extends AdminBaseController
{

    /*
     * 钻石充值列表
     */
    public function ghBeanList()
    {
        $limit = 20;
        $instance = '';
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $user_id = Request::param('user_id', 0);
        $start_time = Request::param('start_time', date('Y-m-d'));
        $end_time = Request::param('end_time', date('Y-m-d', strtotime("+1days")));
        $where = [];
        $where[] = ['event_id', '=', 10014];
        // $where[] = ['type', '=', 5];
        $where[] = ['change_amount', '<', 0];
        if (!empty($user_id)) {
            $where[] = ['touid', '=', $user_id];
        }
        if (!empty($start_time) && !empty($end_time)) {
            $where[] = ['success_time', '>=', strtotime($start_time)];
            $where[] = ['success_time', '<', strtotime($end_time) + 86400];
        }
        $getInstance = ParseUserStateDataCommmon::getInstance()->getMonthTableName($start_time, $end_time);
        if (!empty($where)) {
            $data = UserAssetLogModel::getInstance($getInstance)->getModel($user_id)->where($where)->limit($offset, $limit)->order('success_time desc')->select();
        } else {
            $data = UserAssetLogModel::getInstance($getInstance)->getModel($user_id)->order('success_time desc')->select();
        }
        $count = 0;
        if (!empty($data)) {
            $data = $data->toArray();

            $uids = array_column($data, 'touid');

            $member_list = MemberModel::getInstance()->getWhereAllData([["id", 'in', $uids]], "id,nickname");
            $user_list = array_column($member_list, null, 'id');

            foreach ($data as $key => $value) {
                $data[$key]['createtime'] = date('Y-m-d H:i:s', $value['success_time']);
                $data[$key]['beannum'] = abs($value['change_amount']);
                $nickname = isset($user_list[$value['touid']]) ? $user_list[$value['touid']]['nickname'] : '';
                $data[$key]['tonickname'] = $nickname;
            }
            $count = UserAssetLogModel::getInstance($getInstance)->getModel($user_id)->where($where)->count();
        }
        $totalPage = ceil($count / $limit);
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        View::assign('list', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('user_id', $user_id);
        View::assign('start_time', $start_time);
        View::assign('end_time', $end_time);
        return View::fetch('withdrawal/beanlist');

    }

    /*
     * 提现列表
     */
    public function withdrawalList()
    {
        $limit = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $type = Request::param('type', 0);
        $user_id = Request::param('user_id');
        $start_time = Request::param('start_time');
        $end_time = Request::param('end_time');
        $daochu = Request::param('daochu');
        $is_online = Request::param('is_online', -1);
        $pay_type = Request::param('pay_type', -1);
        $user_role = Request::param('user_role', 0);

        $start_time = $start_time ? $start_time : date('Y-m-d', strtotime('-1 days'));
        $end_time = $end_time ? $end_time : date('Y-m-d');
        $end_timebak = strtotime($end_time) + 86400;
        //搜索条件(类型,房间id)
        $where = [];

        if ($type >= 0) {
            $where[] = ['status', '=', $type];
        }

        $summarywhere = [];

        if ($start_time && $end_time) {
            $where[] = ['created_time', '>=', strtotime($start_time)];
            $where[] = ['created_time', '<', $end_timebak];
            $summarywhere[] = ['created_time', '>=', strtotime($start_time)];
            $summarywhere[] = ['created_time', '<', $end_timebak];
        }

        if ($user_id) {
            $where[] = ['uid', '=', $user_id];
            $summarywhere[] = ['uid', '=', $user_id];
        }

        if ($pay_type >= 0) {
            $where[] = ['type', '=', $pay_type];
        }

        if ($user_role > 0) {
            $where[] = ['user_role', '=', $user_role];
        }

        //线下打款
        if ($is_online == 0) {
            $where[] = ['user_role', "=", 1];
            $where[] = ['type', "=", 2];
        }

        //线上打款 查询用or
        if ($is_online == 1) {
            $condition = $where;
            $where[] = ["user_role", "=", 2];
            $condition[] = ['user_role', "=", 1];
            $condition[] = ['type', "=", 0];
            $where = [$where, $condition];
        }

        if ($daochu == 1) {
            $headercolumn = [
                "created_time" => "创建时间",
                "uid" => "用户ID",
                "g_nickname" => "所属工会",
                "accounts" => "提现账号",
                "username" => "账号真实姓名",
                "nickname" => "用户昵称",
                "money" => "提现金额",
                "type_info" => "提现体系",
                "status_info" => "提现状态",
                "user_role_info" => "用户白名单",
                "message_detail" => "备注",
            ];
            if ($is_online == 1) { //线上打款
                $daochuDataSource = MemberWithdrawalModel::getInstance()->getModel()->whereOr($where);
            } else {
                $daochuDataSource = MemberWithdrawalModel::getInstance()->getModel()->where($where);
            }
            ExportExcelService::getInstance()->dataExpormetCsvByFormat($daochuDataSource, $headercolumn, [FormaterExportDataCommon::getInstance(), "formatterWithdrawOrderList"]);
        }

        if ($is_online == 1) {
            $data = MemberWithdrawalModel::getInstance()->getModel()->whereOr($where)->order("id", "desc")
                ->page($master_page, $limit)->select()->toArray();
            $count = MemberWithdrawalModel::getInstance()->getModel()->whereOr($where)->count();
        } else {
            $data = MemberWithdrawalModel::getInstance()->withdrawalList($where, $offset, $limit);
            $count = MemberWithdrawalModel::getInstance()->getModel()->where($where)->count();
        }

        $daishenhe_white_user_money = 0; //待审核白名单用户金额
        $daishenhe_user_money = 0; //待审核的普通用户金额
        $shenhe_white_user_money = 0; //已打款的白名单金额
        $shenhe_user_money = 0; //已打款的普通用户金额

        $summaryList = MemberWithdrawalModel::getInstance()->getWhereAllData($summarywhere, "*");
        foreach ($summaryList as $sumitem) {

            if ($sumitem['status'] == 0) {
                //白名单的用户
                if ($sumitem['user_role'] == 1) {
                    $daishenhe_white_user_money += $sumitem['money'];
                }

                //普通用户
                if ($sumitem['user_role'] == 2) {
                    $daishenhe_user_money += $sumitem['money']; //普通用户提现
                }
            }

            if ($sumitem['status'] == 3) {
                //白名单的用户
                if ($sumitem['user_role'] == 1) {
                    $shenhe_white_user_money += $sumitem['money'];
                }

                //普通用户
                if ($sumitem['user_role'] == 2) {
                    $shenhe_user_money += $sumitem['money']; //普通用户提现
                }
            }

        }

        $totalPage = ceil($count / $limit);
        Log::record('提现列表:操作人:' . $this->token['username'], 'withdrawalList');
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        View::assign('list', FormaterExportDataCommon::getInstance()->formatterWithdrawOrderList($data));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('type', $type);
        View::assign('user_id', $user_id);
        View::assign('start_time', $start_time);
        View::assign('end_time', $end_time);
        View::assign('daishenhe_white_user_money', $daishenhe_white_user_money);
        View::assign('shenhe_white_user_money', $shenhe_white_user_money);
        View::assign('daishenhe_user_money', $daishenhe_user_money);
        View::assign('shenhe_user_money', $shenhe_user_money);
        View::assign('is_online', $is_online);
        View::assign('pay_type', $pay_type);
        View::assign('user_role', $user_role);
        return View::fetch('withdrawal/index');
    }

/*
 * 同意放款
 */
    public function agreeMake()
    {
        $ids = Request::param('id');
        if (!$ids) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        try {
            $ids_arr = explode(',', $ids);
            $msg = [];
            for ($i = 0; $i < count($ids_arr); $i++) {
                $id = (int) $ids_arr[$i];
                $res = MemberWithdrawalModel::getInstance()->getByWhere(array('id' => $id, 'status' => 0), '*');
                if (empty($res)) {
                    echo $this->return_json(\constant\CodeConstant::CODE_没有查询到数据, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_没有查询到数据]);
                    die;
                }
                $uid = $res->uid;
                $cash = MembercashModel::getInstance()->getModel()->where([['uid', '=', $uid]])->column('name', 'uid');
                if (empty($cash)) {
                    echo $this->return_json("500", null, '未找到支付宝真实姓名');
                    die;
                }
                if ($res['type'] != 0) {
                    echo $this->return_json(500, null, '目前不支持该提现方式');
                    die;
                }
                if ($res['status'] == 1 || $res['status'] == 2 || $res['status'] == 3) {
                    echo $this->return_json(\constant\CodeConstant::CODE_此条内容已经通过请勿重复操作, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_此条内容已经通过请勿重复操作]);
                    die;
                }
                if ($res['status'] == 4) {
                    echo $this->return_json(\constant\CodeConstant::CODE_此条内容状态有误, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_此条内容状态有误]);
                    die;
                }
                //检测商户金额是否足额
                $alipay = new AliPayController();

                $check = $alipay->checkMoneyLimit();

                if ($check['code'] != 10000 || $check['available_amount'] < $res['money']) {
                    echo $this->return_json(\constant\CodeConstant::CODE_可提现金额不足, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_可提现金额不足]);
                    die;
                }

                $redis = $this->getRedis();
                $record_existence = $redis->sIsMember(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_ID . $uid, $id);
                if ($record_existence) {
                    echo $this->return_json(\constant\CodeConstant::CODE_提现申请已打款中, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_提现申请已打款中]);
                    die;
                }
                $redis->sAdd(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_ID . $uid, $id);

                $update['status'] = 1;
                $update['updated_time'] = time();
                MemberWithdrawalModel::getInstance()->getModel()->where(['id' => $id])->update($update);

                $rate = 0.95;
                $res_1 = $alipay->aliPayUpGrade($res['accounts'], $res['money'] * $rate, $cash[$uid], '提现', $res['order_id'], $uid);

                Log::record('ali_pay_transfer_orderid---' . $res['order_id']);
                Log::record('ali_pay_transfer_status---' . $res_1['code']);

                //修改转账日志
                if (!empty($res_1['code']) && $res_1['code'] == 10000) {
                    //成功
                    $update1['status'] = 3;
                    $update1['updated_time'] = time();
                    MemberWithdrawalModel::getInstance()->getModel()->where(['id' => $id])->update($update1);
                    $results = true;
                } else {
                    //提现失败返回金币
                    $update1['status'] = 2;
                    $update1['updated_time'] = time();
                    MemberWithdrawalModel::getInstance()->getModel()->where(['id' => $id])->update($update1);

                    $params = [
                        "userId" => (int) $uid,
                        "assetId" => "user:diamond",
                        "count" => $res['diamond'],
                        "timestamp" => time(),
                        "eventDict" => json_encode(["ext1" => $res['order_number'], "ext3" => $res['diamond']]),
                        "eventId" => 10017,
                    ];

                    $res = ApiService::getInstance()->curlApi(ApiUrlConfig::$withdraw_add_asset, $params, true, false);

                    $results = false;
                }

                if ($results) {
                    $msg[$i] = $id . ':' . $uid . '提现成功';
                } else {
                    $msg[$i] = $id . ':' . $uid . '提现失败，失败信息:' . ($res_1['msg'] ?? '');
                }
                $redis->del(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_ID . $uid);
                usleep(100000);
            }
            $msg_str = implode(',', $msg);
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $msg_str);die;
        } catch (Exception $e) {
            Log::record($e->getTraceAsString());
            echo $this->return_json(\constant\CodeConstant::CODE_提现申请失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_提现申请失败]);die;
        }
    }

/*
 * 拒绝放款
 */
    public function refuseMake()
    {
        $ids = Request::param('id');
        if (!$ids) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        try {
            $ids_arr = explode(',', $ids);
            for ($i = 0; $i < count($ids_arr); $i++) {
                $id = (int) $ids_arr[$i];

                $field = 'id,uid,diamond,status,order_id';
                $res = MemberWithdrawalModel::getInstance()->getByWhere(array('id' => $id, 'status' => 0), $field);
                if (empty($res)) {
                    echo $this->return_json(\constant\CodeConstant::CODE_没有查询到数据, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_没有查询到数据]);
                    die;
                }
                $uid = $res->uid;
                if ($res['status'] == 4) {
                    echo $this->return_json(\constant\CodeConstant::CODE_此条内容已经拒绝过请勿重复操作, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_此条内容已经拒绝过请勿重复操作]);
                    die;
                }
                if ($res['status'] == 1 || $res['status'] == 2 || $res['status'] == 3) {
                    echo $this->return_json(\constant\CodeConstant::CODE_此条内容状态有误, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_此条内容状态有误]);
                    die;
                }
                $where = ['id' => $id];
                $data = ['status' => 4, 'updated_time' => time()];

                MemberWithdrawalModel::getInstance()->exitWithdrawal($where, $data);

                //提现返回金币
                $params = [
                    "userId" => (int) $uid,
                    "assetId" => "user:diamond",
                    "count" => $res['diamond'],
                    "timestamp" => time(),
                    "eventDict" => json_encode(["ext1" => $res['order_number'], "ext3" => $res['diamond']]),
                    "eventId" => 10017,
                ];

                $res = ApiService::getInstance()->curlApi(ApiUrlConfig::$withdraw_add_asset, $params, true);

            }
            Log::record('拒绝用户提现成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'refuseMake');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (Exception $e) {
// MemberWithdrawalModel::getInstance()->rollback();

            MemberWithdrawalModel::getInstance()->exitWithdrawal($where, ['status' => 0, 'updated_time' => time()]);

            Log::record('拒绝用户提现失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'refuseMake');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /*
     * 同意放款
     */
    public function agreeMakeBk()
    {
        try {
            $ids = Request::param('id');
            if (!$ids) {
                throw new Exception("参数错误");
            }
            $ids_arr = explode(',', $ids);
            $msg = [];
            for ($i = 0; $i < count($ids_arr); $i++) {
                $id = (int) $ids_arr[$i];
                $res = MemberWithdrawalModel::getInstance()->getByWhere(array('id' => $id, 'status' => 0), '*');
                if (empty($res)) {
                    throw new Exception("没有查询到此订单");
                }
                $uid = $res['uid'];
                if (!WithdrawalService::getInstance()->withdrawLock($res['order_id'])) {
                    echo $this->return_json(500, null, '此订单正在被占用请稍后重试');
                    die;
                }
                //防止上锁之前订单的状态被其他进程操作过
                $lockBeforeOrderInfo = MemberWithdrawalModel::getInstance()->getByWhere(array('id' => $res['id'], 'status' => 0), '*');
                if (empty($lockBeforeOrderInfo)) {
                    throw new Exception("没有查询到此订单");
                }

                if (!in_array($lockBeforeOrderInfo['type'], [0, 1, 2])) {
                    throw new Exception("目前不支持该提现方式");
                }

                if ($lockBeforeOrderInfo['status'] != 0) {
                    throw new Exception("订单必须是待审核状态");
                }

                if ($lockBeforeOrderInfo['type'] == 2 && $lockBeforeOrderInfo['user_role'] == 1) {
                    throw new Exception("白名单的银行卡提现需要线下操作");
                }

                $withdrawRes = $this->commonWithdraw($lockBeforeOrderInfo);
                //修改转账日志
                if (isset($withdrawRes['code']) && $withdrawRes['code'] == 200) {
                    $update = [];
                    //成功
                    if (isset($withdrawRes['ok']) && $withdrawRes['ok'] == 1) {
                        $update['status'] = 3; //打款成功 公司自己的支付宝打款 同步 status=2打款成功
                    } else {
                        $update['status'] = 1; //第三方打款 异步告知结果  status=1 打款中
                    }
                    $update['updated_time'] = time();
                    MemberWithdrawalModel::getInstance()->getModel()->transaction(function () use ($id, $update) {
                        MemberWithdrawalModel::getInstance()->getModel()->where(['id' => $id])->update($update);
                    });
                    $msg[$i] = $id . ':' . $uid . '提现成功';
                } else {
                    $message_detail = $withdrawRes['msg'] ?? '未知错误';
                    $this->failWithdrawHandle($res, ["updated_time" => time(), "status" => 2, "message_detail" => $message_detail]); //打款失败
                    $msg[$i] = $id . ':' . $uid . '提现失败，失败信息' . $message_detail;
                }
                WithdrawalService::getInstance()->withdrawUnlock($res['order_id']);
            }
        } catch (\Throwable $e) {
            Log::error("withdraw_agreemake:error" . $e->getMessage());
            $msg[$i] = $id . ':' . '提现失败，失败信息:' . $e->getMessage();
        }

        $msg_str = implode(',', $msg);
        echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $msg_str);
        die;
    }

    /*
     * 拒绝放款
     */
    public function refuseMakeBk()
    {
        try {
            $ids = Request::param('id');
            if (!$ids) {
                throw new Exception("参数错误");
            }

            $ids_arr = explode(',', $ids);
            for ($i = 0; $i < count($ids_arr); $i++) {
                $id = (int) $ids_arr[$i];
                $field = 'id,uid,diamond,status,order_id';
                $res = MemberWithdrawalModel::getInstance()->getByWhere(array('id' => $id, 'status' => 0), $field);
                if (empty($res)) {
                    throw new Exception("订单必须是待审核状态");
                }
                $orderId = $res['order_id']; //订单号
                if (!WithdrawalService::getInstance()->withdrawLock($orderId)) {
                    echo $this->return_json(500, null, '此订单正在被占用请稍后重试');
                    die;
                }

                $lockBeforeOrderInfo = MemberWithdrawalModel::getInstance()->getByWhere(array('id' => $id, 'status' => 0), "*");

                if ($lockBeforeOrderInfo['status'] != 0) {
                    throw new Exception("订单必须是待审核状态");
                }
                $where = ['id' => $id];
                $data = ['status' => 4, 'updated_time' => time()];
                $this->failWithdrawHandle($res, $data);
                Log::record('拒绝用户提现成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'refuseMake');
                WithdrawalService::getInstance()->withdrawUnlock($orderId);
            }
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (\Throwable $e) {
            Log::error("withdraw_refusemake:error" . $e->getMessage());
            echo $this->return_json(500, null, $e->getMessage());
            die;
        }
    }

    /**
     * 人工手动转账成功
     */
    public function manMadeTransfer()
    {
        try {
            $id = Request::param('id');
            $uid = Request::param('uid');
            if (!$id || !$uid) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }
            $res = MemberWithdrawalModel::getInstance()->getByWhere(array('id' => $id, 'uid' => $uid), "*");
            $order_id = $res['order_id'];
            if (!WithdrawalService::getInstance()->withdrawLock($order_id)) {
                echo $this->return_json(500, null, '此订单正在被占用请稍后重试');
                die;
            }

            $lockBeforeOrderInfo = MemberWithdrawalModel::getInstance()->getByWhere(array('id' => $id, 'uid' => $uid), "*");

            if (empty($lockBeforeOrderInfo)) {
                echo $this->return_json(\constant\CodeConstant::CODE_没有查询到数据, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_没有查询到数据]);
                die;
            }

            if ($lockBeforeOrderInfo['status'] != 0) {
                throw new Exception("订单必须是待审核状态");
            }
            //修改转账日志
            $adminid = $this->token['id'] ?? 0;
            Sharding::getInstance()->getConnectModel("bi", "")->transaction(function () use ($id, $adminid) {
                $saveData = [];
                $saveData['status'] = 3;
                $saveData['updated_time'] = time();
                $saveData['message_detail'] = "客服($adminid)线下打款";
                MemberWithdrawalModel::getInstance()->exitWithdrawal(['id' => $id], $saveData);
            });
            WithdrawalService::getInstance()->withdrawUnlock($order_id);
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (Exception $e) {
            Log::error("manmadetransfer:error:" . $e->getMessage());
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

//提现账户的选择
    public function withDrawalPayAccount()
    {
        if ($this->request->param('isRequest')) {
            $configList = config("config.widthdrawalconfig");
            $accounts = array_keys($configList);
            $res = [];
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $currentAccount = $redis->get("withdrawalaccount");
            foreach ($accounts as $item) {
                if ($item == $currentAccount) {
                    $res[] = ["account" => $item, 'LAY_CHECKED' => true];
                } else {
                    $res[] = ["account" => $item];
                }
            }
            $data = ["msg" => '', "count" => count($res), "code" => 0, "data" => $res];
            echo json_encode($data);
            exit;
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('user_id', $this->request->param('user_id'));
            return View::fetch('withdrawal/withdrawalselect');
        }
    }

    public function withdrawalpayaccountupdate()
    {
        $account = $this->request->param('account', '', 'trim');
        try {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $redis->set("withdrawalaccount", $account);
            Log::info("withdrawalpayaccountupdate:success:alipayaccount:$account admin_id:" . $this->token['id'] ?? 0);
            $data = ["msg" => '操作成功', "code" => 0];
        } catch (Throwable $e) {
            Log::info("withdrawalpayaccountupdate:error:alipayaccount:$account admin_id:" . $this->token['id'] ?? 0);
            $data = ["msg" => '配置异常', "code" => -1];
        }
        echo json_encode($data);
        exit;
    }

    //提现的公共操作方法
    private function commonWithdraw($res)
    {
        //user_role 1:白名单  2：普通用户
        $returnResult = [];
        //普通用户的提现
        if ($res['user_role'] == 2) {
            $returnResult = WithdrawalService::getInstance()->daLongWithdrawal($res['uid'], $res['order_id'], $res['money'], $res['accounts'], $res['username'], $res['type']);
        }
        //白名单用户只有支付宝用大珑的提现方式
        if ($res['type'] == 0 && $res['user_role'] == 1) {
            $returnResult = WithdrawalService::getInstance()->alipayWithdrawal($res['uid'], $res['order_id'], $res['money'], $res['accounts'], $res['username']);
        }
        return $returnResult;
    }

    /**
     * 同意操作中如果处理失败的逻辑
     * @param $res
     */
    private function failWithdrawHandle($res, $update)
    {
        try {
            //先更改数据库
            MemberWithdrawalModel::getInstance()->getModel()->transaction(function () use ($res, $update) {
                MemberWithdrawalModel::getInstance()->getModel()->where(['id' => $res['id']])->update($update);
            });
            WithdrawalService::getInstance()->addAsset($res);
        } catch (\Throwable $e) {
            throw $e;
        }

    }

    //提现用户白名单
    public function withdrawWhiteList()
    {
        $user_id = $this->request->param('user_id', 0, 'trim');
        $enable = $this->request->param('enable', -1, 'trim'); // 1有效  0无效
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $where = [];
        if ($user_id > 0) {
            $where[] = ["user_id", "=", $user_id];
        }

        if ($enable >= 0) {
            $where[] = ["enable", "=", $enable];
        }

        $callback = function ($items) {
            $uids = array_column($items, "user_id");
            $memberList = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], 'id,nickname,register_time');
            $memberListByid = array_column($memberList, null, "id");
            $membersocityList = MemberSocityModel::getInstance()->getWhereAllData([["user_id", "in", $uids], ["status", "=", 1]], "guild_id,user_id");
            $membersocityListByUid = array_column($membersocityList, null, "user_id");
            $guiids = array_column($membersocityList, "guild_id");
            $memberGuildList = MemberGuildModel::getInstance()->getModel()->where([["id", "in", $guiids]])->field("id,nickname")->select()->toArray();
            $memberGuildListById = array_column($memberGuildList, null, "id");
            foreach ($items as &$item) {
                $guild_id = $membersocityListByUid[$item['user_id']]['guild_id'] ?? 0;
                $item['nickname'] = $memberListByid[$item['user_id']]['nickname'] ?? '';
                $item['guild_id'] = $guild_id;
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                $item['enable'] = $item['enable'] == 0 ? '禁用' : '启用';
                $item['g_nickname'] = $memberGuildListById[$guild_id]['nickname'] ?? '';
            }
            return $items;
        };

        if ($this->request->param('isRequest')) {
            $res = WithdrawWhiteListModel::getInstance()->getModel()->where($where)->page($page, $limit)->select()->toArray();
            $count = WithdrawWhiteListModel::getInstance()->getModel()->where($where)->count();
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $callback($res)];
            echo json_encode($data);
            exit;
        } else {
            View::assign('enable', $enable);
            View::assign('token', $this->request->param('token'));
            View::assign('user_id', $this->request->param('user_id'));
            return View::fetch('withdrawal/withdrawwhitelist');
        }
    }

    /**
     * 白名单的新增或者编辑
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withdrawWhiteListAdd()
    {
        $user_id = $this->request->param('user_id', 0, 'trim');
        $enable = $this->request->param('enable', 0, 'trim');
        $admin_id = $this->token['id'] ?? 0;
        try {

            if ($user_id == 0) {
                echo json_encode(["code" => -1, "msg" => "用户ID为空"]);
                exit;
            }

            if (WithdrawWhiteListModel::getInstance()->getModel()->where("user_id", $user_id)->find()) {
                //更新

                WithdrawWhiteListModel::getInstance()->getModel()->where("user_id", $user_id)
                    ->update(["enable" => $enable, "create_time" => time(), "admin_id" => $admin_id]);
            } else {
                WithdrawWhiteListModel::getInstance()->getModel()
                    ->insert(["user_id" => $user_id, "enable" => $enable, "create_time" => time(), "admin_id" => $admin_id]);
            }
            echo json_encode(["code" => 0, "msg" => ""]);
            exit;
        } catch (\Throwable $e) {

            Log::error("withdrawwhitelistadd:error:" . $e->getMessage());
            echo json_encode(["code" => -1, "msg" => "操作失败"]);
            exit;
        }
    }

}

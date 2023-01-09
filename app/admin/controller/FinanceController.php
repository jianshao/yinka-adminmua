<?php
namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\FinanceModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class FinanceController extends AdminBaseController
{
    /*
     * 查询今日用户明细
     */
    public function selectUserDetail()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id'); //用户ID
        $end_time = Request::param('end_time'); //结束时间
        $start_time = Request::param('start_time'); //结束时间

        if (!empty($user_id) && !empty(!$end_time) && !empty($start_time)) {
            $where = [['user_id', '=', $user_id], ['dates', '>=', strtotime($start_time)], ['dates', '<=', strtotime($end_time)]];
        } else if (!empty($user_id) && !empty($start_time)) {
            $where = ['user_id' => $user_id, 'dates' => strtotime($start_time)];
        } else if (!empty($user_id) && !empty($end_time)) {
            $where = ['user_id' => $user_id, 'dates' => strtotime($end_time)];
        } else if (!empty($start_time) && !empty($end_time)) {
            $where = [['dates', '>=', strtotime($start_time)], ['dates', '<=', strtotime($end_time)]];
        } else if (!empty($user_id)) {
            $where = ['user_id' => $user_id];
        } else if (!empty($start_time)) {
            $where = ['dates' => strtotime($start_time)];
        } else if (!empty($end_time)) {
            $where = ['dates' => strtotime($end_time)];
        } else {
            $where = [];
        }
        $data = [];
        $count = FinanceModel::getInstance()->getModel()->where($where)->count();
        if ($count > 0) {
            $data = FinanceModel::getInstance()->giftList($where, $page, $pagenum);
            foreach ($data as $key => $val) {
                $data[$key]['dates'] = date('Y-m-d', $val['dates']);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('用户每日明细获取成功:操作人:' . $this->token['username'], 'selectUserDetail');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $user_id);
        return View::fetch('finance/index');
    }
    /*
     * 苹果支付
     */
    public function selectUserIos()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'ios_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['ios_detail']);
        Log::record('苹果充值列表:操作人:' . $this->token['username'], 'selectUserIos');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 微信支付
     */
    public function selectUserWechat()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'wechat_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['wechat_detail']);
        Log::record('微信充值列表:操作人:' . $this->token['username'], 'selectUserWechat');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 支付宝
     */
    public function selectUserAli()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'alipay_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['alipay_detail']);
        Log::record('支付宝充值列表:操作人:' . $this->token['username'], 'selectUserAli');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 钻石转M豆
     */
    public function selectUserModes()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'convert_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['convert_detail']);
        Log::record('钻石转M豆列表:操作人:' . $this->token['username'], 'selectUserModes');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 获得礼物
     */
    public function selectUserGift()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'income_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['income_detail']);
        Log::record('获得礼物明细:操作人:' . $this->token['username'], 'selectUserGift');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 买银箱子钥匙
     */
    public function selectBuyYin()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'yin_key_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['yin_key_detail']);
        Log::record('买银箱子钥匙明细:操作人:' . $this->token['username'], 'selectBuyYin');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 买金箱子钥匙
     */
    public function selectBuyJin()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'jin_key_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['jin_key_detail']);
        Log::record('买金箱子钥匙明细:操作人:' . $this->token['username'], 'selectBuyJin');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 用银箱子钥匙
     */
    public function selectUseYin()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'use_yin_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['use_yin_detail']);
        Log::record('用银箱子钥匙明细:操作人:' . $this->token['username'], 'selectUseYin');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 用金箱子钥匙
     */
    public function selectUseJin()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'use_jin_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['use_jin_detail']);
        Log::record('用金箱子钥匙明细:操作人:' . $this->token['username'], 'selectUseJin');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 开箱子礼物收益
     */
    public function selectUserBox()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'box_gift_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['box_gift_detail']);
        Log::record('开箱子礼物收益明细:操作人:' . $this->token['username'], 'selectUserBox');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 背包礼物发起猜拳
     */
    public function selectBagStart()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'bag_start_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['bag_start_detail']);
        Log::record('背包礼物发起猜拳明细:操作人:' . $this->token['username'], 'selectBagStart');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 充值礼物发起猜拳
     */
    public function selectPayStart()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'pay_start_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['pay_start_detail']);
        Log::record('充值礼物发起猜拳明细:操作人:' . $this->token['username'], 'selectPayStart');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 背包礼物应战猜拳
     */
    public function selectBagFight()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'bag_fight_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['bag_fight_detail']);
        Log::record('背包礼物应战猜拳明细:操作人:' . $this->token['username'], 'selectBagFight');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 充值礼物应战猜拳
     */
    public function selectPayFight()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'pay_fight_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['pay_fight_detail']);
        Log::record('充值礼物应战猜拳明细:操作人:' . $this->token['username'], 'selectPayFight');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 猜拳礼物收益
     */
    public function selectUserfinger()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'finger_settlement_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['finger_settlement_detail']);
        Log::record('猜拳礼物收益明细:操作人:' . $this->token['username'], 'selectUserfinger');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 背包礼物
     */
    public function selectUserBagGift()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'knapsack_gift_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['knapsack_gift_detail']);
        Log::record('背包礼物打赏明细:操作人:' . $this->token['username'], 'selectUserBagGift');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }
    /*
     * 购买礼物
     */
    public function selectUserPayGift()
    {
        $id = Request::param('id'); //ID
        $where = ['id' => $id];
        $field = 'buy_gift_detail';
        $data = FinanceModel::getInstance()->getWhereField($where, $field);
        $res = json_decode($data['buy_gift_detail']);
        Log::record('购买礼物打赏明细:操作人:' . $this->token['username'], 'selectUserPayGift');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

}

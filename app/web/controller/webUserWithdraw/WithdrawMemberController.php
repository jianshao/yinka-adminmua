<?php

namespace app\web\controller\webUserWithdraw;

use AlibabaCloud\Client\AlibabaCloud;
use app\admin\common\ApiUrlConfig;
use app\admin\model\BlackDataModel;
use app\admin\model\MembercashModel;
use app\admin\model\MemberWithdrawalModel;
use app\admin\model\UserAccountMapModel;
use app\admin\service\ApiService;
use app\admin\service\MemberService;
use app\web\common\WithdrawBaseController;
use Ramsey\Uuid\Uuid;
use think\App;
use think\Exception;
use think\facade\Log;

class WithdrawMemberController extends WithdrawBaseController
{
    /**
     * @var integer 验证码长度.
     */
    protected $captcha_length = 6;
    /**
     * 阿里短信配置项
     */
    private $ali_sms_accessKeyId;
    private $ali_sms_accessSecret;
    private $ali_sms_product;
    private $ali_sms_action;
    private $ali_sms_host;
    private $ali_sms_regionId;
    private $ali_sms_signName;
    private $ali_sms_templateCode;

    public function __construct(App $app)
    {
        parent::__construct($app);

        $ali_sms = config('config.ali_sms');
        $this->ali_sms_accessKeyId = $ali_sms['ali_sms_accessKeyId'];
        $this->ali_sms_accessSecret = $ali_sms['ali_sms_accessSecret'];
        $this->ali_sms_product = $ali_sms['ali_sms_product'];
        $this->ali_sms_action = $ali_sms['ali_sms_action'];
        $this->ali_sms_host = $ali_sms['ali_sms_host'];
        $this->ali_sms_regionId = $ali_sms['ali_sms_regionId'];
        $this->ali_sms_signName = $ali_sms['ali_sms_signName'];
        $this->ali_sms_templateCode = $ali_sms['ali_sms_templateCode'];
    }

    //获取验证码
    public function webUserWithdrawCodeCheck()
    {
        $phone = $this->request->param('phone');
        $check_phone = $this->checkPhone($phone);
        if ($check_phone === false) {
            echo $this->return_json(\constant\CodeConstant::CODE_此手机号不合法, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_此手机号不合法]);
            die;
        }
        try {
            //查询手机号是否存在用户表中
            $user_info = UserAccountMapModel::getInstance()->getModel()->where('type', 'mobile')->where('value', $phone)->find();
            if (empty($user_info)) {
                echo $this->return_json(\constant\CodeConstant::CODE_手机号尚未注册, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_手机号尚未注册]);
                die;
            }
            $user_info = $user_info->toArray();
            //查询此用户是否被封禁
            $user_black = BlackDataModel::getInstance()->getModel()->where(array(['blackinfo', '=', $user_info['user_id']], ['status', '=', '1']))->value('blackinfo');
            if (!empty($user_black)) {
                echo $this->return_json(\constant\CodeConstant::CODE_您的账号已封禁, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_您的账号已封禁]);
                die;
            }

            $redis = $this->getRedis();
            if (!$code = $redis->get('web_withdrawal_verify_code_' . $phone)) {
                $code = $this->generateRandomStr($this->captcha_length);
            }

            $expired_time = 10; // 单位分钟.

            $redis->setex('web_withdrawal_verify_code_' . $phone, $expired_time * 60, $code);
            Log::record('Web提现阿里短信发送开始日志记录:时间:' . time() . ':手机号:' . $phone . ':验证码:' . $code, 'webUserWithdrawalCodeCheck');
            $result = $this->aliSmsSend($phone, json_encode(array('code' => $code)));
            Log::record('Web提现阿里短信发送结束日志记录:时间:' . time() . ':手机号:' . $phone . ':验证码:' . $code . ':返回数据:' . json_encode($result), 'webUserWithdrawalCodeCheck');
            if ($result['Code'] == 'isv.BUSINESS_LIMIT_CONTROL') {
                if (strpos($result['Message'], '分')) {
                    echo json_encode(['code' => 500, 'msg' => '操作过于频繁稍后重试']);
                    die;
                } elseif (strpos($result['Message'], '时')) {
                    echo json_encode(['code' => 500, 'msg' => '1h内获取验证码次数达到上限']);
                    die;
                } else {
                    echo json_encode(['code' => 500, 'msg' => '当日获取验证码次数达到上限']);
                    die;
                }
            } elseif (empty($result) || $result['Code'] != 'OK') {
                echo $this->return_json(\constant\CodeConstant::CODE_发送验证码失败, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_发送验证码失败]);
                die;
            }
            $this->returnCode = \constant\CodeConstant::CODE_成功;
            $this->returnMsg = \constant\CodeConstant::CODE_OK_MAP[\constant\CodeConstant::CODE_成功];
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        echo $this->return_json($this->returnCode, null, $this->returnMsg);
        die;
    }

    //登录
    public function withdrawLogin()
    {
        $phone = $this->request->param('phone');
        $code = $this->request->param('code');

        if (!$code || strlen($code) < 6) {
            echo $this->return_json(\constant\CodeConstant::CODE_请正确输入验证码, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请正确输入验证码]);
            die;
        }

        //校验验证码
        $redis = $this->getRedis();
        $redis_code = $redis->get('web_withdrawal_verify_code_' . $phone);
        if ($redis_code !== $code) {
            echo $this->return_json(\constant\CodeConstant::CODE_请正确输入验证码, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请正确输入验证码]);
            die;
        }
        //校验成功后生成token
        $userinfo = $this->_login($phone);
        if ($userinfo === false) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户不存在, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_用户不存在]);
            die;
        }

        Log::record('web用户提现登录账号:' . json_encode($userinfo), 'webUserWithdrawalLogin');
//        $redis = $this->getRedis();
        //        $key_lose = strtotime(date('Y-m-d ', strtotime('+1 day'))) - time();
        //        $redis->SETEX(\constant\CommonConstant::WEB_USER_WITHDRAWAL_TOKEN . $userinfo['userInfo']['id'], $key_lose, json_encode(array('id' => $userinfo['userInfo']['id'], 'username' => $userinfo['userInfo']['username'])));

        $redis->del('web_withdrawal_verify_code_' . $phone);
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $userinfo, \constant\CodeConstant::CODE_OK_MAP[\constant\CodeConstant::CODE_成功]);
        die;
    }

    //提现信息页
    public function userWithdrawInfo()
    {
        $id = $this->userinfo['id'];
        //查询用户信息
        $userinfo = MemberService::getInstance()->getOneById($id, 'pretty_id,username,avatar,nickname,id,diamond,exchange_diamond,free_diamond,attestation');
        if (empty($userinfo)) {
            echo $this->return_json(\constant\CodeConstant::CODE_请重新登录, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请重新登录]);
            die;
        }
        $userinfo = $userinfo->toArray();

        $diamond = round((floor($userinfo['diamond']) - floor($userinfo['exchange_diamond']) - floor($userinfo['free_diamond'])) / 10000, 2);
        // $diamondtmp = floor($diamond);
        //钻石余额存入数据库
        $redis = $this->getRedis();
        $key_time = strtotime(date('Y-m-d 23:59:59')) - time();
        $redis->setex(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RESIDUE_MONEY . $id, $key_time, $diamond);
        //修改前端显示余额
        $moneytmp = $diamond;
        $userinfo['money'] = $moneytmp > 0 ? $moneytmp : 0;
        unset($userinfo['exchange_diamond']);
        unset($userinfo['free_diamond']);
        unset($userinfo['diamond']);
        if ($diamond >= 1000 && $diamond < 100000000) {
            $userinfo['diamond'] = $userinfo['money'];
            $userinfo['diamond_msg'] = '';
        } elseif ($diamond >= 100000000) {
            $userinfo['diamond'] = $userinfo['money'];
            $userinfo['diamond_msg'] = '你的钻石数超过显示最大值，不影响兑换，可返回APP查看';
        } else {
            $userinfo['diamond'] = $userinfo['money'];
            $userinfo['diamond_msg'] = '';
        }
        //统计当月用户提现总额
        //查询redis中此用户存在的当前提现总额 如没有则查库再记录
        $user_money_count = $redis->get(\constant\CommonConstant::WEB_USER_WITHDRAWAL_MONEY_COUNT . $id);
        if (!$user_money_count) {
            //没有则查询数据库
            $time = strtotime(date('Y-m-d 00:00:00', time()));
            $user_money_count = MemberWithdrawalModel::getInstance()->getMemberWithdrawalByWhereCount(array(['uid', '=', $id], ['status', '=', 3], ['updated_time', '>=', $time]), 'money');
            if ($user_money_count > 0) {
                $key_time = strtotime(date('Y-m-d 23:59:59')) - time();
                $redis->setex(\constant\CommonConstant::WEB_USER_WITHDRAWAL_MONEY_COUNT . $id, $key_time, $user_money_count);
            }
        }
        //查询redis中此用户最新的提现信息 账号及支付方式
        $pay_type = $redis->get(\constant\CommonConstant::WEB_USER_WITHDRAWAL_PAY_TYPE . $id);
        if (!$pay_type) {
            $pay_type = MemberWithdrawalModel::getInstance()->getMemberWithdrawalByWhere(array('uid' => $id, 'status' => 3), 'type,accounts', array(0, 1));
            if (!empty($pay_type)) {
                $redis->set(\constant\CommonConstant::WEB_USER_WITHDRAWAL_PAY_TYPE . $id, json_encode($pay_type[0]));
            }
        }
        if (!is_array($pay_type)) {
            $pay_type = json_decode($pay_type, 1);
        }
        $dayCount = 20000;
        $userinfo['user_money_count'] = $dayCount - $user_money_count;
        $userinfo['avatar'] = !empty($userinfo['avatar']) ? config('config.APP_URL_image') . $userinfo['avatar'] : 'https://muatest.oss-cn-zhangjiakou.aliyuncs.com/images/mualogo.png';
        $userinfo['pay_type']['type'] = isset($pay_type['type']) ? $pay_type['type'] : 0;
        $userinfo['pay_type']['accounts'] = isset($pay_type['accounts']) ? $pay_type['accounts'] : 0;
        Log::record('web用户提现主页:' . json_encode($userinfo), 'webUserWithdrawalItem');

        //查询用户之前的支付宝用户信息
        $userpay = MembercashModel::getInstance()->getModel()->where(['uid' => $id])->where('status', 'alipay')->order('id desc')->limit(1)->find();
        if (!empty($userpay)) {
            $userinfo['pay_type']['accounts'] = $userpay['alipay'];
            $userinfo['pay_type']['type'] = 0;
        } else {
            $userinfo['pay_type']['accounts'] = '';
            $userinfo['pay_type']['type'] = 1;
        }
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $userinfo, \constant\CodeConstant::CODE_OK_MAP[\constant\CodeConstant::CODE_成功]);
    }

    //提现明细
    public function withdrawLists()
    {
        $default_date = date('Y-m-d');
        $conf = ['审核中', '提现中', '提现失败', '提现成功', '审核拒绝'];
        $type = $this->request->param('type', 0);
        $id = $this->userinfo['id'];
        $page = $this->request->param('page', 1);
        $date = $this->request->param('date', $default_date);
        $end_date = strtotime("$date +1month");
        $size = 20;
        $offset = ($page - 1) * $size;
        //查询用户信息
        $userinfo = MemberService::getInstance()->getOneById($id, 'id');
        if (empty($userinfo)) {
            echo $this->return_json(\constant\CodeConstant::CODE_请重新登录, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请重新登录]);
            die;
        }
        //获取当前用户提现明细列表
        // $user_money_count = MemberWithdrawalModel::getInstance()->getMemberWithdrawalByWhereCount(array(['uid', '=', $id], ['status', '=', 3], ['updated_time', '>=', $time]), 'money');
        $user_money_count = MemberWithdrawalModel::getInstance()->getMemberWithdrawalByWhereCount(array(['uid', '=', $id], ['status', '=', 3], ['created_time', '>=', strtotime($date)], ['created_time', '<', $end_date]), 'money');
        $list = MemberWithdrawalModel::getInstance()->getMemberWithdrawalByWhere(array(['uid', '=', $id], ['created_time', '>=', strtotime($date)], ['created_time', '<', $end_date]), 'type,updated_time,money,status,created_time', array($offset, $size));
        $count = MemberWithdrawalModel::getInstance()->getMemberWithdrawCountByWhere(array(['uid', '=', $id], ['created_time', '>=', strtotime($date)], ['created_time', '<', $end_date]));
        $data = [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $data[$k]['pay_type'] = $v['type'] == 0 ? '支付宝' : '微信';
                // if ($v['status'] == 0) {
                $data[$k]['time'] = date('Y-m-d H:i:s', $v['created_time']);
                // }else{
                //     $data[$k]['time'] = date('Y-m-d H:i:s', $v['updated_time']);
                // }
                $data[$k]['money'] = '+' . $v['money'];
                $data[$k]['zhuangtai'] = $conf[$v['status']] ? $conf[$v['status']] : '-';
            }
        }
        Log::record('web用户提现明细列表:' . json_encode($userinfo), 'webUserWithdrawalLists');
        $res = ['lists' => $data, 'pages' => ceil($count / $size), 'date' => $date, 'total_money' => $user_money_count];

        echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, \constant\CodeConstant::CODE_OK_MAP[\constant\CodeConstant::CODE_成功]);
        die;
    }

    public function userAccountBind()
    {
        $id = $this->userinfo['id'];
        $type = $this->request->param('type');
        $accounts = $this->request->param('accounts');
        $name = $this->request->param('name');

        if ($type != 0 && $type != 1) {
            echo $this->return_json(\constant\CodeConstant::CODE_请选择正确的提现方式, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请选择正确的提现方式]);
            die;
        }
        if (!$accounts || strlen($accounts) > 50) {
            echo $this->return_json(\constant\CodeConstant::CODE_请填写正确的账号信息, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请填写正确的账号信息]);
            die;
        }
        if (preg_match("/[\x7f-\xff]/", $accounts)) {
            echo $this->return_json(\constant\CodeConstant::CODE_请填写正确的账号信息, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请填写正确的账号信息]);
            die;
        }
        if (empty($name)) {
            echo $this->return_json(500, null, '姓名不能为空');
            die;
        }
        $status = $type == 0 ? 'alipay' : 'wechatpay';
        //查询提现alipay账户
        $payment = MembercashModel::getInstance()->getModel()
            ->where('uid', $id)
            ->where('status', $status)
        // ->where('name', $name)
        // ->where('alipay', $accounts)
            ->order('id desc')
            ->limit(1)
            ->find();

        if (!empty($payment)) {
            //换绑
            $data = [
                'uid' => $id,
                'alipay' => $accounts,
                'name' => $name,
                'status' => 'alipay',
                'createdtime' => time(),
            ];
            $res = MembercashModel::getInstance()->getModel()->where('id', $payment->id)->update($data);
        } else {
            $data = [
                'uid' => $id,
                'alipay' => $accounts,
                'name' => $name,
                'status' => 'alipay',
                'createdtime' => time(),
            ];
            $res = MembercashModel::getInstance()->getModel()->save($data);
        }

        Log::record('web用户提现明细操作失败:' . json_encode($res) . ':提现数据:' . json_encode($data), 'webUserWithdrawalOperation');
        echo $this->return_json(\constant\CodeConstant::CODE_账户绑定成功, null, \constant\CodeConstant::CODE_OK_MAP[\constant\CodeConstant::CODE_账户绑定成功]);
        die;
    }

    public function userWithdrawOperation()
    {
        //     if( time() > 1656863940){
        //         echo $this->return_json(500, null, '提现功能升级维护...暂时不支持提现');
        //         die;
        //    }

        Log::info('userWithdrawOperation:{params}', ['params' => json_encode($this->request->param())]);
        $id = $this->userinfo['id'];

        $type = $this->request->param('type');
        $money = $this->request->param('money');
        $redis = $this->getRedis();
        //提交频率
        $isTijiao = $redis->get('tixian_tijiao_' . $id);
        if (!empty($isTijiao)) {
            echo $this->return_json(500, null, '请勿频繁提交,10s之后重试');
            die();
        }

        if (!is_numeric($money) || is_float($money)) {
            echo $this->return_json(\constant\CodeConstant::CODE_提现金额必须是整数, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_提现金额必须是整数]);
            die;
        }
        if ((int) $money % 100 != 0) {
            echo $this->return_json(\constant\CodeConstant::CODE_提现金额必须为100或者100的倍数, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_提现金额必须为100或者100的倍数]);
            die;
        }
        if ($money < 100) {
            echo $this->return_json(\constant\CodeConstant::CODE_提现金额必须大于100, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_提现金额必须大于100]);
            die;
        }
        if ($money > 50000) {
            echo $this->return_json(\constant\CodeConstant::CODE_提现金额必须小于50000, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_提现金额必须小于50000]);
            die;
        }
        if ($type != 0 && $type != 1) {
            echo $this->return_json(\constant\CodeConstant::CODE_请选择正确的提现方式, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请选择正确的提现方式]);
            die;
        }

        //查询用户信息
        $userinfo = MemberService::getInstance()->getOneById($id, 'avatar,nickname,id,diamond,exchange_diamond,free_diamond,attestation,guild_id');
        if (empty($userinfo)) {
            echo $this->return_json(\constant\CodeConstant::CODE_请重新登录, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请重新登录]);
            die;
        }
        // $guildRes = MemberSocityModel::getInstance()->getModel()->where(array('user_id' => $id, 'guild_id' => 197))->find();
        // if (!empty($guildRes)) {
        //     echo $this->return_json(500, null, '公会暂不支持兑换');
        //     die;
        // }
        $userinfo = $userinfo->toArray();
        if ($userinfo['attestation'] != 1) {
            echo $this->return_json(\constant\CodeConstant::CODE_提现需要实名认证, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_提现需要实名认证]);
            die;
        }
        $status = $type == 0 ? 'alipay' : 'wechatpay';

        //查询提现alipay账户
        $payment = MembercashModel::getInstance()->getModel()
            ->where('uid', $id)
            ->where('status', $status)
            ->order('id desc')
            ->limit(1)
            ->find();

        if (empty($payment)) {
            echo $this->return_json(500, null, '账户不存在');
            die;
        }

        //查询此用户是否被封禁
        $user_black = BlackDataModel::getInstance()->getModel()->where(array(['blackinfo', '=', $userinfo['id']], ['status', '=', '1']))->value('blackinfo');
        if (!empty($user_black)) {
            echo $this->return_json(\constant\CodeConstant::CODE_您的账号已封禁, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_您的账号已封禁]);
            die;
        }
        //查询用户余额钻石
        $diamond = ($userinfo['diamond'] - $userinfo['exchange_diamond'] - $userinfo['free_diamond']) / 10000;
        if ($diamond - $money < 0) {
            echo $this->return_json(\constant\CodeConstant::CODE_可提现金额不足, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_可提现金额不足]);
            die;
        };

//        $residueMoney =$redis->get(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RESIDUE_MONEY.$id);
        //        if(((int)$residueMoney - (int)$money * 10000) < 0){
        //            echo $this->return_json(\constant\CodeConstant::CODE_可提现金额不足, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_可提现金额不足]);
        //            die;
        //        }
        //        $resMoney = (int)$residueMoney - (int)$money *10000;
        //        $key_time = $a = strtotime(date('Y-m-d 23:59:59')) - time();
        //        $redis->setex(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RESIDUE_MONEY . $id, $key_time, $resMoney);
        //
        //        //查询可提现额度
        //        $user_money_count = $redis->get(\constant\CommonConstant::WEB_USER_WITHDRAWAL_MONEY_COUNT . $id);
        //        if (!$user_money_count) {
        //            $time = strtotime(date('Y-m-d 00:00:00', time()));
        //            $user_money_count = MemberWithdrawalModel::getInstance()->getMemberWithdrawalByWhereCount(array(['uid', '=', $id], ['status', '=', 3], ['updated_time', '>=', $time]), 'money');
        //            if ($user_money_count > 0) {
        //                $key_time = strtotime(date('Y-m-d 23:59:59')) - time();
        //                $redis->setex(\constant\CommonConstant::WEB_USER_WITHDRAWAL_MONEY_COUNT . $id, $key_time, $user_money_count);
        //            }
        //        }
        $dayCount = 20000;
//        if(false){
        //        // if(intval($user_money_count) > intval($dayCount)){
        //            echo $this->return_json(\constant\CodeConstant::CODE_今日提现额度已用完, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_今日提现额度已用完]);
        //            die;
        //        }
        //        if(false){
        //            echo $this->return_json(\constant\CodeConstant::CODE_提现额度大于今日可提现额度, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_提现额度大于今日可提现额度]);
        //            die;
        //        }
        //当前可进行提现申请
        $data = [];
        $data['uid'] = $id;
        $data['type'] = $type;
        $data['diamond'] = $money * 10000;
        $data['money'] = $money;
        $data['accounts'] = $payment->alipay;
        $data['status'] = 0;
        $data['poundages'] = 0;
        $data['desc'] = '';
        $data['created_time'] = time();
        $data['updated_time'] = 0;
        $data['order_id'] = md5(Uuid::uuid1()->toString());

        $redis->setex('tixian_tijiao_' . $id, 10, 1);
        $info = MemberService::getInstance()->getOneById($id, 'diamond,exchange_diamond,free_diamond');
        $diamond = ($info['diamond'] - $info['exchange_diamond'] - $info['free_diamond']) / 10000;
        if ($diamond - $money < 0) {
            echo $this->return_json(\constant\CodeConstant::CODE_可提现金额不足, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_可提现金额不足]);
            die;
        };

        //更新用户消费钻石数量
        $priceCount = $money * 10000;

        //提现预扣款
        $params = [
            'userId' => (int) $id,
            'assetId' => 'user:diamond',
            'count' => $priceCount,
            'timestamp' => time(),
            'eventDict' => json_encode([
                "ext1" => $data['order_id'],
            ]),
            'eventId' => 10015,
        ];

        MemberWithdrawalModel::getInstance()->getModel()->startTrans();
        try {
            $res = ApiService::getInstance()->curlApi(ApiUrlConfig::$withdraw_consume_asset, $params, true, true);
            if (isset($res['code']) && $res['code'] == 200) {
                if ($res['data']['asset'][0] != $priceCount) {
                    throw new Exception("订单扣款失败", 500);
                }
                MemberWithdrawalModel::getInstance()->addMemberWithdrawal($data);
            }
            MemberWithdrawalModel::getInstance()->getModel()->commit();
            Log::record('web用户提现明细操作成功:' . json_encode($userinfo) . ':提现数据:' . json_encode($data), 'webUserWithdrawalOperation');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, \constant\CodeConstant::CODE_OK_MAP[\constant\CodeConstant::CODE_提现申请成功]);
            die;
        } catch (\Exception $e) {
            MemberWithdrawalModel::getInstance()->getModel()->rollback();
            Log::record('web用户提现明细操作失败:' . json_encode($userinfo) . ':提现数据:' . json_encode($data), 'webUserWithdrawalOperation');
            $tipMsg = $e->getMessage() ?: \constant\CodeConstant::CODE_INSIDE_ERR_MAP[\constant\CodeConstant::CODE_提现申请失败];
            echo $this->return_json(\constant\CodeConstant::CODE_提现申请失败, null, $tipMsg);
            exit;
        }
    }

    private function aliSmsSend($phone, $data)
    {
        AlibabaCloud::accessKeyClient($this->ali_sms_accessKeyId, $this->ali_sms_accessSecret)
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        $result = AlibabaCloud::rpc()
            ->product($this->ali_sms_product)
        // ->scheme('https') // https | http
            ->version('2017-05-25')
            ->action($this->ali_sms_action)
            ->method('POST')
            ->host($this->ali_sms_host)
            ->options([
                'query' => [
                    'RegionId' => $this->ali_sms_regionId,
                    'PhoneNumbers' => $phone,
                    // 'SignName' => $this->ali_sms_signName,
                    'SignName' => config('config.ali_sms')['ali_sms_signName'],
                    'TemplateCode' => $this->ali_sms_templateCode,
                    'TemplateParam' => $data,
                ],
            ])
            ->request();
        return $result->toArray();
    }

    private function _login($phone)
    {
        //更新mysql
        $login = UserAccountMapModel::getInstance()->getModel()->where('type', 'mobile')->where('value', $phone)->findOrEmpty()->toArray();

        //创建token
        $info = [
            'username' => $phone,
            'id' => $login['user_id'],
            'last_login_time' => time(),
        ];

        $webCommon = new WithdrawBaseController($this->app);
        $token = $webCommon->signToken($info);

        $info['token'] = $token;
        //缓存token
        cache(\constant\CommonConstant::WEB_USER_WITHDRAWAL_TOKEN . $token, $info, $info['last_login_time'] + 10000);
        return ['userInfo' => $info];
    }

    private function _getToken($token = '')
    {
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOjE2Mjk4NzU0MzksImF1ZCI6IiIsImlhdCI6MTYyOTg3NTQzOSwiZXhwIjoxNjI5ODg1NDM5LCJkYXRhIjp7ImlkIjoxNDMzMjcxLCJ1c2VybmFtZSI6IjE4ODM0MDkyMDIxIiwibGFzdF9sb2dpbl90aW1lIjoxNjI5ODc1NDM5fX0.Pl5DcKOkf2JEyJPjucBttItU3u-gy25xw425wBxFCCg";
        $webCommon = new WithdrawBaseController($this->app);
        $id = $webCommon->getIdByToken($token);

        return $id;
    }
}

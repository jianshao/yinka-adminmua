<?php

namespace app\admin\controller;

use app\admin\controller\aliPay\AopCertClient;
use app\admin\controller\aliPay\request\AlipayFundAccountQueryRequest;
use app\admin\controller\aliPay\request\AlipayFundTransToaccountTransferRequest;
use app\admin\controller\aliPay\request\AlipayFundTransUniTransferRequest;
use app\BaseController;
use app\common\RedisCommon;
use think\App;
use think\facade\Log;
use Yansongda\Pay\Pay;

class AliPayController extends BaseController
{
    private $payer_name = "mua畅聊";
    protected $appCertPath;
    protected $alipayCertPath;
    protected $rootCertPath;
    protected $gatewayUrl;
    protected $appId;
    protected $rsaPrivateKey;
    protected $format;
    protected $charset;
    protected $signType;

    public function __construct()
    {
        //        $this->appCertPath = dirname(dirname(__FILE__)) . "/controller/aliPay/cert/appCertPublicKey.crt";
        //        $this->alipayCertPath = dirname(dirname(__FILE__)) . "/controller/aliPay/cert/alipayCertPublicKey_RSA2.crt";
        //        $this->rootCertPath = dirname(dirname(__FILE__)) . "/controller/aliPay/cert/alipayRootCert.crt";
        //        $this->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        //        $this->appId = "2019070865745896";
        //        $this->rsaPrivateKey = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCM7b7VoK2yDjx+XtDyvVGmAK2YmBl73/jtWV7M+VM8XiKFrxdUdgw0EHuIFSQBLWXDdlIUSNmi/RvJEnQb/tBARi+m7pU8HOLrLGxOzKHzjaLWmKCbeo3IxwyhRVfcqpTEGFcXR8XB6ZK2pWm2Ov5Bd1VQ8xaeCptVEs1I2mQufSMmYLNG+BOecQiT5mdC3uw0/Axq196rytGDfzwservusIN3bkNnnRVbU6046ouwArd7mLdlsuYLAKBaypUWNyS65kCmXOo961eCu3bY0f4WQgVG89WLghX6y5D+lDykBHgGHgRmKFZjklTeRcnh4pObAUrHYvB57gy1O8yb5r7fAgMBAAECggEARM63hBpFdFAbHSAyKLJisQhXuc9Zce/y2//sl2uMRkc318mbPHO+lZUOm2ym/aQqsXqNrLW8/SVTcaL+6cRJ7XfXQEvwtT7tVCGGaBrlX8LhpKE0mXUG0ObOtdbjhSwMIoo3y3gRiBIAvgiZSo4XIeOf1jw56MQI/0qEzHNEwqVArRX9/ED23pYneZM6gUpgTSI8ZyNFGNxHR1wimdJbuQwsEJLeheZFd3bqTyhJPXax5p1p/SrkszT3chcoSENuBadRqElFt8vUmW71Cc2gIoreenZ2T3Gnl6W8FFKa/DjtDaAr9wHbUGUaIG8qEUxvukAVt+TQqy3yKE+sJO4noQKBgQDKl40umkgFYF+7RoFiRKLBxD2zSDVknIVZlT3l4DI/3+UQ6AoN1sRit08rLSMseru+SKtBs+t6bzDxU2zu/F4/HclrGScHVMFZJXwvyW1E+tua53NLdA7zF3j4dRcpZ3/ucm10+ZSJSfCLIg3Jt/YBYyiFFumOm0yivTRc2LC6JwKBgQCyFK9tbFL4BUmwYfddL7wtUxLKBwDprnx+q4UNu9m2nDZfj5uk2LuMj3giDjCv839l5jVQvlUsnKVfMPrGklZLYy2kCBtTHgTaJNTJgpyAyDwd4KuwHFipjte+Cv7EzxJT/DLLsMJQQiCPTaQ6NjFeBHP7b9eC/eQ9SsFttafgiQKBgChy+9hLK4gPRu3gVOLm60wev2b1StvMuH87YgFssvu320d13NQIhmtjSCZJu9UcqDGE1tSmdKScYLw+OOi6cKLPcrC0c+tty7Dd4B62a9+y6nfSMF3nTTjR/fA1iKtWo99a72nEjxieL63H3dLhrPd38dYozfcQIMv5VOQYy6hPAoGAE8gYdJ9D2Ck+NkmroL5cuOwxeh+tCkhHrAqBjTUAyjgwEg1xzK4Gp2aIgb/xyJnT3Q3lfkKmU35TIG/ga45154ns1/vOjT0YbOMKgBfyKpwTkX4TlEyRzMQBUysFgfc+ofWx7s6Dx0aRN1n4lD7Q3RDBkXyrA/IQGH7lXbqAG4ECgYEAhxasxbVhD+UL79mpIOtXhJXkiIUTr4MeMXwjX/FeEADzkxqNUCZBxIFn8+DWCamdkhOaWzYY3YtNRNz5bUCUTeHtW67Lun4s1qOoWyMu9iaJ/yHkkzduXFuiNFiDyr4mPWzlr5em2Z3sngewmN27JkqY1PISx3PmUE78qILW3gY=';
        //        $this->format = "json";
        //        $this->charset = "utf-8";
        //        $this->signType = "RSA2";
    }

    public function aliPay($pay_no = 0, $amount = 0, $pay_name = '', $memo = '', $oid = '', $uid = '')
    {
        Log::record('aliPay请求开始:订单:' . $oid . ':uid:' . $uid);
        if (!$pay_no || !$amount || !$memo || !$oid || !$uid) {
            Log::record('aliPay参数错误');

            return false;
        }
//        查询此用户下的请求订单是否已存在请求中
        $redis = $this->getRedis();
        $existence = $redis->sIsMember(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_OID . $uid, $oid);
        if ($existence > 0) {
            Log::record('aliPay已存在打款中的记录');
            return false;
        }
        $redis->sAdd(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_OID . $uid, $oid);
        $TiXian = config('config.ALITIXIAN');
        $c = new AopCertClient();
        $appCertPath = $TiXian['appCertPath'];
        $alipayCertPath = $TiXian['alipayCertPath'];
        $rootCertPath = $TiXian['rootCertPath'];
        $c->gatewayUrl = $TiXian['gatewayUrl'];
        $c->appId = $TiXian['appId'];
        $c->rsaPrivateKey = $TiXian['rsaPrivateKey'];
        $c->format = $TiXian['format'];
        $c->charset = $TiXian['charset'];
        $c->signType = $TiXian['signType'];
        //调用getPublicKey从支付宝公钥证书中提取公钥
        $c->alipayrsaPublicKey = $c->getPublicKey($alipayCertPath);
        //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $c->isCheckAlipayPublicCert = true;
        //调用getCertSN获取证书序列号
        $c->appCertSN = $c->getCertSN($appCertPath);
        //调用getRootCertSN获取支付宝根证书序列号
        $c->alipayRootCertSN = $c->getRootCertSN($rootCertPath);
        $request = new AlipayFundTransToaccountTransferRequest();
        $request->setBizContent("{" .
            "\"out_biz_no\":\"" . $oid . "\"," . //商户生成订单号
            "\"payee_type\":\"ALIPAY_LOGONID\"," . //收款方支付宝账号类型
            "\"payee_account\":\"" . $pay_no . "\"," . //收款方账号
            "\"amount\":\"" . $amount . "\"," . //总金额
            "\"payer_show_name\":\"" . $this->payer_name . "\"," . //付款方账户
            "\"payee_real_name\":\"" . $pay_name . "\"," . //收款方姓名
            "\"remark\":\"" . $memo . "\"" . //转账备注
            "}");
        Log::record('aliPay打款参数:' . "{" .
            "\"out_biz_no\":\"" . $oid . "\"," . //商户生成订单号
            "\"payee_type\":\"ALIPAY_LOGONID\"," . //收款方支付宝账号类型
            "\"payee_account\":\"" . $pay_no . "\"," . //收款方账号
            "\"amount\":\"" . $amount . "\"," . //总金额
            "\"payer_show_name\":\"" . $this->payer_name . "\"," . //付款方账户
            "\"payee_real_name\":\"" . $pay_name . "\"," . //收款方姓名
            "\"remark\":\"" . $memo . "\"" . //转账备注
            "}");
        $result = $c->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        Log::record('aliPay用户:' . $uid . ':oid:' . $oid . ':信息:' . json_encode($result->$responseNode));
        return $result->$responseNode;

    }

    public function aliPayUpGrade($pay_no = 0, $amount = 0, $pay_name = '', $memo = '', $oid = '', $uid = '')
    {
        Log::record('aliPayUpGrade请求开始:订单:' . $oid . ':uid:' . $uid);
        if (!$pay_no || !$amount || !$memo || !$oid || !$uid) {
            Log::record('aliPay参数错误');
            return false;
        }
        $withdrawalConfigList = config('config.widthdrawalconfig');
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $account = $redis->get('withdrawalaccount');
        $TiXian = $withdrawalConfigList[$account];
        $appCertPath = $TiXian['appCertPath'];
        $alipayCertPath = $TiXian['alipayCertPath'];
        $rootCertPath = $TiXian['rootCertPath'];
        $c = new AopCertClient();
        $c->gatewayUrl = $TiXian['gatewayUrl'];
        $c->appId = $TiXian['appId'];
        $c->rsaPrivateKey = $TiXian['rsaPrivateKey'];
        $c->format = $TiXian['format'];
        $c->charset = $TiXian['charset'];
        $c->signType = $TiXian['signType'];
        //调用getPublicKey从支付宝公钥证书中提取公钥
        $c->alipayrsaPublicKey = $c->getPublicKey($alipayCertPath);
        //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $c->isCheckAlipayPublicCert = true;
        //调用getCertSN获取证书序列号
        $c->appCertSN = $c->getCertSN($appCertPath);
        //调用getRootCertSN获取支付宝根证书序列号
        // $c->alipayRootCertSN = $c->getRootCertSN($rootCertPath);
        $c->alipayRootCertSN = "687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6";
        $request = new AlipayFundTransUniTransferRequest();

        $params = json_encode([
            "out_biz_no" => $oid,
            "trans_amount" => $amount,
            "product_code" => "TRANS_ACCOUNT_NO_PWD",
            "biz_scene" => "DIRECT_TRANSFER",
            "order_title" => "转账",
            "payee_info" => json_encode([
                "identity_type" => "ALIPAY_LOGON_ID",
                "identity" => $pay_no,
                "name" => $pay_name,
            ]),
            "remark" => $memo,
            // "business_params" => json_encode([
            //     "payer_show_name " => $this->payer_name,
            // ]),
            // "mutiple_currency_detail" => json_encode([
            //     "payment_amount" => "100.00",
            //     "payment_currency" => "CNY",
            //     "trans_amount" => "10.00",
            //     "trans_currency" => "CNY",
            //     "settlement_amount" => "10.00",
            //     "settlement_currency" => "CNY",
            //     "ext_info" => "key=value",
            // ]),
            // "original_order_id" => $oid,
            // "payer_info" => json_encode([
            //     "identity" => "208812*****41234",
            //     "identity_type" => "ALIPAY_USER_ID",
            //     "name" => "黄龙国际有限公司",
            //     "bankcard_ext_info" => json_encode([
            //         "inst_name" => "招商银行",
            //         "account_type" => "1",
            //         "inst_province" => "江苏省",
            //         "inst_city" => "南京市",
            //         "inst_branch_name" => "新街口支行",
            //         "bank_code" => "123456",
            //     ]),
            //     "merchant_user_info" => json_encode([
            //         "merchant_user_id " => "123456 ",
            //     ]),
            //     "ext_info" => json_encode([
            //         "alipay_anonymous_uid " => "2088123412341234 ",
            //     ]),
            // ]),
            // "passback_params" => json_encode([
            //     "merchantBizType " => "peerPay ",
            // ]),
            // "sign_data" => json_encode([
            //     "ori_sign" => "EqHFP0z4a9iaQ1ep==",
            //     "ori_sign_type" => "RSA2",
            //     "ori_char_set" => "UTF-8",
            //     "partner_id" => "签名被授权方支付宝账号ID",
            //     "ori_app_id" => "2021000185629012",
            //     "ori_out_biz_no" => "商户订单号",
            // ]),
        ]);
        $request->setBizContent($params);
        Log::info('转账参数:{params}', ['params' => $params]);
        Log::record('aliPay打款参数:' . "{" .
            "\"out_biz_no\":\"" . $oid . "\"," . //商户生成订单号
            "\"payee_type\":\"ALIPAY_LOGONID\"," . //收款方支付宝账号类型
            "\"payee_account\":\"" . $pay_no . "\"," . //收款方账号
            "\"amount\":\"" . $amount . "\"," . //总金额
            "\"payer_show_name\":\"" . $this->payer_name . "\"," . //付款方账户
            "\"payee_real_name\":\"" . $pay_name . "\"," . //收款方姓名
            "\"remark\":\"" . $memo . "\"" . //转账备注
            "}");
        $result = $c->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        $msg = $result->$responseNode->msg;
        if ($resultCode != 10000) {
            $msg = $result->$responseNode->sub_msg;
        }

        Log::record('aliPay用户:' . $uid . ':oid:' . $oid . ':信息:' . json_encode($result->$responseNode));
        return ['code' => $resultCode, 'msg' => $msg];
    }

    public function checkMoneyLimit()
    {
        $withdrawalConfigList = config('config.widthdrawalconfig');
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $account = $redis->get('withdrawalaccount');
        $TiXian = $withdrawalConfigList[$account];
        $appCertPath = $TiXian['appCertPath'];
        $alipayCertPath = $TiXian['alipayCertPath'];
        $rootCertPath = $TiXian['rootCertPath'];
        $c = new AopCertClient();
        $c->gatewayUrl = $TiXian['gatewayUrl'];
        $c->appId = $TiXian['appId'];
        $c->rsaPrivateKey = $TiXian['rsaPrivateKey'];
        $c->format = $TiXian['format'];
        $c->charset = $TiXian['charset'];
        $c->signType = $TiXian['signType'];
        //调用getPublicKey从支付宝公钥证书中提取公钥
        $c->alipayrsaPublicKey = $c->getPublicKey($alipayCertPath);
        //是否校验自动下载的支付宝公钥证书，如果开启校验要保证支付宝根证书在有效期内
        $c->isCheckAlipayPublicCert = true;
        //调用getCertSN获取证书序列号
        $c->appCertSN = $c->getCertSN($appCertPath);
        //调用getRootCertSN获取支付宝根证书序列号
        // $c->alipayRootCertSN = $c->getRootCertSN($rootCertPath);
        $c->alipayRootCertSN = "687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6";
        $request = new AlipayFundAccountQueryRequest();

        $params = json_encode([
            "alipay_user_id" => $TiXian['alipayUserId'],
            "account_type" => 'ACCTRANS_ACCOUNT',
        ]);
        $request->setBizContent($params);
        Log::info('校验参数:{params}', ['params' => $params]);
        $result = $c->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        Log::record('校验参数返还信息:' . json_encode($result->$responseNode));
        if ($resultCode != 10000) {
            throw new \Exception($result->$responseNode->sub_msg, $resultCode);
        }
        return ['code' => (int) $resultCode, 'available_amount' => (int) $result->$responseNode->available_amount];
    }

    public function aliPayTransfer($pay_no = 0, $amount = 0, $pay_name = '', $memo = '', $oid = '', $uid = '')
    {
        $conf = config('config.alipay_yuansheng');
        $config = [
            'app_id' => $conf['app_id'],
            'notify_url' => $conf['vip_notify_url'],
            'return_url' => $conf['return_url'],
            'ali_public_key' => $conf['ali_public_key'],
            // 加密方式： **RSA2**
            'private_key' => $conf['private_key'],
            'log' => [ // optional
                'file' => $conf['log'],
                'level' => 'debug', //线上info，开发环境为 debug
                'type' => 'single',
                'max_file' => 30,
            ],
            'http' => [
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
            ],
            //'mode' => 'dev',
        ];
        $order = [
            'out_biz_no' => $oid, // 订单号
            'payee_type' => 'ALIPAY_LOGONID', // 收款方账户类型(ALIPAY_LOGONID | ALIPAY_USERID)
            'payee_account' => $pay_no, // 收款方账户
            'amount' => $amount, // 转账金额
            'payer_show_name' => '业务提现转账',
            'payee_real_name' => $pay_name, // 付款方姓名
            'remark' => $memo,
        ];
        Log::record('order_params---' . json_encode($order));
        $content = Pay::alipay($config)->transfer($order)->toArray();
        return $content;
    }

}

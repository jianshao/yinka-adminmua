<?php

namespace app\admin\service;

use app\admin\common\ApiUrlConfig;
use app\admin\common\dalong\OpenApi;
use app\admin\common\dalong\request\OpenRequest;
use app\admin\controller\AliPayController;
use app\admin\model\MemberWithdrawalModel;
use app\admin\model\UserWithdrawInfoModel;
use app\common\FileCipher;
use think\Exception;
use think\facade\Db;
use think\facade\Log;

class WithdrawalService
{
    protected static $instance;

    private $openapi = null;
    private $openrequest = null;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->openapi = new OpenApi();
        $this->openrequest = new OpenRequest();
    }

    /**
     * 获取图片的base64code
     * @param $image_file
     * @return string
     */
    public function base64EncodeImage($image_file)
    {
        $image_data = fread(fopen($image_file, 'rb'), filesize($image_file));
        $base64_image = chunk_split(base64_encode($image_data));
        return $base64_image;
    }

    /**
     * 获取提现人身份信息
     */
    public function getUserIdentity($user_id)
    {
        return UserWithdrawInfoModel::getInstance()->getModel()->where(["user_id" => $user_id])->find();
    }

    /**
     * 解密加密的文件
     * @param $imgurl
     */
    public function decryImage($imgurl, $filecipher = true)
    {
        ini_set('memory_limit', '100M');
        $fileinfo = pathinfo($imgurl);
        $extension = $fileinfo['extension'];
        $filename = $fileinfo['filename'];
        $pathName = app()->getRuntimePath() . $filename . "." . $extension;
        $decrypathName = app()->getRuntimePath() . $filename . '_decry' . '.' . $extension;
        if ($filecipher == true) {
            if (file_put_contents($pathName, file_get_contents($imgurl))) {
                $fileCipher = new FileCipher(base64_decode(config('config.filecipher.key')), config('config.filecipher.iv'), 1024 * 1024, "0001");
                $fileCipher->decryptFile($pathName, $decrypathName);
                if (is_file($pathName)) {
                    @unlink($pathName);
                }
                return $decrypathName;
            }
        } else {
            if (file_put_contents($pathName, file_get_contents($imgurl))) {
                return $pathName;
            }
        }
        return '';
    }

    //提现失败或者拒绝的加资产
    public function addAsset($res)
    {
        $order_id = $res['order_id'];
        $redis = $this->getRedis(['select' => 8]);
        $requestBody = [
            "userId" => (int) $res['uid'],
            "assetId" => "user:diamond",
            "count" => $res['diamond'],
            "timestamp" => time(),
            "eventDict" => json_encode(["ext1" => $order_id, "ext3" => $res['diamond']]),
            "eventId" => 10017,
        ];
        //提现返还资产
        $withdraw_return_addset = "withdrawal_return_orderid";

        //业务逻辑 只有支付失败的时候才能调用  同步失败和异步失败 只能执行一次
        if($redis->setnx("withdrawal:lock:".$order_id,1)){
            try{
                $res = ApiService::getInstance()->curlApi(ApiUrlConfig::$withdraw_add_asset, $requestBody, true, true);

                if($res['code'] == 200){
                    $redis->sAdd($withdraw_return_addset,$order_id);
                }
            }catch (\Throwable $e){
                throw  $e;
            }
        }
    }

    /**
     * 更改提现的状态
     * @param $res
     * @param $mark
     */
    public function changeWithdrawalStatus($res, $result)
    {
        try {
            $updateRes = [];
            $mark = $result['mark'] ?? '';

            if ($mark == 'success') {
                //成功不记金流
                $updateRes['status'] = 3; //订单打款成功
                $updateRes['callback_time'] = time(); //订单结算成功
                $updateRes['ext_1'] = $result['ext_1'] ?? ''; //标记这是大珑转账成功

                MemberWithdrawalModel::getInstance()->getModel()->transaction(function()use($res,$updateRes){
                    MemberWithdrawalModel::getInstance()->getModel()->where(['id' => $res['id']])->update($updateRes);
                });
            }

            //转账异步失败的执行逻辑
            if ($mark == 'fail') {
                //调用增加金流的接口增加资产 新增资产
                $updateRes['status'] = 2; //失败
                $updateRes['callback_time'] = time(); //退单时间
                $updateRes['message_detail'] = $result['msg'] ?? '';
                $updateRes['ext_1'] = $result['ext_1'] ?? ''; //标记这是大珑转账成功的
                MemberWithdrawalModel::getInstance()->getModel()->transaction(function()use($res,$updateRes){
                   $havRes =  MemberWithdrawalModel::getInstance()->getModel()->lock(true)->where("id",$res['id'])->find();
                   if($havRes){
                       MemberWithdrawalModel::getInstance()->getModel()->where("id",$res['id'])->update($updateRes);
                   }
                });
                 $this->addAsset($res);
            }
        } catch (\Throwable $e) {
            throw $e;
        }

    }

    /**
     *
     * 获取第三方数据
     * @param $name
     * @param string $params
     * @return array|mixed
     *  第一个 获取渠道列表
     *  name = 'channel.list';//获取渠道列表 每次转账必须调用获取渠道ID
     * $parseReturnRes['data']['channelList'][0]['channelId'] ?? 0 ;
     *   //{"code":"200","data":{"channelList":[{"address":"","bankName":"浙江稠州银行","bankUser":"123213213123","channelId":16,"landlineNumber":"","nickname":"洋浦大珑","title":"洋浦大珑","uniformCode":"QQQQQQQQQQQQQQQ"}],"payChannels":["1","2"]}}
     *
     *  第二个 添加自由职业者
     * "name = 'employees.insp.free.add'"
     * $params= [
     * 'truename' =>'张朝宇',           //姓名
     * 'phone' => '13347775555',      //手机号
     * 'card' => '210403199011170314', //身份号
     * 'infoPage'=>urlencode($this->base64EncodeImage($zxy1)),                //身份证正面
     * 'emblemPage' =>urlencode($this->base64EncodeImage($zxy2)),             // 身份证反面
     * 'mode' => 0         //上传身份证的模式 0 base64 1 图片地址
     * ]
     * $parseReturnRes['data']['employeesId'];
     *"{"code":"200","data":{"infoUrl":"https://image.dalongsc.com/20220325/1648194711088.jpg","emblemUrl":"https://image.dalongsc.com/20220325/1648194719013.jpg","employeesId":253419}}"
     *
     * 第三个 支付宝转账
     * name = order.add
     * params =
     * [
     * 'orderNo' =>   $orderNo,        //姓名
     * 'bizFee' => '299.99',      //手机号
     * 'name' => '张朝宇', //支付宝预留姓名
     * 'bankCardNo' => '13347775555', //支付宝账号
     * 'papersType' => 0 , //证件类型：0身份证 int
     * 'papersNo' => '210403199011170314' ,  // 收款人证件号码
     * 'phone' => '13347775555',
     * 'notifyUrl' => 'https://recodetestadmin2.fqparty.com/admin/callback', //回调地址
     * 'channelId' => (string)$channelId,
     * 'payChannel' => '2', //支付渠道 2支付宝 3微信
     * 'remark' => '提现转款' //交易备注
     *
     * ]
     *
     *  //{"code":"200","data":{"bankCardNo":"13347775555","baseFee":6.00,"bizFee":100.00,"created":1648196854665,"name":"张朝宇","orderNo":"76071648196854","papersNo":"2104
     * 第四个 银行卡转账
     * name = 'order.add.bank'
     * [
     * 'orderNo' => $orderNo,           //姓名
     * 'bizFee' => '299.99',      //手机号
     * 'name' => '张朝宇', //支付宝预留姓名
     * 'bankCardNo' => '13347775555', //支付宝账号
     * 'papersType' => 0 , //证件类型：0身份证 int
     * 'papersNo' => '210403199011170314' ,  // 收款人证件号码
     * 'phone' => '13347775555',
     * 'notifyUrl' => 'https://recodetestadmin2.fqparty.com/admin/callback', //回调地址
     * 'channelId' => (string)$channelId,
     * 'refundNoticeUrl' => 'https://recodetestadmin2.fqparty.com/admin/callback',
     * 'remark' => '提现转款' //交易备注
     * ]
     *
     * {"code":"200","data":{"bankCardNo":"13347775555","baseFee":6.00,"bizFee":100.00,"created":1648196854665,"name":"张朝宇","orderNo":"76071648196854","papersNo":"2104

     *
     */

    public function executeApi($name, $params = [])
    {
        $api = $this->openapi;
        $request = $this->openrequest;
        $request->name = $name; //必须输入
        if ($params) {
            $request->setContent(json_encode($params));
        }
        $result = $api->execute($request, "POST");
        $parseReturnRes = json_decode($result, true);
        return $parseReturnRes;

    }

    protected function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.redis');
        $param['host'] = $redis_result['host'];
        $param['port'] = $redis_result['port'];
        $param['password'] = $redis_result['password'];
        $param['select'] = 0;
        if (!empty($arr)) {
            foreach ($arr as $k => $v) {
                $param[$k] = $v;
            }
        }

        $this->handler = new \Redis;
        $this->handler->connect($param['host'], $param['port'], 0);
        if ('' != $param['password']) {
            $this->handler->auth($param['password']);
        }

        if (0 != $param['select']) {
            $this->handler->select($param['select']);
        }
        return $this->handler;
    }




    /**
     * 第三方大珑转账
     * aram $res
     * @param $uid 用户id
     * @param $order_id 订单号
     * @param $money   提现金额
     * @param $account 提现账户
     * @param $username 提现姓名
     * @param $pay_type 提现方式  支付宝 银行卡
     * @return array|mixed
     * @throws Exception
     */
    public  function daLongWithdrawal($uid,$order_id,$money,$account,$username,$pay_type=0)
    {
        //调用第三方转账
        //$money = 1;
        try {
            $parseReturnRes = $this->executeApi("channel.list");
            if (isset($parseReturnRes['code']) && $parseReturnRes['code'] == 200) {
                $channelid = $parseReturnRes['data']['channelList'][0]['channelId'] ?? 0;
                if ($channelid < 0) {
                    throw new Exception("大珑渠道获取失败");
                }
                //判断提现人是否添加自由职业人(身份已认证)
                $userIdentify = $this->getUserIdentity($uid);
                if ($userIdentify['status'] != 1) {
                    //用户信息的认证 用异步脚本去跑量认证
                    throw new Exception("用户身份证信息尚未认证,请稍后重试");
                }
                //支付成功的回调地址
                $callbackUrl = config("config.dalongconfig")['dalongcallback'];
                if ($pay_type == 0) { //支付宝
                    $withdrawalInfo = [
                        'orderNo' => $order_id, //订单号
                        'bizFee' => $money, //金额
                        'name' => $username, //支付宝预留姓名
                        'bankCardNo' => $account, //支付宝账号
                        'papersType' => 0, //证件类型：0身份证 int
                        'papersNo' => $userIdentify['identity_number'], // 收款人证件号码
                        'phone' => $userIdentify['real_phone'] ?? '',
                        'notifyUrl' => $callbackUrl, //回调地址
                        'channelId' => (string) $channelid,
                        'payChannel' => '2', //支付渠道 2支付宝 3微信
                        'remark' => '提现转款', //交易备注
                    ];
                    Log::info("dalongwithdrawal:reqeustbody:" . json_encode($withdrawalInfo));
                    $orderReturnRes = $this->executeApi("order.add", $withdrawalInfo);
                    Log::info("dalongwithdrawal:apires:" . json_encode($orderReturnRes));
                    return $orderReturnRes;
                } elseif ($pay_type == 2) { //银行卡
                    $withdrawalInfo = [
                        'orderNo' => $order_id, //订单号
                        'bizFee' => $money, //金额
                        'name' => $username, //预留姓名agree
                        'bankCardNo' => $account, //银行卡号
                        'papersType' => 0, //证件类型：0身份证 int
                        'papersNo' => $userIdentify['identity_number'], // 收款人证件号码
                        'phone' => $userIdentify['real_phone'] ?? '',
                        'notifyUrl' => $callbackUrl, //回调地址
                        'channelId' => (string) $channelid,
                        'refundNoticeUrl' => $callbackUrl,
                        'remark' => '提现转款', //交易备注
                    ];
                    Log::info("dalongwithdrawal:reqeustbody:" . json_encode($withdrawalInfo));
                    $orderReturnRes = $this->executeApi("order.add.bank", $withdrawalInfo);
                    Log::info("dalongwithdrawal:apires:" . json_encode($orderReturnRes));
                    return $orderReturnRes;
                }
            }
        } catch (\Throwable $e) {
            Log::error("dalongwithdrawal:error" . $e->getMessage());
            throw $e;
        }
    }



    /*
 * 公司自己的阿里转账
 */
    public function alipayWithdrawal($uid,$order_id,$money,$account,$username,$pay_type=0)
    {
        try {
            $msg = [];
            $order_number = $order_id;
            //检测商户金额是否足额
            $alipay = new AliPayController();
            $check = $alipay->checkMoneyLimit();
            if ($check['code'] != 10000 || $check['available_amount'] < $money) {
                throw new Exception("可提现金额不足");
            }
            //$money = "0.1"; //测试功能临时性调整
            $alipayResult = $alipay->aliPayUpGrade($account,$money,$username, '提现', $order_id, $uid);
            Log::record('ali_pay_transfer_order_number---' . $order_number . "---" . json_encode($alipayResult));
            //修改转账日志
            if (isset($alipayResult['code']) && $alipayResult['code'] != 10000) {
                return $alipayResult;
            }

            if(isset($alipayResult['code']) && $alipayResult['code'] == 10000){
                return ['code'=>200,"ok"=>1];
            }
        } catch (\Throwable $e) {
            Log::info("alipay-widthdrawal-error:" . json_encode($msg));
            throw $e;
        }
    }



    public function getwithdrawStatusList(){
        return ["0" => "待审核", "1" => "打款中", "2" => "打款失败", "3" => "打款成功", "4" => "拒绝",];
    }


    public function getwithdrawTypeList(){
        return ["0" => "支付宝", "1" => "微信", "2" => "银行卡",];
    }


    public function withdrawLock($order_id){
        $redis = $this->getRedis();
        return $redis->set(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_ID .$order_id, 1,["nx", 'ex' => 20]);
    }


    public function withdrawUnlock($order_id){
        $redis = $this->getRedis();
        $redis->del(\constant\CommonConstant::WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_ID .$order_id);
    }


}

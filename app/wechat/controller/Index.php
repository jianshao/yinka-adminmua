<?php
namespace app\wechat\controller;

use app\BaseController;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;

class Index extends BaseController
{

    //商户id
    const KEY ='5c4a81648ccc8342ce21bbd8c5e590f8';

    //获取code 的微信服务器地址
    const CODEURL = "https://open.weixin.qq.com/connect/oauth2/authorize?";
    //你自己的APPID
    const APPID = 'wxe50b8b85af03082a';

    //获取openid 的微信服务器地址
    const OPENIDURL = 'https://api.weixin.qq.com/sns/oauth2/access_token?';

    //开发者秘钥
    const SECRET = 'b39d6b56c50a7b9bf03bc80c067a4547';


protected $config = [
        'appid' => 'wxe50b8b85af03082a', // APP APPID
        'app_id' => 'wxe50b8b85af03082a', // 公众号 APPID
        'miniapp_id' => 'wxe50b8b85af03082a', // 小程序 APPID
        'mch_id' => '1543515161',
        'key' => '5c4a81648ccc8342ce21bbd8c5e590f8',
        'notify_url' => 'http://mtestapi.57xun.com/index.php/Api/WechatPublic/weiXinBack',
        'cert_client' => './cert/apiclient_cert.pem', // optional，退款等情况时用到
        'cert_key' => './cert/apiclient_key.pem',// optional，退款等情况时用到
        'log' => [ // optional
            'file' => './logs/wechat.log',
            'level' => 'debug', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
        'http' => [ // optional
            'timeout' => 5.0,
            'connect_timeout' => 5.0,
            // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
        ],
        'mode' => 'hk', // optional, dev/hk;当为 `hk` 时，为香港 gateway。
    ];
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V6<br/><span style="font-size:30px">13载初心不改 - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
   }

public function getOpenid()
    {
   
$KEY ='5c4a81648ccc8342ce21bbd8c5e590f8';

    //获取code 的微信服务器地址
    $CODEURL = "https://open.weixin.qq.com/connect/oauth2/authorize?";
    //你自己的APPID
    $APPID = 'wxe50b8b85af03082a';

    //获取openid 的微信服务器地址
    $OPENIDURL = 'https://api.weixin.qq.com/sns/oauth2/access_token?';

    //开发者秘钥
    $SECRET = 'b39d6b56c50a7b9bf03bc80c067a4547';
        //如果已经获取到用户的openId就存储在session中
        if(isset($_SESSION['openid']))
        {
            return $_SESSION['openid'];
        }
        else
        {
            //1.用户访问微信服务器地址 先获取到微信get方式传递过来的code
            //2.根据code获取到openID
            if(! isset($_GET['code']))
            {
                //没有获取到微信返回来的code ，让用户再次访问微信服务器地址

                //redirect_uri 解释
                //跳转地址：你发起请求微信服务器获取code ，
                //微信服务器返回来给你的code的接收地址（通常就是发起支付的页面地址）

                //组装跳转地址
$uri = 'http://mtestapi.57xun.com';
                $redirect_uri = $CODEURL .'appid='.$APPID.'&redirect_uri='.$uri.'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';

//                echo $redirect_uri;

                //跳转 让用过去获取code
                header("location:{$redirect_uri}");
            }
            else
            {
                //调用接口获取openId
                $openidurl = $OPENIDURL.'appid='.$APPID.'&secret='.$SECRET.'&code='.$_GET['code'].'&grant_type=authorization_code';

                //请求获取用户的openID
                $data = file_get_contents($openidurl);
                $arr = json_decode($data,true);
                //获取到的openid保存到session 中
                $_SESSION['openid'] = $arr['openid'];
file_put_contents("test.log",$arr['openid']);
echo $arr['openid'];exit;
                return $_SESSION;
            }
        }

 }
    public function posturl($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        $jsoninfo = json_decode($output, true);
        return $jsoninfo;
    }




    public function wxpay()
{
$order = [
            'out_trade_no' => time(),
            'total_fee' => '1', // **单位：分**
            'body' => 'test bo',
            'openid' => 'o1qR01rd9d70jlklQz7TtuvYHL9M',
        ];

        $pay = Pay::wechat($this->config)->mp($order);
print_r($pay);

}


}

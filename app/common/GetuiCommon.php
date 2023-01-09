<?php
namespace app\common;
use think\facade\Log;


class GetuiCommon
{
    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GetuiCommon();
        }
        return self::$instance;
    }

    private $host = '';
    private $appkey = '';
    private $appid = '';
    private $mastersecret = '';

    private $muahost = '';
    private $muaappkey = '';
    private $muaappid = '';
    private $muamastersecret = '';

    private function init()
    {
        // header("Content-Type: text/html; charset=utf-8");
        $this->appid = config('config.getui.appid');
        $this->appkey = config('config.getui.appkey');
        $this->mastersecret = config('config.getui.mastersecret');
        $this->host = config('config.getui.host');

        $this->muaappid = config('muaconfig.getui.appid');
        $this->muaappkey = config('muaconfig.getui.appkey');
        $this->muamastersecret = config('muaconfig.getui.mastersecret');
        $this->muahost = config('muaconfig.getui.host');

    }

    public function __construct()
    {
        $this->init();
    }

    //单推接口
    function pushMessageToSingle($cid, $type, $content = '', $source){
        $target = new \IGtTarget();
        if($source == 'mua'){
            $igt = new \IGeTui($this->muahost,$this->muaappkey,$this->muamastersecret);
            $target->set_appId($this->muaappid);
            $template = $this->IGtTransmissionTemplate1('',$content,'',$type);  // 穿透消息模板
        }else{
            $igt = new \IGeTui($this->host,$this->appkey,$this->mastersecret);
            $target->set_appId($this->appid);
            $template = $this->IGtTransmissionTemplate('',$content,'',$type);  // 穿透消息模板
        }

        //定义"SingleMessage"
        $message = new \IGtSingleMessage();
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600*12*1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        $message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，2为4G/3G/2G，1为wifi推送，0为不限制推送
        //接收方
        $target->set_alias($cid);      //设置别名
        Log::record("个推日志用户ID-----". json_encode($cid), "info" );

        try {
            $rep = $igt->pushMessageToSingle($message, $target);
            Log::record("个推日志开始-----". time(), "info" );
            Log::record("个推日志-----". json_encode($rep), "info" );
            Log::record("个推日志结束-----".time(), "info" );
            return $rep;
        }catch(RequestException $e){
            $requstId =$e.getRequestId();
            //失败时重发
            $rep = $igt->pushMessageToSingle($message, $target,$requstId);
            return $rep;
        }
    }

    //群推接口案例
    public function pushMessageToList($cid, $type){
        $igt = new \IGeTui($this->host,$this->appkey,$this->mastersecret);
        $template = $this->IGtTransmissionTemplate('','','',$type);   //透传消息模板
        //定义"ListMessage"信息体
        $message = new \IGtListMessage();
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600*12*1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        $message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送，在wifi条件下能帮用户充分节省流量
        $contentId = $igt->getContentId($message);
        $target = new \IGtTarget();
        $targetList = [];
        foreach($cid as $k=>$v)
        {
            $target->set_appId($this->appid);
            $target->set_alias($v);     //设置别名
            $targetList[] = $target;
        }
        $rep = $igt->pushMessageToList($contentId, $targetList);
        Log::record("个推日志-----". json_encode($rep), "info" );
        return $rep;
    }

    //推向所有绑定用户
    public function pushMessageToApp($title, $content) {
        $igt = new \IGeTui($this->host,$this->appkey,$this->mastersecret);
        $template = $this->IGtTransmissionTemplate($title,$content,'',0);
        //个推信息体
        //基于应用消息体
        $message = new \IGtAppMessage();
        $message->set_isOffline(true);
//        $message->setPushTime(201909021050);//在用户设定的时间点进行推送，格式为年月日时分        (定时推送参数)
        $message->set_speed(100);  //定速推送,设置setSpeed为100，则全量送时个推控制下发速度在100条/秒左右。
        $message->set_PushNetWorkType(0);  //设置是否根据WIFI推送消息，2为4G/3G/2G，1为wifi推送，0为不限制推送，在wifi条件下能帮用户充分节省流量
        $message->set_offlineExpireTime(10 * 60 * 1000);  //离线时间单位为毫秒，例，两个小时离线为3600*1000*2
        $message->set_data($template);

        $appIdList=array($this->appid);
        $phoneTypeList=array('ANDROID','IOS');

        $cdt = new \AppConditions();
        $cdt->addCondition3('phoneType', $phoneTypeList);
//        $cdt->addCondition3(AppConditions::REGION, $provinceList);
//        $cdt->addCondition3(AppConditions::TAG, $tagList);

        $message->set_appIdList($appIdList);
        $message->set_conditions($cdt);

        $rep = $igt->pushMessageToApp($message);

        return $rep;
    }

    public function pushMessageToApp2($title, $content) {
        $igt = new \IGeTui($this->muahost,$this->muaappkey,$this->muamastersecret);
        $template = $this->IGtTransmissionTemplate1($title,$content,'',0);
        //个推信息体
        //基于应用消息体
        $message = new \IGtAppMessage();
        $message->set_isOffline(true);
//        $message->setPushTime(201909021050);//在用户设定的时间点进行推送，格式为年月日时分        (定时推送参数)
        $message->set_speed(100);  //定速推送,设置setSpeed为100，则全量送时个推控制下发速度在100条/秒左右。
        $message->set_PushNetWorkType(0);  //设置是否根据WIFI推送消息，2为4G/3G/2G，1为wifi推送，0为不限制推送，在wifi条件下能帮用户充分节省流量
        $message->set_offlineExpireTime(10 * 60 * 1000);  //离线时间单位为毫秒，例，两个小时离线为3600*1000*2
        $message->set_data($template);

        $appIdList=array($this->muaappid);
        $phoneTypeList=array('ANDROID','IOS');

        $cdt = new \AppConditions();
        $cdt->addCondition3('phoneType', $phoneTypeList);
//        $cdt->addCondition3(AppConditions::REGION, $provinceList);
//        $cdt->addCondition3(AppConditions::TAG, $tagList);

        $message->set_appIdList($appIdList);
        $message->set_conditions($cdt);

        $rep = $igt->pushMessageToApp($message);

        return $rep;
    }


    //穿透消息模板   type   0 私聊消息评论点赞。。。。   1 封禁账号
    public function IGtTransmissionTemplate($title = '', $content = '', $payload = '', $type = 0){
        $template =  new \IGtTransmissionTemplate();
        $template->set_appId($this->appid); //应用appid
        $template->set_appkey($this->appkey); //应用appkey
        //透传消息类型
        $template->set_transmissionType(2);     //收到消息是否立即启动应用，1为立即启动（不推荐使用，影响客户体验），2则广播等待客户端自启动
        $payload = [
            'title' => $title,
            'content' => $content,
            'payload' => $payload,
            'type' => $type
        ];
        //透传内容
        $template->set_transmissionContent(json_encode($payload));

        //ios
        $apn = new \IGtAPNPayload();
        $alertmsg=new \DictionaryAlertMsg();
        $alertmsg->body= "";
        $alertmsg->actionLocKey="ActionLockey";
        $alertmsg->locKey="LocKey";
        $alertmsg->locArgs=array("locargs");
        $alertmsg->launchImage="launchimage";
        //  IOS8.2 支持
        $alertmsg->title=$title;
        $alertmsg->titleLocKey="";
        $alertmsg->titleLocArgs=array("TitleLocArg");
        $alertmsg->subtitle="";
        $alertmsg->subtitleLocKey="subtitleLocKey";
        $alertmsg->subtitleLocArgs=array("subtitleLocArgs");

//        $apn->alertMsg=$alertmsg;
        $apn->badge=0;
        $apn->sound="";
        $apn->add_customMsg("payload","payload");
        //设置语音播报类型，int类型，0.不可用 1.播放body 2.播放自定义文本
        $apn->voicePlayType = 0;
        //设置语音播报内容，String类型，非必须参数，用户自定义播放内容，仅在voicePlayMessage=2时生效
        //注：当"定义类型"=2, "定义内容"为空时则忽略不播放
        $apn->voicePlayMessage = "定义内容";
        $apn->contentAvailable=0;
        $apn->category="ACTIONABLE";
        $template->set_apnInfo($apn);
        return $template;
    }

    //穿透消息模板   type   0 私聊消息评论点赞。。。。   1 封禁账号
    public function IGtTransmissionTemplate1($title = '', $content = '', $payload = '', $type = 0){
        $template =  new \IGtTransmissionTemplate();
        $template->set_appId($this->muaappid); //应用appid
        $template->set_appkey($this->muaappkey); //应用appkey
        //透传消息类型
        $template->set_transmissionType(2);     //收到消息是否立即启动应用，1为立即启动（不推荐使用，影响客户体验），2则广播等待客户端自启动
        $payload = [
            'title' => $title,
            'content' => $content,
            'payload' => $payload,
            'type' => $type
        ];
        //透传内容
        $template->set_transmissionContent(json_encode($payload));

        //ios
        $apn = new \IGtAPNPayload();
        $alertmsg=new \DictionaryAlertMsg();
        $alertmsg->body= "";
        $alertmsg->actionLocKey="ActionLockey";
        $alertmsg->locKey="LocKey";
        $alertmsg->locArgs=array("locargs");
        $alertmsg->launchImage="launchimage";
        //  IOS8.2 支持
        $alertmsg->title=$title;
        $alertmsg->titleLocKey="";
        $alertmsg->titleLocArgs=array("TitleLocArg");
        $alertmsg->subtitle="";
        $alertmsg->subtitleLocKey="subtitleLocKey";
        $alertmsg->subtitleLocArgs=array("subtitleLocArgs");

//        $apn->alertMsg=$alertmsg;
        $apn->badge=0;
        $apn->sound="";
        $apn->add_customMsg("payload","payload");
        //设置语音播报类型，int类型，0.不可用 1.播放body 2.播放自定义文本
        $apn->voicePlayType = 0;
        //设置语音播报内容，String类型，非必须参数，用户自定义播放内容，仅在voicePlayMessage=2时生效
        //注：当"定义类型"=2, "定义内容"为空时则忽略不播放
        $apn->voicePlayMessage = "定义内容";
        $apn->contentAvailable=0;
        $apn->category="ACTIONABLE";
        $template->set_apnInfo($apn);
        return $template;
    }

    //通知栏显示 点击跳转url
    function IGtLinkTemplateDemo($title, $content, $logo, $url){
        $template =  new \IGtLinkTemplate();
        $template ->set_appId($this->appid);//应用appid
        $template ->set_appkey($this->appkey);//应用appkey
        $template ->set_title($title);//通知栏标题
        $template ->set_text($content);//通知栏内容
        $template ->set_logo($logo);//通知栏logo
        $template ->set_isRing(true);//是否响铃
        $template ->set_isVibrate(true);//是否震动
        $template ->set_isClearable(true);//通知栏是否可清除
        $template ->set_url($url);//打开连接地址
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息

//        $apn = new \IGtAPNPayload();
//        $apn->alertMsg = "alertMsg";
//        $apn->badge = 11;
//        $apn->actionLocKey = "启动";
//        $apn->category = "ACTIONABLE";
//        $apn->contentAvailable = 0;
//        $apn->locKey = $content;
//        $apn->title = $title;
//        $apn->titleLocArgs = array("titleLocArgs");
//        $apn->titleLocKey = $title;
//        $apn->body = "body";
//        $apn->customMsg = array("payload"=>"payload");
//        $apn->launchImage = "launchImage";
//        $apn->locArgs = array("locArgs");
//        $apn->sound=("test1.wav");;
//        $template->set_apnInfo($apn);
        return $template;
    }

    //通知栏消息 (通知栏显示 点击启动应用)
    function IGtNotificationTemplateDemo($trans_content, $title, $content, $logo){
        $template =  new \IGtNotificationTemplate();
        $template->set_appId($this->appid); //应用appid
        $template->set_appkey($this->appkey); //应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($trans_content);//透传内容
        $template->set_title($title);//通知栏标题
        $template->set_text($content);//通知栏内容
        $template->set_logo($logo);//通知栏logo
        $template->set_isRing(true);//是否响铃
        $template->set_isVibrate(true);//是否震动
        $template->set_isClearable(true);//通知栏是否可清除
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息

        //ios
        $apn = new \IGtAPNPayload();
        $alertmsg=new \DictionaryAlertMsg();
        $alertmsg->body= $content;
        $alertmsg->actionLocKey="ActionLockey";
        $alertmsg->locKey="LocKey";
        $alertmsg->locArgs=array("locargs");
        $alertmsg->launchImage="launchimage";
        //  IOS8.2 支持
        $alertmsg->title=$title;
        $alertmsg->titleLocKey="TitleLocKey";
        $alertmsg->titleLocArgs=array("TitleLocArg");
        $alertmsg->subtitle="subtitle";
        $alertmsg->subtitleLocKey="subtitleLocKey";
        $alertmsg->subtitleLocArgs=array("subtitleLocArgs");

//        $apn->alertMsg=$alertmsg;
        $apn->badge=0;
        $apn->sound="";
        $apn->add_customMsg("payload","payload");
        //设置语音播报类型，int类型，0.不可用 1.播放body 2.播放自定义文本
        $apn->voicePlayType = 0;
        //设置语音播报内容，String类型，非必须参数，用户自定义播放内容，仅在voicePlayMessage=2时生效
        //注：当"定义类型"=2, "定义内容"为空时则忽略不播放
        $apn->voicePlayMessage = "定义内容";
        $apn->contentAvailable=0;
        $apn->category="ACTIONABLE";
        $template->set_apnInfo($apn);


        return $template;
    }
}
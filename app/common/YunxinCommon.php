<?php
/**
 * 网易云类
 **/
namespace app\common;
use think\App;
use think\facade\Log;

class YunxinCommon
{
    private $Nonce;                    //随机数（最大长度128个字符）
    private $CurTime;                //当前UTC时间戳，从1970年1月1日0点0 分0 秒开始到现在的秒数(String)
    private $CheckSum;                //SHA1(AppSecret + Nonce + CurTime),三个参数拼接的字符串，进行SHA1哈希计算，转化成16进制字符(String，小写)
    const   HEX_DIGITS = "0123456789abcdef";

    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new YunxinCommon();
        }
        return self::$instance;
    }

    /**
     * 参数初始化
     * @param $AppKey
     * @param $AppSecret
     */
    public function __construct()
    {
        $this->AppKey      = config('config.yunxin.Appkey');       //appkey
        $this->AppSecret   = config('config.yunxin.Appsecret');       //appsecret
    }

    /**
     * API checksum校验生成
     * @param  void
     * @return $CheckSum(对象私有属性)
     */
    public function checkSumBuilder()
    {
        //此部分生成随机字符串
        $hex_digits = self::HEX_DIGITS;
        $this->Nonce;
        for ($i = 0; $i < 128; $i++) {            //随机字符串最大128个字符，也可以小于该数
            $this->Nonce .= $hex_digits[rand(0, 15)];
        }
        $this->CurTime = (string)(time());    //当前时间戳，以秒为单位

        $join_string    = $this->AppSecret . $this->Nonce . $this->CurTime;
        $this->CheckSum = sha1($join_string);
        //print_r($this->CheckSum);
    }

    /**
     * 将json字符串转化成php数组
     * @param  $json_str
     * @return $json_arr
     */
    public function json_to_array($json_str)
    {
        if (is_array($json_str) || is_object($json_str)) {
            $json_str = $json_str;
        } else if (is_null(json_decode($json_str))) {
            $json_str = $json_str;
        } else {
            $json_str = strval($json_str);
            $json_str = json_decode($json_str, true);
        }
        $json_arr = array();
        foreach ($json_str as $k => $w) {
            if (is_object($w)) {
                $json_arr[$k] = $this->json_to_array($w); //判断类型是不是object
            } else if (is_array($w)) {
                $json_arr[$k] = $this->json_to_array($w);
            } else {
                $json_arr[$k] = $w;
            }
        }
        return $json_arr;
    }

    /**
     * 使用CURL方式发送post请求
     * @param  $url [请求地址]
     * @param  $data [array格式数据]
     * @return $请求返回结果(array)
     */
    public function postDataCurl($url, $data)
    {
        $this->checkSumBuilder();       //发送请求前需先生成checkSum

        $timeout     = 5000;
        $http_header = array(
            'AppKey:' . $this->AppKey,
            'Nonce:' . $this->Nonce,
            'CurTime:' . $this->CurTime,
            'CheckSum:' . $this->CheckSum,
            'Content-Type:application/x-www-form-urlencoded;charset=utf-8'
        );
        $postdataArray = array();
        foreach ($data as $key => $value) {
            array_push($postdataArray, $key . '=' . urlencode($value));
            // $postdata.= ($key.'='.urlencode($value).'&');
        }
        $postdata = join('&', $postdataArray);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (false === $result) {
            $result = curl_errno($ch);
        }
        curl_close($ch);
        return $this->json_to_array($result);
    }
    /**
     * 创建云信ID
     * 1.第三方帐号导入到云信平台；
     * 2.注意accid，name长度以及考虑管理秘钥token
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $name [云信ID昵称，最大长度64字节，用来PUSH推送时显示的昵称]
     * @param  $props [json属性，第三方可选填，最大长度1024字节]
     * @param  $icon [云信ID头像URL，第三方可选填，最大长度1024]
     * @param  $token [云信ID可以指定登录token值，最大长度128字节，并更新，如果未指定，会自动生成token，并在创建成功后返回]
     * @return $result    [返回array数组对象]
     */
    public function createUserId($accid, $name = '', $props = '{}', $icon = '', $token = '')
    {
        $url  = 'https://api.netease.im/nimserver/user/create.action';
        $data = array(
            'accid' => $accid,
            'name'  => $name,
            'props' => $props,
            'icon'  => $icon,
            'token' => $token
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }


    /**
     * 更新云信ID
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $name [云信ID昵称，最大长度64字节，用来PUSH推送时显示的昵称]
     * @param  $props [json属性，第三方可选填，最大长度1024字节]
     * @param  $token [云信ID可以指定登录token值，最大长度128字节，并更新，如果未指定，会自动生成token，并在创建成功后返回]
     * @return $result    [返回array数组对象]
     */
    public function updateUserId($accid, $name = '', $props = '{}', $token = '')
    {
        $url  = 'https://api.netease.im/nimserver/user/update.action';
        $data = array(
            'accid' => $accid,
            'name'  => $name,
            'props' => $props,
            'token' => $token
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 更新并获取新token
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result    [返回array数组对象]
     */
    public function updateUserToken($accid)
    {
        $url  = 'https://api.netease.im/nimserver/user/refreshToken.action';
        $data = array(
            'accid' => $accid
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 封禁云信ID
     * 第三方禁用某个云信ID的IM功能,封禁云信ID后，此ID将不能登陆云信imserver
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result    [返回array数组对象]
     */
    public function blockUserId($accid)
    {
        $url  = 'https://api.netease.im/nimserver/user/block.action';
        $data = array(
            'accid' => $accid
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 解禁云信ID
     * 第三方禁用某个云信ID的IM功能,封禁云信ID后，此ID将不能登陆云信imserver
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result    [返回array数组对象]
     */
    public function unblockUserId($accid)
    {
        $url  = 'https://api.netease.im/nimserver/user/unblock.action';
        $data = array(
            'accid' => $accid
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }


    /**
     * 更新用户名片
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $name [云信ID昵称，最大长度64字节，用来PUSH推送时显示的昵称]
     * @param  $icon [用户icon，最大长度256字节]
     * @param  $sign [用户签名，最大长度256字节]
     * @param  $email [用户email，最大长度64字节]
     * @param  $birth [用户生日，最大长度16字节]
     * @param  $mobile [用户mobile，最大长度32字节]
     * @param  $ex [用户名片扩展字段，最大长度1024字节，用户可自行扩展，建议封装成JSON字符串]
     * @param  $gender [用户性别，0表示未知，1表示男，2女表示女，其它会报参数错误]
     * @return $result      [返回array数组对象]
     */
    public function updateUinfo($accid, $name = '', $icon = '', $sign = '', $email = '', $birth = '', $mobile = '', $gender = '0', $ex = '')
    {
        $url  = 'https://api.netease.im/nimserver/user/updateUinfo.action';
        $data = array(
            'accid'  => $accid,
            'name'   => $name,
            'icon'   => $icon,
            'sign'   => $sign,
            'email'  => $email,
            'birth'  => $birth,
            'mobile' => $mobile,
            'gender' => $gender,
            'ex'     => $ex
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 获取用户名片，可批量
     * @param  $accids [用户帐号（例如：JSONArray对应的accid串，如："zhangsan"，如果解析出错，会报414）（一次查询最多为200）]
     * @return $result    [返回array数组对象]
     */
    public function getUinfos($accids)
    {
        $url  = 'https://api.netease.im/nimserver/user/getUinfos.action';
        $data = array(
            'accids' => json_encode($accids)
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 好友关系-加好友
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $faccid [云信ID昵称，最大长度64字节，用来PUSH推送时显示的昵称]
     * @param  $type [用户type，最大长度256字节]
     * @param  $msg [用户签名，最大长度256字节]
     * @return $result      [返回array数组对象]
     */
    public function addFriend($accid, $faccid, $type = '1', $msg = '')
    {
        $url  = 'https://api.netease.im/nimserver/friend/add.action';
        $data = array(
            'accid'  => $accid,
            'faccid' => $faccid,
            'type'   => $type,
            'msg'    => $msg
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 好友关系-更新好友信息
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $faccid [要修改朋友的accid]
     * @param  $alias [给好友增加备注名]
     * @return $result      [返回array数组对象]
     */
    public function updateFriend($accid, $faccid, $alias)
    {
        $url  = 'https://api.netease.im/nimserver/friend/update.action';
        $data = array(
            'accid'  => $accid,
            'faccid' => $faccid,
            'alias'  => $alias
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 好友关系-获取好友关系
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result      [返回array数组对象]
     */
    public function getFriend($accid)
    {
        $url  = 'https://api.netease.im/nimserver/friend/get.action';
        $data = array(
            'accid'      => $accid,
            'createtime' => (string)(time() * 1000)
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 好友关系-删除好友信息
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $faccid [要修改朋友的accid]
     * @return $result      [返回array数组对象]
     */
    public function deleteFriend($accid, $faccid)
    {
        $url  = 'https://api.netease.im/nimserver/friend/delete.action';
        $data = array(
            'accid'  => $accid,
            'faccid' => $faccid
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 好友关系-设置黑名单
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @param  $targetAcc [被加黑或加静音的帐号]
     * @param  $relationType [本次操作的关系类型,1:黑名单操作，2:静音列表操作]
     * @param  $value [操作值，0:取消黑名单或静音；1:加入黑名单或静音]
     * @return $result      [返回array数组对象]
     */
    public function specializeFriend($accid, $targetAcc, $relationType = '1', $value = '1')
    {
        $url  = 'https://api.netease.im/nimserver/user/setSpecialRelation.action';
        $data = array(
            'accid'        => $accid,
            'targetAcc'    => $targetAcc,
            'relationType' => $relationType,
            'value'        => $value
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 好友关系-查看黑名单列表
     * @param  $accid [云信ID，最大长度32字节，必须保证一个APP内唯一（只允许字母、数字、半角下划线_、@、半角点以及半角-组成，不区分大小写，会统一小写处理）]
     * @return $result      [返回array数组对象]
     */
    public function listBlackFriend($accid)
    {
        $url  = 'https://api.netease.im/nimserver/user/listBlackAndMuteList.action';
        $data = array(
            'accid' => $accid
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 消息功能-发送普通消息
     * @param  $from [发送者accid，用户帐号，最大32字节，APP内唯一]
     * @param  $ope [0：点对点个人消息，1：群消息，其他返回414]
     * @param  $to [ope==0是表示accid，ope==1表示tid]
     * @param  $type [0 表示文本消息,1 表示图片，2 表示语音，3 表示视频，4 表示地理位置信息，6 表示文件，100 自定义消息类型]
     * @param  $body [请参考下方消息示例说明中对应消息的body字段。最大长度5000字节，为一个json字段。]
     * @param  $option [发消息时特殊指定的行为选项,Json格式，可用于指定消息的漫游，存云端历史，发送方多端同步，推送，消息抄送等特殊行为;option中字段不填时表示默认值]
     * @param  $pushcontent [推送内容，发送消息（文本消息除外，type=0），option选项中允许推送（push=true），此字段可以指定推送内容。 最长200字节]
     * @return $result      [返回array数组对象]
     */
    public function sendMsg($from, $ope, $to, $type, $body, $option = array("push" => false, "roam" => true, "history" => false, "sendersync" => true, "route" => false), $pushcontent = '')
    {
        $url  = 'https://api.netease.im/nimserver/msg/sendMsg.action';
        $data = array(
            'from'        => $from,
            'ope'         => $ope,
            'to'          => $to,
            'type'        => $type,
            'body'        => json_encode($body),
            'option'      => json_encode($option),
            'pushcontent' => $pushcontent
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 消息功能-发送自定义系统消息
     * 1.自定义系统通知区别于普通消息，方便开发者进行业务逻辑的通知。
     * 2.目前支持两种类型：点对点类型和群类型（仅限高级群），根据msgType有所区别。
     * @param  $from [发送者accid，用户帐号，最大32字节，APP内唯一]
     * @param  $msgtype [0：点对点个人消息，1：群消息，其他返回414]
     * @param  $to [msgtype==0是表示accid，msgtype==1表示tid]
     * @param  $attach [自定义通知内容，第三方组装的字符串，建议是JSON串，最大长度1024字节]
     * @param  $pushcontent [ios推送内容，第三方自己组装的推送内容，如果此属性为空串，自定义通知将不会有推送（pushcontent + payload不能超过200字节）]
     * @param  $payload [ios 推送对应的payload,必须是JSON（pushcontent + payload不能超过200字节）]
     * @param  $sound [如果有指定推送，此属性指定为客户端本地的声音文件名，长度不要超过30个字节，如果不指定，会使用默认声音]
     * @return $result      [返回array数组对象]
     */
    public function sendAttachMsg($from, $msgtype, $to, $attach, $pushcontent = '', $payload = array(), $sound = '')
    {
        $url  = 'https://api.netease.im/nimserver/msg/sendAttachMsg.action';
        $data = array(
            'from'        => $from,
            'msgtype'     => $msgtype,
            'to'          => $to,
            'attach'      => $attach,
            'pushcontent' => $pushcontent,
            'payload'     => json_encode($payload),
            'sound'       => $sound
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 消息功能-文件上传
     * @param  $content [字节流base64串(Base64.encode(bytes)) ，最大15M的字节流]
     * @param  $type [上传文件类型]
     * @return $result      [返回array数组对象]
     */
    public function uploadMsg($content, $type = '0')
    {
        $url  = 'https://api.netease.im/nimserver/msg/upload.action';
        $data = array(
            'content' => $content,
            'type'    => $type
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 消息功能-文件上传（multipart方式）
     * @param  $content [字节流base64串(Base64.encode(bytes)) ，最大15M的字节流]
     * @param  $type [上传文件类型]
     * @return $result      [返回array数组对象]
     */
    public function uploadMultiMsg($content, $type = '0')
    {
        $url  = 'https://api.netease.im/nimserver/msg/fileUpload.action';
        $data = array(
            'content' => $content,
            'type'    => $type
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }

    /**
     * 历史记录-单聊
     * @param  $from [发送者accid]
     * @param  $to [接收者accid]
     * @param  $begintime [开始时间，ms]
     * @param  $endtime [截止时间，ms]
     * @param  $limit [本次查询的消息条数上限(最多100条),小于等于0，或者大于100，会提示参数错误]
     * @param  $reverse [1按时间正序排列，2按时间降序排列。其它返回参数414.默认是按降序排列。]
     * @return $result      [返回array数组对象]
     */
    public function querySessionMsg($from, $to, $begintime, $endtime = '', $limit = '100', $reverse = '1')
    {
        $url  = 'https://api.netease.im/nimserver/history/querySessionMsg.action';
        $data = array(
            'from'      => $from,
            'to'        => $to,
            'begintime' => $begintime,
            'endtime'   => $endtime,
            'limit'     => $limit,
            'reverse'   => $reverse
        );
        $result = $this->postDataCurl1($url, $data);
        return $result;
    }

    /**
     * 历史记录-群聊
     * @param  $tid [群id]
     * @param  $accid [查询用户对应的accid.]
     * @param  $begintime [开始时间，ms]
     * @param  $endtime [截止时间，ms]
     * @param  $limit [本次查询的消息条数上限(最多100条),小于等于0，或者大于100，会提示参数错误]
     * @param  $reverse [1按时间正序排列，2按时间降序排列。其它返回参数414.默认是按降序排列。]
     * @return $result      [返回array数组对象]
     */
    public function queryGroupMsg($tid, $accid, $begintime, $endtime = '', $limit = '100', $reverse = '1')
    {
        $url  = 'https://api.netease.im/nimserver/history/queryTeamMsg.action';
        $data = array(
            'tid'       => $tid,
            'accid'     => $accid,
            'begintime' => $begintime,
            'endtime'   => $endtime,
            'limit'     => $limit,
            'reverse'   => $reverse
        );
        $result = $this->postDataCurl($url, $data);
        return $result;
    }


    /**
     * 使用CURL方式发送post请求
     * @param  $url [请求地址]
     * @param  $data [array格式数据]
     * @return $请求返回结果(array)
     */
    public function postDataCurl1($url, $data)
    {
        $this->checkSumBuilder();       //发送请求前需先生成checkSum

        $timeout     = 5000;
        $http_header = array(
            'AppKey:' . $this->AppKey,
            'Nonce:' . $this->Nonce,
            'CurTime:' . $this->CurTime,
            'CheckSum:' . $this->CheckSum,
            'Content-Type:application/x-www-form-urlencoded;charset=utf-8'
        );
        $postdataArray = array();
        foreach ($data as $key => $value) {
            array_push($postdataArray, $key . '=' . urlencode($value));
            // $postdata.= ($key.'='.urlencode($value).'&');
        }
        $postdata = join('&', $postdataArray);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //处理http证书问题
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if (false === $result) {
            $result = curl_errno($ch);
        }
        curl_close($ch);
        return $result;
//        return $this->json_to_array($result);
    }

}
<?php


use think\facade\Route;

// Route::get('think', function () {
//     return 'hello,ThinkPHP6!';
// });

Route::group('v1', function () {
    //MQTT接口
    Route::post('getmqtttoken','v1.MqttTokenController/getMqttToken');//获取MQtoken
    // Route::post('getp2ptoken','v1.MqttTokenController/getp2pToken');//获取P2PMQtoken
    Route::post('setmqtttoken','v1.MqttTokenController/setMqttToken');//更新MQtoken

    Route::post('setp2ptoken','v1.MqttTokenController/setp2pToken');//更新p2pMQtoken
    Route::post('setptoken','v1.MqttTokenController/setp2pToken');//更新p2pMQtoken

    Route::post('verifymqtttoken','v1.MqttTokenController/verifyToken');//验证MQtoken
    Route::post('delmqtttoken','v1.MqttTokenController/revokeToken');//撤销MQtoken

    Route::post('sendmsg','v1.MqttMessageController/sendMsg');//发送消息
    // Route::post('receivemsg','v1.MqttMessageController/receiveMsg');//发送消息
    Route::post('sendpmsg','v1.MqttMessageController/p2pMsg');//p2p消息


    Route::post('addcoin','v1.CoinController/addcoin');//加豆
    Route::post('setdaim','v1.CoinController/setdaim');//减钻

    Route::get('initList','v1.InitDataController/initList');    //初始化广告接口

    //用户相关
    Route::post('login','v1.MemberController/login');    //用户登录接口
    Route::post('register','v1.MemberController/register');    //用户注册接口
    Route::get('autologin','v1.MemberController/autologin');    //用户自动登录接口
    Route::post('editUser','v1.MemberController/edit');    //用户修改信息接口

    //动态相关
    Route::get('forumTagList','v1.ForumController/forumTagList');    //动态话题列表接口

    //房间相关
    Route::get('roomList','v1.LanguageroomController/roomList');    //首页房间列表接口
    Route::post('createroom','v1.LanguageroomController/CreateRoom');//创建房间
    Route::get('taglist','v1.LanguageroomController/createRoomTagList');//房间标签列表
    Route::get('followlist','v1.RoomFollowController/followList');  //用户关注房间列表
    Route::post('attentionroom','v1.RoomFollowController/attentionRoom');  //用户关注房间
    Route::get('removeattentionroom','v1.RoomFollowController/removeRoom');  //用户取消关注房间
    Route::get('roomdetails','v1.LanguageroomController/RoomDetails');  //房间详情

    //支付接口
    Route::post('apppay','v1.PayController/AppPay');//app支付
    Route::post('paymentnotify','v1.PayController/AppAlipayNotify');//app支付宝异步回调
    Route::post('apppay','v1.PayController/AppAlipayReturn');//app支付宝同步
    Route::rule('wxp','v1.WxwbPayController/weixinPay');         //微信支付
    Route::rule('wxpno','v1.WxwbPayController/wxWebNotify');     //支付回调

    //猜拳礼物
    Route::get('cqgift','v1.GiftController/cqGift');

    //活动相关
    Route::get('activelist','v1.InitDataController/activeList');    //活动接口

    //用户黑名单
    Route::rule('userBlack','v1.BlackDataController/userBlack');

});
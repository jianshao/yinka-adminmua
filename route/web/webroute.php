<?php

use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('rankroom', 'RankroomController/RoomRankList'); //首页排行榜房间排序
Route::get('indexPlatform', 'AppAwakenController/indexPlatform');
Route::get('zhindex', 'AppAwakenController/indexPlatformzh');

//年度盛典活动
Route::rule('ranklists', 'GeshouController/rankList'); //排行榜首页
Route::get('richlist', 'AnnualController/richList'); //年度用户消费排序
Route::get('roomdaylist', 'AnnualController/roomDayList'); //年度房间日榜排序
Route::get('roomlist', 'AnnualController/roomList'); //年度房间总榜排序
Route::get('getoptions', 'ShengyouController/getOptions'); //报名选项
Route::post('signup', 'ShengyouController/signup'); //报名提交
Route::get('syrank', 'ShengyouController/syRank'); //声优排行榜
Route::get('gsrank', 'GeshouController/gsRank'); //歌手排行榜
Route::get('userinfo', 'GeshouController/userInfo'); //是否报过名信息
Route::get('racelist', 'GeshouController/racelist'); //当前时间在某个赛道接口

Route::group('webUserWithdrawal', function () {
    Route::get('webUserWithdrawalCodeCheck', 'webUserWithdrawal.WebMemberController/webUserWithdrawalCodeCheck'); //web 用户提现获取验证码
    Route::post('webUserWithdrawalLogin', 'webUserWithdrawal.WebMemberController/webUserWithdrawalLogin'); //web 用户提现登录
    Route::get('webUserWithdrawalItem', 'webUserWithdrawal.WebMemberController/webUserWithdrawalItem'); //web 用户提现信息
    Route::get('webUserWithdrawalLists', 'webUserWithdrawal.WebMemberController/webUserWithdrawalLists'); //web 用户提现明细列表

    // Route::post('webUserWithdrawalOperation', 'webUserWithdrawal.WebMemberController/webUserWithdrawalOperation'); //web 用户提现操作
    // Route::get('withdrawalLogin', 'webUserWithdrawal.WithdrawalController/withdrawalLogin'); //用户提现登录页面
    // Route::get('userWithdrawalOption', 'webUserWithdrawal.WithdrawalController/userWithdrawalOption'); //用户提现选择
    // Route::get('userWithdrawalDetail', 'webUserWithdrawal.WithdrawalController/userWithdrawalDetail'); //用户提现明细
});

//新版
Route::group('webUserWithdraw', function () {
    Route::post('withdrawLogin', 'webUserWithdraw.WithdrawMemberController/withdrawLogin'); //用户提现登录页面
    Route::post('userWithdrawInfo', 'webUserWithdraw.WithdrawMemberController/userWithdrawInfo'); //用户提现信息页
    Route::post('withdrawLists', 'webUserWithdraw.WithdrawMemberController/withdrawLists'); //web 用户提现明细列表
    Route::post('userWithdrawOperation', 'webUserWithdraw.WithdrawMemberController/userWithdrawOperation'); //web 用户提现操作
    Route::post('userAccountBind', 'webUserWithdraw.WithdrawMemberController/userAccountBind'); //web 用户提现操作

})->middleware(app\middleware\CheckLogin::class);

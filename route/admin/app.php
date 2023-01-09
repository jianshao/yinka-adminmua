<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});
Route::get('loginIndex', 'LoginController/index'); //登录页面
Route::post('login', 'LoginController/login'); //登录操作
Route::get('index', 'IndexController/index'); //控制台
Route::get('indexConsole', 'IndexController/indexConsole'); //控制台内容

Route::get('loginOut', 'LoginController/loginOut'); //退出
Route::get('adminUserLists', 'AdminUserController/adminUserLists'); //管理员列表
Route::post('addAdminUser', 'AdminUserController/addAdminUser'); //添加管理员
Route::post('editAdminUserInfo', 'AdminUserController/editAdminUserInfo'); //管理员编辑
Route::post('delAdminUser', 'AdminUserController/delAdminUser'); //管理员删除
Route::get('getRoleLists', 'RoleController/getRoleLists'); //角色管理列表
Route::post('addRole', 'RoleController/addRole'); //添加角色
Route::post('delRole', 'RoleController/delRole'); //删除角色
Route::post('editRoleToMenu', 'RoleController/editRoleToMenu'); //角色分配权限
Route::post('editRole', 'RoleController/editRole'); //角色编辑

Route::get('getMenuLists', 'MenuController/getMenuLists'); //菜单列表
Route::post('addMenuItems', 'MenuController/addMenuItems'); //菜单添加
Route::post('editMenuItems', 'MenuController/editMenuItems'); //菜单编辑
Route::post('delMenuItems', 'MenuController/delMenuItems'); //菜单删除

Route::rule('getForumList', 'ForumController/getForumList'); //动态列表接口
Route::rule('delForum', 'ForumController/delForum'); //删除某个动态接口
Route::rule('replyList', 'ForumController/replyList'); //动态回复评论列表接口
Route::rule('delReply', 'ForumController/delReply'); //删除某个回复评论接口
Route::rule('getForumAuditList', 'ForumController/getForumAuditList'); //动态列表接口
Route::rule('forumAuditYes', 'ForumController/forumAuditYes'); //审核通过某个评论接口
Route::rule('forumAuditNo', 'ForumController/forumAuditNo'); //审核未通过某个评论接口
Route::rule('getForumListByWhere', 'ForumController/getForumListByWhere'); //动态数据

Route::get('getegggift', 'giftController/getegggift'); //查询砸蛋前三名获得礼物
Route::post('editegggift', 'giftController/editegggift'); //配置砸蛋前三名获得礼物
Route::get('giftList', 'giftController/giftList'); //礼物列表
Route::post('addGift', 'giftController/addGift'); //添加礼物
Route::post('exitGift', 'giftController/exitGift'); //修改礼物1
Route::post('clearCache', 'giftController/clearCache'); //清除礼物缓存
Route::post('ossFile', 'giftController/ossFile'); //礼物图片上传OSS

Route::get('caseYinList', 'SmashCase/caseYinList'); //银箱子奖次礼物列表
Route::post('addYinGiftUserAssign', 'SmashCase/addYinGiftUserAssign'); //添加银箱子指定用户绑定指定礼物
Route::get('getYinUserToGiftLists', 'SmashCase/getYinUserToGiftLists'); //银箱子指定用户绑定指定礼物列表
Route::post('delYinUserToGiftAssign', 'SmashCase/delYinUserToGiftAssign'); //取消银箱子指定用户绑定指定礼物

Route::get('caseJinList', 'SmashCase/caseJinList'); //金箱子奖次礼物列表
Route::post('addJinGiftUserAssign', 'SmashCase/addJinGiftUserAssign'); //添加金箱子指定用户绑定指定礼物
Route::get('getJinUserToGiftLists', 'SmashCase/getJinUserToGiftLists'); //金箱子指定用户绑定指定礼物列表
Route::post('delJinUserToGiftAssign', 'SmashCase/delJinUserToGiftAssign'); //取消金箱子指定用户绑定指定礼物

Route::get('caseObtain', 'SmashCase/caseObtain'); //箱子中奖接口

Route::get('roomList', 'RoomController/roomList'); //房间列表
Route::post('addRoomPretty', 'RoomController/addRoomPretty'); //房间列表
Route::get('RandomlyMatchedRoom', 'RoomController/RandomlyMatchedRoom'); //随机推荐房间列表
Route::post('AddRandomlyMatchedRoom', 'RoomController/AddRandomlyMatchedRoom'); //添加随机推荐房间列表
Route::post('delRandomlyMatchedRoom', 'RoomController/delRandomlyMatchedRoom'); //删除随机推荐房间列表
Route::post('editroommsg', 'RoomController/editroommsg'); //配置飘屏
Route::get('getroommsg', 'RoomController/getroommsg'); //查询飘屏
Route::post('editsaymsg', 'RoomController/editsaymsg'); //配置公屏
Route::get('getsaymsg', 'RoomController/getsaymsg'); //查询公屏
Route::get('sendGiftNum', 'RoomController/sendGiftNum'); //查询送礼飘屏
Route::get('sendGiftNumSave', 'RoomController/sendGiftNumSave'); //修改送礼飘屏
Route::post('roomEdit', 'RoomController/exitRoom'); //修改房间
Route::get('typeList', 'RoomController/roomTypeList'); //房间类型接口
Route::post('exitRoomChannel', 'RoomController/exitRoomChannel'); //房间关联渠道修改

Route::post('vsitorExternnumberLists', 'VsitorExternnumberController/vsitorExternnumberLists'); //热度值
Route::post('addRoomVisitorExternnumber', 'VsitorExternnumberController/addRoomVisitorExternnumber'); //新增热度值
Route::post('editRoomVisitorExternnumber', 'VsitorExternnumberController/editRoomVisitorExternnumber'); //修改热度值
Route::post('delRoomVisitorExternnumber', 'VsitorExternnumberController/delRoomVisitorExternnumber'); //删除热度值
Route::post('exitRoomType', 'RoomController/exitRoomType'); //修改房间类型

Route::get('bannerList', 'BannerController/bannerList'); //广告列表
Route::get('bannerOpen', 'BannerController/bannerOpen'); //广告自动化时间
Route::post('bannerAdd', 'BannerController/saveBanner'); //添加广告
Route::post('bannerEdit', 'BannerController/exitBanner'); //修改广告
Route::post('exitBannerImg', 'BannerController/exitBannerImg'); //修改广告图片
Route::post('bannerClear', 'BannerController/clearCache'); //清除广告缓存
Route::post('exitBannerChannel', 'BannerController/exitBannerChannel'); //清除广告缓存

//公会管理
Route::get('guildList', 'GuildController/guildList'); //公会列表信息
Route::post('guild/ossFile', 'GuildController/ossFile'); //公会列表信息
Route::post('guildMember', 'GuildController/guildMember'); //公会成员列表信息
Route::post('insertMember', 'GuildController/insertMember'); //添加公会成员信息
Route::post('addGuilds', 'GuildController/addGuilds'); //添加公会
Route::post('guildEdit', 'GuildController/guildEdit'); //修改公会信息
Route::post('exitMember', 'GuildController/exitMember'); //修改公会成员信息
Route::post('delGuildMember', 'GuildController/delGuildMember'); //删除公会成员信息
Route::post('addGuidRoomIndex', 'GuildController/addGuidRoomIndex'); //添加公会首页房间
Route::post('delGuidRoomIndex', 'GuildController/delGuidRoomIndex'); //取消公会首页房间
Route::get('PremiereTimeAndStream', 'GuildController/PremiereTimeAndStream'); //开播时长与流水
Route::get('roomRunningWaterList', 'GuildController/roomRunningWaterList'); //房间用户流水

Route::get('memberList', 'MemberController/memberList'); //用户列表
Route::get('addUser', 'MemberController/addUser'); //添加虚拟用户
Route::get('addUserCode', 'MemberController/addUserCode'); //用户邀请码添加
Route::get('memberCash', 'MemberController/memberCash'); //用户手机号
Route::get('MemberCashDel', 'MemberController/MemberCashDel'); //删除用户手机号
Route::get('cashOutList', 'MemberController/cashOutList'); //所有用户提现消费列表editMemberNickname
Route::post('memberItem', 'MemberController/memberItem'); //所有用户提现消费列表
Route::post('editMemberPretty', 'MemberController/editMemberPretty'); //用户靓号修改
Route::post('editMemberNickname', 'MemberController/editMemberNickname'); //用户昵称修改
Route::post('editMemberUsername', 'MemberController/editMemberUsername'); //用户用户名修改
Route::post('editMemberIntro', 'MemberController/editMemberIntro'); //用户简介修改
Route::post('editPrettyAvatar', 'MemberController/editPrettyAvatar'); //用户头像地址修改
Route::get('online', 'MemberController/online'); //用户头像地址修改
Route::get('changeMemberInfo', 'MemberController/changeMemberInfo'); //用户头像地址修改
Route::post('memberInfoAgree', 'MemberController/memberInfoAgree'); //用户头像地址修改
Route::post('editMemberAttention', 'MemberController/editMemberAttention'); //用户登录记录

Route::get('userPackList', 'MemberController/userPackList'); //用户装备列表
Route::get('loginUserInfo', 'MemberController/loginUserInfo'); //用户登录数据列表
Route::post('userPackDel', 'MemberController/userPackDel'); //用户装备删除
Route::post('userPackAdorn', 'MemberController/userPackAdorn'); //用户装备佩戴
Route::get('userPackGiveList', 'MemberController/userPackGiveList'); //用户装备赠送列表
// Route::post('userPackGive', 'MemberController/userPackGive'); //用户装备赠送 停用

// Route::get('UserBlack', 'BlackListController/UserBlack'); //三封列表 停用
// Route::get('SaveUserBlack', 'BlackListController/SaveUserBlack'); //三封编辑  停用

Route::get('getUserScore', 'BlackIndustryController/getUserScore'); //防黑产事件列表

Route::get('complaintsList', 'ComplaintsController/complaintsList'); //举报用户列表

Route::get('labelList', 'ForumTopicController/labelList'); //标签列表1
Route::get('topicList', 'ForumTopicController/topicList'); //话题列表1
Route::post('addLabel', 'ForumTopicController/addLabel'); //添加标签1
Route::post('addTopic', 'ForumTopicController/addTopic'); //添加话题1
Route::post('exitLabel', 'ForumTopicController/exitLabel'); //修改标签上下架状态1
Route::post('exitTopicTag', 'ForumTopicController/exitTopicTag'); //修改话题标签1
Route::post('exitTopicNum', 'ForumTopicController/exitTopicNum'); //修改话题序号1
Route::post('exitTopicStatus', 'ForumTopicController/exitTopicStatus'); //修改话题上下架状态1
Route::post('exitTopicHot', 'ForumTopicController/exitTopicHot'); //修改话题是否为热门1

Route::get('noticeList', 'NoticeController/noticeList'); //公告列表
Route::get('publishedList', 'NoticeController/publishedList'); //已发布公告列表
Route::get('unpublishedList', 'NoticeController/unpublishedList'); //待发送公告列表删
Route::get('deletedList', 'NoticeController/deletedList'); //已删除公告列表
Route::post('noticeListOne', 'NoticeController/noticeListOne'); //公告详细信息
Route::post('addNotice', 'NoticeController/addNotice'); //添加定时公告
Route::post('nowAddNotice', 'NoticeController/nowAddNotice'); //添加立即发布公告
Route::post('saveNotice', 'NoticeController/saveNotice'); //保存公告
Route::post('exitNotice', 'NoticeController/exitNotice'); //修改公告
Route::post('delNotice', 'NoticeController/delNotice'); //删除公告
Route::post('recoverNotice', 'NoticeController/recoverNotice'); //恢复公告

Route::post('getCoinDetailReceivingList', 'CoindetailController/getCoinDetailReceivingList'); //收礼记录
Route::post('GetCoinDetailGivingList', 'CoindetailController/GetCoinDetailGivingList'); //送礼记录
Route::post('getMemberMoneyDouList', 'MemberMoneyController/getMemberMoneyDouList'); //充值
Route::post('getMemberGoldList', 'MemberMoneyController/getMemberGoldList'); //充值金币记录
Route::post('addMemberMoneyDou', 'MemberMoneyController/addMemberMoneyDou'); //添加充值
Route::post('addMemberGold', 'MemberMoneyController/addMemberGold'); //添加金币充值
Route::post('getChargeDetailList', 'ChargeDetailController/getChargeDetailList'); //客户端充值记录
Route::post('addMemberMoneyDiamondOne', 'MemberMoneyController/addMemberMoneyDiamondOne'); //管理后台钻石添加
Route::post('addMemberMoneyDiamondTwo', 'MemberMoneyController/addMemberMoneyDiamondTwo'); //管理后台钻石减少
Route::post('getMemberMoneyDiamondOneList', 'MemberMoneyController/getMemberMoneyDiamondOneList'); //管理后台钻石添加列表
Route::post('getMemberMoneyDiamondTwoList', 'MemberMoneyController/getMemberMoneyDiamondTwoList'); //管理后台钻石减少列表

Route::post('getMemberScoreList', 'MemberMoneyController/getMemberScoreList'); //充值
Route::post('addMemberScore', 'MemberMoneyController/addMemberScore'); //添加充值

Route::get('excelDataLists', 'AdmininistrationController/excelDataLists'); //数据管理
Route::get('excelList', 'AdmininistrationController/excelList'); //导出excel

//Route::get('test', 'UploadFileController/test');       //修改公告
Route::rule('uploadIndex', 'UploadFileController/uploadIndex'); //修改公告
Route::rule('getAdminLogsList', 'AdminLogsController/getAdminLogsList'); //日志列表

Route::rule('subcontractingGiftAdminList', 'SubcontractingGiftAdminController/subcontractingGiftAdminList'); //外包礼物兑换管理员列表
Route::rule('addSubcontractingGiftAdmin', 'SubcontractingGiftAdminController/addSubcontractingGiftAdmin'); //外包礼物兑换管理员添加
Route::rule('delSubcontractingGiftAdmin', 'SubcontractingGiftAdminController/delSubcontractingGiftAdmin'); //外包礼物兑换管理员添加
Route::rule('subcontractingGiftAdminItem', 'SubcontractingGiftAdminController/subcontractingGiftAdminItem'); //外包礼物兑换管理员详情

Route::rule('mapIndex', 'MapTestController/mapIndex'); //定位测试

Route::get('monitoringList', 'MonitoringController/monitoringList'); //监控列表
Route::post('exitMonitoring', 'MonitoringController/exitMonitoring'); //修改监控模式
Route::post('noLock', 'MonitoringController/noLock'); //不通过
Route::post('delMonitoring', 'MonitoringController/delMonitoring'); //删除监控模式

//活动管理哦
Route::get('activeList', 'ActiveController/activeList'); //活动列表
Route::post('addActive', 'ActiveController/addActive'); //活动添加
Route::get('activeItems', 'ActiveController/activeItems'); //活动详情
Route::post('exitActive', 'ActiveController/exitActive'); //活动编辑
Route::post('activeStart', 'ActiveController/activeStart'); //开启活动
Route::post('activeStop', 'ActiveController/activeStop'); //结束编辑
Route::get('ListNewTask', 'ActiveController/ListNewTask'); //新手任务列表
Route::post('NewTaskSave', 'ActiveController/NewTaskSave'); //新手任务更新
Route::get('ListDailyTask', 'ActiveController/ListDailyTask'); //每日任务列表
Route::post('DailyTaskSave', 'ActiveController/DailyTaskSave'); //每日任务更新
Route::get('ListSignTask', 'ActiveController/ListSignTask'); //签到任务列表
Route::post('SignTaskSave', 'ActiveController/SignTaskSave'); //签到任务更新
Route::get('ListOftenTasks', 'ActiveController/ListOftenTasks'); //日常任务列表
Route::post('OftenTasksSave', 'ActiveController/OftenTasksSave'); //日常任务更新

Route::get('withdrawalList', 'WithdrawalController/withdrawalList'); //提现列表
Route::get('ghBeanList', 'WithdrawalController/ghBeanList'); //公会钻石充值列表
Route::post('agreeMake', 'WithdrawalController/agreeMake'); //同意放款
Route::post('refuseMake', 'WithdrawalController/refuseMake'); //拒绝放款
Route::post('manMadeTransfer', 'WithdrawalController/manMadeTransfer'); //手动转账成功
Route::get('aliPay', 'AliPayController/aliPay'); //修改监控模式

Route::get('equipList', 'PackController/equipList'); //装备列表
Route::post('equipAdd', 'PackController/equipAdd'); //添加装备
Route::post('equipExid', 'PackController/equipExid'); //修改装备
Route::get('equipDetails', 'PackController/equipDetails'); //装备详情
Route::post('equipExidProp', 'PackController/equipExidProp'); //修改装备配置
Route::post('equipAddProp', 'PackController/equipAddProp'); //添加装备配置

Route::get('dataManagementIndex', 'DataManagement/indexNew'); //数据管理列表
Route::get('dataManagement/indexNew', 'DataManagement/indexNew'); //数据管理列表
Route::get('data/userPayStats', 'DataManagement/userPayStats'); //数据管理列表

Route::get('sendAllGifts/sendGiftToAllMembers', 'SendGiftToAllController/sendGiftToAllMembers'); //全麦送礼
Route::get('sendAllGifts/sendGiftToAllMembersDetail', 'SendGiftToAllController/sendGiftToAllMembersDetail'); //全麦送礼详情
Route::get('sendAllGifts/sendGiftToAllMembersByDay', 'SendGiftToAllController/sendGiftToAllMembersByDay'); //全麦送礼详情

Route::get('UsersRetained', 'DataManagement/UsersRetained'); //用户留存
Route::get('UsersRetainedDetails', 'DataManagement/UsersRetainedDetails'); //用户留存详情
Route::get('delete', 'DataManagement/delete'); //删除数据
Route::get('delshow', 'DataManagement/delshow'); //删除数据
Route::get('dataSave', 'DataManagement/dataSave'); //修改数据
Route::get('dataSaveShow', 'DataManagement/dataSaveShow'); //修改数据展示
Route::get('ReYun', 'DataManagement/ReYun'); //热云数据列表
Route::get('ReYunlist', 'DataManagement/ReYunlist'); //热云详情数据列表
Route::get('SpreadName', 'DataManagement/SpreadName'); //热云渠道详情数据列表
Route::get('channel', 'DataManagement/channel'); //渠道数据列表
Route::get('channelPay', 'DataManagement/channelPay'); //渠道付费用户列表
Route::get('channellist2', 'DataManagement/channellist2'); //渠道详情数据列表
Route::get('leave', 'DataManagement/leave'); //渠道用户留存
Route::get('userPay', 'DataManagement/userPay'); //充值人数
Route::get('userPayBeancredit', 'DataManagement/userPayBeancredit'); //工会充值人数
Route::get('selectUserDetail', 'FinanceController/selectUserDetail'); //财务= 用户每日明细查看
Route::get('selectUserIos', 'FinanceController/selectUserIos'); //财务= 苹果支付
Route::get('selectUserWechat', 'FinanceController/selectUserWechat'); //财务= 微信支付
Route::get('selectUserAli', 'FinanceController/selectUserAli'); //财务= 支付宝
Route::get('selectUserModes', 'FinanceController/selectUserModes'); //财务= 钻石转M豆
Route::get('selectUserGift', 'FinanceController/selectUserGift'); //财务= 获得礼物
Route::get('selectUserBox', 'FinanceController/selectUserBox'); //财务= 箱子
Route::get('selectUserfinger', 'FinanceController/selectUserfinger'); //财务= 猜拳
Route::get('selectUserBagGift', 'FinanceController/selectUserBagGift'); //财务= 背包礼物
Route::get('selectUserPayGift', 'FinanceController/selectUserPayGift'); //财务= 购买礼物
Route::get('selectBuyYin', 'FinanceController/selectBuyYin'); //财务= 买银箱子钥匙
Route::get('selectBuyJin', 'FinanceController/selectBuyJin'); //财务= 买金箱子钥匙
Route::get('selectUseYin', 'FinanceController/selectUseYin'); //财务= 用银箱子钥匙
Route::get('selectUseJin', 'FinanceController/selectUseJin'); //财务= 用金箱子钥匙
Route::get('selectBagStart', 'FinanceController/selectBagStart'); //财务= 背包礼物发起猜拳
Route::get('selectPayStart', 'FinanceController/selectPayStart'); //财务= 充值礼物发起猜拳
Route::get('selectBagFight', 'FinanceController/selectBagFight'); //财务= 背包礼物应战猜拳
Route::get('selectPayFight', 'FinanceController/selectPayFight'); //财务= 充值礼物应战猜拳

Route::get('roomdetail', 'DataManagement/roomList'); //房间每天在线统计列表
Route::get('roomdmember', 'DataManagement/roomdmember'); //房间每天消费统计
Route::get('roomdmemberdetail', 'DataManagement/roomdmemberdetail'); //房间每天消费统计
Route::get('channelindex', 'DataManagement/channelindex'); //渠道数据管理列表
Route::get('channelSourceData', 'DataManagement/channelSourceData'); //渠道数据区分source管理列表
Route::get('channelSourceHwData', 'DataManagement/channelSourceHwData'); //华为包渠道数据区分source管理列表
Route::get('channelSourceOppoData', 'DataManagement/channelSourceOppoData'); //华为包渠道数据区分source管理列表
Route::get('channelSourceAppstoreData', 'DataManagement/channelSourceAppstoreData'); //ios包渠道数据区分source管理列表
Route::get('channelSourceDetails', 'DataManagement/channelSourceDetails'); //渠道数据详情
Route::get('channelDetails', 'DataManagement/channelDetails'); //渠道数据详情

Route::get('guildroomlist', 'GuildController/guildRoomList'); //公会房间消费统计
Route::get('guildroomdetail', 'GuildController/guildroomdetail'); //公会房间消费统计
Route::get('roomcoinlist', 'RoomCoinController/roomcoinList'); //房间消费列表
Route::get('roomcoindes', 'RoomCoinController/roomcoindes'); //房间消费详情

Route::post('avatarOssFile', 'MemberController/avatarOssFile'); //用户头像上传OSS
Route::post('prettyavatarOssFile', 'MemberController/prettyavatarOssFile'); //用户头像框上传OSS

Route::post('editRoom', 'RoomController/editRoom'); //修改房间信息

Route::get('roomRecommend', 'RoomController/roomRecommend'); //房间推荐列表
Route::post('roomRecommendAdd', 'RoomController/roomRecommendAdd'); //添加房间推荐
Route::post('roomRecommendDel', 'RoomController/roomRecommendDel'); //取消房间推荐
Route::post('roomOssFile', 'RoomController/roomOssFile'); //房间背景图

Route::get('ysuserList', 'MemberController/ysuserList'); //房间超级管理员列表
Route::post('addYsUser', 'MemberController/addYsUser'); //配置房间超管
Route::post('delYsUser', 'MemberController/delYsUser'); //取消房间超管

Route::post('addGuidRoom', 'GuildController/addGuidRoom'); //配置公会房间
Route::post('delGuidRoom', 'GuildController/delGuidRoom'); //取消公会房间
Route::post('exitGuild', 'GuildController/exitGuild'); //修改公会信息

Route::get('feedbackList', 'ComplaintsController/feedbackList'); //平台反馈列表
Route::get('rechargelist', 'ChargeController/rechargelist'); //充值记录
Route::get('rechargelistnew', 'ChargeController/rechargeListNew'); //新充值记录

Route::post('retention', 'DataManagement/retention'); //bi详情
Route::post('channelRetention', 'DataManagement/channelRetention'); //渠道bi详情
Route::get('userbiDetail', 'DataManagement/userbiDetail'); //bi新增用户付费详情
Route::get('UserStone', 'DataManagement/UserStone'); //用户许愿石详情
Route::get('TheLottery', 'BoxBurstRateController/TheLottery'); //银宝箱爆奖率
Route::get('TheLotterj', 'BoxBurstRateController/TheLotterj'); //金宝箱爆奖率
Route::get('luckyBox', 'DataManagement/luckyBox'); //幸运盒子
Route::get('luckyBoxList', 'DataManagement/luckyBoxList'); //幸运盒子详情
Route::get('BoxList', 'DataManagement/BoxList'); //开箱明细
Route::get('giftFine', 'DataManagement/giftFine'); //礼物明细

Route::get('usermoneylist', 'MemberMoneyController/userMoneyList'); //操作用户钱包明细列表

Route::get('advertList', 'AdertController/advertList'); //首页启动广告列表
Route::post('addAdvert', 'AdertController/addAdvert'); //首页启动添加广告
Route::post('editAdvert', 'AdertController/editAdvert'); //首页启动修改广告
Route::post('editAdvertImg', 'AdertController/editAdvertImg'); //修改广告图片
Route::get('doappList', 'AdertController/doappList'); //推广列表数据

Route::get('giftstart', 'GiftController/giftstart'); //周星魅力礼物列表
Route::post('addGiftStart', 'GiftController/addGiftStart'); //周星活动添加
Route::post('exitGiftStart', 'GiftController/exitGiftStart'); //周星活动修改
Route::post('ossFileStart', 'giftController/ossFileStart'); //周星礼物图片上传OSS

Route::get('giftWealth', 'GiftController/giftWealth'); //周星财富礼物列表
Route::post('addGiftWealth', 'GiftController/addGiftWealth'); //添加财富周星
Route::post('exitGiftWealth', 'GiftController/exitGiftWealth'); //编辑财富周星

Route::get('giftMonth', 'GiftController/giftMonth'); //月魅力列表
Route::post('addGiftMonth', 'GiftController/addGiftMonth'); //添加月魅力
Route::post('exitGiftMonth', 'GiftController/exitGiftMonth'); //编辑月魅力

Route::get('getConsumeList', 'CoindetailController/getConsumeList'); //所有用户消费列表
Route::get('getIncomeList', 'BeandetailController/getIncomeList'); //所有用户收益列表
Route::get('getKeFuAdjust', 'CoindetailController/getKeFuAdjust'); //所有用户消费列表
Route::get('getleftBeanAndDiamond', 'CoindetailController/getleftBeanAndDiamond'); //所有用户剩余豆和钻数量

Route::post('getLoginList', 'LogindetailController/getLoginList'); //用户登录记录
Route::post('getForbidList', 'LogindetailController/getForbidList'); //用户登录记录

Route::get('ratePayList', 'RatePayController/ratePayList'); //续费率列表

Route::get('userCardList', 'UserCardController/userCardList'); //实名认证列表
Route::get('userCoin', 'CoindetailController/userCoin'); //用户送礼明细列表
Route::get('userGetCoin', 'CoindetailController/userGetCoin'); //用户收礼明细列表
Route::get('bill', 'CoindetailController/bill'); //脚本对账数据列表
Route::get('billDetail', 'CoindetailController/billDetail'); //对账明细数据列表
Route::get('payUser', 'CoindetailController/payUser'); //每日充值用户id
Route::get('leftUser', 'CoindetailController/leftUser'); //用户剩余豆
Route::get('MOrder', 'CoindetailController/MOrder'); //用户剩余豆

Route::get('musicList', 'MusicController/musicList'); //音乐列表
Route::post('musicYes', 'MusicController/musicYes'); //音乐通过审核
Route::post('musicNo', 'MusicController/musicNo'); //音乐拒绝审核
Route::post('delMusic', 'MusicController/delMusic'); //音乐删除

Route::get('partyRoomLists', 'RoomController/partyRoomList'); //房间公会列表

Route::get('vipPrivilege', 'VipController/vipPrivilege'); //vip会员特权列表
Route::get('VipOrder', 'VipController/VipOrder'); //vip订单
Route::post('addPrivilege', 'VipController/addPrivilege'); //vip会员特权添加
Route::post('addPrivilegePicture', 'VipController/addPrivilegePicture'); //vip会员特权添加
Route::post('editPrivilege', 'VipController/editPrivilege'); //vip会员特权修改

Route::get('BackgroundImageOfType', 'RoomController/BackgroundImageOfType'); //获取不同房间类型的背景图片
Route::get('photoWallStart', 'RoomController/photoWallStart'); //设置房间默认图
Route::post('editRoomImageByType', 'RoomController/editRoomImageByType'); //获取不同房间类型的背景图片
Route::post('delRoomImageByType', 'RoomController/delRoomImageByType'); //获取不同房间类型的背景图片
Route::post('addRoomImageByType', 'RoomController/addRoomImageByType'); //获取不同房间类型的背景图片

Route::rule('getAttireList', 'AttireController/getAttireList'); //装扮列表
Route::rule('getAttireType', 'AttireController/getAttireType'); //装扮分类列表
Route::post('addAttire', 'AttireController/addAttire'); //装扮添加
Route::post('ossAttireFile', 'AttireController/ossAttireFile'); //装扮图片是oss上传
Route::post('attireStatus', 'AttireController/attireStatus'); //装扮上下架切换
Route::post('statusAttire', 'AttireController/statusAttire'); //装扮上下架切换
Route::post('attireUpdateStatus', 'AttireController/attireUpdateStatus'); //装扮修改
Route::post('attOssFile', 'AttireController/attOssFile'); //装扮图片添加

Route::post('addRoomParty', 'RoomController/addRoomParty'); //加入公会房间

Route::rule('getAttireTypeList', 'AttireTypeController/getAttireTypeList'); //装扮分类列表
Route::rule('addAttireType', 'AttireTypeController/addAttireType'); //装扮分类列表添加
Route::post('updateAttireType', 'AttireTypeController/updateAttireType'); //装扮分类修改
Route::post('statusAttireType', 'AttireTypeController/statusAttireType'); //装扮分类上下架切换

Route::get('roomBackgroundChoice', 'RoomController/roomBackgroundChoice'); //房间背景选择列表
Route::post('roomChoiceType', 'RoomController/roomChoiceType'); //房间背景选择功能

Route::get('roomTypeList', 'RoomTypeController/roomTypeList'); //房间类型列表
Route::post('ossRoomType', 'RoomTypeController/ossRoomType'); //房间类型列表
Route::post('editTypeStatus', 'RoomTypeController/editTypeStatus'); //房间类型上下架修改
Route::post('editTypeShow', 'RoomTypeController/editTypeShow'); //房间类型添加
Route::post('updateType', 'RoomTypeController/updateType'); //放假默认切换
Route::post('addRoomTagRedis', 'RoomTypeController/addRoomTagRedis'); //上线房间分类标签

Route::get('consumption', 'TestController/consumption');
Route::get('recharge', 'TestController/recharge');
// Route::get('channel', 'TestController/channel');
Route::get('withdrawLimit', 'TestController/getWithdrawLimit');

Route::get('addChannel', 'ChannelController/AddChannel'); //添加渠道
Route::get('Three', 'ChannelCensusController/Three'); //添加渠道
Route::get('level', 'ChannelController/level'); //获取渠道id和名称
Route::get('group', 'ChannelController/group'); //渠道用户分组
Route::get('delChannel', 'ChannelController/delChannel'); //删除渠道
Route::get('channelList', 'ChannelController/channelListNew'); //渠道列表
Route::get('threeChannel', 'ChannelCensusController/threeChannel'); //渠道列表
Route::get('StatAot', 'ChannelCensusController/StatAot'); //渠道消费分析
Route::get('channelStats', 'ChannelCensusController/channelStats'); //渠道bi详情
Route::get('getChannelOfId', 'ChannelController/getChannelOfId'); //根据id获取下级渠道的列表
Route::get('editChannel', 'ChannelController/editChannel'); //编辑渠道信息
Route::get('addCensus', 'ChannelCensusController/channelAddCensus'); //渠道新增统计
Route::get('affiliation', 'ChannelCensusController/channeAffiliation'); //归属查询
Route::get('statistics', 'ChannelCensusController/Statistics'); //消费统计
Route::get('statement', 'ChannelCensusController/Statement'); //消费明细

Route::get('BoxGiftList', 'BoxGiftController/BoxGiftList'); //用户背包展示列表
Route::get('roomName', 'RoomNameController/roomName'); //推荐房间名列表
Route::post('delRoomName', 'RoomNameController/delRoomName'); //删除房间名
Route::post('roomModel', 'RoomNameController/roomModel'); //房间模式名称
Route::get('addroomname', 'RoomNameController/addRoomName'); //添加推荐房间名列表
Route::get('updateRoomName', 'RoomNameController/updateRoomName'); //修改推荐房间名列表
Route::get('roomModelName', 'RoomNameController/roomModelName'); //推荐房间标签列表
Route::post('addRoomModelName', 'RoomNameController/addRoomModelName'); //添加房间标签
Route::post('getRoomModel', 'RoomNameController/getRoomModel'); //获取房间分类

Route::get('userAttire', 'UserAttireController/UserAttire'); //用户装扮
Route::post('addUserAttire', 'UserAttireController/addUserAttire'); //用户装扮添加
Route::post('addRoomType', 'RoomTypeController/addRoomType'); //房间类型添加
Route::post('delRoomType', 'RoomTypeController/delRoomType'); //房间类型删除
Route::get('AddChannelShow', 'ChannelController/AddChannelShow'); //房间类型是否显示修改
//Route::post('threeBlack', 'MemberBlackController/threeBlack');       //三封
//Route::post('unDoThreeBlack', 'MemberBlackController/unDoThreeBlack');       //解三封

Route::get('RedEnvelopeList', 'RedEnvelopeController/RedEnvelopeList'); //用户背包展示列表

//红包规则管理
Route::get('RedTherulesList', 'RedTherulesController/RedTherulesList'); //红包规则列表
Route::post('AddRedCoin', 'RedTherulesController/AddRedCoin'); //红包规则修改
Route::get('RedPackets', 'RedTherulesController/RedPackets'); //红包列表
Route::get('RedpacketsDetail', 'RedTherulesController/RedpacketsDetail'); //红包详情列表

//任务管理 task
Route::get('TaskList', 'TaskController/TaskList'); //任务展示列表
Route::get('TaskAdd', 'TaskController/TaskAdd'); //任务添加
Route::get('TaskSave', 'TaskController/TaskSave'); //任务修改

//金币抽奖
Route::get('listGold', 'ActiveController/listGold'); //金币抽奖奖励金币
Route::post('addlistGold', 'ActiveController/addlistGold'); //添加 金币抽奖奖励金币
Route::post('dellistGold', 'ActiveController/dellistGold'); //添加 金币抽奖奖励金币
Route::post('updlistGold', 'ActiveController/updlistGold'); //修改 金币抽奖奖励金币
Route::get('listGoldAttire', 'ActiveController/listGoldAttire'); //金币抽奖奖励装扮
Route::post('addlistGoldAttire', 'ActiveController/addlistGoldAttire'); //金币抽奖奖励装扮
Route::post('updlistGoldAttire', 'ActiveController/updlistGoldAttire'); //金币抽奖奖励装扮
Route::post('dellistGoldAttire', 'ActiveController/dellistGoldAttire'); //金币抽奖奖励装扮
Route::get('listGoldGift', 'ActiveController/listGoldGift'); //金币抽奖奖励礼物
Route::post('addlistGoldGift', 'ActiveController/addlistGoldGift'); //添加金币抽奖奖励礼物
Route::post('updlistGoldGift', 'ActiveController/updlistGoldGift'); //修改金币抽奖奖励礼物
Route::post('dellistGoldGift', 'ActiveController/dellistGoldGift'); //删除金币抽奖奖励礼物

//金币抽奖记录
Route::get('goldCoinBox', 'ActiveController/goldCoinBox'); //列表

//金币商城
Route::get('goldGiftList', 'GoldMallController/goldGiftList'); //金币商城礼物列表
Route::post('addGoldGift', 'GoldMallController/addGoldGift'); //添加金币礼物
Route::post('exitGoldGift', 'GoldMallController/exitGoldGift'); //修改金币礼物
Route::post('clearGoldCache', 'GoldMallController/clearGoldCache'); //清楚金币礼物缓存
Route::post('ossGoldFile', 'GoldMallController/ossGoldFile'); //添加金币礼物图片
//金币装扮
Route::rule('attListGold', 'GoldMallController/attListGold'); //装扮列表
Route::rule('attTypeGold', 'GoldMallController/attTypeGold'); //装扮分类列表
Route::post('addAttGold', 'GoldMallController/addAttGold'); //装扮添加
Route::post('ossAttFileGold', 'GoldMallController/ossAttFileGold'); //装扮图片是oss上传
Route::post('statusAttGold', 'GoldMallController/statusAttGold'); //装扮上下架切换
Route::post('attUpdGold', 'GoldMallController/attUpdGold'); //装扮修改
//礼物盒子
Route::get('giftBox', 'GiftController/giftBox'); //礼物盒子展示
Route::post('addGiftBox', 'GiftController/addGiftBox'); //礼物盒子添加
Route::post('delGiftBox', 'GiftController/delGiftBox'); //礼物盒子删除
Route::post('clearCacheGiftBox', 'GiftController/clearCacheGiftBox'); //礼物盒子清除缓存
Route::get('randomGift', 'GiftController/randomGift'); //随机礼物界面展示
Route::post('delGiftRandom', 'GiftController/delGiftRandom'); //随机礼物删除开奖礼物
Route::post('addGiftRandom', 'GiftController/addGiftRandom'); //随机礼物添加开奖礼物
Route::get('luckBagGift', 'GiftController/luckBagGift'); //随机礼物界面展示
Route::post('luckBagGiftSave', 'GiftController/luckBagGiftSave'); //随机礼物界面展示

//戳一戳动词管理
Route::get('pokeWordsList', 'ConfigController/pokeWordsList'); //戳一戳配词
Route::post('addPokeWords', 'ConfigController/addPokeWords'); //戳一戳添加
Route::post('delPokeWords', 'ConfigController/delPokeWords'); //戳一戳添加
Route::post('clearCachePokeWords', 'ConfigController/clearCachePokeWords'); //戳一戳清除缓存
//打招呼消息管理
Route::get('greetMessageList', 'ConfigController/greetMessageList'); //打招呼配词
Route::post('addGreetMessage', 'ConfigController/addGreetMessage'); //打招呼添加
Route::post('delGreetMessage', 'ConfigController/delGreetMessage'); //打招呼添加
Route::post('clearCacheGreetMessage', 'ConfigController/clearCacheGreetMessage'); //打招呼清除缓存

//表情包管理 EmoticonModel
Route::get('listEmoticon', 'EmoticonController/listEmoticon'); //表情包列表
Route::post('ossEmoticonFile', 'EmoticonController/ossEmoticonFile'); //修改表情图
Route::post('addEmoticon', 'EmoticonController/addEmoticon'); //表情添加
Route::post('delEmoticon', 'EmoticonController/delEmoticon'); //表情删除
Route::post('saveEmoticon', 'EmoticonController/saveEmoticon'); //表情编辑
Route::post('clearGoldEmoti', 'EmoticonController/clearGoldEmoti'); //表情缓存清除

//跑马灯管理 ScrollerModel
Route::get('scroller/list', 'ScrollerController/listInfo'); //跑马灯列表
Route::post('scroller/ossFile', 'ScrollerController/ossFile'); //修改跑马灯图
Route::post('scroller/add', 'ScrollerController/add'); //跑马灯添加
Route::post('scroller/del', 'ScrollerController/del'); //跑马灯删除
Route::post('scroller/save', 'ScrollerController/save'); //跑马灯编辑
Route::post('scroller/clear', 'ScrollerController/clear'); //跑马灯缓存清除

//首页图标配置
Route::get('homePage/show', 'HomePageConfigController/show'); //包列表
Route::post('homePage/ossFile', 'HomePageConfigController/ossFile'); //修改图
Route::post('homePage/add', 'HomePageConfigController/add'); //添加
Route::post('homePage/del', 'HomePageConfigController/del'); //删除
Route::post('homePage/save', 'HomePageConfigController/save'); //编辑
Route::post('homePage/clear', 'HomePageConfigController/clear'); //缓存清除

//房间顶级分类
Route::post('addRoomTypeFather', 'RoomTypeController/addRoomTypeFather'); //添加房间顶级分类

//随机头像
Route::get('cpRecommendImageList', 'ConfigController/cpRecommendImageList'); //随机头像列表
Route::post('addcpRecommendImage', 'ConfigController/addcpRecommendImage'); //随机头像添加
Route::post('delcpRecommendImage', 'ConfigController/delcpRecommendImage'); //随机头像删除
Route::post('clearCacheCpRecommendImage', 'ConfigController/clearCachecpReCommendImage'); //随机头像清除缓存

//房间内充值 pv uv
Route::get('ChannelPoints', 'DataManagement/ChannelPoints'); //房间内充值 pv uv列表
Route::get('Teenagers', 'DataManagement/Teenagers'); //点击隐藏显示青少年模式
Route::get('Recommended', 'DataManagement/Recommended'); //推荐房间
Route::get('Classification', 'DataManagement/Classification'); //点击分类
Route::get('DealerData', 'DataManagement/DealerData'); //积分墙点击数据
Route::get('CpThegame', 'DataManagement/CpThegame'); //cp和游戏
Route::get('Matching', 'DataManagement/Matching'); //点击匹配
Route::get('Dynamic', 'DataManagement/Dynamic'); //点击动态
Route::get('HomePageLooking', 'DataManagement/HomePageLooking'); //首页去找他
Route::get('HomePageThehall', 'DataManagement/HomePageThehall'); //首页大厅
Route::get('SayHhello', 'DataManagement/SayHhello'); //打招呼
Route::get('PokeThe', 'DataManagement/PokeThe'); //戳一下
Route::get('ReleaseTheDynamic', 'DataManagement/ReleaseTheDynamic'); //发布动态

//敏感词管理 bannedList
Route::get('bannedList', 'ConfigController/bannedList'); //敏感词展示
Route::post('addBanned', 'ConfigController/addBanned'); //敏感词添加
Route::post('delBanned', 'ConfigController/delBanned'); //敏感词删除
Route::post('clearBanned', 'ConfigController/clearBanned'); //敏感词清除缓存

//用户聊天记录 messageList
Route::get('messageList', 'MemberController/messageList'); //聊天记录列表
Route::get('messageData', 'MemberController/messageData'); //聊天记录详情

//用户装备管理 userGift
Route::get('userGift', 'GiftController/userGift'); //用户礼物列表
Route::get('SaveUserGift', 'GiftController/SaveUserGift'); //用户礼物列表
Route::get('addPackGift', 'GiftController/addPackGift'); //用户背包礼物添加

//飞行棋
Route::get('gameList', 'ConfigController/gameList'); //飞行棋列表
Route::get('gameJson', 'ConfigController/gameJson'); //飞行棋奖池
//Route::get('saveGame', 'ConfigController/saveGame');       //飞行棋奖池编辑
Route::post('clearGame', 'ConfigController/clearGame'); //飞行棋奖池清除缓存
Route::get('gameLog', 'GameController/gameLog'); //飞行棋奖励记录
Route::get('GameExchangeLog', 'GameController/GameExchangeLog'); //飞行棋兑换记录
Route::get('RoomGame', 'GameController/RoomGame'); //飞行棋房间赠礼
Route::post('GameImage', 'GameController/GameImage'); //飞行棋房间赠礼
Route::get('UserPhysicalStrength', 'GameController/UserPhysicalStrength'); //用戶體力
Route::get('addUserPhysicalStrength', 'GameController/addUserPhysicalStrength'); //用戶體力

//矿石兑换礼物
Route::get('Exchange', 'ConfigController/Exchange'); //矿石兑换礼物
Route::get('saveExchange', 'ConfigController/saveExchange'); //矿石兑换礼物修改
Route::get('addExchange', 'ConfigController/addExchange'); //添加矿石礼物位置
Route::get('delExchange', 'ConfigController/delExchange'); //移除矿石礼物位置

//奖池比例
Route::get('gameProportion', 'ConfigController/gameProportion'); //矿石兑换礼物
Route::get('saveProportion', 'ConfigController/saveProportion'); //矿石兑换礼物编辑

//用户召回
Route::get('memberLoss', 'MemberController/memberLoss'); //！用户流失

//用户实名
Route::get('newUserCardList', 'UserCardController/newUserCardList'); //新实名认证列表
Route::get('cancelUserStatus', 'MemberController/cancelUserStatus'); //用户注销列表
Route::get('cancelUserStatusSave', 'MemberController/cancelUserStatusSave'); //用户注销列表
Route::get('cancelUserStatusCancel', 'MemberController/cancelUserStatusCancel'); //用户注销取消

//游戏
Route::get('GiftGame', 'GameController/GiftGame'); //游戏
Route::post('GiftGameStatus', 'GameController/GiftGameStatus'); //游戏开关

//爵位 duke
Route::get('dukeList', 'DukeController/dukeList'); //爵位列表
Route::post('dukeAdd', 'DukeController/dukeAdd'); //爵位添加
Route::get('dukeDetails', 'DukeController/dukeDetails'); //爵位详情
Route::post('dukeSave', 'DukeController/dukeSave'); //爵位编辑
Route::get('dukeMember', 'DukeController/dukeMember'); //爵位用户
Route::get('dukeMemberAdd', 'DukeController/dukeMemberAdd'); //添加爵位用户
Route::post('dukeRedis', 'DukeController/dukeRedis'); //清缓存

Route::get('dukeConfig', 'ConfigController/dukeConfig'); //爵位配置
Route::post('dukeConfigAdd', 'ConfigController/dukeConfigAdd'); //爵位配置添加
Route::get('dukeDetailsConfig', 'ConfigController/dukeDetailsConfig'); //爵位详情配置

//后台装备操作记录
Route::get('GivePackList', 'GiftController/GivePackList'); //后台装备管理

//dongbozhao 2020-11-28 17:16
Route::get('sqlShwo', 'DataManagement/sqlShwo'); //sql展示
Route::get('sqlQuery', 'DataManagement/sqlQuery'); //sql执行

//房间榜单 roomConsumption
Route::get('roomConsumption', 'DataManagement/roomConsumption'); //房间魅力值榜单
Route::get('roomConsumptionRich', 'DataManagement/roomConsumptionRich'); //房间财富榜单
Route::get('roomConsumptionWeekLike', 'DataManagement/roomConsumptionWeekLike'); //房间魅力值值榜单
Route::get('roomConsumptionWeekRich', 'DataManagement/roomConsumptionWeekRich'); //房间财富周榜单

//封号管理
Route::get('blackList', 'MemberBlackController/blackList'); //封禁列表
Route::post('memberUnsealings', 'MemberBlackController/memberUnsealings'); //用户解封
Route::post('memberBlacks', 'MemberBlackController/memberBlacks'); //用户封号
Route::get('deviceIpList', 'MemberBlackController/deviceIpList'); //ip黑名单
Route::get('deviceDeviceidList', 'MemberBlackController/deviceDeviceidList'); //设备黑名单
Route::get('deviceIdcardList', 'MemberBlackController/deviceIdcardList'); //身份证黑名单
Route::get('deviceIpAdd', 'MemberBlackController/deviceIpAdd'); //添加黑名单
Route::post('deviceIpSave', 'MemberBlackController/deviceIpSave'); //黑名单切换状态

//用户登录记录
Route::get('loginDetail', 'MemberController/loginDetail'); //用户登录记录
//房間分類
Route::get('saveRoomModeSor', 'RoomTypeController/saveRoomModeSor'); //房間分類排序

/*********** <<<<<<<<<<<<<<<<<<<<<<<< 重构-配置 Config **************************************/
Route::get('configShow', 'ConfigController/configShow'); //配置展示页面
Route::get('redisConfig', 'ConfigController/redisConfig'); //配置缓存

Route::get('goodsConf', 'ConfigController/goodsConf'); //商品配置
Route::get('goodsConfDetails', 'ConfigController/goodsConfDetails'); //商品详情
Route::post('goodsConfSave', 'ConfigController/goodsConfSave'); //商品配置修改
Route::post('goodsAdd', 'ConfigController/goodsAdd'); //商品添加
Route::post('goodsDetailsSave', 'ConfigController/goodsDetailsSave'); //商品详情修改

Route::get('mallConf', 'ConfigController/mallConf'); //商城配置
Route::post('mallConfAdd', 'ConfigController/mallConfAdd'); //商城配置添加货币类型
Route::get('mallconfDetails', 'ConfigController/mallconfDetails'); //商城配置第一层
Route::post('mallconfDetailsAdd', 'ConfigController/mallconfDetailsAdd'); //商城配置第一层添加
Route::get('mallconfAreas', 'ConfigController/mallconfAreas'); //商城配置第二层
Route::post('mallAddGoods', 'ConfigController/mallAddGoods'); //商城添加商品
Route::post('delMallGoods', 'ConfigController/delMallGoods'); //删除商城商品
Route::get('giftConf', 'ConfigController/giftConf'); //礼物配置
Route::post('giftConfAdd', 'ConfigController/giftConfAdd'); //礼物配置添加
Route::post('giftConfImg', 'ConfigController/giftConfImg'); //礼物配置添加
Route::post('giftConfDel', 'ConfigController/giftConfDel'); //礼物配置删除
Route::post('giftConfSave', 'ConfigController/giftConfSave'); //礼物配置保存
Route::get('giftPanelsTheFirst', 'ConfigController/giftPanelsTheFirst'); //礼物商城初始化
Route::get('giftPanels', 'ConfigController/giftPanels'); //礼物商城
Route::get('giftPanelsDetails', 'ConfigController/giftPanelsDetails'); //礼物商城详情
Route::post('giftClassificationAdd', 'ConfigController/giftClassificationAdd'); //礼物商城分类添加
Route::post('giftPanelsAdd', 'ConfigController/giftPanelsAdd'); //礼物商城添加
Route::post('giftPanelsDel', 'ConfigController/giftPanelsDel'); //删除商城礼物

Route::get('giftWall', 'ConfigController/giftWall'); //礼物面板
Route::get('giftWallDetails', 'ConfigController/giftWallDetails'); //礼物面板详情
Route::get('saveGiftWeight', 'ConfigController/saveGiftWeight'); //礼物商城权重设置
Route::post('giftWallAdd', 'ConfigController/giftWallAdd'); //礼物面板添加
Route::post('giftWallDel', 'ConfigController/giftWallDel'); //删除面板礼物
Route::get('propConf', 'ConfigController/propConf'); //装扮配置
Route::post('propConfAdd', 'ConfigController/propConfAdd'); //添加装扮配置
Route::post('propConfDel', 'ConfigController/propConfDel'); //删除装扮配置
Route::get('propConfSave', 'ConfigController/propConfSave'); //编辑装扮配置

Route::get('emoticonConf', 'ConfigController/emoticonConf'); //表情包配置
Route::post('emoticonConfAdd', 'ConfigController/emoticonConfAdd'); //表情包配置添加
Route::post('emoticonConfSave', 'ConfigController/emoticonConfSave'); //表情包配置编辑
Route::post('emoticonConfDel', 'ConfigController/emoticonConfDel'); //表情包配置删除

Route::get('emoticonPanelsConf', 'ConfigController/emoticonPanelsConf'); //表情包面板
Route::get('emoticonPanelsConfDetails', 'ConfigController/emoticonPanelsConfDetails'); //表情包面板详情
Route::post('emoticonPanelsConfAdd', 'ConfigController/emoticonPanelsConfAdd'); //添加表情包面板
Route::post('emoticonPanelsConfDel', 'ConfigController/emoticonPanelsConfDel'); //删除表情包面板

Route::get('weekcheckin', 'ConfigController/weekcheckin'); //每日任务配置

Route::get('vipConf', 'ConfigController/vipConf'); //vip/svip配置
Route::get('taojinConf', 'ConfigController/taojinConf'); //淘金配置
Route::get('taojinContent', 'ConfigController/taojinContent'); //淘金详情配置
Route::post('taojinContentSave', 'ConfigController/taojinContentSave'); //淘金详情配置保存
Route::post('saveTaoJinForm', 'ConfigController/saveTaoJinForm'); //淘金配置编辑
Route::post('gameConfImg', 'ConfigController/gameConfImg'); //淘金配置图片
Route::get('newerConf', 'ConfigController/newerConf'); //新手任务配置
Route::get('lotteryConf', 'ConfigController/lotteryConf'); //金币抽奖配置
Route::get('levelConf', 'ConfigController/levelConf'); //等级配置
Route::get('saveGame', 'ConfigController/saveGame'); //飞行棋奖池编辑

Route::get('activeboxConf', 'ConfigController/activeboxConf'); //活跃度奖励配置
Route::get('activeboxdetails', 'ConfigController/activeboxdetails'); //活跃度宝箱奖励详情配置
Route::get('boxConf', 'ConfigController/boxConf'); //宝箱配置
Route::post('boxConfSave', 'ConfigController/boxConfSave'); //宝箱配置保存
Route::get('chargeConf', 'ConfigController/chargeConf'); //充值配置
Route::get('chargemallConf', 'ConfigController/chargemallConf'); //充值面板配置
Route::get('dailyConf', 'ConfigController/dailyConf'); //登录任务
Route::get('ChuShiHua', 'ConfigController/ChuShiHua'); //重构数据初始化

/*********************************************** 重构-配置 Config >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>*/

//代充人列表 daichong
Route::get('daichong', 'MemberController/daichong'); //代充人列表
Route::post('daichongAdd', 'MemberController/daichongAdd'); //代充添加用户
Route::post('daichongStatus', 'MemberController/daichongStatus'); //删除代充用户

//红包活动
Route::get('activityRed', 'RedTherulesController/activityRed'); //红包活动
Route::post('addActivityRed', 'RedTherulesController/addActivityRed'); //添加红包活动
Route::get('activityRedDetails', 'RedTherulesController/activityRedDetails'); //红包活动礼
Route::post('actRedGiftAdd', 'RedTherulesController/actRedGiftAdd'); //红包活动礼添加
Route::post('actRedGiftDel', 'RedTherulesController/actRedGiftDel'); //红包活动礼添加

//三人夺宝
Route::get('treasurePoolList', 'ActiveController/treasurePoolList'); //三人夺宝产出
Route::get('treasurePool', 'ActiveController/treasurePool'); //三人夺宝
Route::get('treasurePoolDetails', 'ActiveController/treasurePoolDetails'); //三人夺宝奖池礼物列表
Route::post('treasurePoolDetailsAdd', 'ActiveController/treasurePoolDetailsAdd'); //三人夺宝奖池添加礼物
Route::post('treasurePoolDetailsDel', 'ActiveController/treasurePoolDetailsDel'); //三人夺宝奖池删除礼物

//用户回归活动
Route::get('returnUserActivityConfig', 'ActiveController/returnUserActivityConfig'); //用户回归活动
Route::post('returnSave', 'ActiveController/returnSave'); //用户回归活动編輯

//giftWallStatus
Route::get('giftWallStatus', 'ConfigController/giftWallStatus'); //礼物墙开关展示
Route::get('giftWallStatusSave', 'ConfigController/giftWallStatusSave'); //礼物墙开关修改

//流失用户
Route::get('iossUser', 'LoginController/iossUser'); //礼物墙开关修改

//福星降临 瓜分番茄豆 shavePoints
Route::get('shavePoints', 'ActiveController/shavePoints'); //瓜分番茄豆
Route::post('shavePointsSave', 'ActiveController/shavePointsSave'); //瓜分番茄豆编辑
Route::get('PublicScreen', 'ConfigController/PublicScreen'); //公平和跑马灯
Route::post('savePublicScreen', 'ConfigController/savePublicScreen'); //公平和跑马灯

/*************** 跑量 ****************/
Route::get('PaoLiangList', 'PaoLiangController/PaoLiangList'); //跑量列表
Route::get('PaoLiangXinzeng', 'PaoLiangController/PaoLiangXinzeng'); //跑量新增列表
Route::post('AddPaoLiang', 'PaoLiangController/AddPaoLiang'); //跑量添加
Route::get('PaoLiangDaoChu', 'PaoLiangController/PaoLiangDaoChu'); //跑量导出
Route::get('PaoLiangChongzhi', 'PaoLiangController/PaoLiangChongzhi'); //跑量充值详情
Route::post('DelPaoLiang', 'PaoLiangController/DelPaoLiang'); //跑量删除
Route::post('PaoLiangSave', 'PaoLiangController/PaoLiangSave'); //跑量编辑
Route::get('PaoLiangLiuCun', 'PaoLiangController/PaoLiangLiuCun'); //跑量留存导出
Route::get('xingzengxiangqing', 'PaoLiangController/xingzengxiangqing'); //跑量详情

Route::get('zbRoomPromotionConf', 'PaoLiangController/zbRoomPromotionConf'); //跑量配置
Route::post('zbRoomPromotionConfAdd', 'PaoLiangController/zbRoomPromotionConfAdd'); //添加跑量
Route::post('zbRoomPromotionConfSave', 'PaoLiangController/zbRoomPromotionConfSave'); //添加跑量
Route::get('zbRoomPromotionConfDel', 'PaoLiangController/zbRoomPromotionConfDel'); //添加跑量
Route::get('roomPromotionDayData', 'PaoLiangController/roomPromotionDayData'); //添加跑量
Route::post('getPromoteDetailByUids', 'PaoLiangController/getPromoteDetailByUids'); //跑量详情
Route::post('getPromoteRetentionById', 'PaoLiangController/getPromoteRetentionById'); //跑量详情

Route::get('PromotionXinzeng', 'PaoLiangController/PromotionXinzeng'); //新增留存
Route::get('PromotionChongzhi', 'PaoLiangController/PromotionChongzhi'); //跑量充值留存

Route::get('PromotionList', 'PromotionController/getPromotionList'); //推广渠道列表
Route::post('PromotionAdd', 'PromotionController/PromotionAdd'); //添加推广渠道
Route::post('PromotionSave', 'PromotionController/PromotionSave'); //保存推广渠道
Route::get('PromotionRoomList', 'PromotionController/getPromotionRoomList'); //推广渠道房间列表
Route::post('PromotionRoomAdd', 'PromotionController/PromotionRoomAdd'); //添加推广渠道房间
Route::post('PromotionRoomSave', 'PromotionController/PromotionRoomSave'); //编辑推广渠道房间

Route::get('PromotionTimesList', 'PromotionController/getPromotionRoomTimesList'); //推广场次配置
Route::post('PromotionRoomTimesAdd', 'PromotionController/PromotionRoomTimesAdd'); //添加推广场次
Route::post('PromotionRoomTimesSave', 'PromotionController/PromotionRoomTimesSave'); //编辑推广场次
Route::post('PromotionRoomTimesDel', 'PromotionController/PromotionRoomTimesDel'); //编辑推广场次

Route::get('getRoom', 'PromotionController/getRoom'); //编辑推广渠道房间
Route::get('getPromote', 'PromotionController/getPromote'); //保存推广渠道
Route::get('getPromoteRoom', 'PromotionController/getPromoteRoom'); //保存推广渠道
Route::get('getUsers', 'PromotionController/getUsers'); //跑量详情

Route::get('retainedShow', 'PaoLiangController/retainedShow'); //跑量留存
Route::post('retainedAdd', 'PaoLiangController/retainedAdd'); //添加日期
Route::post('retainedDel', 'PaoLiangController/retainedDel'); //删除日期
Route::post('retainedUpdate', 'PaoLiangController/retainedUpdate'); //更新日期

/*************** 纯净包提审配置 ****************/
Route::get('versionList', 'ChannelVersionController/list'); //编辑推广渠道房间
Route::post('versionAdd', 'ChannelVersionController/add'); //保存推广渠道
Route::post('versionSave', 'ChannelVersionController/save'); //保存推广渠道
Route::post('versionAgree', 'ChannelVersionController/agree'); //保存推广渠道

/*************** 礼物集合管理 ****************/
Route::get('giftCollectionList', 'GiftCollectionController/list'); //编辑礼物集合
Route::post('giftCollectionAdd', 'GiftCollectionController/add'); //保存礼物集合
Route::post('giftCollectionSave', 'GiftCollectionController/save'); //保存礼物集合
Route::post('giftCollectionOnline', 'GiftCollectionController/online'); //保存礼物集合

/*************** 礼物集合详情管理 ****************/
Route::get('giftCollectionDetailList', 'GiftCollectionDetailController/list'); //编辑礼物集合
Route::post('giftCollectionDetailAdd', 'GiftCollectionDetailController/add'); //保存礼物集合
Route::post('giftCollectionDetailSave', 'GiftCollectionDetailController/save'); //保存礼物集合

/*************** IM背景图管理 ****************/
Route::get('imBgList', 'ImBgImgController/list'); //编辑IM背景
Route::post('imBgAdd', 'ImBgImgController/add'); //保存IM背景
Route::post('imBgSave', 'ImBgImgController/save'); //保存IM背景
Route::post('imBgOnline', 'ImBgImgController/online'); //保存IM背景

Route::get('imEmotionList', 'ImEmotionController/list'); //编辑IM表情包
Route::post('imEmotionAdd', 'ImEmotionController/add'); //保存IM表情包
Route::post('imEmotionSave', 'ImEmotionController/save'); //保存IM表情包
Route::post('shineAlbumSearch', 'WeShineController/shineAlbumSearch'); //保存IM表情包
Route::post('imEmotionOnline', 'ImEmotionController/online'); //保存IM背景

/******************* 表情关键字 ****************/

Route::get('emotionKeywordList', 'EmotionKeyWordController/getlist'); //编辑IM表情包关键字
Route::post('emotionKeywordAdd', 'EmotionKeyWordController/add'); //保存IM表情包关键字
Route::post('emotionKeywordSave', 'EmotionKeyWordController/save'); //保存IM表情包关键字
Route::post('emotionKeywordDel', 'EmotionKeyWordController/del'); //删除IM表情包关键字

/*************** 举报管理 ****************/
Route::get('reportAudit', 'ReportController/reportAudit'); //开始审核
Route::post('reportStartAudit', 'ReportController/reportStartAudit'); //开始审核
Route::post('reportPunish', 'ReportController/reportPunish'); //举报tag删除
Route::post('execPunish', 'ReportController/execPunish'); //执行处罚
Route::post('noPunish', 'ReportController/noPunish'); //开始审核
Route::post('reportTagAdd', 'ReportTagController/add'); //举报tag增加
Route::post('reportTagDel', 'ReportTagController/save'); //举报tag删除
Route::get('roomMsgList', 'RoomMsgController/getList'); //房间公屏信息

/******************* 搜索主播配置 ****************/

Route::get('anchorList', 'AnchorController/getlist'); //编辑IM表情包
Route::post('anchorAdd', 'AnchorController/add'); //保存IM表情包
Route::post('anchorSave', 'AnchorController/save'); //保存IM表情包

/******************* mua ****************/
Route::get('muaNewRoomRecommend', 'RoomController/muaNewRoomRecommend'); //mua新厅推荐
Route::post('muaNewRoomRecommendSave', 'RoomController/muaNewRoomRecommendSave'); //mua新厅推荐保存
Route::get('roomHomepage', 'RoomController/roomHomepage'); //首页房间
Route::post('roomHomepageSave', 'RoomController/roomHomepageSave'); //首页房间保存

Route::get('roomPhoto', 'RoomController/roomPhoto'); //首页房间
Route::post('roomPhotoSave', 'RoomController/roomPhotoSave'); //首页房间保存

Route::get('roomTopOnline', 'ConfigController/roomTopOnline'); //首页最高在线人数
Route::post('roomTopOnlineSave', 'ConfigController/roomTopOnlineSave'); //首页最高在线人数保存

Route::get('muaRoomKingKong', 'RoomController/muaRoomKingKong'); //mua房间金刚位
Route::post('muaRoomKingKongSave', 'RoomController/muaRoomKingKongSave'); //mua房间金刚位保存

/**************************** 活动 ********************************/
Route::get('weeksCharmConf', 'ActiveController/weeksCharmConf'); //万千宠爱奖励
Route::get('weeksActive', 'ActiveController/weeksActive'); //周星
Route::post('addWeeksConfig', 'ActiveController/addWeeksConfig'); //添加万千宠爱奖励
Route::post('saveWeeksConfig', 'ActiveController/saveWeeksConfig'); //编辑万千宠爱奖励
Route::get('weeksWealthConf', 'ActiveController/weeksWealthConf'); //君临天下
Route::get('weeksMonthConf', 'ActiveController/weeksMonthConf'); //月榜配置
Route::get('weeksGiftConf', 'ActiveController/weeksGiftConf'); //周星礼物配置

Route::get('taojinActivityList', 'ActivityController/taojinActivityList'); //淘金详情
Route::get('taojinDetail', 'ActivityController/taojinDetail'); //淘金详情
Route::get('taojinExchangelog', 'ActivityController/taojinExchangelog'); //淘金兑换记录
Route::get('eggTwistedList', 'ActivityController/eggTwistedList'); //淘金兑换记录
Route::get('zhongQiuList', 'ActivityController/zhongQiuList'); //淘金兑换记录
Route::get('thankGivingActivityList', 'ActivityController/thankGivingActivityList'); //淘金兑换记录
Route::get('thankGivingActivityDetail', 'ActivityController/thankGivingActivityDetail'); //淘金兑换记录
Route::get('pkActivityList', 'ActivityController/pkActivityList'); //PK活动数据
Route::get('pkActivityDetail', 'ActivityController/pkActivityDetail'); //PK活动数据
Route::get('pk/show', 'PkConfController/show'); //PK活动数据
Route::post('pk/save', 'PkConfController/save'); //PK活动数据
Route::post('pk/startCrossPk', 'PkConfController/startCrossPk'); //PK活动数据
Route::post('pk/endCrossPk', 'PkConfController/endCrossPk'); //PK活动数据
/****************************** 宝箱 **************************************/
Route::get('getBoxConf', 'BoxConfController/getBoxConf'); //宝箱
Route::post('addBox', 'BoxConfController/addBox'); //添加宝箱
Route::post('getBoxGift', 'BoxConfController/getBoxGift'); //宝箱礼物
Route::post('saveBox', 'BoxConfController/saveBox'); //编辑宝箱
Route::get('boxPool', 'BoxConfController/boxPool'); //宝箱奖池
Route::post('addBoxPool', 'BoxConfController/addBoxPool'); //添加奖池
Route::post('getCondition', 'BoxConfController/getCondition'); //获取奖池条件
Route::post('getPoolGift', 'BoxConfController/getPoolGift'); //获取奖池礼物
Route::post('savePool', 'BoxConfController/savePool'); //编辑奖池
Route::post('saveBoxForm', 'BoxConfController/saveBoxForm'); //宝箱爆率
Route::post('boxSwitch', 'BoxConfController/boxSwitch'); //宝箱爆率
Route::post('clearCacheBoxConf', 'BoxConfController/clearCacheBoxConf'); //宝箱上线
Route::post('refreshAllPool', 'BoxConfController/refreshAllPool'); //刷新宝箱奖池
Route::get('jackpotTheRemaining', 'BoxConfController/jackpotTheRemaining'); //获取奖池剩余

/***************************** 宝箱产出详情 ********************************************/
Route::get('BoxBurstRate', 'BoxBurstRateController/BoxBurstRate'); //宝箱爆率
Route::get('BoxDetails', 'BoxBurstRateController/BoxDetails'); //宝箱详情
Route::get('TheSpecifiedBoxGift', 'BoxBurstRateController/TheSpecifiedBoxGift'); //指定宝箱礼物
Route::post('addTheSpecifiedBoxGift', 'BoxBurstRateController/addTheSpecifiedBoxGift'); //添加指定宝箱礼物
Route::post('cancelTheSpecifiedBoxGift', 'BoxBurstRateController/cancelTheSpecifiedBoxGift'); //取消指定宝箱礼物
Route::get('RealTimeRate', 'BoxBurstRateController/RealTimeRate'); //宝箱实时爆率
Route::get('specialGiftLog', 'BoxBurstRateController/specialGiftLog'); //宝箱实时爆率

Route::get('TheSpecifiedTurntableGift', 'TurntableBurstController/TheSpecifiedTurntableGift'); //指定转盘礼物
Route::post('addTheSpecifiedTurntableGift', 'TurntableBurstController/addTheSpecifiedTurntableGift'); //添加指定转盘礼物
Route::post('cancelTheSpecifiedTurntableGift', 'TurntableBurstController/cancelTheSpecifiedTurntableGift'); //取消指定转盘礼物

/****************************** 转盘 **************************************/
Route::get('getTurntableConf', 'TurntableConfController/getTurntableConf'); //转盘
Route::post('addTurntable', 'TurntableConfController/addTurntable'); //添加转盘
Route::post('getTurntableGift', 'TurntableConfController/getTurntableGift'); //转盘礼物
Route::post('saveTurntable', 'TurntableConfController/saveTurntable'); //编辑转盘
Route::get('turntablePool', 'TurntableConfController/turntablePool'); //转盘奖池
Route::post('addTurntablePool', 'TurntableConfController/addTurntablePool'); //添加转盘奖池
Route::post('getTurntableCondition', 'TurntableConfController/getTurntableCondition'); //获取转盘奖池条件
Route::post('getTurntablePoolGift', 'TurntableConfController/getTurntablePoolGift'); //获取转盘奖池礼物
Route::post('saveTurntablePool', 'TurntableConfController/saveTurntablePool'); //编辑转盘奖池
Route::post('saveTurntableForm', 'TurntableConfController/saveTurntableForm'); //转盘爆率
Route::post('turntableSwitch', 'TurntableConfController/turntableSwitch'); //转盘开关执行
Route::post('clearCacheTurntableConf', 'TurntableConfController/clearCacheTurntableConf'); //转盘上线
Route::post('refreshAllTurntablePool', 'TurntableConfController/refreshAllTurntablePool'); //刷新转盘奖池
Route::get('jackpotTurntableTheRemaining', 'TurntableConfController/jackpotTurntableTheRemaining'); //获取转盘奖池剩余

Route::get('TurntableDetails', 'TurntableBurstRateController/TurntableDetails'); //转盘详情
Route::get('TurntableBurstRate', 'TurntableBurstRateController/TurntableBurstRate'); //转盘爆率
Route::get('TurntableRealTimeRate', 'TurntableBurstRateController/RealTimeRate'); //转盘实时爆率

/****************************** 挖宝配置 **************************************/
Route::get('digTreasure/getConfig', 'DigTreasureController/getConfig');
Route::post('digTreasure/setConfig', 'DigTreasureController/setConfig');
Route::get('digTreasure/getPools', 'DigTreasureController/getPools');
Route::post('digTreasure/setPools', 'DigTreasureController/setPools');
Route::post('digTreasure/clearCache', 'DigTreasureController/clearCache');
Route::post('digTreasure/refreshPool', 'DigTreasureController/refreshPool');
Route::post('digTreasure/getPoolInfo', 'DigTreasureController/getPoolInfo');
Route::get('digTreasure/realTimeRate', 'DigTreasureController/realTimeRate'); //实时爆率
Route::get('digTreasure/outputDetails', 'DigTreasureController/outputDetails'); //产出详情

/**************************************** 配置2 *************************************/
Route::get('roomTagList', 'Config2Controller/roomTagList'); //房间标签配置
Route::post('roomTagAdd', 'Config2Controller/roomTagAdd'); //房间标签配置添加
Route::post('roomTagSave', 'Config2Controller/roomTagSave'); //房间标签配置编辑
Route::get('getRoomTag', 'Config2Controller/getRoomTag'); //获取房间个性标签

/****************************************新宝箱*************************************/
Route::get('box3/boxList', 'Box3ConfController/boxList');
Route::post('box3/setBox', 'Box3ConfController/setBox');
Route::get('box3/getBoxPools', 'Box3ConfController/getBoxPools');
Route::post('box3/setBoxPools', 'Box3ConfController/setBoxPools');
Route::get('box3/getBoxRules', 'Box3ConfController/getBoxRules');
Route::post('box3/setBoxRules', 'Box3ConfController/setBoxRules');
Route::get('box3/getBoxRate', 'Box3ConfController/getBoxRate');
Route::post('box3/setBoxRate', 'Box3ConfController/setBoxRate');
Route::post('box3/clearCacheBoxConf', 'Box3ConfController/clearCacheBoxConf');
Route::post('box3/refreshAllPool', 'Box3ConfController/refreshAllPool');
Route::get('box3/getBoxPointUsers', 'Box3ConfController/getBoxPointUsers');
Route::post('box3/addBoxPointUser', 'Box3ConfController/addBoxPointUser');
Route::post('box3/editBoxPointUser', 'Box3ConfController/editBoxPointUser');
Route::post('box3/delBoxPointUser', 'Box3ConfController/delBoxPointUser');

Route::get('box3/getUserSpecialGift', 'Box3ConfController/getUserSpecialGift');
Route::post('box3/addUserSpecialGift', 'Box3ConfController/addUserSpecialGift');
Route::post('box3/cancelUserSpecialGift', 'Box3ConfController/cancelUserSpecialGift');

Route::get('box3/userBoxRates', 'Box3ConfController/userBoxRates');
Route::get('box3/userSilverBoxRates', 'Box3ConfController/userSilverBoxRates');
Route::get('box3/boxOuputDetails', 'Box3ConfController/boxOuputDetails');

/****************************************打地鼠配置*************************************/
Route::get('gopher/getBaseInfo', 'GopherConfController/getBaseInfo');
Route::post('gopher/setBaseInfo', 'GopherConfController/setBaseInfo');
Route::get('gopher/getPools', 'GopherConfController/getPools');
Route::post('gopher/setPools', 'GopherConfController/setPools');
Route::post('gopher/clearCache', 'GopherConfController/clearCache');
Route::post('gopher/refreshPool', 'GopherConfController/refreshPool');
Route::post('gopher/getPoolInfo', 'GopherConfController/getPoolInfo');
Route::get('gopher/realTimeRate', 'GopherConfController/realTimeRate'); //实时爆率
Route::get('gopher/outputDetails', 'GopherConfController/outputDetails'); //产出详情

/****************************************商城分类管理*************************************/
Route::get('listCategory', 'MallController/listCategory'); //商城分类列表
Route::post('ossCategoryFile', 'MallController/ossCategoryFile'); //修改商城图
Route::post('addCategory', 'MallController/addCategory'); //商城分类添加
Route::post('delCategory', 'MallController/delCategory'); //商城分类删除
Route::post('saveCategory', 'MallController/saveCategory'); //商城分类编辑
//商品管理
Route::get('listGoods', 'MallGoodsController/listGoods'); //商城分类列表
Route::post('ossGoodsFile', 'MallGoodsController/ossGoodsFile'); //修改商城图
Route::post('addGoods', 'MallGoodsController/addGoods'); //商城分类添加
Route::post('delGoods', 'MallGoodsController/delGoods'); //商城分类删除
Route::post('saveGoods', 'MallGoodsController/saveGoods'); //商城分类编辑

Route::get('boxPoolsShow', 'Box3ConfController/boxPoolsShow'); //获取宝箱配置
Route::post('setBoxConf', 'Box3ConfController/setBoxConf'); //修改宝箱配置
Route::get('chartuser', 'ChartUserController/show');
Route::get('userdailychannel', 'ChartUserController/userDailyByChannel');
Route::get('userdailysearch', 'ChartUserController/dailyListBySearch');
Route::get('userchargeNewsearch', 'ChartUserController/chargeListBySearch'); //每日新增充值列表
Route::get('userchargechart', 'ChartUserController/userchargechart'); //用户充值图表
Route::get('channelSourceIosData', 'DataManagement/channelSourceIosData'); //ios包渠道数据区分source管理列表
Route::get('userchargeDetail', 'ChartUserController/userchargeDetail'); //用户充值维度的详情
Route::get('userOnlineMic', 'DataManagement/userOnlineMic'); //在麦时长
Route::get('userkeepList', 'ChartUserController/userKeepList'); //用户留存图表
Route::get('pushTemplateList', 'PushRecallController/pushTemplateList'); //push模板列表
Route::post('pushTemplateEdit', 'PushRecallController/pushTemplateEdit'); //模板内容的编辑
Route::get('pushConfigList', 'PushRecallController/pushConfigList'); //push配置列表
Route::post('pushConfigEdit', 'PushRecallController/pushConfigEdit'); //push配置编辑
Route::get('memberRecallList', 'PushRecallController/memberRecallList'); //用户召回数据
Route::post('manSendConfig', 'PushRecallController/manSendConfig'); //手工推送配置ID
Route::get('checkimMsgList', 'MemberController/checkimMsgList'); //新的聊天记录
Route::get('checkimMsgDetail', 'MemberController/checkimMsgDetail'); //新的聊天记录查看详情
Route::get('loginfeedbackList', 'ComplaintsController/loginFeedbackList'); //登录反馈列表
Route::post('loginFeedbackUpdate', 'ComplaintsController/loginFeedbackUpdate'); //客服跟进登录反馈
Route::get('registeruserprovince', 'ChartUserController/registerUserProvince'); //注册用户区域分布图
Route::get('userRoomBlackList', 'MemberController/userRoomBlackList'); //平台封禁房间用户列表
Route::post('userRoomBlackListEdit', 'MemberController/userRoomBlackListEdit'); //平台封禁房间用户新增
Route::get('asaComeRoomList', 'AsaComeRoomController/asaComeRoomList'); //asa买量用户进房间列表
Route::post('asaComeRoomAdd', 'AsaComeRoomController/asaComeRoomAdd'); //asa买量用户房间新增
Route::post('asaComeRoomDel', 'AsaComeRoomController/asaComeRoomDel'); //asa买量用户房间删除
Route::get('roomCloseList', 'RoomController/roomCloseList'); //封禁房间列表
Route::post('roomCloseEdit', 'RoomController/roomCloseEdit'); //封禁房间编辑
Route::post('roomCloseDel', 'RoomController/roomCloseDel'); //房间封禁解除
Route::get('roomHideList', 'RoomController/roomHideList'); //隐藏房间列表
Route::post('roomHideEdit', 'RoomController/roomHideEdit'); //隐藏房间编辑
Route::post('roomHideDel', 'RoomController/roomHideDel'); //房间隐藏删除
Route::get('roomConsumeList', 'ChartUserController/roomConsumeList'); //房间消费图表
Route::get('roomConsumeDetail', 'RoomController/roomConsumeDetail'); //房间消费列表
Route::get('channelComeRoomList', 'ChannelComeRoomController/channelComeRoomList'); //处cp注册渠道用户进房间列表
Route::post('channelComeRoomAdd', 'ChannelComeRoomController/channelComeRoomAdd'); //处cp注册渠道用户房间新增
Route::post('channelComeRoomDel', 'ChannelComeRoomController/channelComeRoomDel'); //处cp注册渠道房间删除
Route::get('asaSummary', 'ChartUserController/asaSummary'); //asa汇总数据图表
Route::get('asapromotedataList', 'AsaComeRoomController/asapromotedataList'); //asa引流的推广数据
Route::get('asapromotechargelist', 'AsaComeRoomController/asapromotechargelist'); //asa引流充值用户列表
Route::get('asapromoteuserlist', 'AsaComeRoomController/asapromoteuserlist'); //asa其他注册用户列表
Route::get('gashaponConList', 'GashaponConfigController/gashaponConList'); //扭蛋机配置
Route::post('gashaponConAdd', 'GashaponConfigController/gashaponConAdd'); //扭蛋机配置新增
Route::get('gashaponConPublishCache', 'GashaponConfigController/gashaponConPublishCache'); //扭蛋机的配置推送到缓存
Route::get('gashaponRefreshPool', 'GashaponConfigController/gashaponRefreshPool'); //刷新扭蛋机奖池
Route::get('gashaponSeekRuning', 'GashaponConfigController/gashaponSeekRuning'); //查看扭蛋机运行中的
Route::get('gashaponConDel', 'GashaponConfigController/gashaponConDel'); //删除配置
Route::get('gashaponDetail', 'GashaponConfigController/gashaponDetail'); //删除配置
Route::get('memberAuditLog', 'MemberController/memberAuditLog'); //用户审核的日志操作
Route::get('complaintsUserList', 'MemberController/complaintsUserList'); //举报用户列表
Route::post('complaintsUserChangeStatus', 'MemberController/complaintsUserChangeStatus'); //举报用户列表
Route::get('complaintsUserDetail', 'MemberController/complaintsUserDetail'); //举报用户详情
Route::get('anchorcplist', 'AnchorcpController/AnchorcpList'); //主播cp模式主播列表
Route::post('anchorcpadd', 'AnchorcpController/anchorcpadd'); //主播cp模式主播添加
Route::post('anchorcpdel', 'AnchorcpController/anchorcpdel'); //主播cp模式主播添加
Route::get('anchorcppromotelist', 'AnchorcpController/anchorcppromoteList'); //主播cp绑定的用户数据
Route::post('anchorcppromoteabinduser', 'AnchorcpController/anchorcppromoteBinduser'); //添加或者解除绑定关系
Route::get('cpsendgiftdetail', 'AnchorcpController/cpSendGiftDetail'); //主播cp绑定用户的直刷礼物列表
Route::get('decrymp4file', 'AttireController/decrymp4File'); //解密mp4文件
Route::get('cpchargedetail', 'AnchorcpController/cpChargeDetail'); //cp充值用户详情
Route::get('withdrawalpayaccount', 'WithdrawalController/withDrawalPayAccount'); //提现账户的列表
Route::post('withdrawalpayaccountupdate', 'WithdrawalController/withDrawalPayAccountUpdate'); //提现账户的选择
Route::post('withdrawalcbacknotice', 'DalongController/withdrawalcbackNotice'); //大珑第三方转账的回调
Route::get('widthdrawallist', 'UserWithdrawalController/withdrawalList'); //提现申请列表
Route::post('widthdrawalhandle', 'UserWithdrawalController/widthdrawalHandle'); //提现操作
Route::get('giftWallConfList', 'GiftWallConfController/giftWallConfList'); //新的礼物墙的展示列表
Route::post('giftWallConfAdd', 'GiftWallConfController/giftWallConfAdd'); //新的礼物墙礼物新增
Route::post('giftWallConfDel', 'GiftWallConfController/giftWallConfDel'); //新的礼物墙礼物新增
Route::post('addTeaVoiceWhiteUser', 'TeaWhiteController/addTeaVoiceWhiteUser'); //茶茶语音用户白名单添加
Route::post('getTeaVoiceWhiteUserList', 'TeaWhiteController/getTeaVoiceWhiteUserList'); //茶茶语音用户白名单添加
Route::get('teawhiteuserList', 'TeaWhiteController/teawhiteuserList'); //茶茶语音用户白名单添加
Route::get('teablackuserList', 'TeaWhiteController/teablackuserList'); //茶茶语音用户黑名单列表
Route::post('anchorcpagree', 'AnchorcpController/anchorcpagree'); //主播cp模式同意工会申请
Route::post('roomPersonCheck', 'MemberController/roominfoPersonCheck'); //主播cp模式同意工会申请
Route::get('userroomcheckList', 'MemberController/userroomcheckList'); //用户房间信息审核
Route::get('withdrawWhiteList', 'WithdrawalController/withdrawWhiteList'); //用户提现白名单
Route::post('withdrawWhiteListAdd', 'WithdrawalController/withdrawWhiteListAdd'); //用户提现白名单新增或者编辑
Route::post('enterRoomUserList', 'RoomCoinController/enterRoomUserList'); //用户进房间列表
Route::get('whisperList', 'ConfigController/whisperList'); //悄悄话列表
Route::post('whisperHandle', 'ConfigController/whisperHandle'); //悄悄话管理
<?php

namespace constant;

class CodeConstant
{
    /*
     * Code 码处理
     * */

    //成功
    const CODE_成功 = 200;
    const CODE_暂无数据 = 201;
    const CODE_插入成功 = 202;
    const CODE_更新成功 = 203;
    const CODE_删除成功 = 204;
    const CODE_提现申请成功 = 205;
    const CODE_提现进度发起私信成功 = 206;
    const CODE_上传成功 = 207;
    const CODE_账户绑定成功 = 208;

    const CODE_OK_MAP = [
        self::CODE_成功 => '成功',
        self::CODE_暂无数据 => '暂无数据',
        self::CODE_插入成功 => '插入成功',
        self::CODE_更新成功 => '更新成功',
        self::CODE_删除成功 => '删除成功',
        self::CODE_提现申请成功 => '提现申请成功',
        self::CODE_提现进度发起私信成功 => '提现进度发起私信成功',
        self::CODE_账户绑定成功 => '账户绑定成功',
    ];

    /*
     * 失败
     */

    //参数错误
    const CODE_参数错误 = -1000;
    const CODE_账户错误 = -1001;
    const CODE_密码错误 = -1002;
    const CODE_Token错误 = -1003;
    const CODE_接口地址错误 = -1004;
    const CODE_用户ID错误 = -1005;
    const CODE_礼物ID错误 = -1006;
    const CODE_ID错误 = -1007;
    const CODE_请输入正确的角色名称 = -1008;
    const CODE_角色名称已存在 = -1009;
    const CODE_管理员名称已存在 = -1010;
    const CODE_管理员真实姓名已存在 = -1011;
    const CODE_房间ID不能为空 = -1012;
    const CODE_房间类型错误 = -1013;
    const CODE_分页或条数不能为空 = -1014;
    const CODE_动态ID不能为空 = -1015;
    const CODE_动态评论ID不能为空 = -1016;
    const CODE_用户已经加入了其他公会 = -1017;
    const CODE_用户已经创建了其他公会 = -1018;
    const CODE_添加的用户没有指定角色 = -1019;
    const CODE_没有该角色 = -1020;
    const CODE_节点名称已存在 = -1021;
    const CODE_公会分成比例不能超过百分比 = -1022;
    const CODE_此用户已存在封禁 = -1023;
    const CODE_请正确输入热度值 = -1024;
    const CODE_开始时间不可为空 = -1025;
    const CODE_结束时间不可为空 = -1026;
    const CODE_结束时间必须大于开始时间 = -1027;
    const CODE_公会分成比例不能为负数 = -1028;
    const CODE_请选择不在已存在的热度值范围内 = -1029;
    const CODE_此手动热度已被删除 = -1030;
    const CODE_此手动热度已开始或已删除 = -1031;
    const CODE_内容没有进行修改 = -1032;
    const CODE_公会成员分成比例不能为负数 = -1033;
    const CODE_公会成员分成比例不能超过百分比 = -1034;
    const CODE_公会成员分成比例不能超过公会比例 = -1035;
    const CODE_此靓号已存在 = -1036;
    const CODE_此昵称已存在 = -1037;
    const CODE_此手机号已存在 = -1038;
    const CODE_此手机号不合法 = -1039;
    const CODE_清除缓存失败或者没有缓存可以清除 = -1040;
    const CODE_上传公告图片失败 = -1041;
    const CODE_上传图片失败 = -1042;
    const CODE_此条公告已经上传过请勿重复上传 = -1043;
    const CODE_此用户未申请解锁 = -1044;
    const CODE_发送验证码失败 = -1045;
    const CODE_手机号尚未注册 = -1046;
    const CODE_请正确输入验证码 = -1047;
    const CODE_您的账号已封禁 = -1048;
    const CODE_请重新登录 = -1049;
    const CODE_提现金额必须是整数 = -1072;
    const CODE_提现金额必须大于100 = -1051;
    const CODE_请选择正确的提现方式 = -1052;
    const CODE_请填写正确的账号信息 = -1053;
    const CODE_提现金额必须小于50000 = -1054;
    const CODE_可提现金额不足 = -1055;
    const CODE_提现需要实名认证 = -1056;
    const CODE_提现申请已打款中 = -1057;
    const CODE_同意打款申请信息错误 = -1058;
    const CODE_此用户未开启过监控模式 = -1059;
    const CODE_此条内容已经拒绝过请勿重复操作 = -1060;
    const CODE_提现申请正在拒绝中 = -1061;
    const CODE_此条内容已经通过请勿重复操作 = -1062;
    const CODE_此条内容状态有误 = -1063;
    const CODE_今日提现额度已用完 = -1064;
    const CODE_提现额度大于今日可提现额度 = -1065;
    const CODE_提现金额必须为100或者100的倍数 = -1066;
    const CODE_房间关联渠道错误 = -1067;
    const CODE_用户当前装备已过期 = -1068;
    const CODE_用户当前装备已穿戴 = -1069;
    const CODE_该装备不存在 = -1070;
    const CODE_用户已拥有永久时效的该装备 = -1071;
    const CODE_请正确填写活动名称 = -1073;
    const CODE_活动页地址不可为空 = -1074;
    const CODE_活动信息不可为空 = -1075;
    const CODE_活动内容档位最大不可超过四个 = -1076;
    const CODE_用户个性签名可超过50字符 = -1077;
    const CODE_推荐房间ID已存在 = -1078;
    const CODE_隐身用户ID已存在 = -1079;
    const CODE_此房间已加入其他公会 = -1080;
    const CODE_用户钱包不能为负数 = -1081;
    const CODE_音乐ID不能为空 = -1082;
    const CODE_公会ID不存在 = -1083;
    const CODE_当前房间正在游戏中 = -1084;
    const CODE_此条内容已经转账成功请勿重复操作 = -1085;

    const CODE_PARAMETER_ERR_MAP = [
        self::CODE_参数错误 => '参数错误',
        self::CODE_账户错误 => '账户错误',
        self::CODE_密码错误 => '密码错误',
        self::CODE_Token错误 => 'Token错误',
        self::CODE_接口地址错误 => '接口地址错误',
        self::CODE_用户ID错误 => '用户ID错误',
        self::CODE_礼物ID错误 => '礼物ID错误',
        self::CODE_ID错误 => 'ID错误',
        self::CODE_请输入正确的角色名称 => '请输入正确的角色名称',
        self::CODE_角色名称已存在 => '角色名称已存在',
        self::CODE_管理员名称已存在 => '管理员名称已存在',
        self::CODE_管理员真实姓名已存在 => '管理员真实姓名已存在',
        self::CODE_房间ID不能为空 => '房间ID不能为空',
        self::CODE_房间类型错误 => '房间类型错误',
        self::CODE_分页或条数不能为空 => '分页或条数不能为空',
        self::CODE_动态ID不能为空 => '动态ID不能为空',
        self::CODE_动态评论ID不能为空 => '动态评论ID不能为空',
        self::CODE_用户已经加入了其他公会 => '用户已经加入了其他公会',
        self::CODE_用户已经创建了其他公会 => '用户已经创建了其他公会',
        self::CODE_添加的用户没有指定角色 => '添加的用户没有指定角色',
        self::CODE_没有该角色 => '没有该角色',
        self::CODE_节点名称已存在 => '节点名称已存在',
        self::CODE_公会分成比例不能超过百分比 => '公会分成比例不能超过百分比',
        self::CODE_此用户已存在封禁 => '此用户此存在封禁',
        self::CODE_请正确输入热度值 => '请正确输入热度值',
        self::CODE_开始时间不可为空 => '开始时间不可为空',
        self::CODE_结束时间不可为空 => '结束时间不可为空',
        self::CODE_结束时间必须大于开始时间 => '结束时间必须大于开始时间',
        self::CODE_公会分成比例不能为负数 => '公会分成比例不能为负数',
        self::CODE_请选择不在已存在的热度值范围内 => '请选择不在已存在的热度值范围内',
        self::CODE_此手动热度已被删除 => '此手动热度已被删除',
        self::CODE_此手动热度已开始或已删除 => '此手动热度已开始或已删除',
        self::CODE_内容没有进行修改 => '内容没有进行修改',
        self::CODE_公会成员分成比例不能为负数 => '公会成员分成比例不能为负数',
        self::CODE_公会成员分成比例不能超过百分比 => '公会成员分成比例不能超过百分比',
        self::CODE_公会成员分成比例不能超过公会比例 => '公会成员分成比例不能超过公会比例',
        self::CODE_此靓号已存在 => '此靓号已存在',
        self::CODE_此昵称已存在 => '此昵称已存在',
        self::CODE_此手机号已存在 => '此手机号已存在',
        self::CODE_此手机号不合法 => '此手机号不合法',
        self::CODE_清除缓存失败或者没有缓存可以清除 => '清除缓存失败或者没有缓存可以清除',
        self::CODE_上传公告图片失败 => '上传公告图片失败',
        self::CODE_上传图片失败 => '上传图片失败',
        self::CODE_此条公告已经上传过请勿重复上传 => '此条公告已经上传过请勿重复上传',
        self::CODE_此用户未申请解锁 => '此用户未申请解锁',
        self::CODE_发送验证码失败 => '发送验证码失败,请稍后重试',
        self::CODE_手机号尚未注册 => '手机号尚未注册，请使用登录App的手机号进行登录',
        self::CODE_请正确输入验证码 => '请正确输入验证码',
        self::CODE_您的账号已封禁 => '您的账号已封禁，请联系客服或公众号留言',
        self::CODE_请重新登录 => '请重新登录',
        self::CODE_提现金额必须是整数 => '提现金额必须是整数',
        self::CODE_提现金额必须大于100 => '提现金额必须大于100',
        self::CODE_可提现金额不足 => '可提现金额不足',
        self::CODE_请选择正确的提现方式 => '请选择正确的提现方式',
        self::CODE_请填写正确的账号信息 => '请填写正确的账号信息',
        self::CODE_提现金额必须小于50000 => '提现金额必须小于50000',
        self::CODE_提现需要实名认证 => '提现需要实名认证,请前往App进行认证',
        self::CODE_提现申请已打款中 => '提现申请已打款中,请勿重复同意',
        self::CODE_同意打款申请信息错误 => '同意打款申请信息错误,请联系开发者查询实际问题',
        self::CODE_此用户未开启过监控模式 => '此用户未开启过监控模式',
        self::CODE_请正确填写活动名称 => '请正确填写活动名称',
        self::CODE_活动页地址不可为空 => '活动页地址不可为空',
        self::CODE_活动信息不可为空 => '活动信息不可为空',
        self::CODE_此条内容已经拒绝过请勿重复操作 => '此条内容已经拒绝过请勿重复操作',
        self::CODE_提现申请正在拒绝中 => '提现申请正在拒绝中,请勿重复拒绝',
        self::CODE_此条内容已经通过请勿重复操作 => '此条内容已经通过请勿重复操作',
        self::CODE_此条内容状态有误 => '此条内容已经【同意/拒绝】,请勿重复发起申请',
        self::CODE_今日提现额度已用完 => '今日提现额度已用完,明天再来吧~',
        self::CODE_提现额度大于今日可提现额度 => '提现额度大于今日可提现额度',
        self::CODE_提现金额必须为100或者100的倍数 => '提现金额必须为100或者100的倍数',
        self::CODE_房间关联渠道错误 => '房间关联渠道错误',
        self::CODE_用户当前装备已过期 => '用户当前装备已过期',
        self::CODE_用户当前装备已穿戴 => '用户当前装备已穿戴',
        self::CODE_该装备不存在 => '该装备不存在',
        self::CODE_用户已拥有永久时效的该装备 => '用户已拥有永久时效的该装备',
        self::CODE_活动内容档位最大不可超过四个 => '活动内容档位最大不可超过四个',
        self::CODE_用户个性签名可超过50字符 => '用户个性签名可超过50字符',
        self::CODE_推荐房间ID已存在 => '推荐房间ID已存在',
        self::CODE_隐身用户ID已存在 => '隐身用户ID已存在',
        self::CODE_此房间已加入其他公会 => '此房间已加入其他公会',
        self::CODE_用户钱包不能为负数 => '用户钱包不能为负数',
        self::CODE_音乐ID不能为空 => '音乐ID不能为空',
        self::CODE_公会ID不存在 => '公会ID不存在',
        self::CODE_当前房间正在游戏中 => '当前房间正在游戏中',
        self::CODE_此条内容已经转账成功请勿重复操作 => '此条内容已经转账成功请勿重复操作',
    ];

    //内部错误
    const CODE_内部错误 = -2000;
    const CODE_用户不存在 = -2001;
    const CODE_没有查询到数据 = -2002;
    const CODE_用户未登录 = -2003;
    const CODE_该用户没有权限 = -2004;
    const CODE_插入失败 = -2005;
    const CODE_更新失败 = -2006;
    const CODE_删除失败 = -2007;
    const CODE_提现申请失败 = -2008;
    const CODE_提现进度发起私信失败 = -2009;
    const CODE_上传失败 = -2010;
    const CODE_主播ID不存在 = -2011;
    const CODE_房间ID不存在 = -2012;
    const CODE_您还未实名认证哦 = -2013;

    const CODE_INSIDE_ERR_MAP = [
        self::CODE_内部错误 => '内部错误',
        self::CODE_用户不存在 => '用户不存在',
        self::CODE_没有查询到数据 => '没有查询到数据',
        self::CODE_用户未登录 => '用户未登录',
        self::CODE_该用户没有权限 => '该用户没有权限',
        self::CODE_插入失败 => '插入失败',
        self::CODE_更新失败 => '更新失败',
        self::CODE_删除失败 => '删除失败',
        self::CODE_提现申请失败 => '提现申请失败',
        self::CODE_提现进度发起私信失败 => '提现进度发起私信失败',
        self::CODE_您还未实名认证哦 => '您还未实名认证哦',
    ];

}

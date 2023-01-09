<?php
/**
 * 公共配置
 * */
namespace app\admin\common;

class AdminCommonConfig
{
    const IMAGE_URL = 'http://img.57xun.com';

    const SEX = [
        1 => '男',
        2 => '女',
        3 => '保密',
    ];

    const ADMIN_USER_UID = 'admin:user:uid';

    const STATUS_MAP = [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核拒绝',
        // 3 => '已删除',
    ];

    const MEMBER_ACTION = [
        'avatar' => '用户头像',
        'nickname' => '用户昵称',
        'intro' => '用户信息',
        'wall' => '用户墙',
        'voice' => '用户语音',
        'roomName' => '房间名称',
        'roomWelcomes' => '房间欢迎语',
        'roomDesc' => '房间公告',
    ];

    const FORBID_MAP = [
        1 => 'IP',
        2 => '设备',
        3 => '身份证',
        4 => '账号',
        5 => '设备唯一标识',
    ];

}
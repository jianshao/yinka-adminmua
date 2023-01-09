<?php
/**
 * 参数配置
 * User: huashan
 * Date: 2020/10/17
 * Time: 15:29
 */

namespace app\admin\common\dalong\config;


class Config
{
/*    public static $config = [
        // 'openAPIUrl'=>'https://apitest.dalongsc.com/', // 模拟环境
        'openAPIUrl'=>'https://apitest.dalongsc.com/', // 正式环境
        'app_key'=>'948327443775619072', //app id
        'secret'=>'6a5c42891fbd4e0490bd2d92a11e4c46', // app key

        'default_version'=>'',
        'api_name' =>'name',
        'version_name'=>'version',
        'app_key_name'=>'',
        'data_name'=>'',
        'timestamp_name' => "timestamp",
        'sign_name' => "sign",
        'format_name '=> "format",
        'access_token_name ' => "access_token",
        'jwt' => ''
    ];*/

    public static $urlMap = [
        'JWTLOGIN'=>'jwtLogin',//项目初始化
        //自由职业者
        'EMPLOYEES_ADD'=>'/api/employees.add', //添加自由职业者
        'EMPLOYEES_INSP_ADD'=>'/api/employees.insp.free.add', //添加自由职业者（身份证照片）
        'EMPLOYEES_DETAIL'=>'/api/employees.get', //获取自由职业者详情
        'EMPLOYEES_LIST'=>'/api/employees.getList', //获取自由职业者列表
        'EMPLOYEES_UPDATE'=>'/api/employees.update', //修改自由职业者
        'EMPLOYEES_LOGOUT'=>'/api/employees.logout', //注销自由职业者

        //渠道列表
        'CHANNEL_LIST'=>'/api/channel.list', //获取渠道列表

        //单笔提现
        'ORDER_ADD'=>'/api/order.add', //创建订单 支付宝
        'ORDER_DETAIL'=>'/api/ordre.get', //查询订单
        'ORDER_LIST'=>'/api/order.page', //查询订单列表


        //批量提现
        'ORDER_BATCH_ADD'=>'/api/order.batch.add', //创建订单
        'ORDER_BATCH_CONFIRM'=>'/api/order.batch.confirm', //确认批次订单
        'ORDER_BATCH_CANCEL'=>'/api/order.batch.cancel', //取消订单
        'ORDER_BATCH_LIST'=>'/api/order.batch.page',//订单批次列表
        'ORDER_BATCH_DETAIL'=>'/api/order.batch.detail',//订单详情
        'ORDER_BATCH_DETAIL_LIST'=>'/api/order.batch.detailPage', //订单详细列表

    ];

}

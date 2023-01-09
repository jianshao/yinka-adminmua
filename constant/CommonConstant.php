<?php

namespace constant;

class CommonConstant
{
    /*
     * redis
     */
    //登录用户的个人信息redis key 需要拼接用户id
    const ADMIN_USER_UID = 'admin_user_uid:';
    /*
     * 定时公告redis key
     */
    const NOTICE_TIMING_KEY = 'notice:timing:id:';

    //登录用户的个人登录信息redis key 需要拼接用户id
    const ADMIN_USER_LOGIN_HISTORY = 'amin_user_login_history_uid:';

    //token key
    const TOKEN_KEY = 'HS256';

    //web用户提现token redis key
    const WEB_USER_WITHDRAWAL_TOKEN = 'web_user_withdrawal_token:';
    //web用户提现当月总额 redis key
    const WEB_USER_WITHDRAWAL_MONEY_COUNT = 'web_user_withdrawal_money_count:';
    //BI登录用户的个人信息redis key 需要拼接用户id
    const BI_ADMIN_USER_UID = 'Bi_admin_user_uid:';

    const WEB_USER_WITHDRAWAL_PAY_TYPE = 'web_user_withdrawal_pay_type:';
    const WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_ID = 'web_user_withdrawal_record_existence_id:';
    const WEB_USER_WITHDRAWAL_REFUSE_EXISTENCE_ID = 'web_user_withdrawal_refuse_existence_id:';
    const WEB_USER_WITHDRAWAL_RECORD_EXISTENCE_OID = 'web_user_withdrawal_record_existence_oid:';
    const WEB_USER_WITHDRAWAL_RESIDUE_MONEY = 'web_user_withdrawal_residue_money:';

    /*
     * token不校验
     */
    const TOKEN_NO_CHECK_URI_MAP = [
        '/admin/loginIndex',
        '/admin/login',
        '/admin/index',
        '/admin/indexConsole',
        '/admin/loginOut',
        '/admin/caseObtain',
        '/admin/noticeTimingCommand',
        '/admin/test',
        '/admin/uploadIndex',
        '/admin/excelDataLists',
        '/admin/excelList',
    ];

    const WITHDRAW_PREMIT_ACTION = [
        'withdrawlogin', 'webUserWithdrawalCodeCheck',
    ];
}
<?php
/*
检测是否登录
 */
namespace app\middleware;

use constant\CommonConstant;

class CheckLogin
{
    public function handle($request, \Closure $next)
    {
        $noLogin = CommonConstant::WITHDRAW_PREMIT_ACTION; //不登录路由地址
        $action = $request->action(true);
        if (!in_array($action, $noLogin)) {
            $params = $request->param();

            //判断token
            if (!isset($params['token']) || empty($params['token'])) {
                echo return_json(\constant\CodeConstant::CODE_参数错误, [], \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_参数错误]);die;
            }
        }

        return $next($request);
    }
}
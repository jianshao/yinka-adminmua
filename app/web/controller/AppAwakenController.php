<?php


namespace app\web\controller;


use app\BaseController;
use think\facade\View;

class AppAwakenController extends BaseController
{
    public function indexPlatform(){
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
            $get = isset(explode('?', $_SERVER['REQUEST_URI'])[1]) ? '?'.explode('?', $_SERVER['REQUEST_URI'])[1] : '';
            View::assign('get', $get);
            return View::fetch('../web/indexIos');
        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            $get = isset(explode('?', $_SERVER['REQUEST_URI'])[1]) ? explode('?', $_SERVER['REQUEST_URI'])[1] : '';
            View::assign('get', $get);
            return View::fetch('../web/indexAndroid');
        }else{
            echo '非法来源';die;
        }
    }

    public function indexPlatformzh(){
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
            $get = isset(explode('?', $_SERVER['REQUEST_URI'])[1]) ? '?'.explode('?', $_SERVER['REQUEST_URI'])[1] : '';
            View::assign('get', $get);
            return View::fetch('../web/indexIoszh');
        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            $get = isset(explode('?', $_SERVER['REQUEST_URI'])[1]) ? explode('?', $_SERVER['REQUEST_URI'])[1] : '';
            View::assign('get', $get);
            return View::fetch('../web/indexAndroidzh');
        }else{
            echo '非法来源';die;
        }
    }
}
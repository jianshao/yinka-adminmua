<?php


namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ConfigModel;
use app\admin\model\GiftGameModel;
use app\admin\model\GiftModel;
use app\admin\model\SiteconfigModel;
use app\admin\service\Config2Service;
use app\common\RedisCommon;
use think\facade\Log;
use think\facade\View;
use think\facade\Request;

class Config2Controller extends AdminBaseController
{
    public function roomTagList(){
        $data = Config2Service::getInstance()->roomTagList(Request::param());
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('siteconfig/roomTag/index');
    }

    public function roomTagAdd(){
        echo Config2Service::getInstance()->roomTagAdd(Request::param());
    }

    public function roomTagSave(){
        echo Config2Service::getInstance()->roomTagSave(Request::param());
    }

    public function getRoomTag(){
        echo Config2Service::getInstance()->getRoomTag(Request::param());
    }
    
}
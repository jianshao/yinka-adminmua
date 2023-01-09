<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\TurntableBurstService;
use think\facade\Request;
use think\facade\View;

class TurntableBurstController extends AdminBaseController
{
    //转盘
/*    public function cancelTheSpecifiedTurntableGift()
    {
        echo TurntableBurstService::getInstance()->cancelTheSpecifiedTurntableGift(Request::param());
    }

    public function addTheSpecifiedTurntableGift()
    {
        $username = $this->token['username'];
        echo TurntableBurstService::getInstance()->addTheSpecifiedTurntableGift(Request::param(), $username);
    }

    public function TheSpecifiedTurntableGift()
    {
        $data = TurntableBurstService::getInstance()->TheSpecifiedTurntableGift(Request::param());
        View::assign('data', $data['data']);
        View::assign('demo', $data['demo']);
        View::assign('uid', $data['uid']);
        View::assign('type', $data['type']);
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('turntable/TheSpecifiedTurntableGift');
    }*/

    public function BoxDetails()
    {
        $data = TurntableBurstService::getInstance()->BoxDetails(Request::param());
        View::assign('data', $data['data']);
        View::assign('demo', $data['demo']);
        View::assign('uid', $data['uid']);
        View::assign('type', $data['type']);
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/BoxDetails');
    }

    public function BoxBurstRate()
    {
        $data = TurntableBurstService::getInstance()->BoxBurstRate(Request::param());
        View::assign('order', $data['order']);
        View::assign('info', $data['info']);
        View::assign('data', $data['data']);
        View::assign('demo', $data['demo']);
        View::assign('uid', $data['uid']);
        View::assign('type', $data['type']);
        View::assign('page', $data['page_array']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/BoxBurstRate');
    }

}
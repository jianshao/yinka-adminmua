<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\BoxConfService;
use think\facade\Request;
use think\facade\View;

class BoxConfController extends AdminBaseController
{
    public function jackpotTheRemaining()
    {
        $data = BoxConfService::getInstance()->jackpotTheRemaining(Request::param());
        View::assign('data', $data);
        View::assign('boxId', Request::param('boxId'));
        View::assign('poolId', Request::param('poolId'));
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/jackpotThe');
    }

    public function refreshAllPool()
    {
        return BoxConfService::getInstance()->refreshAllPool(Request::param());
    }

    public function clearCacheBoxConf()
    {
        return BoxConfService::getInstance()->clearCacheBoxConf();
    }

    public function boxSwitch()
    {
        return BoxConfService::getInstance()->boxSwitch(Request::param());
    }

    public function saveBoxForm()
    {
        return BoxConfService::getInstance()->saveBoxForm(Request::param());
    }

    public function savePool()
    {
        return BoxConfService::getInstance()->savePool(Request::param());
    }

    public function addBoxPool()
    {
        return BoxConfService::getInstance()->addBoxPool(Request::param());
    }

    public function boxPool()
    {
        $data = BoxConfService::getInstance()->boxPool(Request::param());
        View::assign('data', $data['data']);
        View::assign('count', $data['count']);
        View::assign('boxId', Request::param('boxId'));
        View::assign('boxName', $data['boxName']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/boxPool');
    }

    public function getBoxConf()
    {
        $data = BoxConfService::getInstance()->getBoxConf();
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('box/index');
    }

    public function addBox()
    {
        return BoxConfService::getInstance()->addBox(Request::param());
    }

    public function getBoxGift()
    {
        return BoxConfService::getInstance()->getBoxGift(Request::param());
    }

    public function saveBox()
    {
        return BoxConfService::getInstance()->saveBox(Request::param());
    }

    public function getCondition()
    {
        return BoxConfService::getInstance()->getCondition(Request::param());
    }

    public function getPoolGift()
    {
        return BoxConfService::getInstance()->getPoolGift(Request::param());
    }

}
<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\TurntableConfService;
use think\facade\Request;
use think\facade\View;

class TurntableConfController extends AdminBaseController
{
    public function jackpotTurntableTheRemaining()
    {
        $data = TurntableConfService::getInstance()->jackpotTurntableTheRemaining(Request::param());
        View::assign('data', $data);
        View::assign('turntableId', Request::param('turntableId'));
        View::assign('poolId', Request::param('poolId'));
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('turntable/jackpotThe');
    }

    public function refreshAllTurntablePool()
    {
        return TurntableConfService::getInstance()->refreshAllTurntablePool(Request::param());
    }

    public function clearCacheTurntableConf()
    {
        return TurntableConfService::getInstance()->clearCacheTurntableConf();
    }

    public function turntableSwitch()
    {
        return TurntableConfService::getInstance()->turntableSwitch(Request::param());
    }

    public function saveTurntableForm()
    {
        return TurntableConfService::getInstance()->saveTurntableForm(Request::param());
    }

    public function saveTurntablePool()
    {
        $giftsVal = Request::param('giftsVal');
        $giftsKey = Request::param('giftsKey');
        $type = Request::param('type');

        if ((($giftsVal && count($giftsVal) != 8) || ($giftsVal && count($giftsKey) != 8) || empty($giftsVal) || empty($giftsKey)) && $type != 'delete') {
            echo json_encode(['code' => 500, 'msg' => '奖池礼物必选为8个']);die;
        }
        return TurntableConfService::getInstance()->saveTurntablePool(Request::param());
    }

    public function addTurntablePool()
    {
        $giftsVal = Request::param('giftsVal');
        $giftsKey = Request::param('giftsKey');

        if (($giftsVal && count($giftsVal) != 8) || ($giftsVal && count($giftsKey) != 8) || empty($giftsVal) || empty($giftsKey)) {
            echo json_encode(['code' => 500, 'msg' => '奖池礼物必选为8个']);die;
        }
        return TurntableConfService::getInstance()->addTurntablePool(Request::param());

    }

    public function turntablePool()
    {
        $data = TurntableConfService::getInstance()->turntablePool(Request::param());
        View::assign('data', $data['data']);
        View::assign('count', $data['count']);
        View::assign('turntableId', Request::param('turntableId'));
        View::assign('turntableName', $data['turntableName']);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('turntable/turntablePool');
    }

    //转盘
    public function getTurntableConf()
    {
        $data = TurntableConfService::getInstance()->getTurntableConf();
        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        return View::fetch('turntable/index');
    }

    public function addTurntable()
    {
        return TurntableConfService::getInstance()->addTurntable(Request::param());
    }

    public function getTurntableGift()
    {
        return TurntableConfService::getInstance()->getTurntableGift(Request::param());
    }

    public function saveTurntable()
    {
        return TurntableConfService::getInstance()->saveTurntable(Request::param());
    }

    public function getTurntableCondition()
    {
        return TurntableConfService::getInstance()->getTurntableCondition(Request::param());
    }

    public function getTurntablePoolGift()
    {
        return TurntableConfService::getInstance()->getTurntablePoolGift(Request::param());
    }

}
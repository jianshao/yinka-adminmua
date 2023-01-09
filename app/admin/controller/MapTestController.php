<?php


namespace app\admin\controller;


use app\admin\common\AdminBaseController;
use think\facade\View;

class MapTestController extends AdminBaseController
{
    public function mapIndex()
    {
        return View::fetch('mapTest/index');
    }

}
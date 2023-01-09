<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\WeShineService;
use think\facade\Request;

class WeShineController extends AdminBaseController
{
    /**
     * Notes: 闪萌搜索接口
     */
    public function shineAlbumSearch()
    {
        $keyWord = Request::param('keyword', '');
        $data = WeShineService::getInstance()->shineAlbumSearch($keyWord);
        return rjson($data);
    }
}

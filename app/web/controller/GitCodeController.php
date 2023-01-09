<?php
/**
 * 
 */

namespace app\web\controller;

use think\facade\Request;
use app\BaseController;
use think\facade\Db;
use think\facade\Log;


class GitCodeController extends BaseController
{
	public function getpaihangbang()
    {
    	$data = $this->request->param();
    	Log::write('Git--排行榜------'.json_encode($data),'notice');
        // exec('cd /www/wwwroot/muatest/Muayy/mua/view/web; git pull 2<&1; chown -R www:www /home/wwwroot/app/*;', $output);
        exec('cd /www/wwwroot/muatest/Muayy/mua/view/web; git pull 2<&1;');
    }

   




}
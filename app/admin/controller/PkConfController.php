<?php
/**
 * Created by PhpStorm.
 * User: pussycat
 * Date: 2019/7/23
 * Time: 21:01
 */

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\service\PkConfService;
use think\Exception;
use think\facade\Request;
use think\facade\View;

class PkConfController extends AdminBaseController
{
    //首页配置
    public function show()
    {
        $list = PkConfService::getInstance()->pkInfo();
        $types = ['1', '2', '4', '8', '16'];

        $data = ['rooms' => [], 'start_time' => '', 'stop_time' => ''];
        foreach ($list as $key => $value) {
            $data['start_time'] = isset($list['start_time']) ?? '';
            $data['stop_time'] = isset($list['stop_time']) ?? '';
            if (in_array($key, $types)) {
                $rooms = json_decode($value);
                $data['rooms'][$key] = $rooms;
            }
        }
        View::assign('list', $data);
        View::assign('types', $types);
        View::assign('token', $this->request->param('token'));
        return View::fetch('activity/pk/config');
    }

    //首页房间配置保存
    public function save()
    {
        try {
            $params = (array) Request::param();
            PkConfService::getInstance()->save($params);
            echo json_encode(['code' => 200, 'msg' => '操作完成']);
        } catch (Exception $e) {
            echo json_encode(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }
    //首页房间配置保存
    public function startCrossPk()
    {
        try {
            $params = (array) Request::param();
            PkConfService::getInstance()->startCrossPk($params);
            echo json_encode(['code' => 200, 'msg' => '操作完成']);
        } catch (Exception $e) {
            echo json_encode(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }
    //首页房间配置保存
    public function endCrossPk()
    {
        try {
            $params = (array) Request::param();
            PkConfService::getInstance()->endCrossPk($params);
            echo json_encode(['code' => 200, 'msg' => '操作完成']);
        } catch (Exception $e) {
            echo json_encode(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }
}

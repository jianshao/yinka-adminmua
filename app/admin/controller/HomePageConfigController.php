<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ConfigModel;
use app\admin\service\HomePageConfService;
use app\admin\service\UploadFileService;
use MQ\Config;
use OSS\Core\OssException;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class HomePageConfigController extends AdminBaseController
{
    /*
     * 阿里OSS
     */
    public function ossFile()
    {
        $image = request()->file('image');
        if (empty($image)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);die;
        }
        try {
            $res = UploadFileService::getInstance()->uplaodFile('/bottomNav', $image);
            Log::record('添加图片成功:操作人:' . $this->token['username'] . ':更新条件:' . ':内容:', 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (OssException $e) {
            Log::record('添加图片失败:操作人:' . $this->token['username'] . ':更新条件:' . ':内容:', 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    public function show()
    {
        $app = Request::param('app');
        $json = ConfigModel::getInstance()->getModel()->where('name', 'bottom_menu_conf')->value('json');
        $data = json_decode($json, true);

        $button_map = [
            'home' => '首页',
            'chat' => '聊天',
            'myself' => '我的',
            'forum' => '帖子',
            'party' => '派对',
        ];

        $list = [];
        if ($data) {
            foreach ($data as $app => $item) {
                foreach ($item as $type_name => $button_conf) {
                    $button_conf['type_name'] = $type_name;
                    $button_conf['app'] = $app;
                    if (empty($button_conf['click_icon'])) {
                        $button_conf['click_icon'] = '';
                    } else {
                        $button_conf['click_icon'] = config('config.APP_URL_image') . $button_conf['click_icon'];
                    }
                    $button_conf['default_font_color'] = isset($button_conf['default_font_color']) ? $button_conf['default_font_color'] : '';
                    $button_conf['click_font_color'] = isset($button_conf['click_font_color']) ? $button_conf['click_font_color'] : '';

                    if (empty($button_conf['default_icon'])) {
                        $button_conf['default_icon'] = '';
                    } else {
                        $button_conf['default_icon'] = config('config.APP_URL_image') . $button_conf['default_icon'];
                    }
                    $list[] = $button_conf;
                }
            }
        }

        Log::record('房间类型列表:操作人:' . $this->token['username'], 'roomTypeList');

        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('list', $list);
        View::assign('app', $app);
        View::assign('button_map', $button_map);
        return View::fetch('homePage/index');
    }

    public function add()
    {
        $data = Request::param();
        $is = HomePageConfService::getInstance()->add($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败！']);
            die;
        }
    }

    public function save()
    {
        $data = Request::param();
        $is = HomePageConfService::getInstance()->update($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败！']);
            die;
        }
    }

    /**
     * 清除缓存redis
     */
    public function clear()
    {
        try {
            $res = HomePageConfService::getInstance()->clearCache($this->token['id']);
            if ($res && $res['code'] == 0) {
                $data = ['code' => 200, 'msg' => '更新成功'];
            } else {
                $data = ['code' => 500, 'msg' => $res['desc']];
            }
            echo json_encode($data);die;
        } catch (\Throwable$th) {
            echo json_encode(['code' => 500, 'msg' => '更新失败']);die;
        }
    }
}

<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\ApiUrlConfig;
use app\admin\model\AttireModel;
use app\admin\model\DukeLogModel;
use app\admin\model\DukeModel;
use app\admin\model\GiftModel;
use app\admin\model\MemberModel;
use app\admin\service\ApiService;
use app\exceptions\ApiExceptionHandle;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class DukeController extends AdminBaseController
{
    /**
     * 爵位清除缓存
     */
    public function dukeRedis()
    {
        $redis = $this->getRedis();
        $clearindex1 = $redis->del("duke_gift_res");
        $clearindex2 = $redis->del("duke_gift_all_res");
        if ($clearindex1 || $clearindex2) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    /**
     * 贵族等级列表
     */
    public function dukeList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $duke_name = Request::param('duke_name'); //爵位名称
        $admin_url = config('config.admin_url');
        $where = [];
        if ($duke_name) {
            $where[] = ['duke_name' => $duke_name];
        }
        $count = DukeModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            //todo:关联attire中的特效气泡、贵族卡片、
            $data = DukeModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $k => $v) {
                $data[$k]['upgrade_broadcast'] = $v['upgrade_broadcast'] == 1 ? '有' : '没有';
                $data[$k]['avoid_forbidwords'] = $v['avoid_forbidwords'] == 1 ? '有' : '没有';
                $data[$k]['avoid_kick'] = $v['avoid_kick'] == 1 ? '有' : '没有';
                $data[$k]['is_butler'] = $v['is_butler'] == 1 ? '有' : '没有';
                $data[$k]['special_effects'] = config('config.APP_URL_image') . $v['special_effects'];
                $data[$k]['duke_image'] = config('config.APP_URL_image') . $v['duke_image'];
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('duke/index');
    }

    //爵位详情
    public function dukeDetails()
    {
        $id = Request::param('id'); //爵位ID
        $data = DukeModel::getInstance()->getModel()->where('duke_id', $id)->field('bubble_box,car,headframe,privilege_gift,wheat_aperture,duke_name,duke_id')->select()->toArray();

        $bubble_box = json_decode($data[0]['bubble_box']);
        $car = json_decode($data[0]['car']);
        $headframe = json_decode($data[0]['headframe']);
        $privilege_gift = json_decode($data[0]['privilege_gift']);
        $wheat_aperture = json_decode($data[0]['wheat_aperture']);
        $dataLste['bubble_box'] = $bubble_box;
        $dataLste['car'] = $car;
        $dataLste['headframe'] = $headframe;
        $dataLste['privilege_gift'] = $privilege_gift;
        $dataLste['wheat_aperture'] = $wheat_aperture;
        $list['duke_name'] = $data[0]['duke_name'];
        $list['duke_id'] = $data[0]['duke_id'];
        $url = config('config.APP_URL_image');

        if (is_array($bubble_box)) {
            foreach ($bubble_box as $k => $v) {
                $list['bubble_box'][$k] = $url . AttireModel::getInstance()->getModel()->where('id', $v)->value('attire_image');
            }
        }

        if (is_array($car)) {
            foreach ($car as $k => $v) {
                $list['car'][$k] = $url . AttireModel::getInstance()->getModel()->where('id', $v)->value('attire_image');
            }
        }

        if (is_array($headframe)) {
            foreach ($headframe as $k => $v) {
                $list['headframe'][$k] = $url . AttireModel::getInstance()->getModel()->where('id', $v)->value('attire_image');
            }
        }
        if (is_array($wheat_aperture)) {
            foreach ($wheat_aperture as $k => $v) {
                $list['wheat_aperture'][$k] = $url . AttireModel::getInstance()->getModel()->where('id', $v)->value('attire_image');
            }
        }
        if (is_array($privilege_gift)) {
            foreach ($privilege_gift as $k => $v) {
                $list['privilege_gift'][$k] = $url . GiftModel::getInstance()->getModel()->where('id', $v)->value('gift_image');
            }
        }
        View::assign('dataLste', $dataLste);
        View::assign('data', $list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('duke/dukeDetails');
    }

    public function dukeListArr($data)
    {
        $data_arr = [];
        foreach ($data as $k => $v) {
            if ($v) {
                $data_arr[] = $v;
            }
        }
        return $data_arr;
    }
    /**
     * 编辑贵族等级
     */
    public function dukeSave()
    {
        $duke_id = Request::param("duke_id");
        $duke_image = Request::param("duke_image");
        $special_effects = Request::param("special_effects");
        if (!$duke_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        if (Request::param("type") && Request::param("type") == 1) {
            $data = [
                'bubble_box' => '[' . implode(',', $this->dukeListArr(Request::param('bubble_box'))) . ']',
                'car' => '[' . implode(',', $this->dukeListArr(Request::param('car'))) . ']',
                'headframe' => '[' . implode(',', $this->dukeListArr(Request::param('headframe'))) . ']',
                'privilege_gift' => '[' . implode(',', $this->dukeListArr(Request::param('privilege_gift'))) . ']',
                'wheat_aperture' => '[' . implode(',', $this->dukeListArr(Request::param('wheat_aperture'))) . ']',
            ];
        } else {
            $data = [
                'duke_name' => Request::param('duke_name'),
                'duke_coin' => Request::param('duke_coin'),
                'duke_relegation' => Request::param('duke_relegation'),
                'upgrade_broadcast' => Request::param('upgrade_broadcast'),
                'avoid_forbidwords' => Request::param('avoid_forbidwords'),
                'avoid_kick' => Request::param('avoid_kick'),
                'is_butler' => Request::param('is_butler'),
            ];
            if ($duke_image) {
                $data['duke_image'] = $duke_image;
            }
            if ($special_effects) {
                $data['special_effects'] = $special_effects;
            }
        }

        $res = DukeModel::getInstance()->getModel()->where('duke_id', $duke_id)->save($data);
        if ($res) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    /**
     * 添加贵族等级
     */
    public function dukeAdd()
    {
        if (Request::param('duke_id') == 0 || Request::param('duke_id') == '') {
            echo json_encode(['code' => 500, 'msg' => '等级不可为0或空']);die;
        }
        if (DukeModel::getInstance()->getModel()->where('duke_id', Request::param('duke_id'))->value('duke_id')) {
            echo json_encode(['code' => 500, 'msg' => '等级已存在']);die;
        }
        $request = Request::param();
        if ($request['duke_name'] == '') {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $data = [
            'duke_id' => Request::param('duke_id'),
            'duke_name' => Request::param('duke_name'),
            'duke_image' => Request::param('duke_image'),
            'duke_coin' => Request::param('duke_coin'),
            'duke_relegation' => Request::param('duke_relegation'),
            'bubble_box' => '[' . implode(',', Request::param('bubble_box')) . ']',
            'car' => '[' . implode(',', Request::param('car')) . ']',
            'headframe' => '[' . implode(',', Request::param('headframe')) . ']',
            'privilege_gift' => '[' . implode(',', Request::param('privilege_gift')) . ']',
            'wheat_aperture' => '[' . implode(',', Request::param('wheat_aperture')) . ']',
            'upgrade_broadcast' => Request::param('upgrade_broadcast'),
            'avoid_forbidwords' => Request::param('avoid_forbidwords'),
            'avoid_kick' => Request::param('avoid_kick'),
            'is_butler' => Request::param('is_butler'),
            'special_effects' => Request::param('special_effects'),
        ];
        $res = DukeModel::getInstance()->getModel()->save($data);
        if ($res) {
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /**
     * @return mixed
     * 爵位用户
     */
    public function dukeMember()
    {
        $pagenum = 5;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $duke_id = Request::param('duke_id');
        $uid = Request::param('user_id', 0);
        $where[] = ['duke_id', '<>', 0];
        if ($duke_id) {
            $where[] = ['duke_id', '=', $duke_id];
        }
        if ($uid) {
            $where[] = ['id', '=', $uid];
        }
        $count = MemberModel::getInstance()->getModel($uid)->where($where)->count();
        $data = [];
        $duke = DukeModel::getInstance()->getModel()->column('duke_id,duke_name');
        if ($count > 0) {
            //todo:关联attire中的特效气泡、贵族卡片、
            $data = MemberModel::getInstance()->getModel($uid)->where($where)->limit($page, $pagenum)->field('id,nickname,avatar,duke_id,duke_expires')->select()->toArray();
            foreach ($data as $k => $v) {
                $data[$k]['special_effects'] = $this->img_url . DukeModel::getInstance()->getModel()->where('duke_id', $v['duke_id'])->value('special_effects');
                $data[$k]['duke_image'] = $this->img_url . DukeModel::getInstance()->getModel()->where('duke_id', $v['duke_id'])->value('duke_image');
                $data[$k]['duke_name'] = DukeModel::getInstance()->getModel()->where('duke_id', $v['duke_id'])->value('duke_name');
                $data[$k]['avatar'] = $this->img_url . $v['avatar'];
                $data[$k]['create_time'] = date('Y-m-d H:i:s', DukeLogModel::getInstance()->getModel()->where('uid', $v['id'])->group('create_time desc')->value('create_time'));
                $data[$k]['expires_time'] = date('Y-m-d H:i:s', $v['duke_expires']);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('duke_id', $duke_id);
        View::assign('duke', $duke);
        View::assign('end_time', date('Y-m-d H:i:s'));
        View::assign('uid', $uid);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('duke/dukeMember');
    }

    /**
     * 给用户添加爵位
     */
    public function dukeMemberAdd()
    {
        try {
            $duke_id = Request::param('duke_id');
            $uid = Request::param('uid');
            $duke_expires = Request::param('duke_expires');

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'user_id' => $duke_id,
                'duke_id' => $duke_id,
                'duke_expires' => $duke_expires,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$member_duck_add, $params, true);

            Log::record('用户添加爵位修改成功:操作人:' . $this->token['username'] . ':被修改人:' . $uid . ':修改值为:' . json_encode(['duke_expires' => strtotime($duke_expires), 'duke_id' => $duke_id]), 'editMemberUsername');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('用户添加爵位修改失败:操作人:' . $this->token['username'] . ':被修改人:' . $uid . ':修改值为:' . json_encode(['duke_expires' => strtotime($duke_expires), 'duke_id' => $duke_id]), 'editMemberUsername');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    public function beforeAdd()
    {
        //贵族标识
        $markList = AttireModel::getInstance()->alias('a')->field("a.id,a.attire_name,a.attire_image")->join('zb_attire_type b', 'a.pid = b.id')->where(['a.status' => 1, 'b.name' => '贵族标识'])->select()->toArray();
        //进场特效
        $texiaoList = AttireModel::getInstance()->alias('a')->field("a.id,a.attire_name,a.attire_image")->join('zb_attire_type b', 'a.pid = b.id')->where(['a.status' => 1, 'b.name' => '进场特效气泡'])->select()->toArray();
    }

}
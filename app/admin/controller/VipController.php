<?php
/**
 * Created by PhpStorm.
 * User: pussycat
 * Date: 2019/7/23
 * Time: 21:01
 */

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ChargedetailModel;
use app\admin\model\VipChargedetailModel;
use app\admin\model\VipPrivilegeModel;
use app\common\FormaterExportDataCommon;
use OSS\Core\OssException;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class VipController extends AdminBaseController
{
//    public function VipOrder()
//    {
//        $pagenum = 20;
//        $master_page = $this->request->param('page', 1);
//        $page = ($master_page - 1) * $pagenum;
//        $uid = Request::param('uid') == "" ? '' : Request::param('uid'); //用户ID
//        $where = [];
//        if ($uid != '') {
//            $where[] = ['uid', '=', $uid];
//        }
//        $count = VipChargedetailModel::getInstance()->getModel()->where($where)->count();
//        $data = [];
//        if ($count > 0) {
//            $data = VipChargedetailModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
//            foreach ($data as $key => $val) {
//                if ($val['platform'] == 0) {
//                    $data[$key]['platform'] = "苹果";
//                } elseif ($val['platform'] == 1) {
//                    $data[$key]['platform'] = '微信';
//                } else {
//                    $data[$key]['platform'] = '支付宝';
//                }
//                if ($val['status'] == 0) {
//                    $data[$key]['status'] = "未支付";
//                } else {
//                    $data[$key]['status'] = '已支付';
//                }
//            }
//        }
//        $page_array = [];
//        $page_array['page'] = $master_page;
//        $page_array['total_page'] = ceil($count / $pagenum);
//        Log::record('会员特权列表获取成功:操作人:' . $this->token['username'], 'vipPrivilegeList');
//        View::assign('page', $page_array);
//        View::assign('data', $data);
//        View::assign('user_role_menu', $this->user_role_menu);
//        View::assign('token', $this->request->param('token'));
//        View::assign('uid', $uid);
//        return View::fetch('vip/VipOrder');
//    }

    //会员特权列表
    public function vipPrivilege()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $type = Request::param('type') == "" ? '' : Request::param('type'); //特权类型
        $id = Request::param('id') == "" ? '' : Request::param('id'); //特权ID
        $status = Request::param('status') == "" ? '' : Request::param('status'); //特权状态
        $where = [];
        if ($type != '') {
            $where[] = ['type', '=', $type];
        }
        if ($status != '') {
            $where[] = ['status', '=', $status];
        }
        if ($id != '') {
            $where[] = ['id', '=', $id];
        }
        $count = VipPrivilegeModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = VipPrivilegeModel::getInstance()->privilegeList($where, $page, $pagenum);
            foreach ($data as $key => $val) {
                $data[$key]['picture'] = $val['picture'];
                $data[$key]['title'] = $val['title'];
                $data[$key]['sort'] = $val['sort'];
                if ($val['type'] == 1) {
                    $data[$key]['type'] = "vip特权";
                } else {
                    $data[$key]['type'] = 'svip特权';
                }
                if ($val['status'] == 1) {
                    $data[$key]['status'] = "启用";
                } else {
                    $data[$key]['status'] = '禁用';
                }
                if ($val['state'] == 1) {
                    $data[$key]['state'] = "亮色";
                } else {
                    $data[$key]['state'] = '暗色';
                }
                $data[$key]['picture'] = $url . $val['picture'];
                $data[$key]['preview_picture'] = $url . $val['preview_picture'];
                $data[$key]['content'] = $val['content'];
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('会员特权列表获取成功:操作人:' . $this->token['username'], 'vipPrivilegeList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('id', $id);
        View::assign('status', $status);
        View::assign('type', $type);
        return View::fetch('vip/privilege');
    }

    /*
     * 添加会员特权
     */
    public function addPrivilege()
    {
        $data = [
            'type' => Request::param('type'),
            'content' => Request::param('content'),
            'sort' => Request::param('sort'),
            'status' => Request::param('status'),
        ];
        $res = VipPrivilegeModel::getInstance()->addPrivileges($data);
        if ($res) {
            Log::record('会员特权添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addPrivileges');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('会员特权添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addPrivileges');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /**
     * 添加会员特权图片
     */
    public function addPrivilegePicture()
    {
        $id = Request::param('id');
        $where['id'] = $id;
        $savePath = '/privilege';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $picture = request()->file('picture');
        if ($picture != "") {
            $privilege_imageSaveName = \think\facade\Filesystem::putFile($savePath, $picture);
            $privilege_imageObject = str_replace("\\", "/", $privilege_imageSaveName);
            $privilege_imageFile = STORAGE_PATH . str_replace("\\", "/", $privilege_imageSaveName);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($picture != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $privilege_imageObject, $privilege_imageFile); //上传成功
                $data = ['picture' => '/' . $privilege_imageObject];
                $res = VipPrivilegeModel::getInstance()->setPrivilege($where, $data);
            }
            Log::record('添加会员特权图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'addPrivilegePicture');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加会员特权图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'addPrivilegePicture');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    /**
     * 编辑会员特权
     */
    public function editPrivilege()
    {
        $id = Request::param('id'); //会员特权ID
        $where['id'] = $id;
        $data = [
            'type' => Request::param('type'),
            'content' => Request::param('content'),
            'sort' => Request::param('sort'),
            'status' => Request::param('status'),
        ];
        $res = VipPrivilegeModel::getInstance()->setPrivilege($where, $data);
        if ($res) {
            Log::record('修改会员特权数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editPrivilege');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改会员特权数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editPrivilege');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }


    public function VipOrder()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $uid = Request::param('uid') == "" ? '' : Request::param('uid'); //用户ID
        $where = [];
        $data = [];
        $count = 0 ;
        if (!empty($uid)) {
            $where[] = ['uid', '=', $uid];
            $where[] = ['type', 'in', [2,3]];
            $count = ChargedetailModel::getInstance()->getModel()->where($where)->count();
            if ($count > 0) {
                $data = ChargedetailModel::getInstance()->getModel()->where($where)->page($page, $limit)->order("id desc")->select()->toArray();
            }
        }
        if ($this->request->param("isRequest") == 1) {
            $res = ["msg" => '', "count" => $count, "code" => 0, "data" => FormaterExportDataCommon::getInstance()->formatterVipOrderList($data)];
            echo json_encode($res);
        }else{
            View::assign('token', $this->request->param('token'));
            return View::fetch('vip/vipOrderList');
        }

    }


}

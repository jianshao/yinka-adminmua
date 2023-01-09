<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\CategoryModel;
use app\admin\model\ConfigModel;
use app\admin\model\MallGoodsModel;
use OSS\Core\OssException;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class MallGoodsController extends AdminBaseController
{
    /*
     * 阿里OSS
     */
    public function ossGoodsFile()
    {
        $gift_id = Request::param('id');
        $where['id'] = $gift_id;
        $savePath = '/gift';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $gift_image = request()->file('gift_image');
        $animation = request()->file('animation');
        $gift_animation = request()->file('gift_animation');
        if ($gift_image != "") {
            $gift_imageSavename = \think\facade\Filesystem::putFile($savePath, $gift_image);
            $gift_imageObject = str_replace("\\", "/", $gift_imageSavename);
            $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $gift_imageSavename);
        }
        if ($animation != "") {
            $animationSavename = \think\facade\Filesystem::putFile($savePath, $animation);
            $animationObject = str_replace("\\", "/", $animationSavename);
            $animationFile = STORAGE_PATH . str_replace("\\", "/", $animationSavename);
        }
        if ($gift_animation != "") {
            $gift_animationSavename = \think\facade\Filesystem::putFile($savePath, $gift_animation);
            $gift_animationObject = str_replace("\\", "/", $gift_animationSavename);
            $gift_animationFile = STORAGE_PATH . str_replace("\\", "/", $gift_animationSavename);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($gift_image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ['face_image' => '/' . $gift_imageObject, 'mold_icon' => '/' . $gift_imageObject];
                $res = MallGoodsModel::getInstance()->getModel()->where($where)->save($data);
            }
            if ($animation != "") {
                $animationResult = $ossClient->uploadFile($bucket, $animationObject, $animationFile); //上传成功
                $data = ['animation' => '/' . $animationObject];
                $res = MallGoodsModel::getInstance()->getModel()->where($where)->save($data);
            }
//            if($gift_animation != ""){
            //                $gift_animationResult = $ossClient->uploadFile($bucket, $gift_animationObject, $gift_animationFile);//上传成功
            //                $data = ['gift_animation' => '/' . $gift_animationObject];
            //                $res = MallGoodsModel::getInstance()->getModel()->where($where)->save($data);
            //            }
            Log::record('添加礼物/装备图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加礼物/装备图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    public function pidToGetTree($list, &$data, $pk = 'id', $pid = 'pid', $child_key = "_child", $sort_id = 'sort', $sort_type = SORT_ASC, $start_pid = 0)
    {
        if ($data === null) {
            return;
        }
        if (count($data) == $start_pid) {
            //初始化根列表
            foreach ($list as $key => &$value) {
                if ($value[$pid] == 0) {
                    $data[] = $value;
                    unset($list[$key]);
                }
            }
            $sort_root_arr = array_column($data, $sort_id);
            array_multisort($sort_root_arr, $sort_type, $data);
        }
        foreach ($data as $key => &$value) {
            foreach ($list as $key2 => $value2) {
                if ($value2[$pid] == $value[$pk]) {
                    $value[$child_key][] = $value2;
                    unset($list[$key2]);
                }
            }
            if (empty($value[$child_key])) {continue;}
            $sort_arr = array_column($value[$child_key], $sort_id);
            array_multisort($sort_arr, $sort_type, $value[$child_key]);
            $this->pidToGetTree($list, $value[$child_key], $pk, $pid, $child_key, $sort_id, $sort_type);
        }
    }

    public function listGoods()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $status = Request::param('status', 1);
        $where = [];
        if ($status) {
            $where[] = ['status', '=', $status];
        }

        $count = MallGoodsModel::getInstance()->getModel()->where($where)->count();
        $list = MallGoodsModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        $categories = CategoryModel::getInstance()->getTwoCategory();
        $first_categories = CategoryModel::getInstance()->getFirstCategory();

        $props = ConfigModel::getInstance()->getPropTypeList();
        $propTypes = ConfigModel::getInstance()->getMallTypes();

        $gifts = ConfigModel::getInstance()->getGiftList();
        $app_url = config('config.APP_URL_image');
        Log::record('房间类型列表:操作人:' . $this->token['username'], 'roomTypeList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('propTypes', $propTypes);
        View::assign('props', $props);
        View::assign('gifts', $gifts);
        View::assign('all', $categories);
        View::assign('first_categories', $first_categories);
        View::assign('app_url', $app_url);
        return View::fetch('mall/goods');
    }

    public function addGoods()
    {
        $currency_id = (int) Request::param('currency_id');
        $sort = (int) Request::param('sort', 0);
        $status = (int) Request::param('status', 1);
        $type = (int) Request::param('type', 0);
        $is_show = (int) Request::param('is_show', 0);
        $is_buy = (int) Request::param('is_buy', 0);
        $giftid_or_propid = (int) Request::param('giftid_or_propid', 0);

        $name = (string) Request::param('name', '');
        $desc = (string) Request::param('desc', '');
        $prop_types = (string) Request::param('prop_types', '');

        $img_url = (string) Request::param('img_url', '');
        $tag_url = (string) Request::param('tag_url', '');
        $level_1 = (array) Request::param('level_1', []);
        $level_2 = (array) Request::param('level_2', []);
        $units = (array) Request::param('units', []);
        $count = (array) Request::param('count', []);
        $price = (array) Request::param('price', []);
        $now_price = (array) Request::param('now_price', []);

        $mall_ids = [];
        foreach ($level_1 as $key => $first) {
            $mall_ids[$key]['level_1'] = (int) $first;
            $mall_ids[$key]['level_2'] = (int) $level_2[$key];
        }
        $goods_sku = [];
        foreach ($units as $key => $first) {
            $goods_sku[$key]['units'] = $first;
            $goods_sku[$key]['count'] = (int) $count[$key];
            $goods_sku[$key]['price'] = (int) $price[$key];
            $goods_sku[$key]['now_price'] = (int) $now_price[$key];
        }

        $data = [
            "name" => (string) $name,
            "is_buy" => (int) $is_buy,
            "type" => (int) $type,
            "prop_types" => (string) $prop_types,
            "giftid_or_propid" => $giftid_or_propid,
            "currency_id" => $currency_id,
            "img_url" => $img_url,
            "tag_url" => $tag_url,
            "desc" => $desc,
            "mall_ids" => json_encode($mall_ids),
            "goods_sku" => json_encode($goods_sku),
            "status" => $status,
            "is_show" => $is_show,
            "sort" => $sort,
        ];

        $is = MallGoodsModel::getInstance()->getModel()->insert($data);

        // dd($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '添加成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '添加失败！']);
            die;
        }
    }

    public function delGoods()
    {
        $id = (int) Request::param('id');
        $is = MallGoodsModel::getInstance()->getModel()->where('id', $id)->update(['status' => 0]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败！']);
            die;
        }
    }

    public function saveGoods()
    {
        $id = (int) Request::param('id');
        $currency_id = (int) Request::param('currency_id');
        $sort = (int) Request::param('sort', 0);
        $status = (int) Request::param('status', 1);
        // $type = (int) Request::param('type', 0);
        $is_show = (int) Request::param('is_show', 0);
        $is_buy = (int) Request::param('is_buy', 0);
        $giftid_or_propid = (int) Request::param('giftid_or_propid', 0);

        $name = (string) Request::param('name', '');
        $desc = (string) Request::param('desc', '');
        $prop_types = (string) Request::param('prop_types', '');

        $img_url = (string) Request::param('img_url', '');
        $tag_url = (string) Request::param('tag_url', '');
        $level_1 = (array) Request::param('level_1', []);
        $level_2 = (array) Request::param('level_2', []);
        $units = (array) Request::param('units', []);
        $count = (array) Request::param('count', []);
        $price = (array) Request::param('price', []);
        $now_price = (array) Request::param('now_price', []);
        // dd($giftid_or_propid);

        $mall_ids = [];
        foreach ($level_1 as $key => $first) {
            $mall_ids[$key]['level_1'] = (int) $first;
            $mall_ids[$key]['level_2'] = (int) $level_2[$key];
        }
        $goods_sku = [];
        foreach ($units as $key => $first) {
            $goods_sku[$key]['units'] = trim($first);
            $goods_sku[$key]['count'] = (int) $count[$key];
            $goods_sku[$key]['price'] = (int) $price[$key];
            $goods_sku[$key]['now_price'] = (int) $now_price[$key];
        }

        $data = [
            "name" => (string) $name,
            "is_buy" => (int) $is_buy,
            // "type" => (int) $type,
            "prop_types" => (string) $prop_types,
            "giftid_or_propid" => $giftid_or_propid,
            "currency_id" => $currency_id,
            "img_url" => $img_url,
            "tag_url" => $tag_url,
            "desc" => $desc,
            "mall_ids" => json_encode($mall_ids),
            "goods_sku" => json_encode($goods_sku),
            "status" => $status,
            "is_show" => $is_show,
            "sort" => $sort,
        ];

        $is = MallGoodsModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功！']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败！']);
            die;
        }
    }
}

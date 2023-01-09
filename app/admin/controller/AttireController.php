<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AttireModel;
use app\admin\model\AttireStartModel;
use app\admin\model\AttireTypeModel;
use app\admin\service\AttireService;
use app\common\FileCipher;
use app\common\FileCipherCommon;
use OSS\Core\OssException;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class AttireController extends AdminBaseController
{
    /*
     * 修改或添加图片
     */
    public function attOssFile()
    {
        $gift_id = Request::param('id');
        $where['id'] = $gift_id;
        $savePath = '/attire';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $gift_image = request()->file('gift_image');
        $animation = request()->file('animation');
        $gift_animation = request()->file('gift_animation');
        $gift_MP4 = request()->file('gift_MP4');
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
        if ($gift_MP4 != "") {
            $gift_MP4Savename = \think\facade\Filesystem::putFile($savePath, $gift_MP4);
            $gift_MP4Object = str_replace("\\", "/", $gift_MP4Savename);
            $gift_MP4File = STORAGE_PATH . str_replace("\\", "/", $gift_MP4Savename);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($gift_image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ['attire_image' => '/' . $gift_imageObject];
                $res = AttireModel::getInstance()->setGift($where, $data);
            }
            if ($animation != "") {
                $animationResult = $ossClient->uploadFile($bucket, $animationObject, $animationFile); //上传成功
                $data = ['attire_move_image' => '/' . $animationObject];
                $res = AttireModel::getInstance()->setGift($where, $data);
            }
            if ($gift_animation != "") {
                $gift_animationResult = $ossClient->uploadFile($bucket, $gift_animationObject, $gift_animationFile); //上传成功
                $data = ['attire_move_image' => '/' . $gift_animationObject];
                $res = AttireModel::getInstance()->setGift($where, $data);
            }
            if ($gift_MP4 != "") {
                $gift_MP4Result = $ossClient->uploadFile($bucket, $gift_MP4Object, $gift_MP4File); //上传成功
                $data = ['attire_mp4' => '/' . $gift_MP4Object];
                $res = AttireModel::getInstance()->setGift($where, $data);
            }
            Log::record('添加装扮/装备图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加装扮/装备图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    /**
     * 装饰展示列表
     * @return string
     * @throws \Exception
     */
    public function getAttireList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;

        $attire_id = Request::param('attire_id'); //礼物ID
        $status = Request::param('status'); //装扮的状态
        $is_show = Request::param('is_show');
        $is_show = empty($is_show) ? 2 : $is_show;
        $where = [];
        $where['is_goldmall'] = 0;
        if (!empty($status)) {
            if ($status == 1) {
                $where['status'] = 1;
            } else if ($status == 3) {
                $where['status'] = 0;
            }
        }

        if ($attire_id) {
            $where['id'] = $attire_id;
        }

        if ($is_show != 2) {
            if ($is_show == 1) {
                $where['is_show'] = 1;
            } else if ($is_show == 3) {
                $where['is_show'] = 0;
            }
        }
        $count = AttireModel::getInstance()->getModel()->where($where)->count();
        $list = AttireService::getInstance()->order('id desc')->where($where)->limit($page, $pagenum)->select()->toArray();
        $attiretype = AttireTypeModel::getInstance()->getModel()->select()->toArray();
        foreach ($attiretype as $k => $v) {
            $type[] = $v;
        }
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['attire_price'] = json_decode($value['attire_price'], true);
                $list[$key]['attire_image'] = empty($value['attire_image']) ? '' : config('config.APP_URL_image') . $value['attire_image'];
                $list[$key]['corner_sign'] = empty($value['corner_sign']) ? '' : config('config.APP_URL_image') . $value['corner_sign'];

                foreach ($type as $k => $v) {
                    if ($list[$key]['son_id'] == $type[$k]['id']) {
                        $list[$key]['son_id_name'] = $type[$k]['name'];
                    }
                    if ($list[$key]['pid'] == $type[$k]['id']) {
                        $list[$key]['pid_name'] = $type[$k]['name'];
                    }
                }
                if (isset($list[$key]['is_vip'])) {
                    if ($list[$key]['is_vip'] == 0) {
                        $list[$key]['is_vip'] = '普通装扮';
                    } elseif ($list[$key]['is_vip'] == 1) {
                        $list[$key]['is_vip'] = 'vip装扮';
                    } elseif ($list[$key]['is_vip'] == 2) {
                        $list[$key]['is_vip'] = '超级vip装扮';
                    }
                } else {
                    $list[$key]['is_vip'] = '普通装扮';
                }

                foreach ($list[$key]['attire_price'] as $k => $v) {
                    $list[$key]['price'][$k] = $v['day'] . '天' . $v['price'] . '豆 | ';
                }
                $list[$key]['price'] = rtrim(rtrim(implode($list[$key]['price']), ' '), '|');
                $list[$key]['exp_time'] = $value['exp_time'] == 0 ? "2100-01-01" : date("Y-m-d", $value['exp_time']);
            }

        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('list', $list);
        View::assign('attire_id', $attire_id);
        View::assign('status', $status);
        View::assign('type', $type);
        View::assign('is_show', $is_show);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('attire/index');
    }

    /**装扮分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAttireType()
    {
        $attiretype = AttireTypeModel::getInstance()->getModel()->select()->toArray();
        foreach ($attiretype as $k => $v) {
            $type[] = $v;
            $type[$k]['created_time'] = date("Y-m-d H:i:s", $attiretype[$k]['created_time']);
        }
        return json_encode($type);
    }

    /**多为数组根据指定字段排序
     * @param $array
     * @param $field
     * @param bool $desc
     * @return mixed
     */
    public function sortArrByField(&$array, $field, $desc = false)
    {
        $fieldArr = array();
        foreach ($array as $k => $v) {
            $fieldArr[$k] = $v[$field];
        }
        $sort = $desc == false ? SORT_ASC : SORT_DESC;
        array_multisort($fieldArr, $sort, $array);
        return $array;
    }

    /*
     * 阿里OSS
     */
    public function ossAttireFile()
    {
        $savePath = empty(Request::param('file')) ? '/' : Request::param('file');
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $image = request()->file('image');
        if ($image != "") {
            $gift_imageSavename = \think\facade\Filesystem::putFile($savePath, $image);
            $gift_imageObject = str_replace("\\", "/", $gift_imageSavename);
            $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $gift_imageSavename);

            if (strtolower(pathinfo($gift_imageSavename)['extension']) == 'mp4') {
                $fileinfo = pathinfo($gift_imageSavename);
                $extension = $fileinfo['extension'];
                $filename = $fileinfo['filename'];
                $dirname = $fileinfo['dirname'];
                $encryFileName = $dirname . DIRECTORY_SEPARATOR . $filename . "_encry" . "." . $extension;
                $fileCipher = new FileCipher(base64_decode(config('config.filecipher.key')), config('config.filecipher.iv'), 1024 * 1024, "0001");
                //获取加密文件的路径
                try {
                    $fileCipher->encryptFile($gift_imageFile, STORAGE_PATH . str_replace("\\", "/", $encryFileName));
                } catch (Throwable $e) {
                    Log::info("ossattirefile:mp4:error" . $e->getMessage());
                }
                //变量重新赋值
                $gift_imageObject = str_replace("\\", "/", $encryFileName);
                $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $encryFileName);
            }

        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ['image' => '/' . $gift_imageObject];
            }
            Log::record('添加装扮/装扮图片成功:操作人:' . $this->token['username'] . ':内容:' . json_encode($data), 'ossFile');
            return ['status' => 1, 'msg' => '上传成功', 'image' => '/' . $gift_imageObject];
            die;
        } catch (OssException $e) {
            Log::record('添加礼物/装备图片失败:操作人:' . $this->token['username'] . ':内容:' . json_encode($data), 'ossFile');
            return ['status' => 1, 'msg' => '上传失败'];
            die;
        }
    }

    /**
     * 装扮添加
     * price[] : 价格
     * desc: 0 ：描述
     * attire_name: 名称
     * status: 1 ：上下架状态（1：上架，0：下架）
     * is_buy: 1：可否购买（0：不可，1：可）
     */
    public function addAttire()
    {
        foreach (Request::param('day') as $key => $value) {
            $price[$key]['day'] = intval($value);
        }
        foreach (Request::param('price') as $key => $value) {
            $price[$key]['price'] = intval($value);
        }
        $nowTime = time();
        $time = strtotime(Request::param('exp_time'));
        if (($time - $nowTime) / 365 / 86400 > 70) {
            $exp_time = 0;
        } else {
            $exp_time = $time;
        }
        $data = [
            'pid' => Request::param('typeid'),
            'son_id' => Request::param('typepid'),
            'attire_describe' => Request::param('desc'),
            'attire_price' => json_encode($this->sortArrByField($price, 'day')),
            'attire_name' => Request::param('attire_name'),
            'status' => Request::param('status'),
            'is_buy' => Request::param('is_buy'),
            'is_vip' => Request::param('is_vip'),
            'get_type' => Request::param('get_type'),
            'is_show' => Request::param('is_show'),
            'list_type' => Request::param('list_type'),
            'attire_image' => Request::param('image'),
            'multiple' => Request::param('multiple'),
            'created_time' => time(),
            'exp_time' => $exp_time,
        ];
        if (Request::param('color')) {
            $data += ['color' => Request::param('color')];
        }
        if (Request::param('attire_android_image')) {
            $data['attire_android_image'] = Request::param('attire_android_image');
        }
        if (Request::param('attire_image')) {
            $data['attire_image'] = Request::param('attire_image');
        }
        if (Request::param('corner_sign')) {
            $data['corner_sign'] = Request::param('corner_sign');
        }
        if (Request::param('activity_url')) {
            $data['activity_url'] = Request::param('activity_url');
        }
        $res = AttireStartModel::getInstance()->getModel()->save($data);
        if ($res) {
            Log::record('装扮添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('装扮添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }

    }

    /**
     * 修改
     */
    public function attireUpdateStatus()
    {
        $id = Request::param('attire_id'); //修改ID
        $day = Request::param('day');
        $attire_android_image = Request::param('attire_android_image');
        $price = Request::param('price');
        $image = Request::param('image');
        $corner_sign = Request::param('corner_sign');
        $pid = Request::param('typeid1');
        $son_id = Request::param('typepid');
        $nowTime = time();
        $time = strtotime(Request::param('exp_time'));
        if (($time - $nowTime) / 365 / 86400 > 70) {
            $exp_time = 0;
        } else {
            $exp_time = $time;
        }
        $data = [];
        if (!empty($son_id)) {
            $data['son_id'] = $son_id;
        }
        if (!empty($pid)) {
            $data['pid'] = $pid;
        }
        if (!empty($price) && !empty($day)) {
            foreach ($day as $key => $value) {
                $price_arr[$key]['day'] = intval($value);
            }
            foreach ($price as $key => $value) {
                $price_arr[$key]['price'] = intval($value);
            }
            $data['attire_price'] = json_encode($this->sortArrByField($price_arr, 'day'));
        }
        if (!empty($image)) {
            $data['attire_image'] = $image;
        }
        if (!empty($corner_sign)) {
            $data['corner_sign'] = $corner_sign;
        }

        $where['id'] = $id;
        $data += [
            'get_type' => Request::param('get_type'),
            'attire_describe' => Request::param('desc'),
            'attire_name' => Request::param('attire_name'),
            'status' => Request::param('status'),
            'is_buy' => Request::param('is_buy'),
            'is_vip' => Request::param('is_vip'),
            'is_show' => Request::param('is_show'),
            'list_type' => Request::param('list_type'),
            'activity_url' => Request::param('activity_url'),
            'multiple' => Request::param('multiple'),
            'exp_time' => $exp_time,
            'updated_time' => time(),
        ];
        if (Request::param('color')) {
            $data += ['color' => Request::param('color')];
        }

        if (!empty($attire_android_image)) {
            $data += ['attire_android_image' => $attire_android_image];
        }
        if (Request::param('bubble_word_image')) {
            $data += ['bubble_word_image' => Request::param('bubble_word_image')];
        }

        $res = AttireModel::getInstance()->setAttire($where, $data);
        if ($res) {
            Log::record('修改礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**
     * 装扮上下架切换
     */
    public function statusAttire()
    {
        $id = Request::param('attireType_id');
        $status = Request::param('status');
        if ($status == 1) {
            $data['status'] = 0;
        } else {
            $data['status'] = 1;
        }
        $where['id'] = $id;
        $res = AttireModel::getInstance()->setAttire($where, $data);
        if ($res) {
            Log::record('修改礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }


    public function decrymp4File()
    {
        try {
            $fileUrl = Request::param('fileUrl');
            $fileinfo = pathinfo($fileUrl);
            $extension = $fileinfo['extension'];
            $filename = $fileinfo['filename'];
            $dirPath = app()->getRootPath() . "public/admin/mp4/";
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0777);
            }
            $saveencrypath = $dirPath . $filename . "." . $extension;
            $savedecrypath = $dirPath . md5($filename) . "." . $extension;
            if (is_file($savedecrypath)) {
                echo json_encode(['code' => 200, 'url' => "/admin/mp4/" . md5($filename) . "." . $extension]);exit;
            } else {
                FileCipherCommon::getInstance()->downloadFile($fileUrl, $saveencrypath);
                FileCipherCommon::getInstance()->decryptFile($saveencrypath, $savedecrypath);
                echo json_encode(['code' => 200, 'url' => "/admin/mp4/" . md5($filename) . "." . $extension]);
                exit;
            }
        } catch (Throwable $e) {
            Log::error("decrymp4:" . $e->getMessage() . $e->getLine() . $e->getFile());
            echo json_encode(['code' => 0, 'url' => ""]);
        }
    }


}

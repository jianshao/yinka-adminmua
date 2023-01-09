<?php


namespace app\admin\controller;


use app\admin\common\AdminBaseController;
use think\facade\Log;
use think\facade\View;


class UploadFileController extends AdminBaseController
{
//    private $file_type = array(
//        '/common' => array('.JPG', '.jpg','.PNG','.png','.gif','.GIF'),
//        '/uploadTest' => array('.JPG', '.jpg'),
//
//    );

    public function test()
    {
        return View::fetch('test');
    }

    public function uploadIndex()
    {
        $file = request()->file('files')[0];
        $file_url = !empty(request()->param('file_url')) ? request()->param('file_url') : '';
        $savename = \think\facade\Filesystem::putFile($file_url, $file);
        $type_array = explode('.', $savename);
        $type = '.' . end($type_array);
        //过滤格式
//        if ($file_url == '') {
//            $file_type = $this->file_type['common'];
//        } elseif (isset($this->file_type[$file_url])) {
//            $file_type = $this->file_type[$file_url];
//        } else {
//            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
//            die;
//        }
//        if (!in_array($type, $file_type)) {
//            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
//            die;
//        }
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID'];//阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET'];//阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT'];//阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $SaveNmae =  \think\facade\Filesystem::putFile($file_url, $file);
        $Object =str_replace("\\", "/", $SaveNmae);
        $File = STORAGE_PATH.str_replace("\\", "/", $SaveNmae);
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $gift_animationResult = $ossClient->uploadFile($bucket, $Object, $File);//上传成功
            Log::record('上传图片成功:图片路径:'.json_encode($Object),$file_url);
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $Object, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        } catch (OssException $e) {
            Log::record('上传图片失败:图片路径:'.json_encode($Object),$file_url);
            echo $this->return_json(\constant\CodeConstant::CODE_上传公告图片失败, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_上传公告图片失败]);
            die;
        }
    }
}
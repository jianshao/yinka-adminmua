<?php

namespace app\admin\service;

use constant\CodeConstant;
use OSS\Core\OssException;
use OSS\OssClient;
use think\facade\Log;

class UploadFileService
{
    protected static $instance;

    //文件上传
    public $savePath = '/';
    //OSS第三方配置
    public $ossConfig;
    public $accessKeyId;
    public $accessKeySecret;
    public $endpoint;
    public $bucket;

    public function __construct()
    {
        $this->ossConfig = config('config.OSS');
        $this->accessKeyId = $this->ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $this->accessKeySecret = $this->ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $this->endpoint = $this->ossConfig['ENDPOINT']; //阿里云OSS 地址
        $this->bucket = $this->ossConfig['BUCKET']; //oss中的文件上传空间
    }
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function uplaodFile($savePath, $file)
    {
        $ios_img = '@3x';
        $imageSavename = \think\facade\Filesystem::putFile($savePath, $file, function () use ($file, $ios_img) {
            $uid = uniqid();
            return $uid . $ios_img;
        });

        $imageObject = str_replace("\\", "/", $imageSavename);
        $imageFile = STORAGE_PATH . str_replace("\\", "/", $imageSavename);

        try {
            $ossClient = new \OSS\OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $imageResult = $ossClient->uploadFile($this->bucket, $imageObject, $imageFile); //上传成功

            Log::record('uplaodFile:' . ':更新条件:' . $savePath . ': 返回数据:' . json_encode($imageResult) . '', 'ossFile');
            $oss = 'http://' . $imageResult['oss-requestheaders']['Host'];
            $url = str_replace($oss, '', $imageResult['info']['url']);
            Log::record('uplaodFile:' . ':更新条件:' . $savePath . ': 返回数据url:' . json_encode(['url' => $url]) . '', 'ossFile');
            return ['url' => $url];
        } catch (OssException $e) {
            Log::record('添加图片成功:操作人:' . ':更新条件:' . ':内容:', 'ossFile');
            throw new OssException(CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_更新失败], \constant\CodeConstant::CODE_上传失败);
        }
    }

    public function uplaodImg($savePath, $file)
    {
        $imageSavename = \think\facade\Filesystem::putFile($savePath, $file);

        $imageObject = str_replace("\\", "/", $imageSavename);
        $imageFile = STORAGE_PATH . str_replace("\\", "/", $imageSavename);

        try {
            $ossClient = new \OSS\OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $imageResult = $ossClient->uploadFile($this->bucket, $imageObject, $imageFile); //上传成功

            Log::record('uplaodFile:' . ':更新条件:' . $savePath . ': 返回数据:' . json_encode($imageResult) . '', 'ossFile');
            $oss = 'http://' . $imageResult['oss-requestheaders']['Host'];
            $url = str_replace($oss, '', $imageResult['info']['url']);
            Log::record('uplaodFile:' . ':更新条件:' . $savePath . ': 返回数据url:' . json_encode(['url' => $url]) . '', 'ossFile');
            return ['url' => $url];
        } catch (OssException $e) {
            Log::record('添加图片成功:操作人:' . ':更新条件:' . ':内容:', 'ossFile');
            throw new OssException(CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_更新失败], \constant\CodeConstant::CODE_上传失败);
        }
    }
}

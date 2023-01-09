<?php


namespace app\common;

use think\App;
use think\facade\Log;
use OSS\OssClient;
use OSS\Core\OssException;

/**
 * OSS上传类
 */
class UploadOssFileCommon
{

    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UploadOssFileCommon();
        }
        return self::$instance;
    }

    /*
     * 阿里OSS
     * @param $file_name 文件名
     * @param $file_dir 目录
     */
    public function ossFile($file_name,$file_dir,$path=1)
    {
        if (is_file(__DIR__ . '/../autoload.php')) {
            require_once __DIR__ . '/../autoload.php';
        }
        if (is_file(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        //OSS第三方配置
        if ($path == 1) {
            $ossConfig = config('config.OSS');
        }else{
            $ossConfig = config('config.OSSMUAYY');
        }
        // 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。强烈建议您创建并使用RAM账号进行API访问或日常运维，请登录 https://ram.console.aliyun.com 创建RAM账号。
        $accessKeyId = $ossConfig['ACCESS_KEY_ID'];
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET'];
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = $ossConfig['ENDPOINT'];
        // 存储空间名称
        $bucket = $ossConfig['BUCKET'];
        // 文件名称
        $object = $file_name;
        // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
        $filePath = $file_dir;
        $imageSavename =  \think\facade\Filesystem::putFile($file_dir, $file_name);
        $imageObject =str_replace("\\", "/", $imageSavename);
        $imageFile = STORAGE_PATH.str_replace("\\", "/", $imageSavename);
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $result = $ossClient->uploadFile($bucket, $imageObject, $imageFile);//上传成功
            return $result['info']['url'];
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }

    }

  

}

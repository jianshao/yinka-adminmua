<?php
namespace app\common;

class FileCipherCommon
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new FileCipherCommon();
        }
        return self::$instance;
    }


    public function __construct()
    {
        // 加密的key 字符串
        $this->key = base64_decode(config('config.filecipher.key'));
        // 加密的iv
        $this->iv = config('config.filecipher.iv');
        // 版本号，目前只支持1
        $this->version = '0001';
        // 加密的块长度
        $this->blockSize = 1024 * 1024;
        // 最大加密块长度
        $this->maxBlockSize = 1024 * 1024 * 2;
    }

    /**
     * 分块读取的文件
     * @param $dirFile
     * @return Generator
     */
    private function blockReadFile($dirFile)
    {
        $handle = fopen($dirFile, 'rb');
        if ($handle) {
            while (feof($handle) === false) {
                # 重点 每次读取 1024 个字节
                yield fread($handle, $this->blockSize);
            }
            fclose($handle);
        } else {
            throw new  Exception("open file error");
        }
    }


    /**
     * 内容加密
     * @param $content
     * @return false|string
     * @throws Exception
     */
    private function encrypt($content)
    {
        $bytesToPad = 0;
        if (strlen($content) % 16 != 0) {
            $bytesToPad = 16 - (strlen($content) % 16);
            $content = $content . str_repeat(pack('c', $bytesToPad), $bytesToPad);
        }
        $cipher_str = openssl_encrypt($content, 'AES-128-CBC', $this->key, OPENSSL_NO_PADDING, $this->iv);
        $blocksize = pack("N", strlen($content));
        $cipher_str = $blocksize . pack("c", $bytesToPad) . $cipher_str;
        return $cipher_str;

    }

    /**
     * 加密文件，加密失败抛异常
     * @param $sourceFile
     *  要加密的文件
     * @param $destFile
     *  加密后文件
     */
    public function encryptFile($sourceFile, $destFile)
    {
        try {
            $destFp = fopen($destFile, "w");
            if (!fwrite($destFp, "RQFH")) {
                throw new  \Exception("fwrite is error");
            }

            if (!fwrite($destFp, pack('N', 1))) {
                throw new  \Exception("fwrite is error");
            }

            foreach ($this->blockReadFile($sourceFile) as $blockContent) {
                if (empty($blockContent)) {
                    throw new  \Exception("readfile is error");
                }
                $encryData = $this->encrypt($blockContent);
                if (!fwrite($destFp, $encryData)) {
                    throw new  \Exception("fwrite is error");
                }
            }
            fclose($destFp);
        } catch (\Throwable $e) {
            unlink($destFile);
            throw $e;
        } finally {
            is_resource($destFp) && fclose($destFp);
        }
    }


    /**
     * 解密文件，解密失败抛异常
     * @param $sourceFile
     *  要加密的文件
     * @param $destFile
     *  加密后文件
     */
    function decryptFile($sourceFile, $destFile)
    {
        try {
            //这是解密的过程
            $fp = fopen($sourceFile, 'rb');
            $decryfp = fopen($destFile, 'w');
            if ($fp === false || $decryfp === false) {
                throw new  \Exception("open resource error");
            }
            if (!fread($fp, 8)) {
                throw new  \Exception("header error");
            } //获取头部协议

            while (feof($fp) === false) {
                $blockContent = fread($fp, 4); //读取块大小

                if ($blockContent === false) {
                    throw new  \Exception("block read error");
                }

                if (!strlen($blockContent)) {
                    break;
                }
                $paddContent = fread($fp, 1); //读取填充的大小
                if ($paddContent === false) {
                    throw new  \Exception("padd file error");
                }
                $blocksize = unpack("N", $blockContent);
                if (!isset($blocksize[1]) && $blocksize[1] < 0) {
                    throw new  \Exception("block size error");
                }
                $paddsize = unpack('c', $paddContent);
                if (!isset($paddsize[1]) && $paddsize[1] < 0) {
                    throw new  \Exception("padd size error");
                }
                $realsize = $blocksize[1] - $paddsize[1];
                $decrydata = openssl_decrypt(fread($fp, (int)$blocksize[1]), 'AES-128-CBC', $this->key, OPENSSL_NO_PADDING, $this->iv);
                if (!fwrite($decryfp, substr($decrydata, 0, $realsize))) {
                    throw new  \Exception("fwrite error");
                }
                if ($paddsize[1] > 0) {
                    break;
                }
            }
            fclose($fp);
            fclose($decryfp);
        } catch (\Throwable $e) {
            if (is_file($destFile)) {
                unlink($destFile);
            }
            throw  $e;
        } finally {
            is_resource($fp) && fclose($fp);
            is_resource($decryfp) && fclose($decryfp);
        }
    }

    /**
     * 下载网络文件到本地
     * @param $fileUrl
     * @param $newFileName
     * @param int $size
     */
    public function downloadFile($fileUrl, $newFileName, $size = 1024)
    {
        try {
            $handler = fopen($fileUrl, "rb");
            //即将生成的文件资源
            $newHandler = fopen($newFileName, "w");
            while (!feof($handler)) {
                $content = fread($handler, $size);
                fwrite($newHandler, $content);
            }
            fclose($handler);
            fclose($newHandler);
        } catch (\Throwable $e) {
            throw  $e;
        }

    }

}

/*
$fileCipher = new FileCipher(base64_decode('aa4BtZ4tspm2wnXLb1ThQA=='), '1234567890123456', 1024*1024, "0001");
//$fileCipher->encryptFile("./part/small", "./part/small_encry");
//$fileCipher->decryptFile("./part/small_encry", "./part/small_decry");

//测试白狐文件
$fileCipher->encryptFile("./baihu.mp4", "./part/baihu_encry.mp4");
$fileCipher->decryptFile("./part/baihu_encry.mp4", "./part/baihu_decry.mp4");*/

?>

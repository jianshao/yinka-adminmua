<?php
namespace app\admin\common\dalong\request;

/**
 * User: huashan
 * Date: 2020/10/22
 * Time: 14:34
 */
class OpenRequest implements OpenapiRequest
{

    public $name;

    private $version = '';

    private $data = '';

    private $apiParas = array();


    public function setContent($content = '')
    {
        $this->data = $content;
        $this->apiParas["data"] = $content;
    }

    public function getContent()
    {
        return $this->content;
    }


    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function getApiParas()
    {
        return $this->apiParas;
    }

    public function getName()
    {
        return $this->name;

    }
}

;

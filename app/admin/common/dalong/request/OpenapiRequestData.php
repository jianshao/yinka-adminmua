<?php

namespace app\admin\common\dalong\request;;


class OpenapiRequestData
{
    /**
     * 公共请求参数
     * @var array
     */
    protected $values = [];

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @param $value
     */
    public function setAppKey($value)
    {
        $this->values['app_key'] = $value;
    }

    /**
     * 移动支付平台分配给接入平台的服务商唯一的ID，请向相应的对接负责人获取
     * @return 值
     **/
    public function getAppKey()
    {
        return $this->values['app_key'];
    }

    /**
     * 接口名称
     * @return mixed
     */
    public function getApiName()
    {
        return $this->values['name'];
    }

    /**
     * @param $value
     */
    public function setApiName($value)
    {
        $this->values['name'] = $value;
    }


    /**
     * @param $value
     */
    public function setFormat($value)
    {
        $this->values['format'] = $value;
    }

    /**
     *
     * @return mixed
     */
    public function getFormat()
    {
        return $this->values ['format'];
    }

    /**
     * @param $value
     */
    public function setSignName($value)
    {
        $this->values['sign'] = $value;
    }

    /**
     * 获取签名，详见签名生成算法的值
     * @return mixed
     */
    public function getSignName()
    {
        return $this->values ['sign'];
    }


    /**
     * 调用的接口版本，默认且固定为：null
     * @param $value
     */
    public function setVersion($value)
    {
        $this->values['version'] = $value;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->values ['version'];
    }

    /**
     * @return mixed
     */
    public function getAccessTokenName()
    {
        return $this->values ['access_token'];
    }

    /**
     * @return mixed
     */
    public function getTimestampName()
    {
        return $this->values ['timestamp'];
    }



    /**
     * @return mixed
     */
    public function setTimestampName($value)
    {
        return $this->values ['timestamp'] = $value;
    }



    /**
     * 请求参数的集合，最大长度不限，除公共参数外所有 请求参数都必须放在这个参数中传递
     * @param $value
     */
    public function setDataMame($value)
    {
        $this->values['name'] = $value;
    }

    /**
     * @return mixed
     */
    public function getDataMame()
    {
        return $this->values ['name'];
    }
}

<?php
namespace app\admin\script\analysis\login;

class VersionData
{
    public $version = null;
    public $loginDataMap = [];

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function add($version, $count)
    {
        if (array_key_exists($version, $this->loginDataMap)) {
            $this->loginDataMap[$version]->add($count);
        } else {
            $loginData = new LoginData($version);
            $loginData->add($count);
            $this->loginDataMap[$version] = $loginData;
        }
    }

    public function toJson()
    {
        return $this->encodeLoginDataMap($this->loginDataMap);
    }

    public function encodeLoginDataMap(&$logindataMap)
    {
        $ret = [];
        foreach ($logindataMap as $type => $loginData) {
            $ret[$type] = $loginData->toJson();
        }
        return $ret;
    }

    public function fromJson($jsonObj)
    {
        return $this->loginDataMap = $this->decodeLoginDataMap($jsonObj);
    }

    public function decodeLoginDataMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $version => $count) {
            $loginData = new LoginData($version);
            $loginData->fromJson($count);
            $ret[$version] = $loginData;
        }
        return $ret;
    }

    public function merge($other)
    {
        if (!empty($other->loginDataMap)) {
            foreach ($other->loginDataMap as $version => $loginData) {
                if (!array_key_exists($version, $this->loginDataMap)) {
                    $newLoginData = new LoginData($version);
                    $newLoginData->merge($loginData);
                    $this->loginDataMap[$version] = $newLoginData;
                } else {
                    $this->loginDataMap[$version]->merge($loginData);
                }
            }
        }
        return $this;
    }
}
<?php

namespace app\admin\script\analysis\register;

class RegisterData
{
    public $uid = 0;
    public $mobile = '';
    public $register_time = '';
    public $os = '';
    public $register_ip = 0;
    public $register_channel = 0;
    public $qopenid = '';
    public $wxopenid = '';
    public $appleid = '';
    public $invitcode = '';
    public $version = '';
    public $deviceId = '';
    public $idfa = '';
    public $source = '';

    public function __construct($uid)
    {
        $this->uid = $uid;
    }


    public function add($data)
    {
        $this->uid = $data['uid'] ?? '';
        $this->mobile = $data['mobile'] ?? '';
        $this->register_time = $data['register_time'] ?? '';
        $this->os = $data['os'] ?? '';
        $this->register_ip = $data['register_ip'] ?? '';
        $this->register_channel = $data['register_channel'] ?? '';
        $this->qopenid = $data['qopenid'] ?? '';
        $this->wxopenid = $data['wxopenid'] ?? '';
        $this->appleid = $data['appleid'] ?? '';
        $this->invitcode = $data['invitcode'] ?? '';
        $this->version = $data['version'] ?? '';
        $this->deviceId = $data['deviceId'] ?? '';
        $this->idfa = $data['idfa'] ?? '';
        $this->source = $data['source'] ?? '';

    }


    public function toJson()
    {
        return [
            "uid" => $this->uid,
            "mobile" => $this->mobile,
            "register_time" => $this->register_time,
            "os" => $this->os,
            "register_ip" => $this->register_ip,
            "register_channel" => $this->register_channel,
            "qopenid" => $this->qopenid,
            "wxopenid" => $this->wxopenid,
            "appleid" => $this->appleid,
            "invitcode" => $this->invitcode,
            "version" => $this->version,
            "deviceId" => $this->deviceId,
            "idfa" => $this->idfa,
            "source" => $this->source,
        ];
    }


    public function fromJson($jsonObj)
    {
        return $this;
    }


    public function merge($other = [])
    {
        return [
            "uid" => $this->uid,
            "mobile" => $this->mobile,
            "register_time" => $this->register_time,
            "os" => $this->os,
            "register_ip" => $this->register_ip,
            "register_channel" => $this->register_channel,
            "qopenid" => $this->qopenid,
            "wxopenid" => $this->wxopenid,
            "appleid" => $this->appleid,
            "invitcode" => $this->invitcode,
            "version" => $this->version,
            "deviceId" => $this->deviceId,
            "idfa" => $this->idfa,
            "source" => $this->source,
        ];
    }
}
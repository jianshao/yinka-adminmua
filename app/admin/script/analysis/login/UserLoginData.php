<?php
namespace app\admin\script\analysis\login;

class UserLoginData
{
    public $uid = 0;

    public $osDataMap = [];
    public $channelDataMap = [];
    public $deviceIdDataMap = [];
    public $deviceDataMap = [];

    public function __construct($uid)
    {
        $this->uid = $uid;
    }

    public function addOs($os, $version, $count)
    {
        if (array_key_exists($os, $this->osDataMap)) {
            $this->osDataMap[$os]->add($version, $count);
        } else {
            $VersionData = new VersionData($version);
            $VersionData->add($version, $count);
            $this->osDataMap[$os] = $VersionData;
        }
        return $this;
    }

    public function addChannel($channel, $version, $count)
    {
        if (array_key_exists($channel, $this->channelDataMap)) {
            $this->channelDataMap[$channel]->add($version, $count);
        } else {
            $VersionData = new VersionData($version);
            $VersionData->add($version, $count);
            $this->channelDataMap[$channel] = $VersionData;
        }
        return $this;
    }

    public function addDeviceId($device_id, $version, $count)
    {
        if (array_key_exists($device_id, $this->deviceIdDataMap)) {
            $this->deviceIdDataMap[$device_id]->add($version, $count);
        } else {
            $VersionData = new VersionData($version);
            $VersionData->add($version, $count);
            $this->deviceIdDataMap[$device_id] = $VersionData;
        }
        return $this;
    }

    public function addDevice($mobile_version, $version, $count)
    {
        if (array_key_exists($mobile_version, $this->deviceDataMap)) {
            $this->deviceDataMap[$mobile_version]->add($version, $count);
        } else {
            $VersionData = new VersionData($version);
            $VersionData->add($version, $count);
            $this->deviceDataMap[$mobile_version] = $VersionData;
        }
        return $this;
    }

    public function fromJson($jsonObj)
    {
        if (array_key_exists('os', $jsonObj)) {
            $this->osDataMap = $this->decodeLoginDataMap($jsonObj['os']);
        }
        if (array_key_exists('channel', $jsonObj)) {
            $this->channelDataMap = $this->decodeLoginDataMap($jsonObj['channel']);
        }
        if (array_key_exists('deviceId', $jsonObj)) {
            $this->deviceIdDataMap = $this->decodeLoginDataMap($jsonObj['deviceId']);
        }
        if (array_key_exists('device', $jsonObj)) {
            $this->deviceDataMap = $this->decodeLoginDataMap($jsonObj['device']);
        }
        return $this;
    }

    public function decodeLoginDataMap($jsonObj)
    {
        $ret = [];
        foreach ($jsonObj as $type => $versionDataJson) {
            $newVersionData = new VersionData($type);
            $newVersionData->fromJson($versionDataJson);
            $ret[$type] = $newVersionData;
        }
        return $ret;
    }

    public function toJson()
    {
        return [
            'os' => $this->encodeLoginDataMap($this->osDataMap),
            'channel' => $this->encodeLoginDataMap($this->channelDataMap),
            'deviceId' => $this->encodeLoginDataMap($this->deviceIdDataMap),
            'device' => $this->encodeLoginDataMap($this->deviceDataMap),
        ];
    }

    public function encodeLoginDataMap(&$VersionDataMap)
    {
        $ret = [];
        foreach ($VersionDataMap as $type => $VersionData) {
            $ret[$type] = $VersionData->toJson();
        }
        return $ret;
    }

    public function merge($other)
    {
        if (!empty($other->osDataMap)) {
            foreach ($other->osDataMap as $os => &$osData) {
                if (!array_key_exists($os, $this->osDataMap)) {
                    $newosData = new VersionData($os);
                    $newosData->merge($osData);
                    $this->osDataMap[$os] = $newosData;
                } else {
                    $this->osDataMap[$os]->merge($osData);
                }
            }
        }
        if (!empty($other->channelDataMap)) {
            foreach ($other->channelDataMap as $channel => &$channelData) {
                if (!array_key_exists($channel, $this->channelDataMap)) {
                    $newchannelData = new VersionData($channel);
                    $newchannelData->merge($channelData);
                    $this->channelDataMap[$channel] = $newchannelData;
                } else {
                    $this->channelDataMap[$channel]->merge($channelData);
                }
            }
        }
        if (!empty($other->deviceIdDataMap)) {
            foreach ($other->deviceIdDataMap as $deviceId => &$deviceIdData) {
                if (!array_key_exists($deviceId, $this->deviceIdDataMap)) {
                    $newdeviceIdData = new VersionData($deviceId);
                    $newdeviceIdData->merge($deviceIdData);
                    $this->deviceIdDataMap[$deviceId] = $newdeviceIdData;
                } else {
                    $this->deviceIdDataMap[$deviceId]->merge($deviceIdData);
                }
            }
        }
        if (!empty($other->deviceDataMap)) {
            foreach ($other->deviceDataMap as $device => &$deviceData) {
                if (!array_key_exists($device, $this->deviceDataMap)) {
                    $newdeviceData = new VersionData($device);
                    $newdeviceData->merge($deviceData);
                    $this->deviceDataMap[$device] = $newdeviceData;
                } else {
                    $this->deviceDataMap[$device]->merge($deviceData);
                }
            }
        }

        return $this;
    }
}
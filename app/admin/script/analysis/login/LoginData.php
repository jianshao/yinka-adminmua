<?php
namespace app\admin\script\analysis\login;

class LoginData
{
    public $version = 0;
    public $count = 0;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function toJson()
    {
        return $this->count;
    }

    public function fromJson($count)
    {
        $this->count = $count;
        return $this;
    }

    public function add($count)
    {
        $this->count += $count;
        return $this;
    }

    public function merge($other)
    {
        $this->count += $other->count;
        return $this;
    }
}
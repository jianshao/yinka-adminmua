<?php
/**
 * 公共配置
 * */
namespace app\admin\common;

class AppTypeConst
{
    const APP_TYPE_MAP = [
        0 => ['name' => 'MUA', 'url' => 'https://landing.fqparty.com/mua.html'],
        1 => ['name' => '音恋', 'url' => 'https://landing.fqparty.com/yinlian.html'],
    ];
    const APP_TYPE_MAP_2 = [
        // 'mua' => ['name' => 'MUA', 'url' => 'https://landing.fqparty.com/mua.html'],
        'fanqie' => ['name' => '番茄', 'url' => 'https://landing.fqparty.com/fanqie.html'],
        'yinlian' => ['name' => '音恋', 'url' => 'https://landing.fqparty.com/yinlian.html'],
        'ccp' => ['name' => '音恋语音处CP', 'url' => 'https://landing.fqparty.com/yinlian.html'],
    ];
}

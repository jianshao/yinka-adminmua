<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\validate;

use think\Validate;

class Gift extends Validate
{
    protected $rule = [
        'gift_name'  => 'require|max:25',
        'gift_number'   => 'number',
        'gift_coin' =>'number',
    ];
    protected $message  =   [
        'gift_name.require' => '名称必须',
        'gift_name.max'     => '名称最多不能超过25个字符',
        'gift_number.number'   => '财富值必须是数字',
        'gift_coin.number'        => '虚拟币必须是数字',
    ];

}
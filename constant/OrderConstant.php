<?php

namespace constant;

class OrderConstant
{
    /*
     * Code 码处理
     * */

    //成功
    const ORDER_M豆充值 = 1;
    const ORDER_VIP = 2;
    const ORDER_SVIP = 3;
    const ORDER_红包 = 4;

    const ORDER_TYPE_MAP = [
        self::ORDER_M豆充值 => 'M豆充值',
        self::ORDER_VIP => 'VIP充值',
        self::ORDER_SVIP => 'SVIP充值',
        self::ORDER_红包 => '红包充值',
    ];
}
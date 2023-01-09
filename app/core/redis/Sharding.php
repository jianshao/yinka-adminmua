<?php

namespace app\core\redis;

use app\utils\ArrayUtil;

class Sharding
{
    protected $serviceName;

    protected $dbNameMap = [
        'user' => [
            'db1',
        ],
        'common' => [
            'db1'
        ],
        'room' => [
            'db1'
        ],
        'rank' => [
            'db1'
        ],
        'conf' => [
            'db1'
        ]
    ];

    /**
     * 根据分库字段获取数据库连接
     * @param $shardingColumn
     * @return mixed|null
     */
    protected function getDbName($shardingColumn)
    {
        $dbMap = ArrayUtil::safeGet($this->dbNameMap, $this->serviceName);
        $count = count($dbMap);
        if (is_numeric($shardingColumn)) {
            $dbName = ArrayUtil::safeGet($dbMap, $shardingColumn % $count);
        } else {
            $dbName = ArrayUtil::safeGet($dbMap, crc32($shardingColumn) % $count);
        }
        return $dbName;
    }


}

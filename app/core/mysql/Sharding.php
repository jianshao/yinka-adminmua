<?php

namespace app\core\mysql;

use app\core\model\BaseModel;
use app\core\model\BaseModelIds;
use app\utils\ArrayUtil;

class Sharding
{
    protected $dbMap = [];
    protected static $instance;
    private $dbNameMap = [];

    private function __construct()
    {
        $this->dbNameMap = config("shard");
    }

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Sharding();
        }
        return self::$instance;
    }

    /**
     * 获取
     * @param $shardingColumn
     * @return BaseModel
     */
    public function getModel($serviceName, $tableName, $shardingColumn = '')
    {
        $dbName = $this->getDbName($serviceName, $shardingColumn);
        $model = new BaseModel();
        if (empty($tableName)) {
            throw new \Exception('获取数据库模型异常', 2002);
        }
        $model->setTableName($tableName);
        $model->setConnect($dbName);
        $this->dbMap[sprintf('%s-%s', $dbName, $tableName)] = $model;
        return $model;
    }

    public function getModels($serviceName, $tableName, $shardingColumns = [])
    {
        $models = [];
        foreach ($shardingColumns as $shardingColumn) {
            $model = $this->getModel($serviceName, $tableName, $shardingColumn);
            if (!ArrayUtil::safeGet($models, $model->getConnect())) {
                $models[$model->getConnect()]['model'] = $model;
            }
            $models[$model->getConnect()]['list'][] = $shardingColumn;
        }
        $res = [];
        foreach ($models as $model) {
            $res[] = new BaseModelIds($model['model'], $model['list']);
        }
        return $res;
    }

    public function getModelsMap($serviceName, $tableName, $shardingColumns = [])
    {
        $models = [];
        foreach ($shardingColumns as $shardingColumn) {
            $model = $this->getModel($serviceName, $tableName, $shardingColumn);
            if (!ArrayUtil::safeGet($models, $model->getConnect())) {
                $models[$model->getConnect()]['model'] = $model;
            }
            $models[$model->getConnect()]['list'][] = $shardingColumn;
        }
        $res = [];
        foreach ($models as $model) {
            $res[] = new BaseModelIds($model['model'], $model['list']);
        }
        return $res;
    }

    /**
     * 根据分库字段获取数据库连接
     * @param $shardingColumn
     * @return mixed|null
     */
    public function getDbName($serviceName, $shardingColumn)
    {
        $dbMap = $this->getDbMap($serviceName);
        $count = count($dbMap);
        if (is_numeric($shardingColumn)) {
            $dbName = ArrayUtil::safeGet($dbMap, $shardingColumn % $count);
        } else {
            $dbName = ArrayUtil::safeGet($dbMap, crc32($shardingColumn) % $count);
        }
        return $dbName;
    }

    public function getDbMap($serviceName)
    {
        return ArrayUtil::safeGet($this->dbNameMap, $serviceName);
    }

    public function getConnectModel($serviceName, $shardingColumn): BaseModel
    {
        $dbName = $this->getDbName($serviceName, $shardingColumn);
        $model = new BaseModel();
        $model->setConnect($dbName);
        return $model;
    }


}
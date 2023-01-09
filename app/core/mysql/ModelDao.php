<?php

namespace app\core\mysql;

use think\Exception;
use think\Model;

class ModelDao extends Model
{
    protected $table = '';
    protected $serviceName = '';

    /**
     * 获取
     * @param $shardingColumn
     */
    public function getModel($shardingColumn = '')
    {
        if (empty($this->table) || empty($this->serviceName)) {
            throw new Exception('获取数据库模型异常', 500);
        }

        return Sharding::getInstance()->getModel($this->serviceName, $this->table, $shardingColumn);
    }

    public function getModels($shardingColumns = [])
    {
        return Sharding::getInstance()->getModels($this->serviceName, $this->table, $shardingColumns);
    }

    public function getDbName($shardingColumns)
    {
        return Sharding::getInstance()->getDbName($this->serviceName, $shardingColumns);
    }

    public function getAllModels()
    {
        $shareconfig = config("shard");
        $count = count($shareconfig[$this->serviceName]);
        $key = [];
        for ($i = 0; $i < $count; $i++) {
            $key[] = $i;
        }
        return $this->getModels($key);
    }

    //获取配置来获取所有的model
    public function getallModel()
    {
        $shareconfig = config("shard");
        $count = count($shareconfig[$this->serviceName]);
        $key = [];
        for ($i = 0; $i < $count; $i++) {
            $key[] = $i;
        }
        return $this->getModels($key);
    }

    public function getWhereOrData($whereOr, $offset, $limit)
    {
        $data = [];
        $models = $this->getAllModels();
        foreach ($models as $model) {
            $res = $model->model->whereOr($whereOr)->limit($offset, $limit)->order('id', 'desc')->select()->toArray();
            $data = array_merge($res, $data);
        }
        return $data;
    }

    public function getWhereData($where, $offset, $limit)
    {
        $data = [];
        $models = $this->getAllModels();
        foreach ($models as $model) {
            $res = $model->model->where($where)->limit($offset, $limit)->order('id', 'desc')->select()->toArray();
            $data = array_merge($res, $data);
        }
        return $data;
    }

    public function getWhereAllData($where, $field = '*', $group = '')
    {
        $data = [];
        if ($where) {
            $models = $this->getAllModels();

            foreach ($models as $model) {
                $res = $model->model->field($field);
                if ($where) {
                    $res = $res->where($where);
                }

                if ($group) {
                    $res = $res->group($group);
                }

                $res = $res->select()->toArray();
                // $res = $res->fetchSql(true)->select();
                $data = array_merge($res, $data);
            }
        }
        return $data;
    }

    public function getWhereCount($where)
    {
        $count = 0;
        if ($where) {
            $models = $this->getAllModels();
            foreach ($models as $model) {
                $model_count = $model->model->where($where)->count();
                $count += $model_count;
            }
        }
        return $count;
    }

    /**
     *
     * @param $where
     * @param int $page
     * @param int $limit
     * @param string $field
     * @return array
     */
    public function getDataByWherePage($where, $page = 1, $limit = 100, $field = "*")
    {
        $data = [];
        $models = $this->getAllModels();
        foreach ($models as $model) {
            $res = $model->model->field($field)->where($where)->page($page, $limit)->select()->toArray();
            $data = array_merge($res, $data);
        }
        return $data;
    }
}

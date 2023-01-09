<?php


namespace app\core\model;

use think\Model;

class BaseModel extends Model
{
    public function setTableName($tableName) {
        $this->table = $tableName;
    }

    public function setConnect($dbName) {
        $this->connection = $dbName;
    }

    public function getConnect() {
        return $this->connection;
    }
}
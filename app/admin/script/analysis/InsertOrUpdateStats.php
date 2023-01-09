<?php
namespace app\admin\script\analysis;

use think\Exception;
use think\facade\Db;

class InsertOrUpdateStats
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     *更新同步表数据ID
     */
    public function insertOrUpdate($data, array $unique_keys, $table)
    {
        $this->checkColumns($this->getTableColumn($table), array_keys($data));

        //判断表字段是否相等
        $columns = array_keys($data);
        $key_columns = implode(',', $columns);

        $value_str = '';
        $update_str = '';

        foreach ($data as $column => $value) {
            if (is_string($value)) {
                $str = "'{$value}'";
            } else {
                $str = "{$value}";
            }

            if ($column == $columns[count($columns) - 1]) {
                $value_str .= "{$str}";
                if (!in_array($column, $unique_keys)) {
                    $update_str .= "{$column} = {$str}";
                }
            } else {
                $value_str .= "{$str},";
                if (!in_array($column, $unique_keys)) {
                    $update_str .= "{$column} = {$str},";
                }
            }
        }

        $sql = "insert into `{$table}` ({$key_columns}) values ({$value_str}) on duplicate key update {$update_str};";
        Db::execute($sql);
    }

    protected function getTableColumn($table)
    {
        $sql = "select column_name from information_schema.COLUMNS where table_schema = 'mua' and column_name != 'id' and table_name = '{$table}';";
        return array_column(Db::query($sql), 'column_name');
    }

    protected function checkColumns($compare_arr1, $compare_arr2)
    {
        $res = array_diff($compare_arr1, $compare_arr2);
        if (!empty($res)) {
            throw new Exception("insert cloumns error");
        }
        return true;
    }
}
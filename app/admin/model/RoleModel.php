<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class RoleModel extends ModelDao
{
    protected $table = 'yyht_role';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoleModel();
        }
        return self::$instance;
    }

    public function getRoleLists($where, $field = '*', $page = 1, $size = 20, $order = ' order by id desc')
    {
        // $sql = 'select ' . $field . ' from ' . $this->table . ' where ' . $where . $order . ' limit ' . $page . ',' . $size;
        // return $this->query($sql);

        return $this->getModel()->field($field)->where($where)->limit($page * $size, $page)->order('id desc')->select()->toArray();
    }

    public function getRoleListCounts($where)
    {
        return $this->getModel()->where($where)->count();
    }
}

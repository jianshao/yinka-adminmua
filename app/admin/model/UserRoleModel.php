<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class UserRoleModel extends ModelDao
{
    protected $table = 'yyht_re_user_role';
    protected $pk = 'id';
    // protected $connection = '';
    protected static $instance;
    protected $serviceName = 'bi';

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserRoleModel();
        }
        return self::$instance;
    }

    /*
     * ����user_id||role_id||id��ȡ�û���ɫ��ϵ
     * @param user_id
     * @param id
     * @return array
     */
    public function getUserRoleFind($where, $field)
    {
        $res = $this->getModel()->where($where)->field($field)->find();
        if (empty($res)) {
            return [];
        }
        return $res;
    }
}
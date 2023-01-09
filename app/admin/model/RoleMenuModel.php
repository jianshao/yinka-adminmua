<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class RoleMenuModel extends ModelDao
{
    protected $table = 'yyht_re_role_menu';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoleMenuModel();
        }
        return self::$instance;
    }

    /*
     * ����user_id||role_id||id��ȡ��ɫ�˵�
     * @param user_id
     * @param role_id
     * @param id
     * @return array
     */
    public function getRoleMenuList($role_id, $field = '*')
    {
        $sql = 'select ' . $field . ' from ' . $this->table . ' as a left join yyht_menu as b on a.menu_id = b.id or a.menu_id = b.parent
        where a.role_id = ' . $role_id . ' and status = 1';
        return $this->query($sql);
    }
}

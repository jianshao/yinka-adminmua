<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class MemberBlackModel extends ModelDao
{
    protected $table = 'zb_black_list';
    protected $pk = 'id';
    protected $serviceName = 'bi';

    protected static $instance;

    //����
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberBlackModel();
        }
        return self::$instance;
    }
}

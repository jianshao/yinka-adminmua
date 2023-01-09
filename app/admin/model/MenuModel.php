<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class MenuModel extends ModelDao
{
    protected $table = 'yyht_menu';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MenuModel();
        }
        return self::$instance;
    }

}

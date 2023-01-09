<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class VsitorExternnumberModel extends ModelDao
{
    protected $table = 'yyht_visitor_externnumber';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new VsitorExternnumberModel();
        }
        return self::$instance;
    }
}

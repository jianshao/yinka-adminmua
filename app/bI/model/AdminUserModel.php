<?php


namespace app\bI\model;


use think\Model;

class AdminUserModel extends Model
{
    protected $table = 'bi_admin';
    protected $pk = 'id';
    // protected $connection = '';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new AdminUserModel();
        }
        return self::$instance;
    }
}

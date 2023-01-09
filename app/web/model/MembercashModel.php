<?php
/**

 */
namespace app\web\model;
use think\Model;

class MembercashModel extends Model{

    protected $table = 'zb_member_cash';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new MembercashModel();
        }
        return self::$instance;
    }





}
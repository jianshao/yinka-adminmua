<?php
/**
 * User: yond
 * Date: 2019
 * 苹果内购充值列表
 */
namespace app\common\model;
use think\Model;

class ChargeiosModel extends Model{

    protected $table = 'zb_chargeios';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ChargeiosModel();
        }
        return self::$instance;
    }

}
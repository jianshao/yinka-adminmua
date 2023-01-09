<?php
/**
 * User: yond
 * Date: 2019
 * 充值比例
 */
namespace app\common\model;
use think\Model;

class ChargeModel extends Model{

    protected $table = 'zb_charge';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ChargeModel();
        }
        return self::$instance;
    }
    
    




}
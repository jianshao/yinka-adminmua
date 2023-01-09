<?php
/**
 * User: yond
 * Date: 2019
 * 礼物
 */
namespace app\common\model;
use think\Model;

class GiftModel extends Model{

    protected $table = 'zb_gift';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GiftModel();
        }
        return self::$instance;
    }
    
    




}
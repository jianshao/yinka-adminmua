<?php
/**
 * @author yond
 * 平台等级表
 * $date 2019
 */
namespace app\common\model;

use think\Model;

class GradeDiamondModel extends Model
{
    protected $table = 'zb_grade_diamond';
    protected $pk = 'id';
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GradeDiamondModel();
        }
        return self::$instance;
    }
}
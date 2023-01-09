<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;
use think\Model;

class EmotionKeywordModel extends ModelDao
{
    protected $table = 'zb_shine_black_keyword';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

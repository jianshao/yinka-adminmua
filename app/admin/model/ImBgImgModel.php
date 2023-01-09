<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;

class ImBgImgModel extends ModelDao
{
    protected $table = 'cfg_im_background_images';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
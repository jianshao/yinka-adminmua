<?php

namespace app\admin\model;

use app\core\mysql\ModelDao;

class LoginFeedbackModel extends ModelDao
{
    protected $table = 'zb_login_feedback';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LoginFeedbackModel();
        }
        return self::$instance;
    }

}

<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;

class ReportPunishLevelModel extends ModelDao
{
    protected $table = 'cfg_punish_level';
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

    public function getPunishListByType($type)
    {
        return $this->getModel()->where('type', $type)->field('id,level,title')->select()->toArray();
    }

    public function getOneById($id)
    {
        $res = $this->getModel()
            ->where('id', $id)
            ->findOrEmpty()
            ->toArray();
        if (empty($res)) {
            throw new \Exception('举报等级信息不存在', 500);
        }
        return $res;
    }
}

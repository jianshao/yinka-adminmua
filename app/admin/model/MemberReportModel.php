<?php
namespace app\admin\model;

use app\core\mysql\ModelDao;

class MemberReportModel extends ModelDao
{
    protected $table = 'zb_member_report';
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

    public function limitOne()
    {
        return $this->getModel()->where('status', 0)->where('admin_id', 0)->order('create_time asc')->findOrEmpty()->toArray();
    }

    public function getOneReportInfoByAdminId($admin_id)
    {
        return $this->getModel()
            ->where('status', 1)
            ->where('admin_id', $admin_id)
            ->findOrEmpty()
            ->toArray();
    }

    public function getOneById($id)
    {
        $res = $this->getModel()
            ->where('status', 1)
            ->where('id', $id)
            ->findOrEmpty()
            ->toArray();

        if (empty($res)) {
            throw new \Exception('举报信息不存在', 500);
        }
        return $res;
    }

    public function updateOne($where, $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
}

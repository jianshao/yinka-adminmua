<?php
/**
 * Created by PhpStorm.
 * User: sh
 * Date: 2019/7/24
 * Time: 14:37
 */
namespace app\admin\model;

use app\core\mysql\ModelDao;

class BlackDataModel extends ModelDao
{
    protected $table = 'zb_black_data';
    protected $pk = 'user_id';
    protected static $instance;
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BlackDataModel();
        }
        return self::$instance;
    }

    /**列表
     * @param $where    where条件
     * @param $offset   分页
     * @param $limit    条数
     * @return mixed
     */
    public function getList($where, $offset, $limit)
    {
        return $this->getModel()->where($where)->order('update_time', 'desc')->limit($offset, $limit)->select()->toArray();
    }

    public function getIsOneByWhere($type, $blackinfo)
    {
        return $this->getModel()->where([['blackinfo', '=', $blackinfo], ['type', '=', $type]])->find();
    }

    public function memberBlackList($where)
    {
        return $this->getModel()->where($where)->select()->toArray();
    }

    public function updateBlackDataNew($data)
    {
        $list = [
            'user_id' => $data['user_id'],
            'end_time' => $data['end_time'],
            'status' => $data['status'],
            'admin_id' => $data['admin_id'],
            'reason' => $data['reason'],
            'blacks_time' => $data['blacks_time'],
            'update_time' => $data['update_time'],
        ];
        if (array_key_exists('time', $data)) {
            $list += ['time' => $data['time']];
        } else {
            $list += ['time' => '-1'];
        }
        return $this->getModel()->where('blackinfo', $data['blackinfo'])->save($list);
    }

    public function insertBlackDataNew($data)
    {
        return $this->getModel()->insert($data);
    }

    public function getOneByWhere($where = [], $field)
    {
        return $this->getModel()->field($field)->where($where)->find();
    }

    public function insertBlackData($data = [])
    {
        $where[] = ['type', '=', $data['type']];
        if ($data['type'] == 4) {
            $where[] = ['user_id', '=', $data['user_id']];
        } else {
            $where[] = ['blackinfo', '=', $data['blackinfo']];
        }
        $blackinfo = $this->getModel()->where($where)->value('blackinfo');
        if ($blackinfo) {
            return $this->getModel()->where('blackinfo', $blackinfo)->save($data);
        } else {
            return $this->getModel()->save($data);
        }
    }

    public function updateBlackData($where = [], $data = [])
    {
        return $this->getModel()->where($where)->save($data);
    }

}
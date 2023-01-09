<?php
/**
 * User: yond
 * Date: 2019
 * Time: 14:37
 */
namespace app\common\model;

use think\Model;

class CoindetailModel extends Model
{

    protected $table = 'zb_coindetail';
    protected $pk = 'id';
    protected static $instance;
    protected $serviceName = 'bi';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new CoindetailModel();
        }
        return self::$instance;
    }

    /*
     * 根据条件查询分组房间排序
     */
    public function getCoinRoomRank($where)
    {
    }

    /*
     * 查询列表无分页
     */
    public function getCoindetailListNoPage($where, $order, $column = '*')
    {
        $ret = $this->where($where)->order($order)->field($column)->select();
        if ($ret) {
            return $ret->toArray();
        } else {
            return array();
        }
    }

    /**
     * 根据id获取单个
     *
     * @param $id
     * @param int $uid
     * @return array
     */
    public function getCoindetailById($id)
    {
        $where['id'] = $id;
        $res = $this->where($where)->find();
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }

}

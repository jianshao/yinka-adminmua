<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */
namespace app\admin\service;

use app\admin\model\ComplaintsModel;

class ComplaintsService extends ComplaintsModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ComplaintsService();
        }
        return self::$instance;
    }

    /**获取所有用户的数据
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回类型
     */
    public function getComplaintsListPage($where, $page, $pagenum)
    {
        $limit = [$page, $pagenum];
        $order = ['id', 'desc'];
        $res = ComplaintsModel::getInstance()->getComplaintsListPage($where, $limit, $order);
        return $res;
    }

}

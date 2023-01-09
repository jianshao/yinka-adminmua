<?php
namespace app\admin\common;
use app\admin\script\analysis\ParseUserActionCommon;
use app\admin\script\analysis\UserBehavior;
use think\facade\Db;

/**
 * User: baixin
 * Date: 2022/5/17
 * Time: 22:36
 */

class ParseUserState{

    protected static $instance;
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    public function getParseUserData($database)
    {
        //user_all 日活
        //charge_user:直充
        //agentcharge_user:代充
        //注册用户量
        $userList = [
            'active' => [],      //日活/注册/
            'charge' => [],      //充值/新增充值
        ];
        $userbehavior = new UserBehavior();
        $res = $database->select()->toArray();
        $parseUserList = [];
        foreach ($res as $item) {
            ParseUserActionCommon::getInstance()->parseDataNew($item, $userbehavior, $parseUserList);
        }
        $charge_user = $parseUserList['user']['charge_user'] ?? [];
        $agentcharge_user = $parseUserList['user']['agentcharge_user'] ?? [];
        $active_user = $parseUserList['user']['user_all'] ?? [];
        $charge_all_user = array_unique(array_merge($charge_user, $agentcharge_user));
        $userList['active'] = $active_user;
        $userList['charge'] = $charge_all_user;
        return $userList;
    }

    /**
     * 字符串取交集
     * @param $param
     * @param $param1
     * @param string $mark
     * @return array
     */
    public function  strIntersect($param,$param1,$mark=","){
        $a = explode($mark,$param);
        $b = explode($mark,$param1);
        return array_intersect($a,$b);
    }


}
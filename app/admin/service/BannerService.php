<?php

namespace app\admin\service;

use app\admin\model\BannerModel;
use app\common\RedisCommon;
use think\facade\Log;

class BannerService
{
    protected static $instance;
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BannerService();
        }
        return self::$instance;
    }

    public function clear()
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 0]);
        $clearindex = $redis->del("list_bannerindex");
        $clearroom = $redis->del("list_bannerroom");
        $clearroom_mua = $redis->del("list_bannerindex_mua");
        $clearpay = $redis->del("list_bannerpay");

        if ($clearindex || $clearroom || $clearroom_mua || $clearpay) {
            Log::record('清除banner缓存成功:定时脚本:');
        } else {
            Log::record('清除banner缓存失败:定时脚本:');
        }
    }

    /*
     *上架banner
     */
    public function start_time()
    {
        $time = date('Y-m-d H:i:s');
        $where[] = ['start_time', '<', $time];
        $where[] = ['end_time', '>', $time];
        $is = BannerModel::getInstance()->getModel()->where($where)->save(['status' => 2]);
        $this->clear();
    }

    /*
     *下架banner
     */
    public function end_time()
    {
        $time = date('Y-m-d H:i:s');
        $where[] = ['start_time', '<', $time];
        $where[] = ['end_time', '<', $time];
        $is = BannerModel::getInstance()->getModel()->where($where)->save(['status' => 1]);
        $this->clear();
    }

    public function endTime()
    {
        $where[] = ['end_time', '=', '0000-00-00 00:00:00'];
        $is = BannerModel::getInstance()->getModel()->where($where)->save(['status' => 1]);
        $this->clear();
    }
}

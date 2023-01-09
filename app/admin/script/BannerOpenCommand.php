<?php

namespace app\admin\script;

use app\admin\model\ActivityRedModel;
use app\admin\model\NoticeModel;
use app\admin\service\BannerService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);

class BannerOpenCommand extends Command
{
    protected function configure()
    {
        $this->setName('BannerOpenCommand')->setDescription('BannerOpenCommand');
    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        BannerService::getInstance()->start_time();
        BannerService::getInstance()->end_time();
        BannerService::getInstance()->endTime();
        $this->nowAddNotice();
        $this->activityRed();
    }

    protected function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.redis');
        $param['host'] = $redis_result['host'];
        $param['port'] = $redis_result['port'];
        $param['password'] = $redis_result['password'];
        $param['select'] = 0;
        if (!empty($arr)) {
            foreach ($arr as $v => $v) {
                $param[$v] = $v;
            }
        }

        $this->handler = new \Redis;
        $this->handler->connect($param['host'], $param['port'], 0);
        if ('' != $param['password']) {
            $this->handler->auth($param['password']);
        }

        if (0 != $param['select']) {
            $this->handler->select($param['select']);
        }
        return $this->handler;
    }

    protected function activityRed()
    {
        //关闭通知
        $where2[] = ['update_time', '<=', time()];
        $where2[] = ['status', '=', 1];
        $where2[] = ['is_del', '=', 0];
        $activityRedInfo = ActivityRedModel::getInstance()->getModel()->where($where2)->find();
        if (isset($activityRedInfo['id'])) {
            $str['msgId'] = 2091; //活动红包消息id
            $str['sysRedUnderway'] = false;
            $str['curValue'] = 0;
            $str['activity_image'] = !empty($activityRedInfo['progressbar_image']) ? getavatar($activityRedInfo['progressbar_image']) : '';
            $str['maxValue'] = $activityRedInfo['money'];
            $str['activityId'] = $activityRedInfo['id'];
            $str['roomId'] = 0;
            $msgFull['roomId'] = 0;
            $msgFull['msg'] = json_encode($str);
            $msgFull['toUserId'] = '0';
            $msgDataFull = json_encode($msgFull);
            $socket_url = config('config.socket_url');
            $curlData = curlData($socket_url, $msgDataFull, 'POST', 'json');
            ActivityRedModel::getInstance()->getModel()->where($where2)->save(['status' => 0]);
        }

        //开启通知
        $where1[] = ['create_time', '<=', time()];
        $where1[] = ['update_time', '>=', time()];
        $where1[] = ['is_del', '=', 0];
        $where1[] = ['status', '=', 0];
        $activityRedInfo = ActivityRedModel::getInstance()->getModel()->where($where1)->find();
        if (isset($activityRedInfo['id'])) {
            $str['msgId'] = 2091; //活动红包消息id
            $str['sysRedUnderway'] = true;
            $str['curValue'] = 0;
            $str['activity_image'] = getavatar($activityRedInfo['progressbar_image']);
            $str['maxValue'] = $activityRedInfo['money'];
            $str['activityId'] = $activityRedInfo['id'];
            $str['roomId'] = 0;
            $msgFull['roomId'] = 0;
            $msgFull['msg'] = json_encode($str);
            $msgFull['toUserId'] = '0';
            $msgDataFull = json_encode($msgFull);
            $socket_url = config('config.socket_url');
            $curlData = curlData($socket_url, $msgDataFull, 'POST', 'json');
            ActivityRedModel::getInstance()->getModel()->where($where1)->save(['status' => 1]);
        }
    }

    /*
     * 添加立即发送公告
     */
    public function nowAddNotice()
    {
        $noticeId = NoticeModel::getInstance()->getModel()->where(['notice_status' => 2])->value('id');
        if (!$noticeId) {return;}
        $url = config('config.APP_URL_image');
        $data = [];
        $redisData = [];
        //判断是否上传图片
        $noticeValue = NoticeModel::getInstance()->getModel()->where(['notice_status' => 2])->select()->toArray();
        foreach ($noticeValue as $keys => $value) {
            if ($value['notice_img']) {
                $redisData['notice_img'] = $url . $value['notice_img'];
            } else {
                $redisData['notice_img'] = "";
            }
            //放入数组
            $data['jump_url'] = $value['jump_url'];
            $data['notice_title'] = $value['notice_title'];
            $data['notice_content'] = $value['notice_content'];
            $data['notice_status'] = 1;
            $data['timing_time'] = $value['timing_time'];

            //存入redis数组
            $redis = $this->getRedis();
            $redisData['notice_title'] = $value['notice_title'];
            $redisData['notice_content'] = $value['notice_content'];
            $redisData['timing_time'] = $value['timing_time'];
            //添加操作
            $data['created_user'] = $value['created_user'];
            $data['created_time'] = $value['timing_time'];
            $notice_model = NoticeModel::getInstance()->getModel();
            $notice_model->where(['id' => $value['id']])->save(['notice_status' => 1]);
            $lastId = $notice_model->id;
            $result = \constant\CommonConstant::NOTICE_TIMING_KEY . $lastId;
            $redis->set($result, json_encode($redisData));
            //$redis->del('notice_msg_uid');
            $redis->setex('new_notice', 14400, 1);
            //GetuiCommon::getInstance()->pushMessageToApp($data['notice_title'],$redisData['notice_content']);
            Log::record('定时发送公告添加成功:操作人:' . $value['created_user'] . '@' . json_encode($data), 'nowAddNotice');
        }

    }

}
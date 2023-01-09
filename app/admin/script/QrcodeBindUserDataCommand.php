<?php

namespace app\admin\script;

use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiDaysUserGiftDatasBysendTypeModel;
use app\admin\model\BiDaysUserSendgiftModel;
use app\admin\model\InviteGuildAnchorsModel;
use app\admin\model\InviteQrcodeMemberRelationModel;
use app\admin\model\MemberModel;
use app\admin\service\ElasticsearchService;
use app\common\RabbitMQCommand;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

//
class QrcodeBindUserDataCommand extends Command
{

    //标签码绑定用户的基础跑量数据
    const  UPDATE_TABLE_NAME = 'invite_qrcode_member_relation'; //
    const COMMAND_NAME = "QrcodeBindUserDataCommand";
    private $anchor_uids = []; //主播陪陪的用户id
    const   REPLYMSGTTIMENUMBER = "replymsgtimenumber:anchorid:%s";
    private $redisHandler = NULL;


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        try {
            $this->anchor_uids = InviteGuildAnchorsModel::getInstance()->getModel()->where(['status' => 1])->column("uid", "id");
            $this->redisHandler = RedisCommon::getInstance()->getRedis(['select' => 8]);

            //目前只跑量3个月绑定的用户数据
            $page = 1;
            $maxLimit = 2000;
            $where[] = ['create_time', '>=', strtotime("-90days")];
            $where[] = ["anchor_id", "in", $this->anchor_uids]; //在白名单的主播用户
            $res = InviteQrcodeMemberRelationModel::getInstance()->getModel()->where($where)->page($page, $maxLimit)->select()->toArray();
            while ($res) {
                foreach ($res as $item) {
                    $updateData = [];
                    $register_time = $item['register_time'];
                    if (empty($item['register_time'])) {
                        $register_time = MemberModel::getInstance()->getModel($item['uid'])->where('id', '=', $item['uid'])->value('register_time');
                        $updateData['register_time'] = $register_time;
                    }
                    //设置主播陪陪黑名单
                    $this->setBlackAnchorUser($item);

                    //主播首次回复的时间 以及私聊次数
                    $esparams = ElasticsearchService::getInstance()->searchWhere("zb_check_im_message");
                    $esparams['body']['query']['bool']['must'][] = ['term' => ['from_uid' => $item['anchor_id']]];
                    $esparams['body']['query']['bool']['must'][] = ['term' => ['to_uid' => $item['uid']]];
                    $esparams['body']['from'] = 0;
                    $esparams['body']['size'] = 1;
                    $esparams['body']['sort'] = ["created_time" => ["order" => "asc"]];
                    $searchData = ElasticsearchService::getInstance()->search($esparams);
                    $res = $searchData['data'] ?? [];
                    $anchor_message_count = $searchData['total'] ?? 0;
                    if (isset($res[0]['created_time'])) {
                        $updateData['replay_first_time'] = date('Y-m-d H:i:s', $res[0]['created_time']);
                    }
                    $updateData['anchor_message_count'] = $anchor_message_count;

                    //用户私聊次数
                    $esparams = ElasticsearchService::getInstance()->searchWhere("zb_check_im_message");
                    $esparams['body']['query']['bool']['must'][] = ['term' => ['to_uid' => $item['anchor_id']]];
                    $esparams['body']['query']['bool']['must'][] = ['term' => ['from_uid' => $item['uid']]];
                    $esparams['body']['from'] = 0;
                    $esparams['body']['size'] = 1;
                    $searchData = ElasticsearchService::getInstance()->search($esparams);
                    $user_message_count = $searchData['total'] ?? 0;
                    $updateData['user_message_count'] = $user_message_count;

                    //$register_time  1日充值  7日充值  30日充值
                    if ($register_time) {
                        $date_1 = date('Y-m-d', strtotime($register_time));
                        $date_7 = date("Y-m-d", strtotime($register_time . "+7days"));
                        $date_30 = date("Y-m-d", strtotime($register_time . "+30days"));

                        if ($this->diffBetweenTwoDays($register_time)  <= 1 ){
                            $charge_1day = BiDaysUserChargeModel::getInstance()->getModel()
                                ->where("date", "=", $date_1)
                                ->where('uid', "=", $item['uid'])
                                ->sum('amount');
                            $updateData['charge_1day'] = $this->divedFunc($charge_1day, 10, 2);
                            $where=[];
                            $where[] = ["uid","=",$item['uid']];
                            $where[] = ["touid","=",$item['anchor_id']];
                            $where[] = ["date","=",$date_1];
                            $sendgift_1day = BiDaysUserSendgiftModel::getInstance()->getModel()->where($where)->sum("reward_amount");
                            $updateData['sendgift_1day'] = $this->divedFunc($sendgift_1day, 10, 2);;
                        }

                        if($this->diffBetweenTwoDays($register_time) <= 7){
                            $charge_7day = BiDaysUserChargeModel::getInstance()->getModel()
                                ->where("date", ">=", $date_1)
                                ->where('date', "<=", $date_7)
                                ->where('uid', "=", $item['uid'])
                                ->sum('amount');
                            $updateData['charge_7day'] = $this->divedFunc($charge_7day, 10, 2);
                            $where=[];
                            $where[] = ["uid","=",$item['uid']];
                            $where[] = ["touid","=",$item['anchor_id']];
                            $where[] = ["date",">=",$date_1];
                            $where[] = ["date","<=",$date_7];
                            $sendgift_7day = BiDaysUserSendgiftModel::getInstance()->getModel()->where($where)->sum("reward_amount");
                            $updateData['sendgift_7day'] = $this->divedFunc($sendgift_7day, 10, 2);;
                        }

                        if($this->diffBetweenTwoDays($register_time) <= 30){
                            $charge_30day = BiDaysUserChargeModel::getInstance()->getModel()
                                ->where("date", ">=", $date_1)
                                ->where('date', "<=", $date_30)
                                ->where('uid', "=", $item['uid'])
                                ->sum('amount');
                            $updateData['charge_30day'] = $this->divedFunc($charge_30day, 10, 2);

                            $where=[];
                            $where[] = ["uid","=",$item['uid']];
                            $where[] = ["touid","=",$item['anchor_id']];
                            $where[] = ["date",">=",$date_1];
                            $where[] = ["date","<=",$date_30];
                            $sendgift_30day = BiDaysUserSendgiftModel::getInstance()->getModel()->where($where)->sum("reward_amount");
                            $updateData['sendgift_30day'] = $this->divedFunc($sendgift_30day, 10, 2);;
                        }

                    }

                    InviteQrcodeMemberRelationModel::getInstance()->getModel()->where("id", "=", $item['id'])->update($updateData);
                }
                $page++;
                $res = InviteQrcodeMemberRelationModel::getInstance()->getModel()->where($where)->page($page, $maxLimit)->select()->toArray();
            }
            $this->anchorRemoveHandle();//主播移除白名单

        } catch (\Throwable $e) {
            Log::info(SELF::COMMAND_NAME . ":error:" . $e->getMessage());
        }
    }


    //相除
    private function divedFunc($param1, $param2, $decimal = 2)
    {
        if ($param2 == 0 || $param1 == false) {
            return 0;
        }
        return round($param1 / $param2, $decimal);
    }

    //主播移除白名单
    private function anchorRemoveHandle()
    {
        foreach ($this->anchor_uids as $uid) {
            $anchorkeyName = sprintf(SELF::REPLYMSGTTIMENUMBER, $uid);
            if ($this->redisHandler->SCARD($anchorkeyName) >= 2) {
                $removeUids[] = $uid;
            }
        }
        if (isset($removeUids) && $removeUids) {
            $condition[] = ["uid", "in", $removeUids];
            InviteGuildAnchorsModel::getInstance()->getModel()->where($condition)->update(['status' => -1,'black_time'=>time()]);
        }
    }


    /**
     * 设置主播黑名单
     * @param $item
     * @throws \Throwable
     */
    private function setBlackAnchorUser($item)
    {
        $anchorkeyName = sprintf(SELF::REPLYMSGTTIMENUMBER, $item['anchor_id']);
        if ($item['uid'] > 0 && $item['anchor_id'] && !$this->redisHandler->SISMEMBER($anchorkeyName, $item['anchor_id'])) {
            $esparams = ElasticsearchService::getInstance()->searchWhere("zb_check_im_message");
            $esparams['body']['query']['bool']['should'][] = ["bool" => ["must" => [["term" => ["from_uid" => $item['uid']]], ["term" => ["to_uid" => $item['anchor_id']]]]]];
            $esparams['body']['query']['bool']['should'][] = ["bool" => ["must" => [["term" => ["from_uid" => $item['anchor_id']]], ["term" => ["to_uid" => $item['uid']]]]]];
            $esparams['body']['from'] = 0;
            $esparams['body']['size'] = 100;
            $esparams['body']['sort'] = ["created_time" => ["order" => "asc"]];
            $searchData = ElasticsearchService::getInstance()->search($esparams);
            $res = $searchData['data'] ?? [];
            $uidfirstReplyTime = 0;    //获取第一次用户回复主播的时间
            $anchorfirstReplyTime = 0; //用户回复后 主播首次回应时间
            //数据必须是按照created_time 正序
            foreach ($res as $key => $searchItme) {
                if ($key == 0) {
                    if ($searchItme['from_uid'] == $item ['anchor_id']) {
                        //主播先发起聊天的 不设置黑名单
                        return;
                    }

                }
                if ($searchItme['from_uid'] == $item['uid'] && $uidfirstReplyTime == 0) {
                    $uidfirstReplyTime = $searchItme['created_time'];
                }
                if ($uidfirstReplyTime > 0) {
                    if ($searchItme['from_uid'] == $item['anchor_id']) {
                        $anchorfirstReplyTime = $searchItme['created_time'];
                        break;
                    }
                }
            }
            //如果主播没有回复则用当前时间赋值
            $anchorfirstReplyTime = $anchorfirstReplyTime ?: time();
            if ($uidfirstReplyTime > 0) {
                //用户首次发送消息给主播后，主播首次回复用户那条消息的的时间
                if (($anchorfirstReplyTime - $uidfirstReplyTime) > 180)
                    $this->redisHandler->sadd($anchorkeyName, $item['uid']);
            }

        }
    }

    /**
     * @param $day1 比较的日期
     * @param string $day2 当前日期
     * @return float|int
     */
    public function diffBetweenTwoDays($day1, $day2='')
    {
        if(empty($day2)){
            $day2 = date('Y-m-d');
        }
        $second1 = strtotime(date('Ymd', strtotime($day1)));
        $second2 = strtotime(date('Ymd', strtotime($day2)));
        if ($second2 < $second1) {
            return -1;
        }
        return ($second2 - $second1) / 86400;
    }


}

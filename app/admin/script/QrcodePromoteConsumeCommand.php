<?php

namespace app\admin\script;

use app\admin\model\InviteGuildAnchorsModel;
use app\admin\model\InviteQrcodeMemberRelationModel;
use app\admin\model\InviteQrcodesModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\MemberSocityModel;
use app\admin\model\YunxinModel;
use app\common\ParseUserStateByUniqkey;
use app\common\RabbitMQCommand;
use app\common\RedisCommon;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;

//标签二维码推广数据
class QrcodePromoteConsumeCommand extends Command
{

    const UPDATE_TABLE_NAME = 'invite_qrcode_member_relation'; //
    const COMMAND_NAME = "QrcodePromoteConsumeCommand";
    const MESSAGETIP = " Hi，我是音恋金牌经纪人推荐的，各方面符合您的要求，快来我的直播间看看吧！";
    const COMEROOMTIP = '通过“神秘星球”进入房间';
    const COMEROOMTIPEXPIRE = 3; //进入房间提示的有效期 单位:天


    protected function configure()
    {
        // 指令配置
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        $config = config("config.qrcodepromote_queue");
        $connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            '/', false, 'AMQPLAIN', null, 'en_US', '3.0', '3.0', null, false, 15);
        $channel = $connection->channel();
        //声明队列
        $channel->queue_declare($config['queue_name'], false, true, false, false);
        //消息确认之前  不接受其他消息
        //$channel->basic_qos(null, 1, null);
        //从队列中异步获取数据 //$no_ack 是否关闭确认消息收到
        try {
            $channel->basic_consume($config['queue_name'], '', false, $no_ack = false, false, false, [$this, "callback"]);
            while (count($channel->callbacks)) {
                try {
                    $channel->wait();
                } catch (\Throwable $e) {
                    throw $e;
                }

            }
        } catch (\Throwable $e) {
            Log::info(self::COMMAND_NAME . "error:" . $e->getMessage());
            $channel->close();
            $connection->close();
        }
    }

    public function callback($msg)
    {
        if ($msg->body) {
            Log::info(self::COMMAND_NAME . "原始数据:" . $msg->body);
            $parseRes = json_decode($msg->body, true);
            //{"tag":"qrcode","body":"{\"promotecode\":\"800135\",\"qrcode\":10001,\"user_id\":1439778}"}
            if ($parseRes) {
                $type = $parseRes['tag'] ?? '';
                if ($type == 'qrcode') {
                    $parsebody = json_decode($parseRes['body'], true);
                    $this->handleData($parsebody);
                }
            }
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        }
    }

    /**
     * 保存数据
     * @param $parseRes
     */
    private function handleData($parseRes)
    {
        try {
            $user_id = $parseRes['user_id'] ?? 0;
            $qrcode = $parseRes['qrcode'] ?? 0;
            $promotecode = $parseRes['promotecode'] ?? 0;
            $qrcodeInfo = InviteQrcodesModel::getInstance()->getModel()->where("id", "=", $qrcode)->field('tag_ids,expire_time')->find();
            $anchor_id = 0;
            if (isset($qrcodeInfo['expire_time']) && $qrcodeInfo['expire_time'] > time()) { //激活码在有效期内 获取对应的主播id
                $anchor_id = $this->qrcodeFindAnchor($qrcodeInfo['tag_ids']);
            } else {
                $qrcode = 0;
            }
            $promoteid = $this->getPromoteId($promotecode) ?: 0;
            $data = ["uid" => $user_id, "qrcode" => $qrcode, "anchor_id" => $anchor_id, "promote_id" => $promoteid, "create_time" => time()];
            if ($anchor_id > 0 && $user_id > 0) {
                $sendusermessage = $this->sendUserMessage($anchor_id, $user_id); //代替用户发送私聊消息
                if ($sendusermessage['code'] == 200) {
                    $data['send_msg_first_time'] = time(); //系统发送消息的时间
                }
            }
            ParseUserStateByUniqkey::getInstance()->insertOrUpdateModel(InviteQrcodeMemberRelationModel::getInstance()->getModel(),[$data],["id","uid"]);
            if ($anchor_id > 0) {
                $this->sendUserComeRoomMark($user_id, $anchor_id);
            }
        } catch (\Throwable $e) {
            Log::info(SELF::COMMAND_NAME . ":handledata:" . $e->getMessage());
            throw  $e;
        }

    }

    /**
     * 根据promotecode 来获取对应的id
     */
    private function getPromoteId($promotecode)
    {
        return MarketChannelModel::getInstance()->getModel()->where("invitcode", "=", $promotecode)->value('id');
    }

    /**
     * 根据标签code来获取对应的陪陪或者主播id
     */
    private function qrcodeFindAnchor($tagid)
    {
        //派单规则优先级
        //先找到对应的标签 主播
        //判断主播是否在线
        //对应标签中 优先选择在线的
        //如果没有标签选中 则给与在线的最高标签
        //从主播里面匹配核实的主播
        if (empty($tagid)) return 0;
        $tag_ids_list = explode(",", $tagid);
        //查找主播对应的标签值
        $anchorList = InviteGuildAnchorsModel::getInstance()->getModel()->field('uid,point,tag_ids')
            ->where("status", ">=", 1)->select()->toarray();

        //获取主播在线房间数据
        $redis = RedisCommon::getInstance()->getRedis();
        $user_current_room = $redis->hGetAll("user_current_room");

        foreach ($anchorList as $index => $item) {
            $anchorList[$index]['match_number'] = count(array_intersect($tag_ids_list, explode(",", $item['tag_ids'])));
            $anchorList[$index]['online'] = $user_current_room[$item['uid']] ?? 0; //返回当前在线的房间ID
        }
        //按照匹配的标签匹配度|积分 排序
        array_multisort(array_column($anchorList, "match_number"), SORT_DESC, array_column($anchorList, 'point'), SORT_DESC, $anchorList);

        //在线用户
        $online_match_user = 0;
        //非在线用户
        $notonline_match_user = 0;

        foreach ($anchorList as $match_item) {
            if ($online_match_user == 0 && $match_item['online'] > 0) {
                $online_match_user = $match_item['uid'];
            }

            if ($notonline_match_user == 0 && $match_item['online'] == 0) {
                $notonline_match_user = $match_item['uid'];
            }
        }

        $match_user = $online_match_user ?: $notonline_match_user;
        //如果标签匹配不到就取积分最高的用户
        return $match_user;  //返回适合用户的主播用户

    }

    /*
     * 系统代替用户发送im消息
     * */
    private function sendUserMessage($from, $to)
    {
        $msg = ["msg" => SELF::MESSAGETIP];
        $message = YunxinModel::getInstance()->sendMsg($from, 0, $to, 0, $msg);
        Log::info(SELF::COMMAND_NAME . ":sendusermessage:" . json_encode($message));
        return $message;
    }

    /**
     * 记录用户进房间的标记 比如:神秘星球
     */
    private function sendUserComeRoomMark($uid, $anchor_id, $qrcodeinfo = [])
    {
        //获取主播在线房间数据
        $cachekey = "qrcodepromoteuser";
        $redis = RedisCommon::getInstance()->getRedis();
        $comeroomtipExpire = SELF::COMEROOMTIPEXPIRE;
        $expire_time = strtotime("+{$comeroomtipExpire}days");
        //获取主播的工会房间
        $guiId = MemberGuildModel::getInstance()->getModel()->where("user_id", "=", $anchor_id)->where("status", "=", 1)->value("id");
        if ($guiId == 0) {
            $guiId = MemberSocityModel::getInstance()->getModel()->where("user_id", "=", $anchor_id)
                ->where("status", "=", 1)
                ->value("guild_id");
        }
        if ($guiId > 0) {
            $roominfo = LanguageroomModel::getInstance()->getWhereAllData(["guild_id"=>$guiId],"id");
            //$roominfo = LanguageroomModel::getInstance()->getwhere("guild_id", "=", $guiId)->field('id')->select()->toArray();
            $roomids = array_column($roominfo, "id");
            if ($roominfo) {
                $redis->hset($cachekey, $uid, json_encode(["expiretime" => $expire_time, 'tip' => SELF::COMEROOMTIP, 'room_id' => $roomids]));
            }
        }

    }
}

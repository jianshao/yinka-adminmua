<?php

namespace app\admin\script\analysis;

use app\common\ParseUserStateDataCommmon;

class RoomSendRecevieGift
{
    public static $instance = NULL;

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    protected $sendgift_map = [
        'SENDGIFT_其他' => 0,
        'SENDGIFT_直送' => 1,
        'SENDGIFT_背包送礼' => 2,
        'SENDGIFT_礼物盒子' => 3,
        'SENDGIFT_小火锅' => 4,
    ];


    public function getType($gift_id, $gift_Type = 'pannel')
    {
        $send_type = $this->sendgift_map['SENDGIFT_其他'];
        if ($gift_id == '395') {
            //小火锅
            $send_type = $this->sendgift_map['SENDGIFT_小火锅'];
        } elseif ($gift_id == '376') {
            //礼物盒子
            $send_type = $this->sendgift_map['SENDGIFT_礼物盒子'];
        } else {
            if ($gift_Type == 'pannel') {
                //直送
                $send_type = $this->sendgift_map['SENDGIFT_直送'];
            } elseif ($gift_Type == 'bag') {
                //背包送礼
                $send_type = $this->sendgift_map['SENDGIFT_背包送礼'];
            }
        }
        return $send_type;
    }


    // $result = "大json" 这是一天合并的数据
    public function parseGift($result)
    {
        $returnData = [];
        foreach ($result as $key => $item) {
            $params = ParseUserStateDataCommmon::getInstance()->identifySplit($key);
            $uid = $params[1] ?? 0; //用户的id
            $date = $params[0] ?? ''; //日期
            if (empty($uid) || empty($date)) {
                continue;
            }

            $sendGiftList     =    $item['sendGift'] ?? [];
            $receiveGiftList  =    $item['receiveGift'] ?? [];

            if($sendGiftList){
                $this->handleSendGift($sendGiftList, $uid, $date, $returnData);
            }

            if($receiveGiftList){
                $this->handleReceiveGift($receiveGiftList, $uid, $date, $returnData);
            }
        }

        return $returnData;
    }


    public function handleSendGift($roomGiftList, $uid, $date, &$returnData)
    {
        $type = 1;
        foreach ($roomGiftList as $roomid => $bag_panel) {
            foreach ($bag_panel as $gift_type => $giftList) {
                foreach ($giftList as $gift_id => $detail) {
                    $send_type = $this->getType($gift_id, $gift_type);
                    $returnData[] =
                        [
                            "count" => $detail['count'],
                            "uid" => $uid,
                            "type" => $type,
                            "send_type" => $send_type,
                            "room_id" => $roomid,
                            "gift_id" => $gift_id,
                            "date" => $date,
                            "consume_amount" => $detail["amount"],
                            "reward_amount" => 0,
                        ];
                }
            }
        }
    }


    public function handleReceiveGift($roomGiftList, $uid, $date, &$returnData)
    {
        $type = 2;
        foreach ($roomGiftList as $roomid => $bag_panel) {
            foreach ($bag_panel as $gift_type => $giftList) {
                foreach ($giftList as $gift_id => $detail) {
                    if(!isset($detail['real'])){
                        continue;
                    }
                    foreach($detail['real'] as $real_id=>$real_item){
                        $send_type = $this->getType($real_id, $gift_type);
                        $returnData[] =
                            [
                                "count" => $real_item['count'],
                                "uid" => $uid,
                                "type" => $type,
                                "send_type" => $send_type,
                                "room_id" => $roomid,
                                "gift_id" => $real_id,
                                "date" => $date,
                                "consume_amount" => 0,
                                "reward_amount" => $real_item["amount"],
                            ];

                    }

                }
            }
        }
    }









}






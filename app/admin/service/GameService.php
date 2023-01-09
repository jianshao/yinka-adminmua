<?php

namespace app\admin\service;

use app\admin\model\LanguageroomModel;
use app\admin\model\MemberModel;
use app\admin\model\UserAssetLogModel;

class GameService
{
    public static $instance = null;
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GameService();
        }
        return self::$instance;
    }
    public $gift_id = 395;
    public $giftname = [395 => '火锅', 399 => '铁矿石', 400 => '银矿石', 401 => '金矿石', 402 => '化石'];

    public function differenceArray(array $array1, array $array2)
    {
        if (empty($array1) || empty($array2)) {
            return false;
        } else {
            $result = [];
            $price = 0;
            foreach ($array1 as $key => $val) {
                if ($val['exchenge'] == $array2[$key]['exchenge']) {
                    $price = $val['price'] - $array2[$key]['exchenge'];
                }
                $result[$key] = $val;
                $result[$key]['_price'] = $price;
            }
        }
    }

    public function RoomGame($pageNew, $page, $master_page, $demo, $roomid, $uid, $touid)
    {
        if ($demo) {
            $arr = explode(" - ", $demo);
            $where[] = ['created_time', '>=', strtotime($arr[0] . " 00:00:00")];
            $where[] = ['created_time', '<=', strtotime($arr[1] . " 23:59:59")];
        } else {
            $where[] = ['success_time', '>=', strtotime(date('Y-m-d') . " 00:00:00")];
            $where[] = ['created_time', '<=', strtotime(date('Y-m-d') . " 23:59:59")];
        }
        if ($roomid) {
            $where[] = ['room_id', '=', $roomid];
        }
        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }
        if ($touid) {
            $where[] = ['touid', '=', $touid];
        }
        $where[] = ['event_id', '=', 10002];
        $where[] = ['type', '=', 7];
        $where[] = ['asset_id', '=', 'energy'];
        $count = UserAssetLogModel::getInstance()->getModel()->where($where)->count();
        $data = UserAssetLogModel::getInstance()->getModel()->where($where)->order('id desc')->field('uid,touid,room_id,')->limit($page, $pageNew)->select()->toArray();
        if ($count > 0) {
            foreach ($data as $k => $v) {
                $data[$k]['gift_name'] = '';
                $data[$k]['u_name'] = '';
                $data[$k]['r_name'] = '';
                $data[$k]['tou_name'] = '';
                $data[$k]['PhysicalStrength'] = 0;
                if ($data[$k]['giftid'] == $this->gift_id) {
                    $data[$k]['PhysicalStrength'] = $data[$k]['giftcount'] * 50;
                }
                if ($data[$k]['giftid']) {
                    $data[$k]['gift_name'] = $this->giftname[$data[$k]['giftid']];
                }
                if ($data[$k]['uid']) {
                    $data[$k]['u_name'] = MemberModel::getInstance()->getModel($data[$k]['uid'])->where('id', $data[$k]['uid'])->value('nickname');
                }
                if ($data[$k]['touid']) {
                    $data[$k]['tou_name'] = MemberModel::getInstance()->getModel($data[$k]['uitouidd'])->where('id', $data[$k]['touid'])->value('nickname');
                }
                if ($data[$k]['room_id']) {
                    $data[$k]['r_name'] = LanguageroomModel::getInstance()->getModel($data[$k]['room_id'])->where('id', $data[$k]['room_id'])->value('nickname');
                }
            }
        }
        $coin = 0;
        $giftcount = 0;
        $PhysicalStrength = 0;
        $coin += UserAssetLogModel::getInstance()->getModel()->where($where)->sum('ext_4');
        $giftcount += UserAssetLogModel::getInstance()->getModel()->where($where)->sum('ext_3');
        $PhysicalStrength += UserAssetLogModel::getInstance()->getModel()->where($where)->sum('change_amount');

        $data['page_array']['page'] = $master_page;
        $data['page_array']['total_page'] = ceil($count / $pageNew);
        $data['data'] = $data;
        $data['coin'] = $coin;
        $data['giftcount'] = $giftcount;
        $data['PhysicalStrength'] = $PhysicalStrength;
    }

}

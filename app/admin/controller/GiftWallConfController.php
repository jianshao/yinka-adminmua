<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\CommonConst;
use app\admin\model\ConfigModel;
use app\admin\model\SiteconfigModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ConfigService;
use app\admin\service\RoomService;
use app\common\RedisCommon;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class GiftWallConfController extends AdminBaseController
{

    //礼物墙的配置列表
    public function giftWallConfList()
    {
        if ($this->request->param("isRequest") == 1) {
            $giftwallList = ConfigService::getInstance()->JsonEscape('gift_wall_conf');
            if (!is_array($giftwallList)) {
                $giftwallList = json_decode($giftwallList, true);
            }
            $giftByIdList = $this->matchImplGiftWall($giftwallList);
            array_multisort(array_column($giftByIdList, "price"), SORT_DESC, $giftByIdList);
            echo json_encode(["msg" => '', "count" => count($giftByIdList), "code" => 0, "data" => $giftByIdList]);
            exit;
        } else {
            View::assign('token', $this->request->param('token'));
            return View::fetch('gift/giftwallconf');
        }


    }

    //礼物墙新增礼物
    public function giftWallConfAdd()
    {
        try {
            $giftid = $this->request->param('giftid', 0, 'trim'); //道具ID
            $giftwallList = ConfigService::getInstance()->JsonEscape("gift_wall_conf");
            if (!is_array($giftwallList)) {
                $giftwallList = json_decode($giftwallList, true);
            }
            if (in_array($giftid, $giftwallList)) {
                echo json_encode(["code" => -1, "msg" => "礼物ID已存在礼物墙"]);
                exit;
            } else {
                $giftList = $this->matchImplGiftWall([$giftid]);
                if (empty($giftList)) {
                    echo json_encode(["code" => -1, "msg" => "礼物不存在"]);
                    exit;
                }
            }
            $redis = $this->getRedis(['select' => 3]);
            $giftwallList[] = $giftid;
            $matchGiftWall = $this->matchImplGiftWall($giftwallList);
            array_multisort(array_column($matchGiftWall, "price"), SORT_DESC, $matchGiftWall);
            $mark = $redis->set("gift_wall_conf", json_encode(array_column($matchGiftWall, "giftid")));
            if ($mark) {
                echo json_encode(["code" => 0, "msg" => "操作成功"]);
                exit;
            }

        } catch (\Throwable $e) {
            Log::info("giftwallconfcontroller:error" . $e->getMessage());
            echo json_encode(["code" => -1, "msg" => "操作异常"]);
            exit;
        }

    }


    //礼物墙配置删除
    public function giftWallConfDel()
    {
        $giftid = $this->request->param('giftid', 0, 'trim'); //道具ID
        $giftwallList = ConfigService::getInstance()->JsonEscape("gift_wall_conf");
        if (!is_array($giftwallList)) {
            $giftwallList = json_decode($giftwallList, true);
        }
        try {
            foreach ($giftwallList as $key => $item) {
                if ($item == $giftid) {
                    unset($giftwallList[$key]);
                }
            }
            $redis = $this->getRedis(['select' => 3]);
            $redis->set("gift_wall_conf", json_encode(array_values($giftwallList)));
            echo json_encode(["code" => 0, "msg" => "操作成功"]);
        } catch (\Throwable $e) {
            Log::error("giftwallconfdel:error" . $e->getMessage());
            echo json_encode(["code" => -1, "msg" => "操作异常"]);
        }
    }


    /**
     * 根据礼物墙来匹配对应的礼物列表
     * @param $giftwallList
     * @return mixed
     */
    private function matchImplGiftWall($giftwallList = [])
    {
        $giftList = ConfigService::getInstance()->JsonEscape("gift_conf");
        return array_reduce($giftList, function ($res, $item) use ($giftwallList) {
            $giftId = $item['giftId'];
            if ($giftwallList) {
                if (in_array($giftId, $giftwallList)) {
                    $res[$giftId] = [
                        "giftid" => $item['giftId'],
                        "name" => $item['name'],
                        "price" => $item['price']['count'] ?? 0
                    ];
                }
            } else {
                $res[$giftId] = [
                    "giftid" => $item['giftId'],
                    "name" => $item['name'],
                    "price" => $item['price']['count'] ?? 0
                ];
            }

            return $res;
        }, []);
    }

}

<?php

//飞行棋
namespace app\admin\controller;

ini_set('memory_limit', '1024M');
use app\admin\common\AdminBaseController;
use app\admin\model\ConfigModel;
use app\admin\model\GameLogModel;
use app\admin\model\GiftGameModel;
use app\admin\model\GiftModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MemberModel;
use app\admin\model\UserAssetLogModel;
use app\admin\model\UserExtendModel;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class GameController extends AdminBaseController
{
    public $gift_id = 395;
    public $giftname = [399 => '铁矿石', 400 => '银矿石', 401 => '金矿石', 402 => '化石'];
    /**
     * 游戏开关列表
     */
    public function GiftGame()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $id = $this->request->param('id');
        $json = ConfigModel::getInstance()->getModel()->where('name', 'taojin_conf')->value('json');

        $where = [];
        if ($id) {
            $data = json_decode($json, true)['games'][$id - 1];
        } else {
            $data = json_decode($json, true)['games'];
        }
        foreach ($data as $k => $v) {
            $src[$k]['id'] = $v['gameId'];
            $src[$k]['game_name'] = $v['name'];
            $src[$k]['game_status'] = $v['status'];
        }
        $count = count($src);
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $src);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('id', $id);
        return View::fetch('game/GiftGame');
    }

    public function GiftGameStatus()
    {
        $id = $this->request->param('gid');
        $status = $this->request->param('status');
        $json = ConfigModel::getInstance()->getModel()->where('name', 'taojin_conf')->value('json');
        $taojin = json_decode($json, true);
        $array = $taojin['games'];
        foreach ($array as $k => $v) {
            if ($v['gameId'] == $id) {
                if ($status == 1) {
                    $array[$k]['status'] = 0;
                } else {
                    $array[$k]['status'] = 1;
                }
            }
        }
        $taojin['games'] = $array;
        $is = ConfigModel::getInstance()->getModel()->where('name', 'taojin_conf')->save(['json' => json_encode($taojin)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    /**
     * @return mixed
     * 用戶體力
     */
    public function UserPhysicalStrength()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = $this->request->param('uid');
        $type = empty($this->request->param('type')) ? 1 : $this->request->param('type');
        $where = [];
        if ($type == 1) {
            if ($uid) {
                $where[] = ['id', '=', $uid];
            }
            $where[] = ['energy', '>', 0];
            $count = MemberModel::getInstance()->getModel($uid)->where($where)->count();
            $data = MemberModel::getInstance()->getModel($uid)->where($where)->field('id,nickname,energy')->order('energy desc')->limit($page, $pagenum)->select()->toArray();
        } else {
            if ($type == 2) {
                if ($uid) {
                    $where[] = ['uid', '=', $uid];
                }
                $where[] = ['fossil_ore', '>', 0];
                $count = UserExtendModel::getInstance()->getModel()->where($where)->count();
                $data = UserExtendModel::getInstance()->getModel()->where($where)->field('uid id,fossil_ore energy')->order('fossil_ore desc')->limit($page, $pagenum)->select()->toArray();
            } elseif ($type == 3) {
                if ($uid) {
                    $where[] = ['uid', '=', $uid];
                }
                $where[] = ['gold_ore', '>', 0];
                $count = UserExtendModel::getInstance()->getModel()->where($where)->count();
                $data = UserExtendModel::getInstance()->getModel()->where($where)->field('uid id,gold_ore energy')->order('gold_ore desc')->limit($page, $pagenum)->select()->toArray();
            } elseif ($type == 4) {
                if ($uid) {
                    $where[] = ['uid', '=', $uid];
                }
                $where[] = ['silver_ore', '>', 0];
                $count = UserExtendModel::getInstance()->getModel()->where($where)->count();
                $data = UserExtendModel::getInstance()->getModel()->where($where)->field('uid id,silver_ore energy')->order('silver_ore desc')->limit($page, $pagenum)->select()->toArray();
            } elseif ($type == 5) {
                if ($uid) {
                    $where[] = ['uid', '=', $uid];
                }
                $where[] = ['iron_ore', '>', 0];
                $count = UserExtendModel::getInstance()->getModel()->where($where)->count();
                $data = UserExtendModel::getInstance()->getModel()->where($where)->field('uid id,iron_ore energy')->order('iron_ore desc')->limit($page, $pagenum)->select()->toArray();
            }

            foreach ($data as $k => $v) {
                $data[$k]['nickname'] = MemberModel::getInstance()->getModel($v['id'])->where('id', $v['id'])->value('nickname');
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('uid', $uid);
        View::assign('type', $type);
        return View::fetch('game/UserPhysicalStrength');
    }

    /**
     * 用戶體力
     */
    public function addUserPhysicalStrength()
    {
        $energy = $this->request->param('energy');
        $uid = $this->request->param('uid');
        $is = MemberModel::getInstance()->getModel($uid)->where('id', $uid)->save(['energy' => $energy]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);
        }
    }

    /**
     * 飞行棋房间赠礼
     */
    public function GameImage()
    {
        $id = Request::param('id');
        $type = Request::param('type');
        $where['id'] = $id;
        $savePath = '/game';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $gift_image = request()->file('gift_image');
//        $animation = request()->file('animation');
        //        $gift_animation = request()->file('gift_animation');
        if ($gift_image != "") {
            $gift_imageSavename = \think\facade\Filesystem::putFile($savePath, $gift_image);
            $gift_imageObject = str_replace("\\", "/", $gift_imageSavename);
            $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $gift_imageSavename);
        }
//        if($animation != ""){
        //            $animationSavename = \think\facade\Filesystem::putFile($savePath, $animation);
        //            $animationObject = str_replace("\\", "/", $animationSavename);
        //            $animationFile = STORAGE_PATH.str_replace("\\", "/", $animationSavename);
        //        }
        //        if($gift_animation != ""){
        //            $gift_animationSavename = \think\facade\Filesystem::putFile($savePath, $gift_animation);
        //            $gift_animationObject = str_replace("\\", "/",  $gift_animationSavename);
        //            $gift_animationFile = STORAGE_PATH.str_replace("\\", "/", $gift_animationSavename);
        //        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($gift_image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ["$type" => '/' . $gift_imageObject];
                $res = LogindetailModel::getInstance()->getModel()->where($where)->save($data);
            }
//            if($animation != ""){
            //                $animationResult = $ossClient->uploadFile($bucket, $animationObject, $animationFile);//上传成功
            //                $data = ['animation' => '/' . $animationObject];
            //                $res = GiftModel::getInstance()->setGift($where, $data);
            //            }
            //            if($gift_animation != ""){
            //                $gift_animationResult = $ossClient->uploadFile($bucket, $gift_animationObject, $gift_animationFile);//上传成功
            //                $data = ['gift_animation' => '/' . $gift_animationObject];
            //                $res = GiftModel::getInstance()->setGift($where, $data);
            //            }
            Log::record('添加飞行棋/' . $type . '图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加飞行棋/' . $type . '图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    /**
     * @return mixed
     * 房价送特殊礼物获得的体力
     */
    public function RoomGame()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $roomid = $this->request->param('roomid');
        $uid = $this->request->param('uid');
        $touid = $this->request->param('touid');
        $where[] = ['created_time', '>=', $start];
        $where[] = ['created_time', '<', $end];

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
        $data = UserAssetLogModel::getInstance()->getModel()->where($where)->field('room_id,uid,ext_1 giftid,touid,ext_3,ext_4 coin,change_amount PhysicalStrength,created_time')->order('id desc')->limit($page, $pagenum)->select()->toArray();
        if ($count > 0) {
            foreach ($data as $k => $v) {
                $data[$k]['gift_name'] = '';
                $data[$k]['addtime'] = date('Y-m-d H:i', $v['created_time']);
                $data[$k]['u_name'] = '';
                $data[$k]['r_name'] = '';
                $data[$k]['tou_name'] = '';
                if ($data[$k]['giftid']) {
                    $data[$k]['gift_name'] = $data[$k]['giftid'];
                }
                if ($data[$k]['uid']) {
                    $data[$k]['u_name'] = MemberModel::getInstance()->getModel($data[$k]['uid'])->where('id', $data[$k]['uid'])->value('nickname');
                }
                if ($data[$k]['touid']) {
                    $data[$k]['tou_name'] = MemberModel::getInstance()->getModel($data[$k]['touid'])->where('id', $data[$k]['touid'])->value('nickname');
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
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('roomid', $roomid);
        View::assign('uid', $uid);
        View::assign('touid', $touid);
        View::assign('coin', $coin);
        View::assign('giftcount', $giftcount);
        View::assign('PhysicalStrength', $PhysicalStrength);
        return View::fetch('game/RoomGame');
    }

    /**
     * @return mixed
     * 飞行棋奖励记录
     */
    public function gameLog()
    {
        $count = 0;
        $pagenum = 5;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $gift = GiftModel::getInstance()->getModel()->whereIn('gift_name', '金矿石,银矿石,铁矿石,化石')->column('id,gift_name');
        $gameArray = GiftGameModel::getInstance()->getModel()->column('id,game_name');
        if (!$gameArray) {
            $gameArray = [];
        }
        $where = [];
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $game_id = $this->request->param('game_id');
        if ($game_id) {
            $where[] = ['game_id', '=', $game_id];
        }
        $gift_id = $this->request->param('gift_id');
        if ($gift_id != 0) {
            $where[] = ['gift_id', '=', $gift_id];
        }
        $type = $this->request->param('type');
        if ($type != 0) {
            $where[] = ['type', '=', $type];
        }

        $where[] = ['create_time', '>=', strtotime($start)];
        $where[] = ['create_time', '<', strtotime($end)];

        $uid = $this->request->param('uid');
        $energy = 0;
        if ($uid) {
            $where[] = ['uid', '=', $uid];
            $energy = MemberModel::getInstance()->getModel($uid)->where('id', $uid)->value('energy');
        } else {
            $energy = MemberModel::getInstance()->getModel($uid)->value('sum(energy)');
        }

        $count = GameLogModel::getInstance()->getModel()->where($where)->count();
        $list = GameLogModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select();
        if ($list) {
            $data = $list->toArray();
        } else {
            $data = [];
        }
        foreach ($data as $k => $v) {
            $data[$k]['typeD'] = '';
            $data[$k]['gift_name'] = '';
            $data[$k]['game_name'] = '';
            if ($data[$k]['type']) {
                if ($data[$k]['type'] == 1) {
                    $data[$k]['typeD'] = '化石';
                } elseif ($data[$k]['type'] == 2) {
                    $data[$k]['typeD'] = '金';
                } elseif ($data[$k]['type'] == 3) {
                    $data[$k]['typeD'] = '银';
                } elseif ($data[$k]['type'] == 4) {
                    $data[$k]['typeD'] = '铁';
                } elseif ($data[$k]['type'] == 5) {
                    $data[$k]['typeD'] = '豆';
                }
            }
            if ($data[$k]['gift_id']) {
                $data[$k]['gift_name'] = GiftModel::getInstance()->getModel()->where('id', $data[$k]['gift_id'])->value('gift_name');
            }
            if ($data[$k]['game_id']) {
                $data[$k]['game_name'] = LogindetailModel::getInstance()->getModel()->where('id', $data[$k]['game_id'])->value('game_name');
            }
            $data[$k]['game_force'] = LogindetailModel::getInstance()->getModel()->where('id', $v['game_id'])->value('game_energy');
        }
        $forceVal = GiftGameModel::getInstance()->getModel()->field('id,game_energy')->select()->toArray();
        $force = 0;
        foreach ($forceVal as $k => $v) {
            $forceWhere = [];
            $forceWhere[] = ['game_id', '=', $forceVal[$k]['id']];
            $forceWhere = array_merge($forceWhere, $where);
            $countForce = GameLogModel::getInstance()->getModel()->where($forceWhere)->field('game_id')->count();
            $force += $countForce * $forceVal[$k]['game_energy'];
        }
        $jin = 0;
        $yin = 0;
        $tie = 0;
        $hua = 0;
        $wWhere = [];
        $wWhere[] = ['type', '=', 5];
        $wWhere = array_merge($wWhere, $where);
        $M = GameLogModel::getInstance()->getModel()->where($wWhere)->field('sum(gift_num) M')->select()->toArray();
        if (count($M) <= 0) {
            $M = 0;
        } else {
            $M = $M[0]['M'];
        }
        $wWhere1 = [];
        $wWhere1[] = ['type', '=', 4];
        $wWhere1 = array_merge($wWhere1, $where);
        $tie += GameLogModel::getInstance()->getModel()->where($wWhere1)->value('sum(gift_num)');
        $wWhere2 = [];
        $wWhere2[] = ['type', '=', 3];
        $wWhere2 = array_merge($wWhere2, $where);
        $yin += GameLogModel::getInstance()->getModel()->where($wWhere2)->value('sum(gift_num)');
        $wWhere3 = [];
        $wWhere3[] = ['type', '=', 2];
        $wWhere3 = array_merge($wWhere3, $where);
        $jin += GameLogModel::getInstance()->getModel()->where($wWhere3)->value('sum(gift_num)');
        $wWhere4 = [];
        $wWhere4[] = ['type', '=', 1];
        $wWhere4 = array_merge($wWhere4, $where);
        $hua += GameLogModel::getInstance()->getModel()->where($wWhere4)->value('sum(gift_num)');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('M', $M);
        View::assign('tie', $tie);
        View::assign('yin', $yin);
        View::assign('jin', $jin);
        View::assign('hua', $hua);
        View::assign('uid', $uid);
        View::assign('gift', $gift);
        View::assign('type', $type);
        View::assign('force', $force);
        View::assign('energy', $energy);
        View::assign('count', $count);
        View::assign('gameArray', $gameArray);
        View::assign('game_id', $game_id);
        View::assign('gift_id', $gift_id);
        return View::fetch('game/gameLog');
    }

    /**
     * @return mixed
     * 飞行棋兑换礼物
     */
    public function GameExchangeLog()
    {
        $count = 0;
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $type = Request::param('type');
        $goods = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'goods_conf')->value('json'), true);
        $where = [];
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $ore = ['fossil', 'gold', 'silver', 'iron', 'bean'];
        $game_id = empty($this->request->param('game_id')) ? 1 : $this->request->param('game_id');
        if ($type) {
            $where[9] = ['asset_id', '=', $ore[$type]];
        }
        $where = [
            ['event_id', '=', 10005],
            ['ext_1', '=', 'ore'],
            ['type', '=', 8],
        ];
        $where[] = ['success_time', '>=', strtotime($start)];
        $where[] = ['success_time', '<', strtotime($end)];
        $uid = $this->request->param('uid');
        if ($uid) {
            $where[] = ['uid', '=', $uid];
        }
        $count = UserAssetLogModel::getInstance()->getModel()->where($where)->count();
        $data = UserAssetLogModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();

        foreach ($data as $k => $v) {
            $data[$k]['typeD'] = '';
            $data[$k]['gift_name'] = '';
            $data[$k]['game_name'] = '';
            $data[$k]['success_time'] = date('Y-m-d H:i', $v['success_time']);
            if ($data[$k]['type']) {
                if ($data[$k]['asset_id'] == $ore[1]) {
                    $data[$k]['typeD'] = '化石';
                } elseif ($data[$k]['asset_id'] == $ore[2]) {
                    $data[$k]['typeD'] = '金矿石';
                } elseif ($data[$k]['asset_id'] == $ore[3]) {
                    $data[$k]['typeD'] = '银矿石';
                } elseif ($data[$k]['asset_id'] == $ore[4]) {
                    $data[$k]['typeD'] = '铁矿石';
                } elseif ($data[$k]['asset_id'] == $ore[5]) {
                    $data[$k]['typeD'] = '番茄豆';
                }
            }
            foreach ($goods as $kk => $vv) {
                if ($v['ext_2'] == $vv['goodsId']) {
                    $data[$k]['gift'] = $vv['name'];
                }
            }
        }
        $tie = 0;
        $where[9] = ['asset_id', '=', $ore[4]];
        $tie = UserAssetLogModel::getInstance()->getModel()->where($where)->value('sum(abs(change_amount))');
        $yin = 0;
        $where[9] = ['asset_id', '=', $ore[3]];
        $yin = UserAssetLogModel::getInstance()->getModel()->where($where)->value('sum(abs(change_amount))');
        $jin = 0;
        $where[9] = ['asset_id', '=', $ore[2]];
        $jin = UserAssetLogModel::getInstance()->getModel()->where($where)->value('sum(abs(change_amount))');
        $hua = 0;
        $where[9] = ['asset_id', '=', $ore[1]];
        $hua = UserAssetLogModel::getInstance()->getModel()->where($where)->value('sum(abs(change_amount))');

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('game_id', $game_id);
        View::assign('tie', $tie);
        View::assign('yin', $yin);
        View::assign('jin', $jin);
        View::assign('hua', $hua);
        View::assign('type', $type);
        return View::fetch('game/GameExchangeLog');
    }

}
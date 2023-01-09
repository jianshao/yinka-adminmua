<?php

namespace app\admin\controller;

use aliyuncs\src\OSS\Core\OssException;
use app\admin\common\AdminBaseController;
use app\admin\model\ConfigModel;
use app\admin\model\DukeModel;
use app\admin\model\GiftModel;
use app\admin\model\GiftStartModel;
use app\admin\model\GivePackModel;
use app\admin\model\MemberModel;
use app\admin\model\PackModel;
use app\admin\model\SiteconfigModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ConfigService;
use app\common\RedisCommon;
use app\common\UploadOssFileCommon;
use constant\CodeConstant;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class GiftController extends AdminBaseController
{
    /**
     * @return mixed
     * dongbozhao
     * 用户装备管理
     * 2020-11-28
     */
    public function userGift()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $user_id = Request::param('user_id');
        $giftid = Request::param('giftid'); //礼物ID
        $where = [];
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if ($giftid) {
            $where[] = ['gift_id', '=', $giftid];
        }
        $count = PackModel::getInstance()->getModel($user_id)->where($where)->group('gift_id')->count();
        $data = [];
        if ($count > 0) {
            $data = PackModel::getInstance()->getModel($user_id)->where($where)->limit($page, $pagenum)->group('gift_id')->select()->toArray();
            foreach ($data as $key => $val) {
                $data[$key]['gift_image'] = $url . $this->GetGift($val['gift_id'], 'image');
                $data[$key]['gift_name'] = $this->GetGift($val['gift_id'], 'name');
            }
        }
        $giftIDArray = array_column($data, 'gift_id');

        $giftList = $this->GetGift();

        foreach ($giftList as $k => $v) {
            if (in_array($v['id'], $giftIDArray)) {
                unset($giftList[$k]);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物列表获取成功:操作人:' . $this->token['username'], 'giftList');
        View::assign('page', $page_array);
        View::assign('giftList', $giftList);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('giftid', $giftid);
        View::assign('uid', $user_id);
        return View::fetch('gift/userGift');
    }

    //获取礼物配置
    public function GetGift($id = 0, $title = '')
    {
        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        if ($id == true) {
            foreach ($gift as $k => $v) {
                if ($id == $v['giftId']) {
                    return $v[$title];
                }
            }
        } else {
            foreach ($gift as $k => $v) {
                $rsc[$k]['id'] = $v['giftId'];
                $rsc[$k]['gift_name'] = $v['name'];
                $rsc[$k]['gift_name'] = $v['name'];
            }
            return $rsc;
        }

    }

    /**
     * dongbozhao
     * 用户装备数量编辑
     * 2020-11-28
     */
    public function SaveUserGift()
    {
        $giftid = Request::param('giftid');
        $uid = Request::param('uid');
        $giftnum = Request::param('giftnum');
        $adminId = isset($token['id']) ? $token['id'] : $this->token['id'];
        $assetId = 'gift:' . $giftid;
        echo $this->inner($uid, $assetId, $giftnum, $adminId);
    }

    /**
     * dongbozhao
     * 用户装备添加
     * 2020-11-28
     */
    public function addPackGift()
    {
        $giftId = (int) Request::param('gift');
        $giftnum = (int) Request::param('gift_num');
        $uid = (int) Request::param('uid');

        if (!GiftsCommon::getInstance()->checkGiftIdExists($giftId)) {
            echo json_encode(['code' => 500, 'msg' => '礼物ID不存在']);
            die;
        }
        $assetId = 'gift:' . $giftId;
        echo $this->inner($uid, $assetId, $giftnum, $this->token['id']);
    }

    // public function addAllGifts($uid, $giftnum, $adminId)
    // {
    //     $gift_map = GiftsCommon::getInstance()->getGifts();
    //     foreach ($gift_map as $gift_id => $_) {
    //         $assetId = 'gift:' . $gift_id;
    //         $this->inner($uid, $assetId, $giftnum, $adminId);
    //         sleep(3);
    //     }
    // }

    /**
     * @return mixed
     * dongbozhao
     * 用户装备日志  废弃
     */
    public function GivePackList()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $user_id = Request::param('user_id');
        $giftid = Request::param('giftid'); //礼物ID
        $where = [];
        if ($user_id) {
            $where[] = ['uid', '=', $user_id];
        }
        if ($giftid) {
            $where[] = ['gift_id', '=', $giftid];
        }
        $count = GivePackModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = GivePackModel::getInstance()->getModel()->where($where)->order('id desc')->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $key => $val) {
                $data[$key]['gift_image'] = $url . GiftModel::getInstance()->getModel()->where('id', $val['gift_id'])->value('gift_image');
                $data[$key]['gift_name'] = GiftModel::getInstance()->getModel()->where('id', $val['gift_id'])->value('gift_name');
                $data[$key]['avatar'] = $url . MemberModel::getInstance()->getModel($val['uid'])->where('id', $val['uid'])->value('avatar');
                $data[$key]['created_time'] = date('Y-m-d H:i:s', $val['created_time']);
            }
        }
        $giftList = GiftModel::getInstance()->getModel()->where('status', 1)->column('id,gift_name');
        $giftList2 = GiftModel::getInstance()->getModel()->where('status', 2)->column('id,gift_name');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物列表获取成功:操作人:' . $this->token['username'], 'giftList');
        View::assign('page', $page_array);
        View::assign('giftList', $giftList);
        View::assign('giftList2', $giftList2);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('giftid', $giftid);
        View::assign('uid', $user_id);
        return View::fetch('gift/GivePackList');
    }

    /**
     * 清除缓存redis
     * 礼物盒子列表接口
     */
    public function clearCacheGiftBox()
    {
        $type = Request::param('type');
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $json = ConfigModel::getInstance()->getModel()->where('name', $type)->value('json');
        $is = $redis->set($type, $json);
        ConfigService::getInstance()->register();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        }
    }

    //幸运盒子-删除礼物
    public function delGiftBox()
    {
        $Gid = Request::param('giftid');

        $giftid = 376; //礼物盒子id
        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftid) {
                $boxList = $v['box'];
            }
        }

        foreach ($boxList as $k => $v) {
            if ($v['giftId'] == $Gid) {
                unset($boxList[$k]);
            }
        }

        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftid) {
                $gift[$k]['box'] = $boxList;
            }
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($gift)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //幸运盒子-添加礼物
    public function addGiftBox()
    {
        $Gid = (int) Request::param('giftid');
        $weight = (int) Request::param('weight');

        $giftid = 376; //礼物盒子id
        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftid) {
                $boxList = $v['box'];
            }
        }

        array_push($boxList, ['giftId' => $Gid, 'weight' => $weight]);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftid) {
                $gift[$k]['box'] = $boxList;
            }
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($gift)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //礼物盒子配置
    public function giftBox()
    {
        $giftid = 376; //礼物盒子id
        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftid) {
                $boxList = $v['box'];
            }
        }

        $list = array_column($gift, 'giftId', 'name');
        foreach ($list as $k => $v) {
            $giftList[] = [
                'id' => $v,
                'gift_name' => $k,
            ];
        }

        $data = [];
        foreach ($boxList as $k => $v) {
            foreach ($gift as $kk => $vv) {
                if ($vv['giftId'] == $v['giftId']) {
                    $data[$k]['giftname'] = $vv['name'];
                }
            }
            $data[$k]['giftid'] = $v['giftId'];
            $data[$k]['weight'] = $v['weight'];
        }
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('data', $data);
        View::assign('gift', $giftList);
        return View::fetch('gift/giftBox');
    }

    //随机礼物配置
    public function randomGift()
    {
        $giftId = (int) $this->request->param('giftId');
        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftId) {
                $boxList = isset($v['box']) ? $v['box'] : [];
            }
        }

        $list = array_column($gift, 'giftId', 'name');
        foreach ($list as $k => $v) {
            $giftList[] = [
                'id' => $v,
                'gift_name' => $k,
            ];
        }

        $data = [];
        foreach ($boxList as $k => $v) {
            foreach ($gift as $kk => $vv) {
                if ($vv['giftId'] == $v['giftId']) {
                    $data[$k]['giftname'] = $vv['name'];
                }
            }
            $data[$k]['giftid'] = $v['giftId'];
            $data[$k]['weight'] = $v['weight'];
        }

        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('giftId', $giftId);
        View::assign('data', $data);
        View::assign('gift', $giftList);
        return View::fetch('gift/giftRandom');
    }

    //福袋礼物配置
    public function luckBagGift()
    {
        $giftId = (int) $this->request->param('giftId');
        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);

        $giftInfo = [];
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftId) {
                $giftInfo[] = $v;
            }
        }

        $info = [];
        foreach ($giftInfo as $k => $v) {
            $info[] = [
                'giftId' => $v['giftId'],
                'name' => $v['name'],
                'assetId' => 'user:bean',
                'randValues0' => isset($v['gainContents']) ? $v['gainContents'][0]['randValues'][0] : 0,
                'randValues1' => isset($v['gainContents']) ? $v['gainContents'][0]['randValues'][1] : 0,
            ];
        }

        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('giftId', $giftId);
        View::assign('data', $info);
        return View::fetch('gift/luckgift');
    }

    //福袋-添加礼物
    public function luckBagGiftSave()
    {
        $giftId = (int) Request::param('giftId');
        $randValues0 = (int) Request::param('randValues0');
        $randValues1 = (int) Request::param('randValues1');

        if ($randValues0 < 0 || $randValues1 < 0) {
            return rjson([], CodeConstant::CODE_参数错误, CodeConstant::CODE_PARAMETER_ERR_MAP[CodeConstant::CODE_参数错误]);
        }

        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => &$item) {
            if ($item['giftId'] == $giftId) {
                $item['type'] = 'luckyBag';
                $item['functions'] = ["open"];
                $item['gainContents'] = [['type' => 'SingleRandomContent', 'assetId' => 'user:bean', 'randValues' => [$randValues0, $randValues1]]];
            }
        }

        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($gift)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //随机礼物-删除礼物
    public function delGiftRandom()
    {
        $giftId = (int) Request::param('giftIdRandom');
        $Gid = Request::param('giftid');

        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftId) {
                $boxList = isset($v['box']) ? $v['box'] : [];
            }
        }

        foreach ($boxList as $k => $v) {
            if ($v['giftId'] == $Gid) {
                unset($boxList[$k]);
            }
        }

        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftId) {
                $gift[$k]['box'] = $boxList;
            }
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($gift)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //随机礼物-添加礼物
    public function addGiftRandom()
    {
        $giftId = (int) Request::param('giftIdRandom');
        $giftid = (int) Request::param('giftid');
        $weight = (int) Request::param('weight');

        $gift = json_decode(ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->value('json'), true);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftId) {
                $boxList = isset($v['box']) ? $v['box'] : [];
            }
        }

        array_push($boxList, ['giftId' => $giftid, 'weight' => $weight]);
        foreach ($gift as $k => $v) {
            if ($v['giftId'] == $giftId) {
                $gift[$k]['box'] = $boxList;
            }
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', 'gift_conf')->save(['json' => json_encode($gift)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    //查询砸蛋获得礼物
    public function getegggift()
    {
        $data = [];
        $rankconf = ['coin1' => '第一名', 'coin2' => '第二名', 'coin3' => '第三名'];
        $res = SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->findOrEmpty()->toArray();
        $eggjson = json_decode($res['eggjson'], true);
        $giftlist = [];
        $giftids = [];
        if (!empty($eggjson)) {
            foreach ($eggjson as $key => $value) {
                foreach ($value as $k => $v) {
                    $giftids[] = $v;
                }
            }
            $giftlist = GiftModel::getInstance()->getModel()->where([['id', 'in', $giftids]])->select();
            if (!empty($giftlist)) {
                $giftlist = $giftlist->toArray();
            }
            $tmp = [];
            foreach ($giftlist as $key => $value) {
                $tmp[$value['id']] = $value;
            }
            foreach ($eggjson as $key => $value) {
                foreach ($value as $k => $v) {
                    if ($v == $tmp[$v]['id']) {
                        @$data[$key][$k] = ['rank' => $rankconf[$k], 'giftid' => $v, 'giftname' => $tmp[$v]['gift_name']];
                    }
                }
            }
        }
        $jin = isset($data['jin']) ? $data['jin'] : [];
        $yin = isset($data['yin']) ? $data['yin'] : [];
        View::assign('resjin', $jin);
        View::assign('resyin', $yin);
        View::assign('token', $this->request->param('token'));
        return View::fetch('gift/giftdetail');
    }

    //配置砸蛋获得
    public function editegggift()
    {
        $coin1 = Request::param('coin1');
        $coin2 = Request::param('coin2');
        $coin3 = Request::param('coin3');
        $type = Request::param('type');
        if (empty($coin1) || empty($coin2) || empty($coin3)) {
            return rjsonadmin([], 500, '参数设置错误');
        }
        $giftlist = GiftModel::getInstance()->getModel()->where([['id', 'in', [$coin1, $coin2, $coin3]]])->select();
        if (!empty($giftlist)) {
            $giftlist = $giftlist->toArray();
            if (count($giftlist) < 3) {
                return rjsonadmin([], 500, '礼物不存在');
            }
        } else {
            return rjsonadmin([], 500, '礼物不存在');
        }
        $oldres = SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->findOrEmpty()->toArray();
        $olddata = json_decode($oldres['eggjson'], true);
        if (empty($olddata)) {
            $olddata = ['jin' => [], 'yin' => []];
        }
        if ($type == 1) { //金
            $olddata['jin'] = ['coin1' => $coin1, 'coin2' => $coin2, 'coin3' => $coin3];
            SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->save(['eggjson' => json_encode($olddata)]);
            return rjsonadmin([], 200, '设置成功');

        }
        if ($type == 2) { //银
            $olddata['yin'] = ['coin1' => $coin1, 'coin2' => $coin2, 'coin3' => $coin3];
            SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->save(['eggjson' => json_encode($olddata)]);
            return rjsonadmin([], 200, '设置成功');

        }
        return rjsonadmin([], 500, '参数设置错误');
    }

    /*
     *礼物列表
     */
    public function giftList()
    {

        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $type = Request::param('type'); //礼物类型
        $id = Request::param('gift_id'); //礼物ID
        $status = Request::param('status'); //礼物状态
        $isshow = Request::param('isshow');
        $isshow = empty($isshow) ? 3 : $isshow;
        $status = $status == 2 ? $status : 1;
        // if ($type && $id && $status) {
        //     $where = ['gift_type' => $type, 'id' => $id, 'status' => $status,'type'=>0];
        // } else if ($type && $status) {
        //     $where = ['gift_type' => $type, 'status' => $status,'type'=>0];
        // } else if ($type && $id) {
        //     $where = ['gift_type' => $type, 'id' => $id,'type'=>0];
        // } else if ($status && $id) {
        //     $where = ['status' => $status, 'id' => $id,'type'=>0];
        // } else if ($type) {
        //     $where = ['gift_type' => $type,'type'=>0];
        // } else if ($status) {
        //     $where = ['status' => $status,'type'=>0];
        // } else if ($id) {
        //     $where = ['id' => $id,'type'=>0];
        // } else {
        //     $where = ['type'=>0];
        // }
        $where = [];
        $where[] = ['type', '=', 0];
        if ($type) {
            $where[] = ['gift_type', '=', $type];
        }
        if ($status) {
            $where[] = ['status', '=', $status];
        }
        if ($id) {
            $where[] = ['id', '=', $id];
        }
        if ($isshow != 3) {
            if ($isshow == 2) {
                $where[] = ['is_show', '=', 0];
            } else {
                // $isshow = 1;
                $where[] = ['is_show', '=', 1];
            }
        }
        $duke = DukeModel::getInstance()->getModel()->column('duke_id,duke_name');
        $count = GiftModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = GiftModel::getInstance()->giftList($where, $page, $pagenum);
            foreach ($data as $key => $val) {
                $data[$key]['is_show'] = $val['is_show'];
                $data[$key]['gift_types'] = $val['gift_type'];
                $data[$key]['class_types'] = $val['class_type'];
                $data[$key]['broadcasts'] = $val['broadcast'];
                $data[$key]['statuss'] = $val['status'];
                $data[$key]['animationUrl'] = $val['animation'];
                $data[$key]['gift_animationUrl'] = $val['gift_animation'];
                $data[$key]['gift_imageUrl'] = $val['gift_image'];
                if ($data[$key]['animation'] != "") {
                    $data[$key]['animation'] = $url . $val['animation'];
                }
                if ($data[$key]['gift_animation'] != "") {
                    $data[$key]['gift_animation'] = $url . $val['gift_animation'];
                }
                if ($data[$key]['gift_image'] != "") {
                    $data[$key]['gift_image'] = $url . $val['gift_image'];
                }
                if ($val['is_show'] == 1) {
                    $data[$key]['is_shows'] = "是";
                } else {
                    $data[$key]['is_shows'] = "否";
                }
                if ($val['gift_type'] == 1) {
                    $data[$key]['gift_type'] = "普通礼物";
                } else if ($val['gift_type'] == 2) {
                    $data[$key]['gift_type'] = "动画礼物";
                } else if ($val['gift_type'] == 3) {
                    $data[$key]['gift_type'] = '免费礼物';
                } else if ($val['gift_type'] == 4) {
                    $data[$key]['gift_type'] = '终极礼物';
                } else if ($val['gift_type'] == 5) {
                    $data[$key]['gift_type'] = '极品礼物';
                }
                if ($val['class_type'] == 1) {$data[$key]['class_type'] = '礼物';} else { $data[$key]['class_type'] = '小礼物';}
                if ($val['broadcast'] == 1) {$data[$key]['broadcast'] = '广播';} else { $data[$key]['broadcast'] = '不广播';}
                if ($val['status'] == 1) {$data[$key]['status'] = '上架';} else { $data[$key]['status'] = '下架';}
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物列表获取成功:操作人:' . $this->token['username'], 'giftList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $id);
        View::assign('status', $status);
        View::assign('search_type', $type);
        View::assign('duke', $duke);
        View::assign('search_status', $status);
        View::assign('isshow', $isshow);
        return View::fetch('gift/index');
    }

    /*
     * 添加礼物
     */
    public function addGift()
    {
        $getprize = (empty(Request::param('prize_rate')) || Request::param('prize_rate') == '0') ? '' : Request::param('prize_rate');
        $data = [
            'gift_name' => Request::param('gift_name'),
            'gift_classification' => Request::param('gift_classification'),
            'gift_introduce' => Request::param('gift_introduce'),
            'gift_number' => Request::param('gift_number'),
            'gift_coin' => Request::param('gift_coin'),
            'is_active' => Request::param('is_active'),
            'gift_gold' => 0,
            'gift_type' => Request::param('gift_type'),
            'class_type' => (int) Request::param('class_type'),
            'broadcast' => Request::param('broadcast'),
            'is_giftganme' => Request::param('is_giftganme'),
            'status' => 1,
            'one_weight' => Request::param('one_weight'),
            'color_weight' => Request::param('color_weight'),
            'is_sort' => Request::param('is_sort'),
            'is_show' => Request::param('is_show'),
            'prize_rate' => $getprize,
            'type' => 0,
        ];
        if (Request::param('is_duke')) { //介绍
            $data['is_duke'] = Request::param('is_duke');
        }
        if (Request::param('gift_tags')) {
            $data['gift_tags'] = Request::param('gift_tags');
        }
        $res = GiftModel::getInstance()->addGifts($data);
        if ($res) {
            // $redis = $this->getRedis();
            // if($data['color_weight'] > 0){
            //     $poolKeyJin = 'gold_egg_pool';//金宝箱奖池key
            //     $poolKeyNumJin = 'gold_egg_pool_num';//金宝箱奖池总量
            //     $redis->DEL($poolKeyJin);
            //     $redis->DEL($poolKeyNumJin);
            // }
            // if($data['one_weight'] > 0){
            //     $poolKeyYin = 'silver_egg_pool';//银宝箱奖池key
            //     $poolKeyNumYin = 'silver_egg_pool_num';//银宝箱奖池总量
            //     $redis->DEL($poolKeyYin);
            //     $redis->DEL($poolKeyNumYin);
            // }
            Log::record('礼物添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('礼物添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGift');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /*
     * 修改礼物信息
     */
    public function exitGift()
    {
        $id = Request::param('gift_id'); //礼物ID
        $getprize = (empty(Request::param('prize_rate')) || Request::param('prize_rate') == '0') ? '' : Request::param('prize_rate');
        $where['id'] = $id;

        if (Request::param('saveImgUrl')) { //标签
            $data['gift_image'] = Request::param('gift_image');
            $data['animation'] = Request::param('animation');
            $data['gift_animation'] = Request::param('gift_animation');
            $data['gift_tags'] = Request::param('gift_tags');
        } else {
            $data = [
                'gift_name' => Request::param('gift_name'),
                'gift_number' => Request::param('gift_number'),
                'gift_coin' => Request::param('gift_coin'),
                'gift_type' => Request::param('gift_type'),
                'class_type' => (int) Request::param('class_type'),
                'is_giftganme' => Request::param('is_giftganme'),
                'is_active' => Request::param('is_active'),
                'broadcast' => Request::param('broadcast'),
                'status' => Request::param('status'),
                'one_weight' => Request::param('one_weight'),
                'color_weight' => Request::param('color_weight'),
                'is_sort' => Request::param('is_sort'),
                'is_show' => Request::param('is_show'),
                'prize_rate' => $getprize,
            ];
            if (Request::param('gift_classification')) { //介绍
                $data['gift_classification'] = Request::param('gift_classification');
            }
            $data['is_duke'] = Request::param('is_duke');
            if (Request::param('gift_introduce')) { //分类名
                $data['gift_introduce'] = Request::param('gift_introduce');
            }
            if (Request::param('gift_tags')) { //标签
                $data['gift_tags'] = Request::param('gift_tags');
            }
        }
        $res = GiftModel::getInstance()->setGift($where, $data);
        if ($res) {
            // $redis = $this->getRedis();
            // $poolKeyJin = 'gold_egg_pool';//金宝箱奖池key
            // $poolKeyNumJin = 'gold_egg_pool_num';//金宝箱奖池总量
            // $redis->DEL($poolKeyJin);
            // $redis->DEL($poolKeyNumJin);
            // $poolKeyYin = 'silver_egg_pool';//银宝箱奖池key
            // $poolKeyNumYin = 'silver_egg_pool_num';//银宝箱奖池总量
            // $redis->DEL($poolKeyYin);
            // $redis->DEL($poolKeyNumYin);
            Log::record('修改礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     * 阿里OSS
     */
    public function ossFile()
    {
        $gift_id = Request::param('id');
        $where['id'] = $gift_id;
        $savePath = '/gift';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $gift_image = request()->file('gift_image');
        $animation = request()->file('animation');
        $gift_animation = request()->file('gift_animation');
        if ($gift_image != "") {
            $gift_imageSavename = \think\facade\Filesystem::putFile($savePath, $gift_image);
            $gift_imageObject = str_replace("\\", "/", $gift_imageSavename);
            $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $gift_imageSavename);
        }
        if ($animation != "") {
            $animationSavename = \think\facade\Filesystem::putFile($savePath, $animation);
            $animationObject = str_replace("\\", "/", $animationSavename);
            $animationFile = STORAGE_PATH . str_replace("\\", "/", $animationSavename);
        }
        if ($gift_animation != "") {
            $gift_animationSavename = \think\facade\Filesystem::putFile($savePath, $gift_animation);
            $gift_animationObject = str_replace("\\", "/", $gift_animationSavename);
            $gift_animationFile = STORAGE_PATH . str_replace("\\", "/", $gift_animationSavename);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($gift_image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ['gift_image' => '/' . $gift_imageObject];
                $res = GiftModel::getInstance()->setGift($where, $data);
            }
            if ($animation != "") {
                $animationResult = $ossClient->uploadFile($bucket, $animationObject, $animationFile); //上传成功
                $data = ['animation' => '/' . $animationObject];
                $res = GiftModel::getInstance()->setGift($where, $data);
            }
            if ($gift_animation != "") {
                $gift_animationResult = $ossClient->uploadFile($bucket, $gift_animationObject, $gift_animationFile); //上传成功
                $data = ['gift_animation' => '/' . $gift_animationObject];
                $res = GiftModel::getInstance()->setGift($where, $data);
            }
            Log::record('添加礼物/装备图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加礼物/装备图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    /**
     * 清除缓存redis
     * 礼物列表接口
     */
    public function clearCache()
    {
        $key = Request::param('type');
        if (empty($key)) {
            echo json_encode(['code' => 500, 'msg' => '参数为空']);die;
        }
        echo $this->updateRedisConfig($key);die;
    }

    /*
     *周星魅力列表
     */
    public function giftstart()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $gift_id = Request::param('gift_id'); //礼物ID
        $where = [];
        if ($gift_id) {
            $where['gift_id'] = $gift_id;
        }
        $where[] = ['type', '=', 1];
        $count = GiftStartModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = GiftStartModel::getInstance()->getModel()->where($where)->order('id desc')->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $key => $val) {
                //'1:周星魅力 2:周星财富 2:月星魅力'
                if ($val['type'] == 1) {
                    $data[$key]['types'] = '魅力';
                } elseif ($val['type'] == 2) {
                    $data[$key]['types'] = '财富';
                } elseif ($val['type'] == 3) {
                    $data[$key]['types'] = '月星';
                }

                $data[$key]['statuss'] = $val['status'];
                if ($data[$key]['image'] != "") {
                    $data[$key]['image'] = $url . $val['image'];
                }
                if ($data[$key]['named_url'] != "") {
                    $data[$key]['named_url'] = $url . $val['named_url'];
                }
                if ($data[$key]['gift_url'] != "") {
                    $data[$key]['gift_url'] = $url . $val['gift_url'];
                }
                if ($data[$key]['gift_avatar'] != "") {
                    $data[$key]['gift_avatar'] = $url . $val['gift_avatar'];
                }
                $data[$key]['start_time'] = date('Y-m-d H:i:s', $val['start_time']);
                $data[$key]['end_time'] = date('Y-m-d H:i:s', $val['end_time']);
                if ($val['status'] == 1) {$data[$key]['status'] = '本周';} else { $data[$key]['status'] = '上周';}
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物周星列表获取成功:操作人:' . $this->token['username'], 'giftstart');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $gift_id);
        return View::fetch('gift/giftstart');
    }

    /*
     * 添加礼物周星
     */
    public function addGiftStart()
    {
        //检查当前gift_id
        $gift_result = GiftModel::getInstance()->getModel()->where(array("id" => Request::param('gift_id')))->find();
        if (empty($gift_result)) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        $data = [
            'gift_id' => Request::param('gift_id'),
            'name' => Request::param('name'),
            'avatar_name' => Request::param('avatar_name'),
            'gift_name' => Request::param('gift_name'),
            'type' => 1,
            'named_name' => Request::param('named_name'),
            'status' => Request::param('status'),
        ];
        $res = GiftStartModel::getInstance()->getModel()->save($data);
        if ($res) {
            Log::record('周星添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('周星添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /*
     * 修改礼物周星信息
     */
    public function exitGiftStart()
    {
        $id = Request::param('start_id');
        $gift_result = GiftModel::getInstance()->getModel()->where(array("id" => Request::param('gift_id')))->find();
        if (empty($gift_result)) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        $where['id'] = $id;
        $data = [
            'gift_id' => Request::param('gift_id'),
            'name' => Request::param('name'),
            'avatar_name' => Request::param('avatar_name'),
            'gift_name' => Request::param('gift_name'),
            'named_name' => Request::param('named_name'),
            'avatar_details' => Request::param('avatar_details'),
            'gift_details' => Request::param('gift_details'),
            'named_details' => Request::param('named_details'),
            'type' => 1,
//            'start_time'=>strtotime(Request::param('start_time')),
            //            'end_time'=>strtotime(Request::param('end_time'))
        ];
        $res = GiftStartModel::getInstance()->setGift($where, $data);
        if ($res) {
            Log::record('修改周星礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改周星礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     * 周星礼物阿里OSS
     */
    public function ossFileStart()
    {
        $start_id = Request::param('id');
        $where['id'] = $start_id;
        $file_dir = '/giftstart';
        $gift_avatar = request()->file('gift_avatar');
        $animation = request()->file('image');
        $gift_url = request()->file('gift_url');
        $named_url = request()->file('named_url');
        $rich_car_url = request()->file('rich_car_url');
        $rich_box_url = request()->file('rich_box_url');
        $gift_url = request()->file('gift_url');
        $UploadOssFileCommon = new UploadOssFileCommon();
        if ($gift_avatar) {
            $gift_avatar_url = $UploadOssFileCommon->ossFile($gift_avatar, $file_dir);
        } else {
            $gift_avatar_url = '';
        }
        if ($animation) {
            $animation_url = $UploadOssFileCommon->ossFile($animation, $file_dir);
        } else {
            $animation_url = '';
        }
        if ($gift_url) {
            $gift_url_url = $UploadOssFileCommon->ossFile($gift_url, $file_dir);
        } else {
            $gift_url_url = '';
        }
        if ($named_url) {
            $named_url_url = $UploadOssFileCommon->ossFile($named_url, $file_dir);
        } else {
            $named_url_url = '';
        }
        if ($rich_car_url) {
            $rich_car_url_url = $UploadOssFileCommon->ossFile($rich_car_url, $file_dir);
        } else {
            $rich_car_url_url = '';
        }
        if ($rich_box_url) {
            $rich_box_url_url = $UploadOssFileCommon->ossFile($rich_box_url, $file_dir);
        } else {
            $rich_box_url_url = '';
        }
        if ($gift_url) {
            $$gift_url_url = $UploadOssFileCommon->ossFile($gift_url, $file_dir);
        } else {
            $$gift_url_url = '';
        }
        try {
            if ($gift_avatar_url) {
                $result = parse_url($gift_avatar_url);
                $data = ['gift_avatar' => $result['path']];
                GiftStartModel::getInstance()->setGift($where, $data);
            }
            if ($animation_url) {
                $result = parse_url($animation_url);
                $data = ['image' => $result['path']];
                GiftStartModel::getInstance()->setGift($where, $data);
            }
            if ($gift_url_url) {
                $result = parse_url($gift_url_url);
                $data = ['gift_url' => $result['path']];
                GiftStartModel::getInstance()->setGift($where, $data);
            }
            if ($named_url_url) {
                $result = parse_url($named_url_url);
                $data = ['named_url' => $result['path']];
                GiftStartModel::getInstance()->setGift($where, $data);
            }
            if ($rich_car_url_url) {
                $result = parse_url($rich_car_url_url);
                $data = ['rich_car_url' => $result['path']];
                GiftStartModel::getInstance()->setGift($where, $data);
            }
            if ($rich_box_url_url) {
                $result = parse_url($rich_box_url_url);
                $data = ['rich_box_url' => $result['path']];
                GiftStartModel::getInstance()->setGift($where, $data);
            }
            if ($gift_url_url) {
                $result = parse_url($gift_url_url);
                $data = ['gift_url' => $result['path']];
                GiftStartModel::getInstance()->setGift($where, $data);
            }
            Log::record('添加周星礼物图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFileStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加周星礼物图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFileStart');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    /*
     *周星财富列表
     */
    public function giftWealth()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $gift_id = Request::param('gift_id'); //礼物ID
        $where = [];
        if ($gift_id) {
            $where['gift_id'] = $gift_id;
        }
        $where[] = ['type', '=', 2];
        $count = GiftStartModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = GiftStartModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $key => $val) {
                //'1:周星魅力 2:周星财富 2:月星魅力'
                if ($val['type'] == 1) {
                    $data[$key]['types'] = '魅力';
                } elseif ($val['type'] == 2) {
                    $data[$key]['types'] = '财富';
                } elseif ($val['type'] == 3) {
                    $data[$key]['types'] = '月星';
                }

                $data[$key]['statuss'] = $val['status'];
                if ($data[$key]['image'] != "") {
                    $data[$key]['image'] = $url . $val['image'];
                }
                if ($data[$key]['rich_car_url'] != "") {
                    $data[$key]['rich_car_url'] = $url . $val['rich_car_url'];
                }
                if ($data[$key]['gift_url'] != "") {
                    $data[$key]['gift_url'] = $url . $val['gift_url'];
                }
                if ($data[$key]['gift_avatar'] != "") {
                    $data[$key]['gift_avatar'] = $url . $val['gift_avatar'];
                }
                $data[$key]['start_time'] = date('Y-m-d H:i:s', $val['start_time']);
                $data[$key]['end_time'] = date('Y-m-d H:i:s', $val['end_time']);
                if ($val['status'] == 1) {$data[$key]['status'] = '本周';} else { $data[$key]['status'] = '上周';}
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物周星列表获取成功:操作人:' . $this->token['username'], 'giftstart');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $gift_id);
        return View::fetch('gift/giftWealth');
    }

    /*
     * 添加礼物周星财富
     */
    public function addGiftWealth()
    {
        //检查当前gift_id
        $gift_result = GiftModel::getInstance()->getModel()->where(array("id" => Request::param('gift_id')))->find();
        if (empty($gift_result)) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        $data = [
            'gift_id' => Request::param('gift_id'),
            'name' => Request::param('name'),
            'avatar_name' => Request::param('avatar_name'),
            'rich_car_name' => Request::param('rich_car_name'),
            'status' => Request::param('status'),
            'type' => 2,
        ];
        print_r($data);die;
        $res = GiftStartModel::getInstance()->getModel()->save($data);
        if ($res) {
            Log::record('周星添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('周星添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /*
     * 修改礼物周星财富信息
     */
    public function exitGiftWealth()
    {
        $id = Request::param('start_id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        $where['id'] = $id;
        $data = [
            'gift_id' => Request::param('gift_id'),
            'name' => Request::param('name'),
            'avatar_name' => Request::param('avatar_name'),
            'rich_car_name' => Request::param('rich_car_name'),
            'avatar_details' => Request::param('avatar_details'),
            'rich_car_details' => Request::param('rich_car_details'),
            'type' => 2,
        ];
        $res = GiftStartModel::getInstance()->setGift($where, $data);
        if ($res) {
            Log::record('修改周星礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改周星礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     *月魅力列表
     */
    public function giftMonth()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $gift_id = Request::param('gift_id'); //礼物ID
        $where = [];
        if ($gift_id) {
            $where['gift_id'] = $gift_id;
        }
        $where[] = ['type', '=', 3];
        $count = GiftStartModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = GiftStartModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $key => $val) {
                //'1:周星魅力 2:周星财富 2:月星魅力'
                if ($val['type'] == 1) {
                    $data[$key]['types'] = '魅力';
                } elseif ($val['type'] == 2) {
                    $data[$key]['types'] = '财富';
                } elseif ($val['type'] == 3) {
                    $data[$key]['types'] = '月星';
                }

                $data[$key]['statuss'] = $val['status'];
                if ($data[$key]['image'] != "") {
                    $data[$key]['image'] = $url . $val['image'];
                }
                if ($data[$key]['rich_car_url'] != "") {
                    $data[$key]['rich_car_url'] = $url . $val['rich_car_url'];
                }
                if ($data[$key]['rich_box_url'] != "") {
                    $data[$key]['rich_box_url'] = $url . $val['rich_box_url'];
                }
                if ($data[$key]['gift_url'] != "") {
                    $data[$key]['gift_url'] = $url . $val['gift_url'];
                }
                if ($data[$key]['gift_avatar'] != "") {
                    $data[$key]['gift_avatar'] = $url . $val['gift_avatar'];
                }
                $data[$key]['start_time'] = date('Y-m-d H:i:s', $val['start_time']);
                $data[$key]['end_time'] = date('Y-m-d H:i:s', $val['end_time']);
                if ($val['status'] == 1) {$data[$key]['status'] = '本周';} else { $data[$key]['status'] = '上周';}
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物周星列表获取成功:操作人:' . $this->token['username'], 'giftstart');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $gift_id);
        return View::fetch('gift/giftMonth');
    }

    /*
     * 添加礼物周星
     */
    public function addGiftMonth()
    {
        $data = [
            'avatar_name' => Request::param('avatar_name'),
            'rich_box_name' => Request::param('rich_box_name'),
            'gift_name' => Request::param('gift_name'),
            'status' => Request::param('status'),
            'type' => 3,
        ];
        $res = GiftStartModel::getInstance()->getModel()->save($data);
        if ($res) {
            Log::record('月榜奖励添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('月榜奖励添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /*
     * 修改礼物周星信息
     */
    public function exitGiftMonth()
    {
        $id = Request::param('start_id');
        if (empty($id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        $where['id'] = $id;
        $data = [
            'avatar_name' => Request::param('avatar_name'),
            'rich_box_name' => Request::param('rich_box_name'),
            'gift_name' => Request::param('gift_name'),
            'avatar_details' => Request::param('avatar_details'),
            'gift_details' => Request::param('gift_details'),
            'rich_box_details' => Request::param('rich_box_details'),
        ];
        $res = GiftStartModel::getInstance()->getModel()->where($where)->save($data);
        if ($res) {
            Log::record('修改月榜奖励数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改月榜奖励数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

}

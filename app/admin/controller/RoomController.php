<?php
/**
 * Created by PhpStorm.
 * User: pussycat
 * Date: 2019/7/23
 * Time: 21:01
 */

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\ApiUrlConfig;
use app\admin\model\BiRoomHideLogModel;
use app\admin\model\ChannelModel;
use app\admin\model\ConfigModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\PhotoWallModel;
use app\admin\model\RoomCloseModel;
use app\admin\model\RoomHideModel;
use app\admin\model\RoomModeModel;
use app\admin\model\SiteconfigModel;
use app\admin\script\analysis\GiftsCommon;
use app\admin\service\ApiService;
use app\admin\service\ConfigService;
use app\admin\service\RoomModeService;
use app\admin\service\RoomService;
use app\common\RedisCommon;
use app\common\UploadOssFileCommon;
use app\exceptions\ApiExceptionHandle;
use OSS\Core\OssException;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class RoomController extends AdminBaseController
{
    //首页配置
    public function roomHomepage()
    {
        $list = RoomService::getInstance()->roomHomepage();

        $home_page_rooms = RoomService::getInstance()->homePageRooms();
        $homePageCounts = RoomService::getInstance()->homePageValue();


        $home_data = [];
        foreach (array_unique(array_keys($home_page_rooms)) as $_ => $room_id) {
            if (isset($homePageCounts[$room_id])) {
                $home_data[$room_id]['init'] = (int) $homePageCounts[$room_id];
            } else {
                $home_data[$room_id]['init'] = 0;
            }
            $home_data[$room_id]['real'] = (int) $home_page_rooms[$room_id];
        }

        $enjoy_rooms = RoomService::getInstance()->enjoyRooms();
        $enjoyCounts = RoomService::getInstance()->enjoyValue();

        $enjoy_data = [];
        foreach (array_unique(array_keys($enjoy_rooms)) as $_ => $room_id) {
            if (isset($enjoyCounts[$room_id])) {
                $enjoy_data[$room_id]['init'] = (int) $enjoyCounts[$room_id];
            } else {
                $enjoy_data[$room_id]['init'] = 0;
            }
            $enjoy_data[$room_id]['real'] = (int) $enjoy_rooms[$room_id];
        }

        $roomPartyActiveCounts = RoomService::getInstance()->roomPartyValue();
        $party_rooms = $list[3];
        foreach ($party_rooms as $_ => $room_id) {
            if (!isset($roomPartyActiveCounts[$room_id])) {
                $roomPartyActiveCounts[$room_id] = 0;
            }
        }

        $newUserRoom = RoomService::getInstance()->getNewOldUserComeRoom(6);
        $oldUserRoom = RoomService::getInstance()->getNewOldUserComeRoom(7);

        arsort($roomPartyActiveCounts);
        View::assign('list', $list);
        View::assign('roomPartyActiveCounts', $roomPartyActiveCounts);
        View::assign('homePageCounts', $home_data);
        View::assign('enjoyCounts', $enjoy_data);
        View::assign('token', $this->request->param('token'));
        View::assign('newUserRoom', $newUserRoom);
        View::assign('oldUserRoom', $oldUserRoom);
        return View::fetch('room/roomHomepage');
    }

    //首页房间配置保存
    public function roomHomepageSave()
    {
        $room = (array) Request::param('room');
        $count = (array) Request::param('count');
        $action = (int) Request::param('action');

        if($action == 6  || $action == 7){
            $sort = (array) Request::param('sort');
            $msg = (array) Request::param('msg');
            RoomService::getInstance()->newOldUserComeRoom($action, $room,$sort,$msg);
            exit;
        }


        if (!in_array($action, [1, 2, 3, 4, 5])) {
            echo json_encode(['code' => 500, 'msg' => '提交类型错误']);
            die;
        }
        echo RoomService::getInstance()->roomHomepageSave($action, $room, $count);
    }

    //  mua配置-新厅推荐
    public function muaNewRoomRecommend()
    {
        $list = RoomService::getInstance()->muaNewRoomRecommend();
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        return View::fetch('room/mua/muaNewRoomRecommend');
    }

    //  mua配置-新厅推荐保存
    public function muaNewRoomRecommendSave()
    {
        echo RoomService::getInstance()->muaNewRoomRecommendSave(Request::param('room'));
    }

    //房间照片墙配置
    public function roomPhoto()
    {
        $gifts = GiftsCommon::getInstance()->getGifts();
        $list = RoomService::getInstance()->roomPhoto();

        View::assign('list', $list);
        View::assign('gifts', $gifts);
        View::assign('token', $this->request->param('token'));
        return View::fetch('room/mua/roomPhoto');
    }

    //房间照片墙配置保存
    public function roomPhotoSave()
    {
        $gifts = Request::param('gifts');
        echo RoomService::getInstance()->roomPhotoSave($gifts);
    }

    //  mua配置-房间金刚位
    public function muaRoomKingKong()
    {
        $list = RoomService::getInstance()->muaRoomKingKong();
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        return View::fetch('room/mua/muaRoomKingKong');
    }

    //  mua配置-房间金刚位保存
    public function muaRoomKingKongSave()
    {
        echo RoomService::getInstance()->muaRoomKingKongSave(Request::param('room'));
    }

    /**
     * @添加房间靓号
     * @dongbozhao
     * @2020-12-10 11:50
     */
    public function addRoomPretty()
    {
        try {
            $room_id = $this->request->param('id');
            $pretty_room_id = $this->request->param('pretty_room_id_val');

            if (empty($pretty_room_id)) {
                $pretty_room_id = $room_id;
            }

            // $isRoomPretty = LanguageroomModel::getInstance()->getModel()->where('pretty_room_id', $pretty_room_id)->value('id');
            // if ($isRoomPretty) {
            //     echo json_encode(['code' => 500, 'msg' => '靓号已存在']);
            //     die;
            // }

            // $is = LanguageroomModel::getInstance()->getModel()->where('id', $room_id)->save(['pretty_room_id' => $pretty_room_id]);
            // if ($is) {
            //     $modestr = ['roomId' => (int) $room_id, 'type' => 'baseData'];
            //     $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
            //     $msgData = json_encode($modestr);
            //     $res = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("房间靓号-----" . $msgData, "info");
            //     Log::record("房间靓号接口返回-----" . $res, "info");
            // }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'room_id' => (int) $room_id,
                'pretty_room_id_val' => (int) $pretty_room_id,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$add_room_pretty, $params, true);

            Log::record('添加房间靓号成功:操作人:' . $this->token['username'] . ':内容:' . json_encode($params), 'addRoomPretty');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('添加房间靓号失败:操作人:' . $this->token['username'] . ':内容:' . json_encode($params), 'addRoomPretty');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }

    }

    /**
     * 推荐房间展示列表
     */
    public function RandomlyMatchedRoom()
    {
        $redis = $this->getRedis();
        $data = $redis->sMembers('task_room_id');
        $list = [];
        foreach ($data as $k => $room_id) {
            $list[$k]['name'] = LanguageroomModel::getInstance()->getModel($room_id)->where('id', $room_id)->value('room_name');
            $list[$k]['id'] = $room_id;
        }
        View::assign('data', $list);
        View::assign('token', $this->request->param('token'));
        return View::fetch('room/randomlymatchedroom');
    }

    /**
     * 添加房间ID
     */
    public function AddRandomlyMatchedRoom()
    {
        $rid = $this->request->param('rid');
        $is = LanguageroomModel::getInstance()->getModel($rid)->where('id', $rid)->select()->toArray();
        if ($is) {
            $redis = $this->getRedis();
            $type = $redis->sAdd('task_room_id', trim($rid));
            if ($type) {
                echo json_encode(['code' => 200, 'msg' => '添加成功']); //php编译join
            } else {
                echo json_encode(['code' => 500, 'msg' => '房间已存在']); //php编译join
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '房间ID不存在']); //php编译join
        }
    }

    /**
     * 添加房间ID
     */
    public function delRandomlyMatchedRoom()
    {
        $rid = $this->request->param('rid');
        $redis = $this->getRedis();
        $is = $redis->sismember('task_room_id', $rid);
        if ($is) {
            $type = $redis->srem('task_room_id', $rid);
            if ($type) {
                echo json_encode(['code' => 200, 'msg' => '删除成功']); //php编译join
            } else {
                echo json_encode(['code' => 500, 'msg' => '删除失败']); //php编译join
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '房间ID不存在']); //php编译join
        }
    }

    private $key = 'room_visitor_externnumber:';

    //房间推荐
    private $room_key = "regist_roomid";
    private $room_key_save = "regist_roomid_push";

    //配置飘屏
    public function editroommsg()
    {
        $coin = (int) Request::param('coin');
        if ($coin > 0) {
            $json = ConfigModel::getInstance()->getModel()->where('name', 'box_conf')->value('json');
            $res = json_decode($json, true);
            $res['fullServerCoin'] = $coin;
            $newConf = json_encode($res);
            ConfigModel::getInstance()->getModel()->where('name', 'box_conf')->save(['json' => $newConf]);
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $redis->set('box_conf', $newConf);
            ConfigService::getInstance()->register();
            echo $this->return_json(200, null, '设置成功');
        } else {
            echo $this->return_json(500, null, '参数设置错误');
        }
    }

    //查询飘屏
    public function getroommsg()
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', 'box_conf')->value('json');
        $res = json_decode($json, true)['fullServerCoin'];
        View::assign('res', $res);
        View::assign('token', $this->request->param('token'));
        return View::fetch('room/roommsg');
    }

    //配置公屏
    public function editsaymsg()
    {
        $coin = (int) Request::param('coin');
        if ($coin > 0) {
            $json = ConfigModel::getInstance()->getModel()->where('name', 'box_conf')->value('json');
            $res = json_decode($json, true);
            $res['eggCoin'] = $coin;
            $newConf = json_encode($res);
            ConfigModel::getInstance()->getModel()->where('name', 'box_conf')->save(['json' => $newConf]);
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $redis->set('box_conf', $newConf);
            ConfigService::getInstance()->register();
            echo $this->return_json(200, null, '设置成功');
        } else {
            echo $this->return_json(500, null, '参数设置错误');
        }
    }

    //房间送礼飘屏
    public function sendGiftNum()
    {
        $res = SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->findOrEmpty()->toArray();
        View::assign('res', $res);
        View::assign('token', $this->request->param('token'));
        return View::fetch('room/sendGiftNum');
    }

    //配置公屏
    public function sendGiftNumSave()
    {
        $coin = Request::param('coin');
        if ($coin > 0) {
            SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->save(['send_gift_num' => $coin]);
            echo $this->return_json(200, null, '设置成功');
        } else {
            echo $this->return_json(500, null, '参数设置错误');
        }
    }

    //查询公屏
    public function getsaymsg()
    {
        $json = ConfigModel::getInstance()->getModel()->where('name', 'box_conf')->value('json');
        $res = json_decode($json, true)['eggCoin'];
        View::assign('res', $res);
        View::assign('token', $this->request->param('token'));
        return View::fetch('room/roomsaymsg');
    }

    /*
     * 房间列表
     * @param string $token　token值
     * @param string $limit  limit条数
     * @param string $page  page分页
     * @param string $type  房间类型
     */
    public function roomList()
    {
        $limit = 20;
        $totalPage = 0;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $type = Request::param('type');
        $user_id = Request::param('user_id');
        //房间类型
        if (!empty($type) && !is_numeric($type)) {
            echo $this->return_json(\constant\CodeConstant::CODE_房间类型错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_房间类型错误]);
            die;
        }
        $channels_id = Request::param('channels_id');
        //房间类型
        if (!empty($channels_id) && !is_numeric($channels_id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_房间关联渠道错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_房间关联渠道错误]);
            die;
        }
        $room_id = Request::param('room_id'); //房间id
        $pretty_room_id_select = Request::param('pretty_room_id_select'); //房间id
        $room_name = Request::param('room_name'); //房间名称
        $is_hot = Request::param('is_hot') ? 1 : 0; //是否推荐
        $is_hide = Request::param('is_hide', -1); //是否隐藏
        $is_block = Request::param('is_block', -1); //是否封禁
        $where = [];
        $whereOr = [];
        //搜索条件(类型,房间id)
        if ($room_id) {
            $whereOr = [[['id', '=', $room_id]], [['pretty_room_id', '=', $room_id]]];
        }
        if ($type) {
            $where[] = ['room_type', '=', $type];
        }
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if ($room_name) {
            $where[] = ['room_name', 'like', $room_name . '%'];
        }
        if ($is_hot) {
            $where[] = ['is_hot', '=', $is_hot];
        }
        if ($is_hide > 0) {
            $where[] = ['is_hide', '=', $is_hide];
        }
        if ($is_block > 0) {
            $where[] = ['is_block', '=', $is_block];
        }

        $count = LanguageroomModel::getInstance()->getCount($where, $whereOr, $offset, $limit);
        $totalPage = ceil($count / $limit);

        $data = LanguageroomModel::getInstance()->RoomListNew($where, $whereOr, $offset, $limit);

        $room_type_list = [];
        if (!empty($data)) {
            foreach ($data as $key => $vo) {
                if ($vo['room_lock'] == 0) {
                    $data[$key]['room_lock'] = "未锁定";
                    $data[$key]['room_password'] = '无';
                } else {
                    $data[$key]['room_lock'] = $vo['room_lock'];
                }

                $data[$key]['background_image'] = getavatar($vo['background_image']);
                $data[$key]['hot_status'] = $vo['is_hot'];
                $data[$key]['is_hot'] = $vo['is_hot'] == 1 ? '是' : '否';
                //查询房间渠道
                $channel_name = '';
                $channel_id = '';
                if ($vo['room_channel'] != 0) {
                    $whereIn = $this->bitSplit($vo['room_channel']);
                    $channel = ChannelModel::getInstance()->getModel()->field('name,id')->where([['id', 'in', $whereIn]])->select()->toArray();
                    if (!empty($channel)) {
                        $channel_name = implode(',', array_column($channel, 'name'));
                        $channel_id = implode(',', array_column($channel, 'id'));
                    }
                }
                $data[$key]['channel_name'] = $channel_name;
                $data[$key]['channel_id'] = $channel_id;
                //根据room_type查对应的类型名称
                $data[$key]['room_type_id'] = $data[$key]['room_type'];
                $data[$key]['room_type'] = RoomModeModel::getInstance()->getOneById($vo['room_type'], 'room_mode');
            }

        }
        $typeWhere[] = ['pid', 'in', '1,2'];
        $typeWhere[] = ['is_show', '=', 1];
        $room_type = json_decode($this->roomTypeList($typeWhere), 1);
        if ($room_type['code'] == 200) {
            $room_type_list = $room_type['data']['list'];
        }
        foreach ($room_type_list as $key => $val) {
            $val = join('-', $val);
            $roomType[] = $val;
        }
        $channel_array = ChannelModel::getInstance()->getModel()->field('id,name')->select()->toArray();
        $roomType = implode(',', $roomType);
        //派对房间
        $partyWhere[] = ['pid', 'in', '100'];
        $partyWhere[] = ['is_show', '=', 1];
        $party_room_type = json_decode($this->roomTypeList($partyWhere), 2);
        if ($party_room_type['code'] == 200) {
            $party_room_type = $party_room_type['data']['list'];
        }
        Log::record('房间列表:操作人:' . $this->token['username'], 'roomList');
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        $admin_url = config('config.admin_url');
        View::assign('list', $data);
        View::assign('channel_array', $channel_array);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('room_type_list', $room_type_list);
        View::assign('roomType', $roomType);
        View::assign('type', $type);
        View::assign('channels_id', $channels_id);
        View::assign('user_id', $user_id);
        View::assign('room_id', $room_id);
        View::assign('pretty_room_id_select', $pretty_room_id_select);
        View::assign('room_name', $room_name);
        View::assign('is_hot', $is_hot);
        View::assign('party_room_type', $party_room_type);
        View::assign('admin_url', $admin_url);
        View::assign('is_hide', $is_hide);
        View::assign('is_block', $is_block);
        return View::fetch('room/index');
    }

    /*
     * 修改房间信息
     * @param $token token值
     * @param $id 修改房间id
     * @param $name 修改的字段值
     * @param $value 需要修改的新值
     */
    public function exitRoomType()
    {
        $room_id = Request::param('room_id');
        $field = Request::param('field'); //修改字段
        $value = Request::param('value'); //新值
        //$data = [$field => $value];
        //正在游戏中不能切
        $redis = $this->getRedis();
        $isGame = $redis->SISMEMBER('isgaming', $room_id);
        Log::record("查询房间游戏中-----" . $isGame, "info");
        if (!empty($isGame)) {
            echo $this->return_json(\constant\CodeConstant::CODE_当前房间正在游戏中, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_当前房间正在游戏中]);
            die;
        }
        $where = ['id' => $room_id]; //条件
        $background_image = PhotoWallModel::getInstance()->getModel()->where(array('room_mode' => $value, 'status' => 2))->value('image');
        $data = [
            $field => $value,
            "background_image" => $background_image,
        ];
        $roomInfo = LanguageroomModel::getInstance()->getModel($room_id)->where($where)->find();
        //判断是否切分类
        $typeRes = RoomModeModel::getInstance()->getModel()->where([['id', 'in', [$value, $roomInfo['room_type']]]])->column('pid', 'id');
        $isChangeRoom = false;
        if (!empty($typeRes)) {
            if ($typeRes[$value] != $typeRes[$roomInfo['room_type']]) {
                $isChangeRoom = true;
            }
            if ($typeRes[$value] == 2 && $typeRes[$roomInfo['room_type']] == 2) {
                $isChangeRoom = true;
            }
        }
        $room_modeName = RoomModeModel::getInstance()->getModel()->where(array("id" => $value))->value("room_mode");

        $res_type = LanguageroomModel::getInstance()->exitRoom($where, $data, $room_id);
        if ($res_type) {
            //修改房间成功后发送消息
            $str = ['msgId' => 2050, 'room_name' => $roomInfo['room_name'], 'room_desc' => $roomInfo['room_desc'], 'room_welcomes' => $roomInfo['room_welcomes'], 'modeName' => $room_modeName, 'isChangeRoom' => $isChangeRoom, 'ModePid' => $value];
            $msg['msg'] = json_encode($str);
            $msg['roomId'] = (int) $room_id;
            $msg['toUserId'] = '0';
            $socket_url = config('config.socket_url');
            $msgData = json_encode($msg);
            $res = curlData($socket_url, $msgData, 'POST', 'json');
            Log::record("房间信息修改类型消息发送参数-----" . $msgData, "info");
            Log::record("房间信息修改类型消息发送-----" . $res, "info");
            if ($data['background_image']) {
                //发消息操作
                $str = ['msgId' => 2051, 'room_bg' => getavatar($data['background_image'])];
                $msg['msg'] = json_encode($str);
                $msg['roomId'] = (int) $room_id;
                $msg['toUserId'] = '0';
                $socket_url = config('config.socket_url');
                $msgData = json_encode($msg);
                $res = curlData($socket_url, $msgData, 'POST', 'json');
                Log::record("房间背景图发送参数-----" . $msgData, "info");
                Log::record("房间背景图发送-----" . $res, "info");
            }

            //发消息操作
            $modestr = ['roomId' => (int) $room_id, 'type' => 'mode'];
            $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
            $msgData = json_encode($modestr);
            $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
            Log::record("房间类型切换添加发送参数-----" . $msgData, "info");
            Log::record("房间类型切换添加发送-----" . $moderesmsg, "info");
            //修改房间热门热度值
            Log::record('修改房间类型:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitRoomType');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改房间类型:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitRoomType');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     * 加入派对房间类型
     * @param $token token值
     * @param $id 修改房间id
     * @param $check_id 选择类型id
     * @param $guild_id 公会id
     */
    public function addRoomParty()
    {
        try {
            $room_id = $this->request->param('id');
            $check_id = $this->request->param('check_id');
            $guild_id = $this->request->param('guild_id');
            if (!$room_id || !$check_id || !$guild_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);die;
            }

            //查询当前公会ID是否存在
            // $guildResult = MemberGuildModel::getInstance()->getModel()->where(array("id" => $guild_id))->find();
            // if (empty($guildResult)) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_公会ID不存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_公会ID不存在]);die;
            // }

            // $where = ['id' => $room_id]; //条件
            // $room_mode = RoomModeModel::getInstance()->getModel()->where('id', $check_id)->value('pid');
            // $background_image = PhotoWallModel::getInstance()->getModel()->where(array('room_mode' => $room_mode, 'status' => 2, 'start' => 1))->value('image');
            // $data = [
            //     "room_type" => $check_id,
            //     "background_image" => $background_image,
            //     "guild_id" => $guild_id,
            // ];

            // $roomInfo = LanguageroomModel::getInstance()->getModel()->where($where)->find();
            // //判断是否切分类
            // $typeRes = RoomModeModel::getInstance()->getModel()->where([['id', 'in', [$check_id, $roomInfo['room_type']]]])->column('pid', 'id');
            // $isChangeRoom = false;
            // if (!empty($typeRes)) {
            //     if ($typeRes[$check_id] != $typeRes[$roomInfo['room_type']]) {
            //         $isChangeRoom = true;
            //     }
            // }
            // $room_modeName = RoomModeModel::getInstance()->getModel()->where(array("id" => $check_id))->value("room_mode");

            // $res_type = LanguageroomModel::getInstance()->exitRoom($where, $data);
            // if ($res_type) {
            //     //修改成功后发送消息操作
            //     $str = ['msgId' => 2050, 'room_name' => $roomInfo['room_name'], 'room_desc' => $roomInfo['room_desc'], 'room_welcomes' => $roomInfo['room_welcomes'], 'modeName' => $room_modeName, 'isChangeRoom' => $isChangeRoom, 'ModePid' => $check_id];
            //     $msg['msg'] = json_encode($str);
            //     $msg['roomId'] = (int) $room_id;
            //     $msg['toUserId'] = '0';
            //     $socket_url = config('config.socket_url');
            //     $msgData = json_encode($msg);
            //     $res = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("房间信息修改类型消息发送参数-----" . $msgData, "info");
            //     Log::record("房间信息修改类型消息发送-----" . $res, "info");
            //     if ($data['background_image']) {
            //         //发消息操作
            //         $str = ['msgId' => 2051, 'room_bg' => getavatar($data['background_image'])];
            //         $msg['msg'] = json_encode($str);
            //         $msg['roomId'] = (int) $room_id;
            //         $msg['toUserId'] = '0';
            //         $socket_url = config('config.socket_url');
            //         $msgData = json_encode($msg);
            //         $res = curlData($socket_url, $msgData, 'POST', 'json');
            //         Log::record("房间背景图发送参数-----" . $msgData, "info");
            //         Log::record("房间背景图发送-----" . $res, "info");
            //     }

            //     //发送公会与类型修改消息开始
            //     $modestr = ['roomId' => (int) $room_id, 'type' => 'mode'];
            //     $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
            //     $msgData = json_encode($modestr);
            //     $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("房间类型切换添加发送参数-----" . $msgData, "info");
            //     Log::record("房间类型切换添加发送-----" . $moderesmsg, "info");

            //     //发公会消息操作
            //     $modestr = ['roomId' => (int) $room_id, 'type' => 'guild'];
            //     $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
            //     $msgData = json_encode($modestr);
            //     $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("房间类型切换添加发送参数-----" . $msgData, "info");
            //     Log::record("房间类型切换添加发送-----" . $moderesmsg, "info");
            //     //结束
            //     //修改房间热门热度值
            //     VsitorExternnumberService::getInstance()->saveRoomNumber($room_id, 1);
            // }

            // room_id    int    是    房间id
            // check_id    int    是    房间类型
            // guild_id

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'room_id' => (int) $room_id,
                'check_id' => (int) $check_id,
                'guild_id' => (int) $guild_id,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$add_room_party, $params, true);

            Log::record('修改房间类型:操作人:' . $this->token['username'] . ':内容:' . json_encode($params), 'exitRoomType');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('修改房间类型:操作人:' . $this->token['username'] . ':内容:' . json_encode($params), 'exitRoomType');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /*
     * 房间类型接口
     * @param $string $token token值
     * @return json
     */
    public function roomTypeList($type)
    {
        //获取所有房间类型数据
        $mode_list = RoomModeService::getInstance()->getList($type);
        $mode_list = !empty($mode_list) ? $mode_list : $mode_list = [];
        $result = [
            "list" => $mode_list,
        ];
        return $this->return_json(\constant\CodeConstant::CODE_成功, $result, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
    }

    public function exitRoomChannel()
    {
        $id = $this->request->param('id');
        $check_id = $this->request->param('check_id');
        if (!$id || !$check_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $ok = LanguageroomModel::getInstance()->getModel($id)->where(array('id' => $id))->save(array('room_channel' => $check_id));
        if ($ok) {
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        }
        echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        die;
    }

    /*
     * 修改房间信息
     */
    public function editRoom()
    {
        try {
            $room_id = Request::param('room_id'); //房间ID
            $guild_id = Request::param('guild_id');
            $room_name = Request::param('room_name');
            $tag_id = Request::param('tag_id', 0);
            $is_hot = Request::param('is_hot');
            $is_show = Request::param('is_show');

            if (!$room_id) {
                $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误], true);
            }

            //查询当前公会ID是否存在
            // $guild_info = MemberGuildModel::getInstance()->getModel()->where(array("id" => $guild_id))->find();
            // if (empty($guild_info)) {
            //     echo json_encode(['code' => 500, 'msg' => '公会ID不存在']);die;
            // }

            // $where['id'] = $room_id;
            // $roomInfoMsg = LanguageroomModel::getInstance()->getModel()->where($where)->find();
            // if ($roomInfoMsg['guild_id'] != Request::param('guild_id')) {
            //     $modestr = ['roomId' => (int) $room_id, 'type' => 'guild'];
            //     $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
            //     $msgData = json_encode($modestr);
            //     $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("房间公会切换添加发送参数-----" . $msgData, "info");
            //     Log::record("房间公会切换添加发送-----" . $moderesmsg, "info");
            // }

            // $res = LanguageroomModel::getInstance()->getModel()->where($where)->save($data);
            // if ($res) {
            //     $isChangeRoom = false;
            //     $roomInfo = LanguageroomModel::getInstance()->getModel()->where($where)->find();
            //     $room_type = RoomModeModel::where('id', $roomInfo['room_type'])->value('room_mode');
            //     $str = ['msgId' => 2050, 'room_name' => $data['room_name'], 'room_desc' => $roomInfo['room_desc'], 'room_welcomes' => $roomInfo['room_welcomes'], 'modeName' => $room_type, 'isChangeRoom' => $isChangeRoom];
            //     $msg['msg'] = json_encode($str);
            //     $msg['roomId'] = (int) $room_id;
            //     $msg['toUserId'] = '0';
            //     $socket_url = config('config.socket_url');
            //     $msgData = json_encode($msg);
            //     $res = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("房间信息修改消息发送参数-----" . $msgData, "info");
            //     Log::record("房间信息修改消息发送-----" . $res, "info");
            //     Log::record('修改房间数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editRoom');
            //     echo json_encode(['code' => 200, 'msg' => '更新成功']);
            //     die;
            // }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'room_id' => $room_id,
                'guild_id' => $guild_id,
                'tag_id' => $tag_id,
                'room_name' => $room_name,
                'is_hot' => (int) $is_hot,
                'is_show' => (int) $is_show,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$edit_room, $params, true);

            Log::record('修改房间数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params), 'editRoom');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('修改房间数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params), 'editRoom');
            echo json_encode(['code' => 500, 'msg' => '更新失败']);
            die;
        }
    }

    /**
     * 推荐房间列表
     */
    public function roomRecommend()
    {
        $redis = $this->getRedis();
        $getRes = $redis->hGetAll($this->room_key_save);
        $content = array_values($getRes);
        $data = [];
        foreach ($content as $k => $v) {
            //循环将数组的键值拼接起来
            $params = json_decode($v, true);
            $data[$k]['id'] = $params['room_id'];
            $data[$k]['begin_time'] = $params['begin_time'] ?? '';
            $data[$k]['room_id'] = $params['room_id'] ?? '';
            $data[$k]['end_time'] = $params['end_time'] ?? '';
            $data[$k]['status'] = $params['status'] ?? '';
        }

        View::assign('data', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('room/roomRecommend');
    }

    /**
     * 添加推荐房间列表
     */
    public function roomRecommendAdd()
    {
        $room_id = Request::param('room_id'); //房间ID
        $begin_time = Request::param('begin_time'); //开始的时间
        $end_time = Request::param('end_time'); //结束的时间
        if (!$room_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where['id'] = $room_id;
        $res = LanguageroomModel::getInstance()->getModel($room_id)->where($where)->find();
        if (!$res) {
            echo $this->return_json(\constant\CodeConstant::CODE_房间ID不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_房间ID不能为空]);
            die;
        }

        if (strtotime($begin_time) >= strtotime($end_time)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }

        $redis = $this->getRedis();
        $body = [
            "room_id" => $room_id,
            "begin_time" => $begin_time,
            "end_time" => $end_time,
        ];

        $haveRes = $redis->hget($this->room_key_save, $room_id);
        if ($haveRes) {
            echo $this->return_json(\constant\CodeConstant::CODE_推荐房间ID已存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_推荐房间ID已存在]);
            die;
        }

        $redis->hset($this->room_key_save, $room_id, json_encode($body));

        //$content = $redis->hGetAll($this->room_key_save);
        /*  foreach($content as $room_id=>$items){
        $params =  json_decode($items,true);
        if(strtotime($params['begin_time']) < $currentTimestamp && strtotime($params['end_time']) > $currentTimestamp){
        $Recommend = $redis->SISMEMBER($this->room_key, $room_id);
        if(!$Recommend){
        $redis->SADD($this->room_key, $room_id);
        Log::record('指定房间推荐添加成功:操作人:' . $this->token['username'] . '@' . json_encode($room_id), 'roomRecommendAdd');
        }
        }elseif(strtotime($params['end_time']) < $currentTimestamp){
        $redis->multi();
        $redis->sRem($this->room_key, $room_id);
        $redis->hDel($this->room_key_save,$room_id);
        $redis->exec();
        }
        }*/

        echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
        die;
    }

    /**
     * 取消推荐房间列表   待确认是否有用
     */
    public function roomRecommendDel()
    {
        $room_id = Request::param('room_id'); //房间ID
        $redis = $this->getRedis();
        $result = $redis->hGet($this->room_key_save, $room_id);
        if (!$result) {
            Log::record('指定房间推荐取消失败:操作人:' . $this->token['username'] . '@' . json_encode($room_id), 'roomRecommendDel');
        } else {
            $redis->hDel($this->room_key_save, $room_id);
            $redis->srem($this->room_key, $room_id);
            Log::record('指定房间推荐取消成功:操作人:' . $this->token['username'] . '@' . json_encode($room_id), 'roomRecommendDel');
        }
        echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
        die;
    }

    /**
     * 房间背景图
     */
    public function roomOssFile()
    {
        try {
            //获取数据
            $room_id = Request::param('id');
            $failure_time = Request::param('failure_time');

            if (!$room_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            };
            // $where['id'] = $room_id;
            $avatar = request()->file('background_image');

            $file_dir = "/background_image";
            $UploadOssFileCommon = new UploadOssFileCommon();
            $avatarurl = $UploadOssFileCommon->ossFile($avatar, $file_dir);

            if (!$avatarurl) {
                echo $this->return_json('上传文件失败', null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;

                // $data = ['background_image' => $result['path']];
                // $res = LanguageroomModel::getInstance()->getModel()->where($where)->save($data);

                // //添加数据
                // $addDate['image'] = $result['path'];
                // $addDate['room_id'] = $room_id;
                // $addDate['failure_time'] = strtotime(date("Y-m-d", strtotime("+" . $failure_time . " months", time())));
                // PhotoWallModel::getInstance()->getModel()->insert($addDate);
            }

            // if ($res) {
            //     $str = ['msgId' => 2051, 'room_bg' => getavatar($result['path'])];
            //     $msg['msg'] = json_encode($str);
            //     $msg['roomId'] = (int) $room_id;
            //     $msg['toUserId'] = '0';
            //     $socket_url = config('config.socket_url');
            //     $msgData = json_encode($msg);
            //     $res = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("房间信息背景图发送参数-----" . $msgData, "info");
            //     Log::record("房间信息背景图发送-----" . $res, "info");
            //     //发消息操作
            //     $modestr = ['roomId' => (int) $room_id, 'type' => 'baseData'];
            //     $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
            //     $msgData = json_encode($modestr);
            //     $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
            //     Log::record("房间其他信息修改添加发送参数-----" . $msgData, "info");
            //     Log::record("房间其他信息修改换添加发送-----" . $moderesmsg, "info");
            // }

            $result = parse_url($avatarurl);
            $background_image = $result['path'];

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'room_id' => (int) $room_id,
                'failure_time' => strtotime(date("Y-m-d", strtotime("+" . $failure_time . " months", time()))),
                'background_image' => $background_image,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$room_oss_file, $params);

            Log::record('房间背景图编辑成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'roomOssFile');
            $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功], true);
        } catch (OssException $e) {
            Log::record('房间背景图编辑失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'roomOssFile');
            $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败], true);
        }
    }

    /*
     * 派对房间列表
     * @param string $token　token值
     * @param string $limit  limit条数
     * @param string $page  page分页
     * @param string $type  房间类型
     */
    public function partyRoomList()
    {
        $limit = 5;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $type = Request::param('type');
        $is_hot = Request::param('is_hot') ? 1 : 0; //是否推荐
        $is_hide = Request::param('is_hide', "-1", "trim"); //是否隐藏
        $is_block = Request::param('is_block', "-1", "trim"); //是否封禁
        //房间类型
        if (!empty($type) && !is_numeric($type)) {
            echo $this->return_json(\constant\CodeConstant::CODE_房间类型错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_房间类型错误]);
            die;
        }
        $channels_id = Request::param('channels_id');
        //房间类型
        if (!empty($channels_id) && !is_numeric($channels_id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_房间关联渠道错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_房间关联渠道错误]);
            die;
        }
        $where = [];
        $whereOr = [];
        $room_id = Request::param('room_id'); //房间id
        //搜索条件(类型,房间id)
        if ($room_id) {
            $whereOr = [[['id', '=', $room_id]], [['pretty_room_id', '=', $room_id]]];
        }
        //搜索条件(类型,房间id)
        if ($type) {
            $where[] = ['room_type', '=', $type];
        }
        if ($room_id) {
            $where[] = ['id', '=', $room_id];
        }
        if ($is_hot) {
            $where[] = ['is_hot', '=', $is_hot];
        }

        if ($is_hide > 0) {
            $where[] = ['is_hide', '=', $is_hide];
        }

        if ($is_block > 0) {
            $where[] = ['is_block', '=', $is_block];
        }

        //公会房间
        $where[] = ['guild_id', '>', 0];
        $count = LanguageroomModel::getInstance()->getCount($where, $whereOr, $offset, $limit);
        $totalPage = ceil($count / $limit);

        $data = LanguageroomModel::getInstance()->RoomListNew($where, $whereOr, $offset, $limit);

        $room_type_list = [];
        if (!empty($data)) {
            foreach ($data as $key => $vo) {
                if ($vo['room_lock'] == 0) {
                    $data[$key]['room_lock'] = "未锁定";
                    $data[$key]['room_password'] = '无';
                } else {
                    $data[$key]['room_lock'] = $vo['room_lock'];
                }

                $data[$key]['background_image'] = getavatar($vo['background_image']);
                $data[$key]['hot_status'] = $vo['is_hot'];
                $data[$key]['is_hot'] = $vo['is_hot'] == 1 ? '是' : '否';
                //查询房间渠道
                $channel_name = '';
                $channel_id = '';
                if ($vo['room_channel'] != 0) {
                    $whereIn = $this->bitSplit($vo['room_channel']);
                    $channel = ChannelModel::getInstance()->getModel()->field('name,id')->where([['id', 'in', $whereIn]])->select()->toArray();
                    if (!empty($channel)) {
                        $channel_name = implode(',', array_column($channel, 'name'));
                        $channel_id = implode(',', array_column($channel, 'id'));
                    }
                }
                $data[$key]['channel_name'] = $channel_name;
                $data[$key]['channel_id'] = $channel_id;
                //根据room_type查对应的类型名称
                $data[$key]['room_type_id'] = $data[$key]['room_type'];
                $data[$key]['room_type'] = RoomModeModel::getInstance()->getOneById($vo['room_type'], 'room_mode');
            }
        }

        $typeWhere[] = ['pid', '=', 100];
        $typeWhere[] = ['is_show', '=', 1];
        $room_type = json_decode($this->roomTypeList($typeWhere), 1);
        if ($room_type['code'] == 200) {
            $room_type_list = $room_type['data']['list'];
        }
        foreach ($room_type_list as $key => $val) {
            $val = join('-', $val);
            $roomType[] = $val;
        }
        $channel_array = ChannelModel::getInstance()->getModel()->field('id,name')->select()->toArray();
        $roomType = implode(',', $roomType);
        Log::record('派对房间列表:操作人:' . $this->token['username'], 'partyRoomList');
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        $admin_url = config('config.admin_url');
        View::assign('list', $data);
        View::assign('channel_array', $channel_array);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('room_type_list', $room_type_list);
        View::assign('roomType', $roomType);
        View::assign('type', $type);
        View::assign('channels_id', $channels_id);
        View::assign('room_id', $room_id);
        View::assign('is_hot', $is_hot);
        View::assign('is_hide', $is_hide);
        View::assign('is_block', $is_block);
        View::assign('admin_url', $admin_url);
        return View::fetch('room/partyIndex');
    }

    /**
     * 设置房间默认背景
     * @dongbozhao
     * @2020-12-11 15:12
     */
    public function photoWallStart()
    {
        $id = Request::param('id');
        $type = Request::param('type');
        $room_mode = RoomModeModel::getInstance()->getModel()->where('room_mode', $type)->value('id');

        $is1 = PhotoWallModel::getInstance()->getModel()->where('id', $id)->save(['start' => 1]);
        if ($is1) {
            $is2 = PhotoWallModel::getInstance()->getModel()->where([['id', '<>', $id], ['room_mode', '=', $room_mode]])->save(['start' => 0]);
            if ($is2) {
                echo json_encode(['code' => 200, 'msg' => '修改成功']); //php编译join
            } else {
                echo json_encode(['code' => 500, 'msg' => '修改失败']); //php编译join
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']); //php编译join
        }

    }

    public function BackgroundImageOfType()
    {
        $limit = 5;
        $room_type_list = [];
        $room_type = json_decode($this->roomTypeList([['pid', '=', 0]]), 1);
        if ($room_type['code'] == 200) {
            $room_type_list = $room_type['data']['list'];
        }
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $type = Request::param('type');
        $where = [];
        $where_row = '';
        //搜索条件(类型,房间id)
        if ($type) {
            $where[] = ['a.room_mode', '=', $type];
        }
        //默认是普通用户房间
        $data = PhotoWallModel::getInstance()->getModel()->alias('a')->field('a.*,b.room_mode')->leftjoin('zb_room_mode b', 'a.room_mode = b.id')->where($where)->limit($offset, $limit)->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['image'] = getavatar($v['image']);
            if ($v['status'] == 3) {
                $data[$k]['del'] = '已刪除';
            } else {
                $data[$k]['del'] = '未删除';
            }
        }
        $page_array['page'] = $master_page;
        $count = PhotoWallModel::getInstance()->getModel()->alias('a')->leftjoin('zb_room_mode b', 'a.room_mode = b.id')->where($where)->count();
        $totalPage = ceil($count / $limit);
        $page_array['total_page'] = $totalPage;
        View::assign('list', $data);
        View::assign('room_type_list', $room_type_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('type', $type);
        return View::fetch('room/roomImage');
    }

    public function editRoomImageByType()
    {
        //获取数据
        $id = Request::param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        };
        $where['id'] = $id;
        $image = request()->file('background_image');
        $file_dir = "/background_image";
        $UploadOssFileCommon = new UploadOssFileCommon();
        $imageUrl = $UploadOssFileCommon->ossFile($image, $file_dir);
        try {
            if ($imageUrl) {
                $result = parse_url($imageUrl);
                $data = ['image' => $result['path']];
                $res = PhotoWallModel::getInstance()->getModel()->where($where)->save($data);
                Log::record('类型房间背景图编辑成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'roomOssFile');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            }
        } catch (OssException $e) {
            Log::record('类型背景图编辑失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'roomOssFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    public function delRoomImageByType()
    {
        $id = Request::param('id'); //获取被删除记录id
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        };
        $where['id'] = $id;
        $data['status'] = 3;
        $res = PhotoWallModel::getInstance()->getModel()->where($where)->save($data);
        if ($res) {
            Log::record('类型房间背景图删除成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'delRoomImage');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除成功]);
            die;
        } else {
            Log::record('类型房间背景图删除失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'delRoomImage');
            echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除失败]);
            die;
        }
    }

    //添加房间背景图
    public function addRoomImageByType()
    {
        $data = [
            'room_mode' => Request::param('type'),
            'id_del' => Request::param('is_del'),
            'is_vip' => Request::param('is_vip'),
            'status' => 2,
            'user_id' => $this->token['username'],
            'ctime' => time(),
        ];
        $res = PhotoWallModel::getInstance()->getModel()->save($data);
        if ($res) {
            Log::record('房间类型背景图添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addRoomImageByType');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('房间类型背景图添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addRoomImageByType');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    //房间背景选择列表
    public function roomBackgroundChoice()
    {
        $limit = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $room_id = Request::param('room_id');
        $roomInfo = LanguageroomModel::getInstance()->getModel($room_id)->where(array("id" => $room_id))->find();
        $where = [];
        //默认是普通用户房间
        $where[] = ['room_mode', '=', $roomInfo['room_type']];
        $where[] = ['status', '=', 2]; //通过审核
        $where[] = ['is_del', '=', 1]; //未删除
        $list = PhotoWallModel::getInstance()->getModel()->where($where)->limit($offset, $limit)->select()->toArray();
        foreach ($list as $k => $v) {
            $list[$k]['image'] = getavatar($v['image']);
        }
        $page_array['page'] = $master_page;
        $count = PhotoWallModel::getInstance()->getModel()->where($where)->count();
        $totalPage = ceil($count / $limit);
        $page_array['total_page'] = $totalPage;
        View::assign('list', $list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('room_id', $room_id);
        return View::fetch('room/roomImageChoice');
    }

    /**选择房间背景
     * @param $type_id  类型ID
     * @param $room_id  房间ID
     */
    public function roomChoiceType()
    {
        $room_id = Request::param('room_id');
        $type_id = Request::param('type_id');
        if (!$room_id || !$type_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        };
        $where['id'] = $room_id;
        //根据type_id获取图片地址
        $typeImage = PhotoWallModel::getInstance()->getModel()->where(array("id" => $type_id))->value("image");
        try {
            if ($typeImage) {
                $data = ['background_image' => $typeImage];
                $res = LanguageroomModel::getInstance()->getModel($room_id)->where($where)->save($data);
            }
            if ($res) {
                $str = ['msgId' => 2051, 'room_bg' => getavatar($typeImage)];
                $msg['msg'] = json_encode($str);
                $msg['roomId'] = (int) $room_id;
                $msg['toUserId'] = '0';
                $socket_url = config('config.socket_url');
                $msgData = json_encode($msg);
                $res = curlData($socket_url, $msgData, 'POST', 'json');
                Log::record("房间信息背景图选择发送参数-----" . $msgData, "info");
                Log::record("房间信息背景图选择发送-----" . $res, "info");
            }
            Log::record('房间背景图选择成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'roomChoiceType');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('房间背景图选择失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'roomChoiceType');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    //房间封禁列表
    public function roomCloseList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $room_id = $this->request->param('room_id', '', 'trim'); //房间id
        $where = [];

        if ($room_id > 0) {
            $where[] = ['room_id', '=', $room_id];
        }

        if ($this->request->param("isRequest") == 1) {
            $res = RoomCloseModel::getInstance()->getModel()->where($where)->page($page, $limit)->order("id desc")->select()->toArray();
            $roomids = array_column($res, "room_id");
            foreach ($res as $key => $item) {
                $res[$key]["room_name"] = LanguageroomModel::getInstance()->getModel($item['room_id'])->where('id', $item['room_id'])->value('room_name');

                $res[$key]["ctime"] = date('Y-m-d H:i:s', $item['ctime']);
                $res[$key]["utime"] = date('Y-m-d H:i:s', $item['utime']);
            }
            $count = RoomCloseModel::getInstance()->getModel()->where($where)->count();
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('timenode', RoomCloseModel::TIMENODE);
            View::assign('token', $this->request->param('token'));
            return View::fetch('room/roomclose');
        }

    }

    //房间内封禁编辑
    public function roomCloseEdit()
    {
        $room_id = $this->request->param('room_id', '', 'trim');
        $longtime = $this->request->param('longtime', 0, 'trim');
        $reason = $this->request->param('reason', '', 'trim');
        $currentTimestamp = time();
        $roominfo = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->find();
        if (empty($roominfo)) {
            echo json_encode(["code" => 1, "msg" => "房间ID不存在"]);
            exit;
        }

        $data = ["room_id" => $room_id, "operator" => $this->token['id'], "longtime" => $longtime, "reason" => $reason];
        if ($longtime == -1) {
            $data['end_time'] = -1; //永久封禁 目前的逻辑已经废弃永久的节点
        } else {
            $data['end_time'] = $currentTimestamp + $longtime;
        }
        try {
            //先判断是否存在记录中
            $haveRes = RoomCloseModel::getInstance()->getModel()->where("room_id", $room_id)->find();
            if ($haveRes) { //编辑
                $data['utime'] = $currentTimestamp;
                if ($haveRes['status'] == 1) {
                    echo json_encode(["code" => 1, "msg" => "此房间正处于封禁中"]);
                    exit;
                }
                $data['status'] = 0;
                RoomCloseModel::getInstance()->getModel()->where("id", $haveRes['id'])->save($data);
            } else { //新增
                $data['utime'] = $currentTimestamp;
                $data['ctime'] = $currentTimestamp;
                RoomCloseModel::getInstance()->getModel()->insert($data);
            }
            $format_reason = sprintf("您的房间因存在%s违规行为已被封禁,%s前无法进入", $data['reason'], date('Y-m-d H:i:s', $data['end_time']));
            $requestBody = ["roomId" => intval($room_id), "operator" => (int) $this->token['id'], "reasonInfo" => $format_reason];
            $requestBody['isBan'] = 1; //isBan 1是封禁 2解禁
            $resMsg = $this->banRoom($requestBody);
            $parseMsg = json_decode($resMsg, true);
            if ($parseMsg['code'] == 0) {
                RoomCloseModel::getInstance()->getModel()->where("room_id", $room_id)->save(['status' => 1]);
                //设置房间封禁状态
                LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->save(["is_block" => 1]);
                $user_id = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->value("user_id");
                $params = [
                    'userId' => (int) $user_id,
                    'msg' => $format_reason,
                ];
                $requestApi = config('config.app_api_url') . 'api/inner/game/sendAssistantMsg';
                curlData($requestApi, $params);
                Log::info(sprintf('roomcloseedit:success roomId=%d  data=%s resMsg=%s operator=%s', $room_id, json_encode($data), $resMsg, $this->token['username']));
                //添加封禁的业务逻辑
                echo json_encode(["code" => 0, "msg" => ""]);
                exit;
            } else {
                Log::info(sprintf('roomcloseedit:error roomId=%d  data=%s resMsg=%s operator=%s', $room_id, json_encode($data), $resMsg, $this->token['username']));
                //添加封禁的业务逻辑
                $desc = $parseMsg['desc'] ?? '操作异常';
                echo json_encode(["code" => 1, "msg" => $desc]);
                exit;
            }

        } catch (Throwable $e) {
            Log::info(sprintf("roomcloseedit:error=%s", $e->getMessage()));
            echo json_encode(["code" => 1, "msg" => "操作异常"]);
            exit;
        }
    }

    //房间解封
    public function roomCloseDel()
    {
        $room_id = $this->request->param('room_id', 0, 'trim');
        $currentTimestamp = time();
        $roomcloseInfo = RoomCloseModel::getInstance()->getModel()->where("room_id", $room_id)->find();
        if (empty($roomcloseInfo) || $roomcloseInfo['status'] != 1) {
            echo json_encode(["code" => 1, "msg" => "操作错误"]);
            exit;
        }
        $room_id = $roomcloseInfo['room_id'] ?? 0;

        $roominfo = LanguageroomModel::getInstance($room_id)->getModel()->where("id", $room_id)->find();
        if (empty($roominfo)) {
            echo json_encode(["code" => 1, "msg" => "房间ID不存在"]);
            exit;
        }

        try {
            $requestBody = ["roomId" => intval($room_id), "operator" => (int) $this->token['id'], "reasonInfo" => "您的房间已被解除封禁，请严格遵守平台规范", "isBan" => 2];
            $resMsg = $this->banRoom($requestBody);
            $parseMsg = json_decode($resMsg, true);
            if (isset($parseMsg['code']) && $parseMsg['code'] == 0) {
                RoomCloseModel::getInstance()->getModel()->where("room_id", $room_id)->save(['utime' => $currentTimestamp, "status" => 2, "operator" => $this->token['id']]);
                LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->save(["is_block" => 0]);
                $user_id = $roominfo['user_id'] ?? 0; //房主
                $params = [
                    'userId' => (int) $user_id,
                    'msg' => "您的房间已被解除封禁，请严格遵守平台规范",
                ];
                $requestApi = config('config.app_api_url') . 'api/inner/game/sendAssistantMsg';
                curlData($requestApi, $params);
                Log::info(sprintf('roomclosedel::success roomId=%d resMsg=%s operator=%s', $room_id, $resMsg, $this->token['username']));
                //添加封禁的业务逻辑
                echo json_encode(["code" => 0, "msg" => "解封成功"]);
                exit;
            } else {
                Log::info(sprintf('roomclosedel::error roomId=%d   resMsg=%s operator=%s', $room_id, $resMsg, $this->token['username']));
                //添加封禁的业务逻辑
                echo json_encode(["code" => 1, "msg" => "解封失败"]);
                exit;
            }

        } catch (Throwable $e) {
            Log::info(sprintf("roomclosedel::error=%s", $e->getMessage()));
            echo json_encode(["code" => 1, "msg" => "操作异常"]);
            exit;
        }
    }

    public function banRoom($body)
    {
        $data = json_encode($body);
        $url = config('config.socket_url_base') . 'iapi/banRoom';
        return curlData($url, $data, 'POST', 'json');
    }

    //房间隐藏列表
    public function roomHideList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $s_room_id = $this->request->param('s_room_id', 0, 'trim'); //房间id
        $room_id = $this->request->param('room_id', 0, 'trim'); //房间id
        $where = [];

        if ($s_room_id > 0) {
            $where[] = ['room_id', '=', $s_room_id];
        }

        if ($this->request->param("isRequest") == 1) {
            $res = RoomHideModel::getInstance()->getModel()->where($where)->page($page, $limit)->order("id desc")->select()->toArray();
            $roomids = array_column($res, "room_id");
            $roomList = LanguageroomModel::getInstance()->getWhereAllData([["id", "in", $roomids]], "id,room_name");
            $roomInfo = array_column($roomList, null, "id");
            foreach ($res as $key => $item) {
                $res[$key]["room_name"] = $roomInfo[$item['room_id']]['room_name'] ?? '';
                $res[$key]["start_time"] = date('Y-m-d H:i:s', $item['start_time'] ?? 0);
                $res[$key]["end_time"] = date('Y-m-d H:i:s', $item['end_time'] ?? 0);
            }

            $count = RoomHideModel::getInstance()->getModel()->where($where)->count();
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('room_id', $room_id);
            return View::fetch('room/roomhide');
        }

    }

    //房间隐藏编辑
    public function roomHideEdit()
    {
        $room_id = $this->request->param('room_id', '', 'trim');
        $start_time = $this->request->param('start_time', 0, 'trim');
        $end_time = $this->request->param('end_time', 0, 'trim');
        $currentTimestamp = time();
        $roominfo = LanguageroomModel::getInstance()->getModel($room_id)->where("id", $room_id)->find();
        if (empty($roominfo)) {
            echo json_encode(["code" => 1, "msg" => "房间ID不存在"]);
            exit;
        }

        if (strtotime($start_time) >= strtotime($end_time)) {
            echo json_encode(["code" => 1, "msg" => "日期设置错误"]);
            exit;
        }

        $data = ["room_id" => $room_id, "operator" => $this->token['id'], "start_time" => strtotime($start_time), "end_time" => strtotime($end_time)];
        try {
            //先判断是否存在记录中
            $haveRes = RoomHideModel::getInstance()->getModel()->where("room_id", $room_id)->find();
            if ($haveRes) { //编辑
                RoomHideModel::getInstance()->getModel()->where("id", $haveRes['id'])->save($data);
                Log::info(sprintf("roomhide:edit:success=%s,operator=%s", json_encode($data), $this->token['username']));
            } else { //新增
                $data['ctime'] = $currentTimestamp;
                RoomHideModel::getInstance()->getModel()->insert($data);
                Log::info(sprintf("roomhide:add:success=%s,operator=%s", json_encode($data), $this->token['username']));
            }

            echo json_encode(["code" => 0, "msg" => ""]);
            exit;

        } catch (Throwable $e) {
            Log::info(sprintf("roomhide:error=%s,operator=%s", $e->getMessage()));
            echo json_encode(["code" => 1, "msg" => "操作异常"]);
            exit;
        }
    }

    //房间隐藏删除
    public function roomHideDel()
    {
        $room_id = $this->request->param('room_id', 0, 'trim');
        $haveRes = RoomHideModel::getInstance()->getModel()->where("room_id", $room_id)->find();
        if (empty($haveRes)) {
            echo json_encode(["code" => 1, "msg" => "操作错误"]);
            exit;
        }
        $room_id = $haveRes['room_id'] ?? 0;
        $roominfo = LanguageroomModel::getInstance($room_id)->getModel()->where("id", $room_id)->find();
        if (empty($roominfo)) {
            echo json_encode(["code" => 1, "msg" => "房间ID不存在"]);
            exit;
        }

        try {
            unset($haveRes['id']);
            unset($haveRes['success_time']);
            $params = ['room_id' => (int)$room_id, 'profile' => json_encode(["is_hide" => 0])];
            BiRoomHideLogModel::getInstance()->getModel()->transaction(function()use($haveRes,$room_id){
                BiRoomHideLogModel::getInstance()->getModel()->insert($haveRes->toArray());
                RoomHideModel::getInstance()->getModel()->where("room_id", $room_id)->delete();
            });
            //因为有些房间尚未开始所以请求api放在下面
            ApiService::getInstance()->curlApi(ApiUrlConfig::$update_roominfo, $params, true);
            Log::info(sprintf('roomhidedel:success roomId=%d  operator=%s', $room_id, $this->token['username']));
            echo json_encode(["code" => 0, "msg" => "删除成功"]);
            exit;
        } catch (Throwable $e) {
            Log::info(sprintf("roomhidedel:error=%s", $e->getMessage().$e->getFile().$e->getLine()));
            echo json_encode(["code" => 0, "msg" => "操作完成"]);
            exit;
        }
    }

    /*新的房间消费明细*/
    public function roomConsumeDetail()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $demo = $this->request->param("demo", '', 'trim');
        $export = $this->request->param("export", 0, 'trim');
        $dateFormat = explode(" - ", $demo);
        $date_b = $dateFormat[0] ?? '';
        $date_e = $dateFormat[1] ?? '';
        $room_id = $this->request->param('room_id', 0, 'trim'); //类型多选 getuipush,xxxx,xxx

        $where = [['type', '=', 1]];

        if ($date_b && $date_e) {
            $where[] = ['date', '>=', $date_b];
            $where[] = ['date', '<', date('Y-m-d', strtotime("+1days", strtotime($date_e)))];
        }

        if ($room_id > 0) {
            $where[] = ["room_id", "=", $room_id];
        }

        if ($this->request->param("isRequest") == 1) {

            $count = Db::table("bi_days_user_gift_datas_bysend_type")
                ->where($where)
                ->count(); //发送量

            if ($export == 1) {
                $res = Db::table("bi_days_user_gift_datas_bysend_type")
                    ->where($where)
                    ->select()->toArray(); //房间送礼
            } else {
                $res = Db::table("bi_days_user_gift_datas_bysend_type")
                    ->where($where)
                    ->page($page, $limit)
                    ->select()->toArray(); //房间送礼
            }

            $room_ids = array_column($res, "room_id");
            $giftJson = ConfigModel::getInstance()->getModel()->where('name', "gift_conf")->value('json');
            $giftList = json_decode($giftJson, true);
            $giftListById = array_column($giftList, null, "giftId");
            $roomList = LanguageroomModel::getInstance()->getWhereAllData([["id", "in", $room_ids]], "id,room_name");
            $roomListById = array_column($roomList, null, "id");
            $sendTypeList = ['1' => '直送', 2 => '背包送', '3' => '礼物盒子', 4 => '小火锅'];

            foreach ($res as $key => $item) {
                $res[$key]['gift_name'] = $giftListById[$item['gift_id']]['name'] ?? '';
                $res[$key]['room_name'] = $roomListById[$item['room_id']]['room_name'] ?? '';
                $res[$key]['sendtype'] = $sendTypeList[$item['send_type']] ?? '';
            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('demo', $demo);
            View::assign('room_id', $room_id);
            return View::fetch('room/roomconsumedetail');
        }
    }

}
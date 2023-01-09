<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\BiRoomEveryroomConsume;
use app\admin\model\LanguageroomModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\UserAssetLogModel;
use app\admin\model\UserOnlineRoomCensusModel;
use app\admin\service\ExportExcelService;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class RoomCoinController extends AdminBaseController
{
    /*
     * 房间消费统计列表
     * @param string $token　token值
     * @param string $limit  limit条数
     * @param string $page  page分页
     */
    public function roomcoinList()
    {
        try {
            $limit = 10;
            $master_page = $this->request->param('page', 1);
            $offset = ($master_page - 1) * $limit;
            $demo = $this->request->param('demo', $this->default_date);
            list($start_time, $end_time) = getBetweenDate($demo);
            $room_id = Request::param('room_id', 0); //房间id
            $gonghui_id = Request::param('gonghui_id'); //公会id
            $daochu = Request::param('daochu');
            $isgh = Request::param('isgh');
            $isgh = $isgh ? $isgh : 1;
            $roomWhere=[];

            if($daochu == 1){
                $limit = 20000;
            }


            if (!empty($gonghui_id)) {
                $roomid= LanguageroomModel::getInstance()->getModel()->where("guild_id",$gonghui_id)->column("id");
                $roomWhere[] = ['room_id', 'in', $roomid];
            }


            if ($room_id) {
                $roomWhere[] = ['room_id', '=', $room_id];
            }

            $roomWhere[] = ["date", ">=", $start_time];
            $roomWhere[] = ["date", "<", $end_time];


            $roomidList = BiRoomEveryroomConsume::getInstance()->where($roomWhere)->distinct("true")->field("room_id")->select()->toArray();
            $roomidList = array_column($roomidList,"room_id");

            $roomids = array_slice($roomidList,$offset,$limit);
            $count = count($roomidList);


            $roomInfo = LanguageroomModel::getInstance()->getModel()
                ->field("id,guild_id,user_id,room_name")
                ->where([["id","in",$roomids]])
                ->select()->toArray();

            $guiids = array_column($roomInfo, "guild_id");
            $roominfoById = array_column($roomInfo, NULL, "id");

            $memberGuildInfo = MemberGuildModel::getInstance()->getModel()->where("id", "in", $guiids)
                ->field("id,nickname,user_id,phone")
                ->select()->toArray();

            $memberGuildById = array_column($memberGuildInfo, NULL, "id");


            $fields = "";
            $fields .= "room_id,";
            $fields .= "sum(case when send_type=1 || send_type =3 then  reward_amount else 0 end)  as 'othercoin',";
            $fields .= "sum(case when send_type=2 then  reward_amount else 0 end)  as 'packagecoin',";
            $fields .= "sum(case when send_type=1 || send_type=2 || send_type=3  then  reward_amount else 0 end)  as 'totailcoin'";
            $condition[] = ["room_id", "in", $roomids];
            $condition[] = ["date", ">=", $start_time];
            $condition[] = ["date", "<", $end_time];

            $data = BiRoomEveryroomConsume::getInstance()->where($condition)->field($fields)->group("room_id")->select()->toArray();


            foreach ($data as $key => $item) {
                $guiid = $roominfoById[$item['room_id']]['guild_id'] ?? 0;
                $data[$key]['ghuid'] = $guiid;
                $data[$key]['phone'] = $memberGuildById[$guiid]['phone'] ?? '';
                $data[$key]['ghname'] = $memberGuildById[$guiid]['nickname'] ?? '';
                $data[$key]['user_id'] = $roominfoById[$item['room_id']]['user_id'] ?? 0;
                $data[$key]['room_name'] = $roominfoById[$item['room_id']]['room_name'] ?? '';
            }

        /*
         $assetWhere[] = ['date', '>=', $start_time];
         $assetWhere[] = ['date', '<', $end_time];
         $online_room_where[] = ['date', '>=', $start_time];
         $online_room_where[] = ['date', '<', $end_time];

         $online_room_data = UserOnlineRoomCensusModel::getInstance()->getOnlineUsersByRoom($online_room_where, "room_id,GROUP_CONCAT(distinct(user_id),',') uids", 'room_id');

         if ($daochu == 1) {
             $src = UserAssetLogModel::getInstance()->getRoomcoinNew($roomWhere, $assetWhere, 0, 10000, $online_room_data, $room_id, true);
         } else {
             $src = UserAssetLogModel::getInstance()->getRoomcoinNew($roomWhere, $assetWhere, $offset, $limit, $online_room_data, $room_id);
         }
         $count = $src['count'];
         $data = $src['data'];
         */

            if ($daochu == 1) {
                $headerArray = [
                    'ghuid' => '公会长id',
                    'phone' => '公会长账号',
                    'ghname' => '公会名',
                    'room_id' => '房间Id',
                    'user_id' => '房主Id',
                    'room_name' => '房间名称',
                    'totailcoin' => '房间总消费',
                    'packagecoin' => '房间背包消费',
                    'othercoin' => '非背包消费',
                    //'online_count' => '进厅人数',
                ];
                ExportExcelService::getInstance()->export($data, $headerArray);
            }

            $totalPage = ceil($count / $limit);
            Log::record('房间统计列表:操作人:' . $this->token['username'], 'roomcoinlist');
            $page_array['page'] = $master_page;
            $page_array['total_page'] = $totalPage;
            $admin_url = config('config.admin_url');
            //获取当前时间
            $search_end_time = date("Y-m-d", time());

            //查公会
            $gonghui = MemberGuildModel::getInstance()->getghData(['status' => 1], "id,nickname");

            View::assign('list', $data);
            View::assign('user_role_menu', $this->user_role_menu);
            View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
            View::assign('token', $this->request->param('token'));
            View::assign('page', $page_array);
            View::assign('room_id', $room_id);
            View::assign('demo', $demo);
            View::assign('admin_url', $admin_url);
            View::assign('search_end_time', $search_end_time);
            View::assign('gonghui', $gonghui);
            View::assign('gonghui_id', $gonghui_id);
            View::assign('isgh', $isgh);

            return View::fetch('dataManagement/roomcoinlist');
        } catch (\Throwable $e) {
            echo $e->getMessage() . $e->getLine() . $e->getFile();
        }
    }

    public function getEnterRoomUsers()
    {
        try {
            $demo = $this->request->param('demo', $this->default_date);
            list($start_time, $end_time) = getBetweenDate($demo);
            $room_id = Request::param('room_id', 0); //房间id
            $gonghui_id = Request::param('gonghui_id'); //公会id

            $online_room_list = UserOnlineRoomCensusModel::getInstance()->getWhereAllData([['date', '>=', $start_time], ['date', '<', $end_time]], 'room_id,user_id', 'room_id,user_id');

            $online_room_data = $online_room_info = [];
            if ($online_room_list) {
                foreach ($online_room_list as $item) {
                    $online_room_info[$item['room_id']][] = $item['user_id'];
                }

                foreach ($online_room_info as $key => $uid_list) {
                    $online_room_arr['room_id'] = $key;
                    $online_room_arr['uids'] = implode(',', $uid_list);
                    $online_room_arr['count'] = count($uid_list);
                    $online_room_data[$key] = $online_room_arr;
                }
            }

        } catch (\Throwable $e) {
            echo $e->getMessage() . $e->getLine() . $e->getFile();
        }
    }


    public function enterRoomUserList()
    {
        try {
            $room_id = Request::param('room_id', 0); //房间id
            $demo = $this->request->param('demo', $this->default_date);
            list($start_time, $end_time) = getBetweenDate($demo);
            $uids = UserOnlineRoomCensusModel::getInstance()
                ->getWhereAllData([['date', '>=', $start_time], ['date', '<', $end_time],["room_id","=",$room_id]], 'user_id');
            $uids = array_column($uids,"user_id");
            $uids = array_unique($uids);
            $returnRes=["data"=>$uids,"count"=>count($uids)];
            return rjson($returnRes, 200, '成功');
        } catch (\Throwable $e) {
            Log::error($e->getMessage() . $e->getLine() . $e->getFile());
        }
    }
}

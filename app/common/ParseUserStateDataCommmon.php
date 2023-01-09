<?php

namespace app\common;

use app\admin\model\BiMessageEnterRoomModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\admin\model\PromotionRoomConfModel;
use app\core\mysql\Sharding;
use think\facade\Db;
use think\facade\Log;

class ParseUserStateDataCommmon
{
    public static $instance = NULL;
    const LIMIT = 1000;

    public static function getInstance()
    {

        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /*
 * 登陆数据
 */
    public function getUserInfo($uids)
    {
        $fields = "";
        $fields .= "id,id as uid, mobile,register_time,case when register_channel = 'appStore' then 'ios' else 'android' end os,";
        $fields .= "register_ip,register_channel,qopenid,wxopenid,appleid,invitcode,regist_version as version,deviceId,idfa,source,imei";
        $models = MemberModel::getInstance()->getModels($uids);
        $res = [];
        foreach ($models as $model) {
            $data = $model->getModel()->where('id', 'in', $model->getList())
                ->field($fields)
                ->select()
                ->toArray();
            $res = array_merge($res, $data);
        }
        return $res;

    }


    public function getUserBaseInfo($uids, $readmaster = false): array
    {
        // id as uid, mobile,register_time,case when register_channel = 'appStore' then 'ios' else 'android' end os,
        //         register_ip,register_channel,qopenid,wxopenid,appleid,invitcode,regist_version as version,deviceId,idfa,source
        //init初始化获取推广码渠道
        $user_data = [];
        if (!empty($uids)) {
            //重新获取用户的信息确保能获取用户的推广码渠道等
            $users_info = $this->getUserInfo($uids);
            if (empty($users_info)) return []; //防止意外数据
            //以用户id为维度的用户信息
            $userInfoByid = array_column($users_info, null, 'id');

            $market_channel_map = $this->getMarketChannel();
            $promote_channel_map = $this->getPromoteChannel();

            foreach ($uids as $uid) {
                //获取用户渠道信息
                $user_info = $userInfoByid[$uid] ?? [];
                if (empty($user_info)) {
                    Log::error("dealUserJsonData:getuserinfo:为空 userid:{$uid} time:" . date('Y-m-d H:i:s'));
                    continue;
                }
                $invitcode = $user_info['invitcode'] ?: '';
                $promote_channel = 0;
                if (isset($market_channel_map[$invitcode])) {
                    $promote_channel = $market_channel_map[$invitcode];
                }

                if (isset($promote_channel_map[$invitcode])) {
                    $promote_channel = $promote_channel_map[$invitcode];
                }

                $insert_data['promote_channel'] = $promote_channel;

                $insert_data['source'] = isset($user_info['source']) ? $user_info['source'] : '';

                if (!isset($user_info['register_channel'])
                    || $user_info['register_channel'] == '0'
                    || $user_info['register_channel'] == ''
                    || $user_info['register_channel'] == '1'
                    || empty($user_info['register_channel'])
                ) {
                    $register_channel = $this->getLoginChannel($uid);
                    $register_channel = empty($register_channel) ? '0' : $register_channel;
                    $register_channel_map[$uid] = $register_channel;
                } else {
                    $register_channel = $user_info['register_channel'];
                }
                $insert_data['register_channel'] = $register_channel;
                $insert_data['register_time'] = isset($user_info['register_time']) ? $user_info['register_time'] : '';
                $insert_data['register_ip'] = isset($user_info['register_ip']) ? $user_info['register_ip'] : '';
                $insert_data['invitcode'] = isset($user_info['invitcode']) ? $user_info['invitcode'] : '';
                $insert_data['deviceId'] = isset($user_info['deviceId']) ? $user_info['deviceId'] : '';
                $insert_data['idfa'] = isset($user_info['idfa']) ? $user_info['idfa'] : '';
                $insert_data['imei'] = isset($user_info['imei']) ? $user_info['imei'] : '';
                $insert_data['uid'] = $uid;
                $user_data[$uid] = $insert_data;
            }
        }
        return $user_data;
    }


    public function getLoginChannel($uid)
    {
        return LogindetailModel::getInstance()->getModel($uid)
            ->where('user_id', $uid)
            ->where('channel', '<>', '0')
            ->where('channel', '<>', '')
            ->order('id', 'asc')
            ->limit(1)
            ->value('channel');
    }


    /*
 * 获取推广码渠道
 */
    protected function getMarketChannel()
    {
        return MarketChannelModel::getInstance()->getModel()->column('id', 'invitcode');
    }

    /*
     * 获取推广码渠道
     */
    protected function getPromoteChannel()
    {
        return PromotionRoomConfModel::getInstance()->getModel()->column('id');
    }


    /**
     * $interval_data格式
     * ["register"=["userid"=>[],"user_id"=>[]]
     * ["login"=["userid"=>[],"user_id"=>[]]
     *
     * 返回带json_data数据数据
     * [
     * "userid"=>["register_channel"=>"xxxx","register_time"=>"xxx","date"=>"xxx","json_data"=>"xxxxx","interval_time"=>"xxxxx"],
     * "userid"=>["register_channel"=>"xxxx","register_time"=>"xxx","date"=>"xxx","json_data"=>"xxxxx","interval_time"=>"xxxxx"]
     * ]
     *
     */
    public function dealTodayData($interval_time, array $interval_data): array
    {
        try {
            Log::info('dealTodayData::json_data====>{json_data}', ['json_data' => json_encode($interval_data)]);
            return $this->dealUserJsonData($interval_time, $interval_data);
        } catch (\Exception $e) {
            Log::error(sprintf('CalculateUserStatsByIdCommand::dealTodayData ex=%d:%s trace=%s', $e->getCode(), $e->getMessage(), $e->getTraceAsString()));
        }
    }


    /*
 * 充值数据
 */
    public function dealChargeData($orderno)
    {
        return ChargedetailModel::getInstance()->getModel()
            ->where('orderno', '=', $orderno)
            ->field('uid,channel,type,rmb * 10 as amount,1 as count') //单位:豆
            ->find();
    }


    /**
     * @description: 获取时间
     * @param {time}
     * @return {*}
     */
    public function getIntervalTime($time)
    {
        $time = date("Y-m-d H:i:00", strtotime($time));
        $divide_time = strtotime($time) % 300;
        $interval_time_5mins = date("Y-m-d H:i:s", strtotime($time) - $divide_time);

        return $interval_time_5mins;
    }


    /*
     * 首冲数据
     */
    public function dealFirstChargeData($uid)
    {
        if (!empty($uid)) {
            // $sql = "select uid,min(addtime) as firstChargeTime from zb_chargedetail where uid = $uid and status in (1,2) order by addtime asc limit 1";
            return ChargedetailModel::getInstance()->getModel()->field("uid,addtime")
                ->force("id_uid")
                ->where("uid", "=", $uid)
                ->where("status", "in", [1, 2])
                ->order("addtime asc")
                ->find();
        }
        return [];
    }


    public function identifySplit($identify)
    {
        return explode("#", $identify);
    }

    public function identifyMerge($param1, $param2)
    {
        return $param1 . "#" . $param2;
    }


    /**
     * 获取时间节点列表
     * @param $start
     * @return array
     * $flag=true  $start=2021-01-01 $enddate=2021-01-02  return  ["2021-01-01","2021-01-02"]
     * $flag=false  $start=2021-01-01 $enddate=2021-01-02 return  ["2021-01-01"]
     */
    public function getTimeNode($start, $enddate, $flag = false)
    {
        if (empty($start) || empty($enddate)) {
            return [];
        }
        if ($enddate) {
            $days = (strtotime($enddate) - strtotime($start)) / (24 * 3600);
        } else {
            $days = (strtotime(date('Y-m-d')) - strtotime($start)) / (24 * 3600);
        }

        $list = [];
        if ($flag) {
            for ($i = 0; $i <= $days; $i++) {
                $list[] = date('Y-m-d', strtotime($start . " +$i days"));
            }
        } else {

            for ($i = 0; $i < $days; $i++) {
                $list[] = date('Y-m-d', strtotime($start . " +$i days"));
            }

        }
        return $list;
    }


    /**
     * 这是日期
     * @param $date_b
     * @param $date_e
     * @return false|string
     */
    public function getMonthTableName($date_b, $date_e)
    {
        $getInstance = ""; //读分表的标识符
        $b_m = date('Ym', strtotime($date_b));
        $e_m = date('Ym', strtotime($date_e));
        if ($b_m == $e_m) {
            $getInstance = date('Ym', strtotime($date_b));
        } elseif ($b_m == date('Ym', (strtotime($date_e) - 1))) { //判断是否是月初第一天
            $getInstance = date('Ym', strtotime($date_b));
        }
        if ($getInstance == '') {
            $getInstance = $b_m;
        }
        return $getInstance;
    }


    //相除
    public function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }


    function getMonthTableNameList($date_b, $date_e)
    {
        $getInstances = []; //读分表的标识符
        while (true) {
            $b_m = date('Ym', strtotime($date_b));
            $e_m = date('Ym', strtotime($date_e));
            if ($b_m == $e_m || $b_m == date('Ym', (strtotime($date_e) - 1))) {
                $getInstances[] = $b_m;
                break;
            } elseif ($e_m > $b_m) {
                $getInstances[] = $b_m;
                $date_b = date('Y-m-d', strtotime($date_b . "+1month"));
            }
        }
        return $getInstances;
    }


    public function setGroupConcatLength($dbname = 'bi', $size = 1024000)
    {
        Sharding::getInstance()->getConnectModel($dbname, "")->execute("SET SESSION group_concat_max_len =" . $size);
    }


    //获取用户的进房间列表
    public function getEnterRoomUsers($start, $end, $room_id)
    {
        $enterUserList = [];
        $enterUserList = $this->getEnterRoomUsersByApi($start, $end, $room_id);
        if (empty($enterUserList)) {
            $enterUserList = $this->getEnterRoomUsersByDb($start, $end, $room_id);
        }
        return $enterUserList;
    }


    //通过api来获取进房间的用户列表
    private function getEnterRoomUsersByApi($start, $end, $room_id)
    {
        $userList = [];
        $requestBody = ['room_id' => $room_id, 'start_time' => $start, 'end_time' => $end];
        $res = curlData(config('config.enter_room_url'), $requestBody);
        if($res){
            $res = json_decode($res, true);
        }

        if (isset($res['code']) && $res['code'] == 0) {
            $userList = $res['data']['user_list'] ?? [];
        }
        return $userList;
    }

    //通过数据库来获取进房间的用户列表
    private function getEnterRoomUsersByDb($start, $end, $room_id)
    {
        $instance = $this->getMonthTableName($start, $end);
        $where = [];
        $where[] = ['room_id', '=', $room_id];
        $where[] = ['ctime', '>=', strtotime($start)];
        $where[] = ['ctime', '<', strtotime($end)];
        $data = BiMessageEnterRoomModel::getInstance($instance)->getModel()->where($where)->column("uid");
        return array_values($data);
    }

}





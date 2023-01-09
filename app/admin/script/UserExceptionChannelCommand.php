<?php

namespace app\admin\script;

use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BlackDataModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MarketChannelModel;
use app\admin\model\MemberModel;
use app\common\ParseUserStateByUniqkey;
use app\common\ParseUserStateDataCommmon;
use app\common\RedisCommon;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Log;

ini_set('set_time_limit', 0);
ini_set('memory_limit', -1);

class UserExceptionChannelCommand extends Command
{
    const COMMAND_NAME = "UserExceptionChannelCommand";
    const SAVETABLENAME = 'user_exception_channel';

    //结算的用户注册的时间段
    const BEGINDATE = '2022-07-01';
    const ENDDATE = '2022-08-01';

    //这是封禁的时间段用户
    const BLACKUSER_BEGINDATE = '2022-06-01';
    const BLACKUSER_ENDDATE = '2022-08-01';
    const NOTLOGINHOURS =  24; //24小时
    protected $redis = NULL;
    protected $USERMARK = [];  //用户 已经处理
    protected $IDFAMARK = [];  //IDFA 已经处理
    protected $IMEIMARK = [];  //IMEI 已经处理
    protected $DEVICEIDMARK = []; //DEVICEID  已经处理
    protected $LOGINIPMARK = [];  // IP 已经处理


    protected function configure()
    {
        $this->setName(SELF::COMMAND_NAME)
            ->addArgument('list', Argument::OPTIONAL, "list", false)
            ->setDescription(SELF::COMMAND_NAME);
    }


    public function execute(Input $input, Output $output)
    {
        try {
            $this->redis = RedisCommon::getInstance()->getRedis(['select' => 8]);
            ParseUserStateDataCommmon::getInstance()->setGroupConcatLength();
            $blackUids = $this->getBlackExceptionUsers();
            $deviceUids = $this->getDeviceIdOrImeiOrIdfaExceptionUsers();
            $chargeLessEqTenUids = $this->getchargeLessEqTenUids(); //充值累计小于等于10元的
            $muids = array_unique(array_merge($blackUids, $deviceUids, $chargeLessEqTenUids));
            $memberModels = MemberModel::getInstance()->getModels($muids);
            $zbinvitcode = 2146;//作弊渠道的invitcode
            foreach ($memberModels as $memberModel) {
                $insertData = [];
                $memberInfo = $memberModel->getModel()->where("id", "in", $memberModel->getList())->field("id,invitcode")->select()->toArray();
                foreach ($memberInfo as $minfo) {
                    $insertData[] = ["uid" => $minfo['id'], "type" => 2, "invitcode" => $zbinvitcode, "invitcode_old" => $minfo['invitcode'], "create_time" => time()];
                }
                ParseUserStateByUniqkey::getInstance()->insertOrUpdateMul($insertData, "bi_member_invitcode_log", ["id", "uid"]);
            }
        } catch (\Throwable $e) {
            Log::error(SELF::COMMAND_NAME . ":error:" . $e->getMessage() . $e->getFile() . $e->getLine());
        }

    }


    /**
     * 封禁用户的裂变
     */
    public function getBlackExceptionUsers()
    {
        $this->redis->del(SELF::SAVETABLENAME); //初始化数据
        $userList = $this->getuserid();
        //未处理用户 用:0表示  已处理用:>1
        foreach ($userList as $uid) {
            $this->redis->zadd(SELF::SAVETABLENAME, 0, $uid);
        }

        while ($res = $this->redis->ZRANGEBYSCORE(SELF::SAVETABLENAME, 0, 0, ['limit' => [0, 1000], 'WITHSCORES' => true])) {
            foreach ($res as $exceptionUid => $score) {

                if (in_array($exceptionUid, $this->USERMARK)) {
                    //表示用户已经处理
                    $this->redis->ZINCRBY(SELF::SAVETABLENAME, 1, $exceptionUid);
                    continue;
                }
                //获取此用户的所有的登录信息的设备信息
                $loginDetail = $this->getLoginDetail($exceptionUid);
                if (!empty($loginDetail['idfa'])) {
                    foreach ($loginDetail['idfa'] as $idfa) {
                        $uids = $this->findUserByWhere([["idfa", "=", $idfa]]);
                        foreach ($uids as $uid) {
                            if ($this->redis->zadd(SELF::SAVETABLENAME, 0, $uid)) {
                                Log::info(SELF::COMMAND_NAME . ":where:" . $uid . ":idfa:" . $idfa . ":parent:" . $exceptionUid);
                            }
                            $this->IDFAMARK[] = $idfa;

                        }
                    }
                }

                if (!empty($loginDetail['imei'])) {
                    foreach ($loginDetail['imei'] as $imei) {
                        $uids = $this->findUserByWhere([["imei", "=", $imei]]);
                        foreach ($uids as $uid) {
                            if ($this->redis->zadd(SELF::SAVETABLENAME, 0, $uid)) {
                                Log::info(SELF::COMMAND_NAME . ":where:" . $uid . ":imei:" . $imei . ":parent:" . $exceptionUid);
                            }
                            $this->IMEIMARK[] = $imei;
                        }
                    }
                }

                if (!empty($loginDetail['deviceid'])) {
                    foreach ($loginDetail['deviceid'] as $deviceid) {
                        $uids = $this->findUserByWhere([["deviceid", "=", $deviceid]]);
                        foreach ($uids as $uid) {
                            if ($this->redis->zadd(SELF::SAVETABLENAME, 0, $uid)) {
                                Log::info(SELF::COMMAND_NAME . ":where:" . $uid . ":deviceid:" . $deviceid . ":parent:" . $exceptionUid);
                            }
                            $this->DEVICEIDMARK[] = $deviceid;
                        }
                    }
                }

                if (!empty($loginDetail['login_ip'])) {
                    foreach ($loginDetail['login_ip'] as $ip) {
                        $uids = $this->findUserByWhere([["register_ip", "=", $ip]]);
                        foreach ($uids as $uid) {
                            if ($this->redis->zadd(SELF::SAVETABLENAME, 0, $uid)) {
                                Log::info(SELF::COMMAND_NAME . ":where:" . $uid . ":login_ip:" . $ip . ":parent:" . $exceptionUid);
                            }
                            $this->LOGINIPMARK[] = $ip;
                        }
                    }
                }

                $this->USERMARK[] = $exceptionUid;
                $this->redis->ZINCRBY(SELF::SAVETABLENAME, 1, $exceptionUid);
            }
        }

        return $this->redis->ZRANGEBYSCORE(SELF::SAVETABLENAME, 0, '+inf');
    }


    public function getDeviceIdOrImeiOrIdfaExceptionUsers()
    {

        $idfaList = BiDaysUserChargeModel::getInstance()->getModel()
            ->where("idfa", "<>", "")
            ->where("idfa", "<>", "00000000-0000-0000-0000-000000000000")
            ->where("date", "<", SELF::BEGINDATE)
            ->where("register_time", "<", SELF::BEGINDATE)
            ->distinct(true)->column("idfa");

        $deviceList = BiDaysUserChargeModel::getInstance()->getModel()
            ->where("deviceid", "<>", "")
            ->where("date", "<", SELF::BEGINDATE)
            ->where("register_time", "<", SELF::BEGINDATE)
            ->distinct(true)->column("deviceid");

        $imeiList = BiDaysUserChargeModel::getInstance()->getModel()
            ->where("imei", "<>", "")
            ->where("imei", "<>", "unknown")
            ->where("date", "<", SELF::BEGINDATE)
            ->where("register_time", "<", SELF::BEGINDATE)
            ->distinct(true)->column("imei");


        $chargeUids = $this->getCurrentMonthChargeUser();
        $findUids = [];


        foreach ($chargeUids as $k => $item) {

            echo $k . PHP_EOL;
            if ($item['deviceid'] != '') {
                if (array_search($item['deviceid'], $deviceList) !== false) {
                    $findUids[] = $item['uid'];
                    continue;
                }
            }

            if ($item['imei'] != '' && $item['imei'] != 'unknown') {
                if (array_search($item['imei'], $imeiList) !== false) {
                    $findUids[] = $item['uid'];
                    continue;
                }
            }

            if ($item['idfa'] != '' && $item['idfa'] != '00000000-0000-0000-0000-000000000000') {
                if (array_search($item['idfa'], $idfaList) !== false) {
                    $findUids[] = $item['uid'];
                    continue;
                }
            }
        }

        return array_unique($findUids);

    }


    //获取用户的登录信息的设备
    public function getLoginDetail($uid)
    {
        $returnRes = ['idfa' => [], 'imei' => [], 'deviceid' => [], 'login_ip' => []];
        $detailRes = LogindetailModel::getInstance()->getModel($uid)
            ->field("device_id,imei,idfa,login_ip")
            ->where("user_id", $uid)->select()->toArray();
        foreach ($detailRes as $item) {
            $deviceid = $item['device_id'];
            $imei = $item['imei'];
            $idfa = $item['idfa'];
            $loginip = $item['login_ip'];

            if ($deviceid <> '' && (!in_array($deviceid, $this->DEVICEIDMARK)) && (!in_array($deviceid, $returnRes['deviceid']))) {
                $returnRes['deviceid'][] = $deviceid;
            }

            if ($imei <> '' && $imei <> 'unknown' && (!in_array($imei, $this->IMEIMARK)) && (!in_array($imei, $returnRes['imei']))) {
                $returnRes['imei'][] = $imei;
            }

            if ($idfa <> '' && $idfa <> '00000000-0000-0000-0000-000000000000' && (!in_array($idfa, $this->IDFAMARK)) && (!in_array($idfa, $returnRes['idfa']))) {
                $returnRes['idfa'][] = $idfa;
            }

            if ($loginip <> '' && (!in_array($loginip, $this->LOGINIPMARK)) && (!in_array($loginip, $returnRes['login_ip']))) {
                $returnRes['login_ip'][] = $loginip;
            }

        }
        return $returnRes;
    }

    //根据 deviceid  idfa  imei 需要用户
    private function findUserByWhere($where)
    {
        $condition = [
            ["register_time", ">=", SELF::BEGINDATE . ' 00:00:00'],
            ["register_time", "<", SELF::ENDDATE . ' 00:00:00'],
            ["date", ">=", SELF::BEGINDATE],
            ["date", "<", SELF::ENDDATE . ' 00:00:00'],
            ["promote_channel", ">", 0],
            ["promote_channel", "<", 800000],
        ];

        $where = array_merge($condition, $where);
        return BiDaysUserChargeModel::getInstance()->getModel()->where($where)->column("uid");
    }


    //封禁用户并且充值的1v1用户
    private function getuserid()
    {
        $start_timestamp = strtotime(SELF::BLACKUSER_BEGINDATE);
        $end_timestamp = strtotime(SELF::BLACKUSER_ENDDATE);

        //封禁的用户
        $blackuids = BlackDataModel::getInstance()->getModel()->where(
            [
                ["blacks_time", ">=", $start_timestamp],
                ["blacks_time", "<", $end_timestamp],
                ["user_id", ">", 0],
                ["time", "=", -1],
            ]
        )->column("user_id");

        //封禁用户中存在充值用户
        $userList = BiDaysUserChargeModel::getInstance()->getModel()
            ->where("uid", "in", $blackuids)
            ->where("register_time", ">=", self::BEGINDATE . ' 00:00:00')
            ->where("register_time", "<", self::ENDDATE . ' 00:00:00')
            ->where("date", ">=", self::BEGINDATE)
            ->where("date", "<", self::ENDDATE)
            ->where("promote_channel", ">", 0)
            ->where('promote_channel', '<', 800000)
            ->distinct(true)
            ->column("uid");
        return $userList;
    }


    //获取目标月份充值的用户列表
    public function getCurrentMonthChargeUser()
    {
        $marketChannelIds = MarketChannelModel::getInstance()->getModel()->column("id");
        $res = BiDaysUserChargeModel::getInstance()->getModel()
            ->field("uid,imei,idfa,deviceid")
            ->where("date", ">=", SELF::BEGINDATE)
            ->where("date", "<", SELF::ENDDATE)
            ->where("register_time", ">=", SELF::BEGINDATE . " 00:00:00")
            ->where("register_time", "<", SELF::ENDDATE . " 00:00:00")
            ->where("promote_channel", "in", $marketChannelIds)
            ->distinct(true)
            ->select()->toArray();
        return $res;
    }


    //获取累计用户充值少于等于10元的
    public function getchargeLessEqTenUids()
    {
        //1.规则结算期间满足充值10元且注册后24小时没有任何登录信息 则视为作弊用户
        //2.规则结算期间满足充值少于10元的则视为作弊用户
        $marketChannelIds = MarketChannelModel::getInstance()->getModel()->column("id");
        $result = BiDaysUserChargeModel::getInstance()->getModel()
            ->field("uid,(sum(amount)/10) as money,register_time")
            ->where("date", ">=", SELF::BEGINDATE)
            ->where("date", "<", SELF::ENDDATE)
            ->where("register_time", ">=", SELF::BEGINDATE . " 00:00:00")
            ->where("register_time", "<", SELF::ENDDATE . " 00:00:00")
            ->where("promote_channel", "in", $marketChannelIds)
            ->group("uid")
            ->having("money <= 10")
            ->select()->toArray();
        $cheatUids = []; //作弊用户列表
        foreach ($result as $info) {
            if ((int)$info['money'] < 10) {
                $cheatUids[] = $info['uid'];
            } elseif ((int)$info['money'] < 10) {
                $condition = [];
                $timeNode = strtotime($info['register_time'] . "+".SELF::NOTLOGINHOURS."hours");
                $condition[] = ["ctime", ">", $timeNode];
                $condition[] = ["user_id", "=", $info['uid']];
                if (!LogindetailModel::getInstance()->getModel($info['uid'])->where($condition)->find()) {
                    $cheatUids[] = $info['uid'];
                }
            }
        }
        return $cheatUids;
    }


}


?>
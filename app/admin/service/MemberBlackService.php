<?php

namespace app\admin\service;

use app\admin\model\BlackDataModel;
use app\admin\model\BlackLogModel;
use app\admin\model\MemberBlackModel;
use app\admin\model\MemberModel;
use app\admin\model\UserIdentityModel;
use app\admin\model\UserLastInfoModel;
use app\common\GetuiV2Common;
use app\common\RedisCommon;
use think\facade\Log;

class MemberBlackService extends MemberBlackModel
{

    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberBlackService();
        }
        return self::$instance;
    }

    //封禁类型
    public static $BLACKTYPE_IP = 1;
    public static $BLACKTYPE_DEviCEID = 2;
    public static $BLACKTYPE_CERTNO = 3;
    public static $BLACKTYPE_UID = 4;

    public static $avatar = '/useravatar/20210609/0db5690075cb34ddcc190698ac1444ec.png';

    public static $BLACKDESC = '很抱歉！由于您违反了平台相关规定已被永久拉入黑名单，由此给您带来的不便请谅解，如有异议，请联系我们的客服QQ：3425184378';

    //封禁状态
    public static $BLACKSTATUS_YES = 1;
    public static $BLACKSTATUS_NO = 0;

    //封账号
    public function memberBlacks($uid, $time, $desc, $admin_token_info)
    {
        $curtime = time();
        if ($time != -1) {
            $end_time = $time;
            $celcEndTime = $curtime + $time;
        } else {
            $celcEndTime = $end_time = -1;
        }
        $this->perform($uid, 4, $desc, $end_time, $uid, $curtime, $admin_token_info); //个推
        //查询此用户是否存在封号记录
        $data = BlackDataModel::getInstance()->getIsOneByWhere(self::$BLACKTYPE_UID, $uid);
        if (!empty($data)) {
            $data = $data->toArray();
            $is = $this->isBlacksEnd($data);
            if (!$is) {
                throw new \Exception("封禁已存在", 500);
            }
            $data['end_time'] = $celcEndTime;
            $data['status'] = self::$BLACKSTATUS_YES;
            $data['admin_id'] = $admin_token_info['id'];
            $data['reason'] = $desc;
            $data['time'] = $end_time;
            $data['blacks_time'] = $curtime;
            $data['update_time'] = $curtime;

            $forbid_data = $data;
            return BlackLogModel::getInstance()->getModel()->transaction(function () use ($data, $forbid_data) {
                BlackDataModel::getInstance()->updateBlackDataNew($data);
                return BlackLogModel::getInstance()->getModel()->insert($forbid_data);
            });
        } else {
            $data = [
                'user_id' => $uid,
                'type' => self::$BLACKTYPE_UID,
                'blackinfo' => $uid,
                'create_time' => $curtime,
                'update_time' => $curtime,
                'time' => $end_time,
                'status' => self::$BLACKSTATUS_YES,
                'reason' => $desc,
                'admin_id' => $admin_token_info['id'],
                'end_time' => $celcEndTime,
                'blacks_time' => $curtime,
            ];

            $forbid_data = $data;
            $forbid_data['forbid_type'] = 0;

            return BlackLogModel::getInstance()->getModel()->transaction(function () use ($data, $forbid_data) {
                BlackDataModel::getInstance()->insertBlackDataNew($data);
                return BlackLogModel::getInstance()->getModel()->insert($forbid_data);
            });
        }
    }

    //基于账号封： IP 设备 身份证 账号
    public function deviceIpAdd($uid, $type, $reason, $time, $admin_token_info)
    {
        if ($type == 1) {
            $blackinfo = MemberModel::getInstance()->getModel($uid)->where('id', $uid)->value('register_ip');
            if (!$blackinfo) {
                echo json_encode(['code' => 500, 'msg' => '用户注册IP为空！']);
                die;
            }
        } elseif ($type == 2) {
            $blackinfo = UserLastInfoModel::getInstance()->getModel($uid)->where('user_id', $uid)->value('deviceid');
            //客户端提出此设备号异常 存在多个用户重复此设备deviceID 所以后台禁止封禁此设备ID
            if(trim($blackinfo) == 'EB248FFD5FC4AA86907AE3A707C1AA15B3E6D'){
                echo json_encode(['code' => 500, 'msg' => 'EB248FFD5FC4AA86907AE3A707C1AA15B3E6D 此设备ID禁止封禁']);
                die;
            }
            if (!$blackinfo) {
                echo json_encode(['code' => 500, 'msg' => '用户设备为空！']);
                die;
            }
        } elseif ($type == 3) {
            $attestation = MemberModel::getInstance()->getModel($uid)->where('id', $uid)->value('attestation');
            if ($attestation != 1) {
                echo json_encode(['code' => 500, 'msg' => '此用户未实名！']);
                die;
            } else {
                $blackinfo = UserIdentityModel::getInstance()->getModel()->where([['uid', '=', $uid], ['status', '=', 1]])->value('certno');
                if (!$blackinfo) {
                    echo json_encode(['code' => 500, 'msg' => '用户身份证号码为空！']);
                    die;
                }
            }
        } elseif ($type == 4) {
            $blackinfo = $uid;
        } elseif ($type == 5) {
            //封禁用户唯一设备标识
            $blackinfo = UserLastInfoModel::getInstance()->getModel($uid)->where('user_id', $uid)->value('imei');
            if (!$blackinfo) {
                echo json_encode(['code' => 500, 'msg' => '用户唯一设备标识为空！']);
                die;
            }
        }

        $curtime = time();
        $ent_time = -1;
        $timeLength = -1;
        //封禁用户
        if ($type == 4 && $time != -1) {
            $ent_time = $curtime + $time;
            $timeLength = $time;
        }

        //查询此用户是否存在封号记录
        $data = BlackDataModel::getInstance()->getIsOneByWhere($type, $blackinfo);

        if (!empty($data)) {
            $data = $data->toArray();
            if ($data['status'] == 1) {
                throw new \Exception("封禁已存在", 500);
            }
            $data['user_id'] = $uid;
            $data['end_time'] = $ent_time;
            $data['status'] = self::$BLACKSTATUS_YES;
            $data['admin_id'] = $admin_token_info['id'];
            $data['reason'] = '违规';
            $data['blacks_time'] = $curtime;
            $data['update_time'] = $curtime;
            $data['time'] = $timeLength;
            try {
                $blacklogModel = BlackLogModel::getInstance()->getModel();
                $blacklogModel->startTrans();
                $is = BlackDataModel::getInstance()->updateBlackDataNew($data);
                $blacklogModel->insert($data);
                $blacklogModel->commit();
            } catch (\Throwable $e) {
                $blacklogModel->rollback();
            }
        } else {
            $info = [
                'user_id' => $uid,
                'type' => $type,
                'blackinfo' => $blackinfo,
                'create_time' => time(),
                'update_time' => time(),
                'reason' => $reason,
                'status' => 1,
                'admin_id' => $admin_token_info['id'],
                'blacks_time' => $curtime,
                'time' => $timeLength,
                'end_time' => $ent_time,
            ];
            try {
                $blacklogModel = BlackLogModel::getInstance()->getModel();
                $blacklogModel->startTrans();
                $is = BlackDataModel::getInstance()->insertBlackDataNew($info);
                $blacklogModel->insert($info);
                $blacklogModel->commit();
            } catch (\Throwable $e) {
                $blacklogModel->rollback();
            }
        }

        $this->perform($uid, $type, $reason, $time, $blackinfo, $curtime, $admin_token_info);
        return $is;
    }

    //基于黑名单封： IP 设备 身份证
    public function memberBlacksAdd($type, $reason, $time, $blackinfo, $admin_token_info)
    {
        $reason = empty($reason) ? '管理员封禁' : $reason;
        $curtime = time();
        if($type == 2 && $blackinfo == 'EB248FFD5FC4AA86907AE3A707C1AA15B3E6D'){
            //客户端提出此设备号异常 存在多个用户重复此设备deviceID 所以后台禁止封禁此设备ID
            echo json_encode(['code' => 500, 'msg' => 'EB248FFD5FC4AA86907AE3A707C1AA15B3E6D 此设备ID禁止封禁']);
            die;
        }
        //查询此用户是否存在封号记录
        $data = BlackDataModel::getInstance()->getIsOneByWhere($type, $blackinfo);

        if (!empty($data)) {
            Log::debug(sprintf('>>>>>封禁信息更新>>>>>'));
            $data = $data->toArray();

            Log::info(sprintf(__CLASS__ . sprintf('-----封禁信息查询结果-----,res=====>', json_encode($data))));
            if ($data['status'] == 1) {
                throw new \Exception("封禁已存在", 500);
            } else {
                $data['end_time'] = '-1';
                $data['status'] = self::$BLACKSTATUS_YES;
                $data['admin_id'] = $admin_token_info['id'];
                $data['reason'] = '管理员封禁';
                $data['time'] = '-1';
                $data['blacks_time'] = $curtime;
                $data['update_time'] = $curtime;
                BlackLogModel::getInstance()->transaction(function () use ($data) {
                    BlackDataModel::getInstance()->updateBlackDataNew($data);
                    BlackLogModel::getInstance()->getModel()->insert($data);
                });
            }
        } else {
            Log::debug(sprintf('>>>>>封禁信息新增>>>>>'));
            $info = [
                'time' => '-1',
                'end_time' => '-1',
                'type' => $type,
                'blackinfo' => $blackinfo,
                'create_time' => time(),
                'update_time' => time(),
                'reason' => $reason,
                'status' => 1,
                'admin_id' => $admin_token_info['id'],
                'blacks_time' => $curtime,
            ];
            BlackLogModel::getInstance()->transaction(function () use ($info) {
                BlackDataModel::getInstance()->insertBlackData($info);
                BlackLogModel::getInstance()->getModel()->insert($info);
            });

        }

        $this->perform(0, $type, $reason, $time, $blackinfo, $curtime, $admin_token_info);
        return 1;
    }

    //到期时间
    public function celcEndTime($time, $curtime)
    {
        return $curtime + $time * 86400;
    }

    //封禁是否到期
    public function isBlacksEnd($data)
    {
        return $data['end_time'] < time() ? true : false;
    }

    //组合信息放入redis
    public function perform($uid, $type, $reason, $time, $blackinfo, $curtime, $admin_token_info)
    {
        Log::debug(sprintf('>>>>>封禁信息通知>>>>>'));

        $qq = config('config.qq');
        $serviceEmail = config('config.service_email');
        $date = date("Y年m月d日 H:i:s");
        if ($type == 1) {
            return true;
            //            $uidSource = UserLastInfoModel::getInstance()->getModel()->where('login_ip',$blackinfo)->field('user_id id,source')->select()->toArray();
            //            $content = self::$BLACKDESC;
            //            $this->kickedOut($uidSource,$content);  //踢出
        } elseif ($type == 2) {
            return true;
            //            $uidSource = MemberModel::getInstance()->getWhereAllData([["deviceid", "=", $blackinfo]], "id,source");
            //            $content = self::$BLACKDESC;
            //            $this->kickedOut($uidSource,$content);  //踢出
        } elseif ($type == 3) {
            return true;
        } elseif ($type == 5) {
            return true;
        } elseif ($type == 4) {
            $permanentBlock = 0 ;
            //组装封禁消息
            if ($time == -1) { //永封
                $content = "很抱歉！由于您" . $reason . "，违反了平台相关规定，您的账号已于" . $date . "被永久封禁，由此给您带来的不便请谅解，如有异议，请联系客服邮箱：{$serviceEmail}";
                $permanentBlock = 1;
                MemberService::getInstance()->saveAvatarurl(['user_id' => $uid, 'where' => ['id' => $uid], 'avatarurl' => self::$avatar, 'admin_token_info' => $admin_token_info]);
            } else {
                $unsealTime = date("Y年m月d日H:i:s", $curtime + $time);
                $content = "很抱歉！由于您" . $reason . "，违反了平台相关规定，您的账号已于" . $date . "被封禁，解封时间" . $unsealTime . "。由此给您带来的不便请谅解，如有异议，请联系客服邮箱：{$serviceEmail}";
            }
            $uidSource = UserLastInfoModel::getInstance()->getModel($uid)->where('user_id', $uid)->field('user_id,source')->select()->toArray();
            $this->kickedOut($uidSource, $content,$permanentBlock); //踢出
        }
    }

    //个推
    public function kickedOut($uidSource, $content,$permanentBlock=0)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 0]);
        foreach ($uidSource as $k => $v) {
            $token = $redis->get($v['user_id']);
            $redis->del($v['user_id']);
            $redis->del($token);
            $source = $v['source'] == 'mua' ? 'muaconfig' : 'config';
            GetuiV2Common::getInstance($source)->toSingleTransmission($v['user_id'], 1, $content);
            GetuiV2Common::getInstance($source)->toSingleTransmission2($v['user_id'], $content);
            //通知API
            CurlApiService::getInstance()->blockUserNotice($v['user_id'], 1,$permanentBlock);
//            GetuiCommon::getInstance()->pushMessageToSingle($v['id'], 1, $content,$v['source']);
        }
    }

    //获取uid
    public function blackMember($type, $blackinfo)
    {
        if ($type == 1) {
            return MemberModel::getInstance()->getWhereAllData([["register_time", "=", $blackinfo]], "id");
        } elseif ($type == 2) {
            return MemberModel::getInstance()->getWhereAllData([["deviceid", "=", $blackinfo]], "id");
        } else {
            return UserIdentityModel::getInstance()->getModel()->where([['certno', '=', $blackinfo], ['status', '=', 1]])->field('uid')->select();
        }
    }

    //封禁时间的格式化
    public function blackTimeFormat($timeParams)
    {
        if ($timeParams == -1) {
            return "永久";
        } else if ($timeParams < '3600') {
            return (int) ($timeParams / 60) . '分钟';
        } else if ($timeParams < 86400) {
            return (int) ($timeParams / 3600) . "小时";
        } else {
            return ((int) $timeParams / 86400) . "天";
        }
    }
}
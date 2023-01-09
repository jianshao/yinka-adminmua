<?php

namespace app\admin\service;

use app\admin\common\ApiUrlConfig;
use app\admin\model\ForumModel;
use app\admin\model\ForumReplyModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberReportModel;
use app\admin\model\ReportPunishLevelModel;
use app\admin\model\ReportTagModel;
use app\admin\model\YunxinModel;
use app\common\RedisCommon;
use think\facade\Log;

class ReportService
{
    protected static $instance;
    protected $redis;

    protected $pagenum = 20;
    protected $user_info_key = "userinfo_";

    const REPORT_TYPE_用户私聊 = 1;
    const REPORT_TYPE_用户资料 = 2;
    const REPORT_TYPE_房间资料 = 3;
    const REPORT_TYPE_房间内用户违规 = 4;
    const REPORT_TYPE_动态举报 = 5;
    const REPORT_TYPE_动态评论举报 = 6;

    const REPORT_MAP = [
        1 => '用户私聊', 2 => '用户资料', 3 => '房间资料', 4 => '房间内用户违规', 5 => '动态举报', 6 => '动态评论举报',
    ];

    const REPORT_AUDIO_STATUS_MAP = [
        1 => '成功', 2 => '录制失败', 3 => '合并文件失败', 4 => '获取资源失败', 5 => '录制中',
    ];

    const REPORT_USER_EDIT_EXP_KEY = 'report_user_edit_info_';
    const REPORT_ENTER_ROOM_EXP_KEY = 'report_enter_room_exp_';
    const REPORT_USER_CHAT_EXP_KEY = 'report_user_disable_msg_';

    const FORBID_ROOM_KEY = 'report_user_operator_';

    const REPORT_TYPE_自定义审核 = 0;
    const REPORT_TYPE_执行审核 = 1;

    const REPORT_STATUS_未审核 = 0;
    const REPORT_STATUS_审核中 = 1;
    const REPORT_STATUS_审核完成并处罚 = 2;
    const REPORT_STATUS_未处罚 = 3;

    const REPORT_STATUS_MAP = [
        0 => '未审核', 1 => '审核中', 2 => '审核完成并处罚', 3 => '未处罚',
    ];

    const REPORT_NO_PUNISH_MSG = '友友您好，官方已对您所举报的用户进行核实，暂无发现该用户存在违规行为，但已将其纳入重点监控名单，后续核实到违规会及时进行处罚，感谢您的反馈';

    const REPORT_USER_INFO_MAP = [
        'user_avatar' => [
            'column' => 'avatar',
            'values' => [
                0 => 'Public/Uploads/image/logo.png',
                1 => 'Public/Uploads/image/male.png',
                2 => 'Public/Uploads/image/female.png',
                3 => 'Public/Uploads/image/logo.png',
            ],
        ],
        'user_nickname' => [
            'column' => 'nickname',
            'values' => '用户_',
        ],
        'user_intro' => [
            'column' => 'intro',
            'values' => '',
        ],
        'user_voice_url' => [
            'column' => 'pretty_avatar',
            'values' => '',
        ],
    ];

    const REPORT_ROOM_INFO_MAP = [
        'room_name' => [
            'column' => 'room_name',
            'values' => '',
        ],
        'room_desc' => [
            'column' => 'room_desc',
            'values' => '',
        ],
        'room_welcomes' => [
            'column' => 'room_welcomes',
            'values' => '欢迎来到我的房间～',
        ],
    ];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->redis = RedisCommon::getInstance()->getRedis();
    }

    public function addOrUpdate($data, $where = [])
    {
        if (empty($where)) {
            $res = ReportTagModel::getInstance()->getModel()->insertAll($data);
        } else {
            $res = ReportTagModel::getInstance()->getModel()->where([$where])->update($data);
        }
        return $res;
    }

    public function startReport($admin_id)
    {
        $report_info = MemberReportModel::getInstance()->limitOne();
        if (empty($report_info)) {
            throw new \Exception("暂无举报数据", 500);
        }

        MemberReportModel::getInstance()->getModel()
            ->where('id', $report_info['id'])
            ->where('status', ReportService::REPORT_STATUS_未审核)
            ->where('audio_status', '<>', 5)
            ->update(['admin_id' => $admin_id, 'status' => ReportService::REPORT_STATUS_审核中]);
        return MemberReportModel::getInstance()->getOneReportInfoByAdminId($admin_id);
    }

    public function getReportTagList()
    {
        $list = ReportTagModel::getInstance()->getModel()
            ->field('id tag_id,punish_level,title')
            ->where('type', 0)
            ->order('punish_level asc')
            ->select()
            ->toArray();

        $data = [];
        foreach ($list as $key => $item) {
            $data[$item['punish_level']][] = $item;
        }
        return $data;
    }

    public function getReportInfo($admin_id)
    {
        $report_info = MemberReportModel::getInstance()->getOneReportInfoByAdminId($admin_id);
        if (empty($report_info)) {
            $report_info = $this->startReport(($admin_id));
        }

        if ($report_info && !in_array($report_info['from_type'], array_keys(self::REPORT_MAP))) {
            throw new \Exception("举报类型错误", 500);
        }

        $images = [];
        $report_user_info = [];
        $reported_user_info = [];
        $room_info = [];
        $forum_info = [];
        $forum_reply_info = [];
        $punish_info = [];

        if ($report_info) {
            $images = dealImages($report_info['images'], '.com', config('config.APP_URL_image'));
            //获取被举报用户信息
            $users_info_map = $this->getReportUsersInfo($report_info);

            //举报者信息
            $report_user_info = $users_info_map[$report_info['report_uid']] ?? [];
            //被举报者信息
            $reported_user_info = $users_info_map[$report_info['reported_uid']] ?? [];

            //获取被举报房间信息
            if ($report_info['from_type'] == self::REPORT_TYPE_房间资料) {
                $room_info = $this->getReportRoomInfo($report_info['reported_id']);
            }

            //获取被举报动态信息
            if ($report_info['from_type'] == self::REPORT_TYPE_动态举报) {
                $forum_info = $this->getReportForumInfo($report_info['reported_id']);
            }

            //获取被举报评论信息
            if ($report_info['from_type'] == self::REPORT_TYPE_动态评论举报) {
                $forum_reply_info = $this->getReportForumReplyInfo($report_info['reported_id']);
            }

            if ($report_info['audio_url']) {
                $report_info['audio_url'] = $report_info['audio_url'];
            }

            $report_info['audio_status_desc'] = '';
            //房间内用户违规
            if ($report_info['from_type'] == self::REPORT_TYPE_房间内用户违规) {
                $report_info['audio_status_desc'] = self::REPORT_AUDIO_STATUS_MAP[$report_info['audio_status']] ?? '未知';
            }

            //获取相应的惩罚列表
            $punish_list = ReportPunishLevelModel::getInstance()->getPunishListByType($report_info['from_type']);
            foreach ($punish_list as $_ => $punish) {
                $punish_info[$punish['level']][$punish['id']] = $punish['title'];
            }
        }

        $report_info['images'] = $images;
        $report_info['report_user_info'] = $report_user_info;
        $report_info['reported_user_info'] = $reported_user_info;
        $report_info['room_info'] = $room_info;
        $report_info['forum_info'] = $forum_info;
        $report_info['forum_reply_info'] = $forum_reply_info;
        $report_info['punish_info'] = $punish_info;
        // dump($report_info);
        return $report_info;
    }

    private function getReportRoomInfo($room_id)
    {
        return LanguageroomModel::getInstance()->getModel()->where('id', $room_id)->field('id room_id,room_name,room_welcomes,room_desc,background_image')->findOrEmpty()->toArray();
    }

    private function getReportForumInfo($forum_id)
    {
        $forum_info = ForumModel::getInstance()->getModel()->where('id', $forum_id)->field('forum_image images, forum_voice, forum_content content')->findOrEmpty()->toArray();
        if (!empty($forum_info['images'])) {
            $forum_info['images'] = dealImages($forum_info['images'], '.com', config('config.APP_URL_image'));
        }
        return $forum_info;
    }

    private function getReportForumReplyInfo($forum_reply_id)
    {
        $forum_reply_info = ForumReplyModel::getInstance()->getModel()->where('id', $forum_reply_id)->field('reply_content content, forum_id')->findOrEmpty()->toArray();
        $forum_reply_info['forum_info'] = $this->getReportForumInfo($forum_reply_info['forum_id']);
        return $forum_reply_info;
    }

    private function getReportUsersInfo(array $report_info)
    {
        //获取被举报用户信息
        $default_avatar = '/Public/Uploads/image/logo.png';
        $users_info = MemberModel::getInstance()->getWhereAllData([['id', 'in', [$report_info['report_uid'], $report_info['reported_uid']]]], 'id,sex,username,nickname,avatar,intro,pretty_avatar voice_url');
        $url = config('config.APP_URL_image');

        $this->redis = RedisCommon::getInstance()->getRedis();

        foreach ($users_info as $_ => &$user_info) {
            if (empty($user_info['avatar'])) {
                $user_info['avatar'] = $default_avatar;
            }

            $user_info['avatar'] = $url . $user_info['avatar'];

            $voice_url = '';
            if ($user_info['voice_url']) {
                $voice_url = $url . $user_info['voice_url'];
                $user_info['voice_type'] = 1;
                if (strpos($voice_url, '.amr') != false) {
                    $user_info['voice_type'] = 2;
                    $voice_url = base64_encode(file_get_contents($voice_url));
                }
            }
            $user_info['voice_url'] = $voice_url;

            $userKey = 'userinfo_' . $user_info['id'];

            $album_info = $this->redis->hget($userKey, 'album');
            $user_info['album'] = dealImages($album_info, '.com', config('config.APP_URL_image'));
        }

        return array_column($users_info, null, 'id');

    }

    public function punish($params, $token_info, $punish_type = self::REPORT_TYPE_自定义审核)
    {
        list(
            $reason,
            $punish_id,
            $punish_level,
            $report_content,
            $reported_content,
            $edit_obj,
            $report_id
        ) = [
            $params['reason'],
            $params['punish_id'],
            $params['punish_level'],
            $params['report_content'],
            $params['reported_content'],
            $params['edit_obj'],
            $params['report_id'],
        ];

        $punish_info = ReportPunishLevelModel::getInstance()->getOneById($punish_id);
        $report_info = MemberReportModel::getInstance()->getOneById($report_id);
        //获取被举报用户信息
        $users_info_map = $this->getReportUsersInfo($report_info);

        // //举报者信息
        // $report_user_info = $users_info_map[$report_info['report_uid']] ?? [];
        //被举报者信息
        $report_info['reported_user_info'] = $users_info_map[$report_info['reported_uid']] ?? [];

        $this->redis = RedisCommon::getInstance()->getRedis();
        $from_type = $report_info['from_type'];
        $report_uid = $report_info['report_uid'];
        $reported_uid = $report_info['reported_uid'];
        $reported_id = $report_info['reported_id'];
        $chat_exp_time = $punish_info['chat_exp_time'];
        $black_time = $punish_info['black_time'];
        $use_exp_time = $punish_info['use_exp_time'];

        $edit_types = json_decode($edit_obj, true);
        if ($black_time == -1) {
            if ($from_type == self::REPORT_TYPE_房间资料) {
                $room_id = $reported_id;
                // 永封房间
                $data = ["room_id" => $room_id, "operator" => $token_info['id'], "longtime" => -1, "reason" => $reason, 'end_time' => -1];
                RoomCloseService::getInstance()->banRoom($data, $token_info);

                //修改房间信息
                $this->editRoomInfo($edit_types, $report_info, $token_info);
            } else {
                // 永封用户
                MemberBlackService::getInstance()->memberBlacks($reported_uid, -1, $reason, $token_info);
                //修改用户信息
                $this->editUserInfo($edit_types, $report_info, $token_info);
            }

        } else {
            if ($from_type == self::REPORT_TYPE_房间资料) {
                // 限制房间使用
                if ($use_exp_time > 0) {
                    Log::debug(sprintf('----限制房间使用----'));
                    // $this->redis->setex(self::REPORT_ENTER_ROOM_EXP_KEY . $reported_uid, $use_exp_time, time());
                    $room_id = $reported_id;
                    $data = ["room_id" => $room_id, "operator" => $token_info['id'], "longtime" => $use_exp_time, "reason" => $reason];
                    RoomCloseService::getInstance()->banRoom($data, $token_info);
                }

                //屏蔽违规房间信息
                $this->editRoomInfo($edit_types, $report_info, $token_info);
            } else if ($from_type == self::REPORT_TYPE_房间内用户违规 || $from_type == self::REPORT_TYPE_用户私聊) {
                //
                // 封禁用户
                if ($black_time > 0) {
                    Log::debug(sprintf('----封禁用户----'));
                    MemberBlackService::getInstance()->memberBlacks($reported_uid, $black_time, $reason, $token_info);
                }

                // 禁言
                $this->forbidChat($reported_uid, $chat_exp_time);

            } else {
                // 禁言
                $this->forbidChat($reported_uid, $chat_exp_time);

                // 限制用户修改功能
                if ($use_exp_time > 0) {
                    Log::debug(sprintf('----限制用户修改功能----'));
                    // if ($this->redis->get(self::REPORT_USER_EDIT_EXP_KEY . $reported_uid)) {
                    //     throw new \Exception("该用户已被限制使用修改功能", 500);
                    // }
                    $this->redis->setex(self::REPORT_USER_EDIT_EXP_KEY . $reported_uid, $use_exp_time, time());
                }

                if ($from_type == self::REPORT_TYPE_用户资料) {
                    // 屏蔽违规信息
                    $this->editUserInfo($edit_types, $report_info, $token_info);
                } elseif ($from_type == self::REPORT_TYPE_动态举报) {
                    //删除动态
                    ApiService::getInstance()->curlApi(ApiUrlConfig::$del_forum, [
                        'token' => $token_info['admin_token'],
                        'forumId' => $reported_id,
                        'operatorId' => $token_info['id'],
                    ]);

                } elseif ($from_type == self::REPORT_TYPE_动态评论举报) {
                    //删除评论
                    ApiService::getInstance()->curlApi(ApiUrlConfig::$del_reply, [
                        'token' => $token_info['admin_token'],
                        'replyId' => $reported_id,
                        'operatorId' => $token_info['id'],
                    ]);
                }
            }
        }

        //举报者发私聊消息
        YunxinModel::getInstance()->sendMsg(config('config.fq_assistant'), 0, $report_uid, 0, ['msg' => $report_content]);
        YunxinModel::getInstance()->sendMsg(config('config.fq_assistant'), 0, $reported_uid, 0, ['msg' => $reported_content]);
        MemberReportModel::getInstance()->updateOne([['id', '=', $report_id], ['status', '=', self::REPORT_STATUS_审核中]], ['punish_type' => $punish_type, 'audit_time' => time(), 'admin_id' => $token_info['id'], 'status' => self::REPORT_STATUS_审核完成并处罚]);
    }

    public function noPunish($params, $token_info)
    {
        $report_id = $params['report_id'];

        $report_info = MemberReportModel::getInstance()->getOneById($report_id);
        MemberReportModel::getInstance()->updateOne([['id', '=', $report_id], ['status', '=', self::REPORT_STATUS_审核中]], ['admin_id' => $token_info['id'], 'audit_time' => time(), 'status' => self::REPORT_STATUS_未处罚]);

        //通知举报用户
        YunxinModel::getInstance()->sendMsg(config('config.fq_assistant'), 0, $report_info['report_uid'], 0, ['msg' => self::REPORT_NO_PUNISH_MSG]);
    }

    public function execPunish($params, $token_info)
    {
        $report_id = $params['report_id'];
        $edit_obj = $params['edit_obj'];
        $report_tag_id = $params['report_tag_id'];

        $report_tag_info = ReportTagModel::getInstance()->getModel()
            ->where('id', $report_tag_id)
            ->findOrEmpty()
            ->toArray();

        //获取用户违规次数
        $report_info = MemberReportModel::getInstance()->getOneById($report_id);
        $punish_type_count = MemberReportModel::getInstance()->getModel()
            ->whereIn('status', [1, 2])
            ->where('reported_uid', $report_info['reported_uid'])
            ->field('count(0) count,from_type')
            ->group('from_type')
            ->select()
            ->toArray();

        $punish_type_count_map = [];
        if ($punish_type_count) {
            $punish_type_count_map = array_column($punish_type_count, 'count', 'from_type');
        }

        $punish_id = ReportPunishLevelModel::getInstance()->getModel()
            ->where('punish_count', '<=', $punish_type_count_map[$report_info['from_type']])
            ->where('level', $report_tag_info['punish_level'])
            ->where('type', $report_info['from_type'])
            ->order('punish_count desc')
            ->limit(1)
            ->value('id');

        $data = [
            'reason' => $report_tag_info['title'],
            'punish_id' => $punish_id,
            'punish_level' => $report_tag_info['punish_level'],
            'report_content' => $report_tag_info['report_content'],
            'reported_content' => $report_tag_info['reported_content'],
            'report_id' => $report_id,
            'edit_obj' => $edit_obj,
        ];

        $this->punish($data, $token_info, self::REPORT_TYPE_执行审核);
    }

    private function editRoomInfo($edit_types, $report_info, $token_info)
    {
        Log::debug(sprintf('----屏蔽违规房间信息----'));
        Log::debug(sprintf('editUserInfo=====>edit_types: %s , report_info: %s , token_info: %s', json_encode($edit_types), json_encode($report_info), json_encode($token_info)));

        $sex = $report_info['reported_user_info']['sex'];
        $room_id = $report_info['reported_id'];
        $uid = $report_info['reported_uid'];
        $room_info = [];
        $user_info = [];
        if ($edit_types) {
            foreach ($edit_types as $_ => $edit_type) {
                if ($edit_type == 'room_name') {
                    $room_info[self::REPORT_ROOM_INFO_MAP[$edit_type]['column']] = self::REPORT_ROOM_INFO_MAP[$edit_type]['values'] . $room_id;
                } elseif ($edit_type == 'user_avatar') {
                    $user_info[self::REPORT_USER_INFO_MAP[$edit_type]['column']] = self::REPORT_USER_INFO_MAP[$edit_type]['values'][$sex];
                } else {
                    $room_info[self::REPORT_ROOM_INFO_MAP[$edit_type]['column']] = self::REPORT_ROOM_INFO_MAP[$edit_type]['values'];
                }
            }
        }

        Log::debug(sprintf('editUserInfo=====>room_info: %s', json_encode($room_info)));

        if ($room_info) {
            //设置用户信息
            $params = [
                'operatorId' => $token_info['id'],
                'token' => $token_info['admin_token'],
                'room_id' => (int) $room_id,
                'check_auth' => 1,
                'profile' => json_encode($room_info),
            ];
            ApiService::getInstance()->curlApi(ApiUrlConfig::$update_roominfo, $params);
        }

        if ($user_info) {
            //设置用户信息
            $params = [
                'operatorId' => $token_info['id'],
                'token' => $token_info['admin_token'],
                'userId' => (int) $uid,
                'datas' => json_encode($user_info),
            ];
            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);
        }

    }

    private function editUserInfo($edit_types, $report_info, $token_info)
    {
        Log::debug(sprintf('-----屏蔽违规用户信息开始-----'));
        Log::debug(sprintf('editUserInfo=====>edit_types: %s , report_info: %s , token_info: %s', json_encode($edit_types), json_encode($report_info), json_encode($token_info)));

        $sex = $report_info['reported_user_info']['sex'];
        $uid = $report_info['reported_uid'];
        $user_album = [];
        $user_info = [];
        if ($edit_types) {
            foreach ($edit_types as $_ => $edit_type) {
                if (strpos($edit_type, 'user_album') !== false) {
                    $user_album_key = explode('_', $edit_type);
                    $user_album[] = $user_album_key[2];
                } else {
                    if ($edit_type == 'user_avatar') {
                        $user_info[self::REPORT_USER_INFO_MAP[$edit_type]['column']] = self::REPORT_USER_INFO_MAP[$edit_type]['values'][$sex];
                    } elseif ($edit_type == 'user_nickname') {
                        $user_info[self::REPORT_USER_INFO_MAP[$edit_type]['column']] = self::REPORT_USER_INFO_MAP[$edit_type]['values'] . $uid;
                    } else {
                        $user_info[self::REPORT_USER_INFO_MAP[$edit_type]['column']] = self::REPORT_USER_INFO_MAP[$edit_type]['values'];
                    }
                }
            }
        }

        Log::debug(sprintf('editUserInfo=====>user_info: %s , user_album: %s', json_encode($user_info), json_encode($user_album)));

        if ($user_info) {
            //设置用户信息
            $params = [
                'operatorId' => $token_info['id'],
                'token' => $token_info['admin_token'],
                'userId' => (int) $uid,
                'datas' => json_encode($user_info),
            ];
            ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);
        }

        if ($user_album) {
            $albums = $report_info['reported_user_info']['album'];
            foreach ($albums as $key => $album) {
                if (in_array($key, $user_album)) {
                    unset($albums[$key]);
                }
            }
            //设置用户相册
            $userKey = $this->user_info_key . $uid;
            $this->redis = RedisCommon::getInstance()->getRedis();

            Log::debug(sprintf('editUserInfo@ albums: %s', json_encode(array_values($albums))));
            $this->redis->hset($userKey, 'album', implode(',', array_values($albums)));
        }

        Log::debug(sprintf('-----屏蔽违规用户信息结束-----'));
    }

    private function forbidChat(int $uid, int $time)
    {
        if ($time > 0) {
            Log::debug(sprintf('----禁言用户----'));
            // if ($this->redis->get(self::REPORT_USER_CHAT_EXP_KEY . $uid)) {
            //     throw new \Exception("该用户已被禁言", 500);
            // }
            $this->redis->setex(self::REPORT_USER_CHAT_EXP_KEY . $uid, $time, time());
        }
    }
}
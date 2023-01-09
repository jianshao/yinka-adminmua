<?php
namespace app\admin\common;

class ApiUrlConfig
{
    public static $block_user_notice = 'api/inner/blockUserNotice'; //封禁用户通知接口
    public static $pk_room_conf = 'api/inner/setAcrossPKRank';

    public static $home_room_list = 'api/inner/homeHotRoomList'; //封禁用户通知接口
    public static $enjoy_room_conf = 'api/inner/recreationHotRoomList';

    public static $pk_cross_start = 'iapi/startAcrossPK';
    public static $pk_cross_end = 'iapi/endAcrossPK';
    public static $ban_room = 'iapi/banRoom';

    public static $set_user_info = 'api/inner/setUserInfo';
    public static $reset_attention = 'api/inner/user/resetAttention';
    public static $member_duck_add = 'api/inner/user/dukeMemberAdd';
    public static $gh_agree_apply = 'api/inner/gh/agreeApply';
    public static $gh_kickout_member = 'api/inner/gh/kickMember';
    public static $gh_exit_member = 'api/inner/gh/exitMember';
    public static $update_member_invitcode = 'api/inner/user/updateUserInvitcode';
    public static $add_virtual_member = 'api/inner/user/addUser';
    public static $add_room_party = 'api/inner/room/addRoomParty';
    public static $add_room_pretty = 'api/inner/room/addRoomPretty';
    public static $del_room_manager = 'api/inner/user/delYsUser';
    public static $add_room_manager = 'api/inner/user/addYsUser';

    public static $add_guild_room = 'api/inner/room/addGuidRoom';
    public static $del_guild_room = 'api/inner/room/delGuidRoom';
    public static $add_guild_room_index = 'api/inner/room/addGuidRoomIndex';
    public static $del_guild_room_index = 'api/inner/room/delGuidRoomIndex';

    public static $edit_room = 'api/inner/room/editRoom'; //修改房间信息
    public static $room_oss_file = 'api/inner/room/roomOssFile';

    public static $create_guild = 'api/inner/createGuild';
    public static $edit_guild_info = 'api/inner/editGuildInfo';
    public static $edit_guild_member = 'api/inner/editGuildMember';
    public static $add_guild_member = 'api/inner/addGuildMember';
    public static $remove_guild_member = 'api/inner/removeGuildMember';

    public static $check_forum = 'api/inner/checkForum';
    public static $del_forum = 'api/inner/delForum';
    public static $del_reply = 'api/inner/forum/delReply';

    public static $withdraw_consume_asset = 'api/inner/withdraw/consumeAsset';
    public static $withdraw_add_asset = 'api/inner/withdraw/addAsset';

    public static $update_roominfo = 'api/inner/room/roomInfoUpdate'; //编辑房间某个字段

}
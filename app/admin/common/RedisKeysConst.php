<?php
/**
 * 常量及变量配置
 * */
namespace app\admin\common;

class RedisKeysConst
{
    //首页模拟恋爱set key
    const RANDOM_KEY = 'index_rand_room_set';
    //首页热门房间
    const INDEXHOTROOM_KEY = 'index_hot_room_set';
    //派对页推荐房间
    const PARTRECOMMENDROOM_KEY = 'part_recommend_room_set';
    //首页活跃值
    const POPULAR_HOME_ROOM_KEY_SET = 'home_room_hot_set';
    //娱乐页活跃值
    const POPULAR_ENJOY_ROOM_KEY_SET = 'recreation_room_hot_set';
    //派对页房间活跃值
    const POPULAR_VALUE_ROOM_KEY = 'popular_value_room';
    //首页活跃值
    const POPULAR_HOME_ROOM_KEY = 'home_room_hot';
    //娱乐页活跃值
    const POPULAR_ENJOY_ROOM_KEY = 'recreation_room_hot';
    //PK活动配置
    const PK_CONF = 'across_pk_activity';
    //用户缓存KEY
    const USER_INFO_CACHE = 'user:info:%s';
    const INVITCODE_LIST = 'invitcode_list';

    const SEARCH_HOT_ANCHOR_BUCKET = 'search_hot_anchor_bucket';
    //新用户匹配进房间
    const HOME_NEW_USER_COME_ROOM = "home_new_user_come_room";
    //老用户匹配进房间
    const HOME_Old_USER_COME_ROOM = "home_old_user_come_room";

    public static $roomHomePageKeys = [
        1 => self::RANDOM_KEY,
        2 => self::INDEXHOTROOM_KEY,
        3 => self::PARTRECOMMENDROOM_KEY,
        4 => self::POPULAR_HOME_ROOM_KEY_SET,
        5 => self::POPULAR_ENJOY_ROOM_KEY_SET,
    ];

    public static $popularRedisKey = [
        3 => self::POPULAR_VALUE_ROOM_KEY,
        4 => self::POPULAR_HOME_ROOM_KEY,
        5 => self::POPULAR_ENJOY_ROOM_KEY,
    ];

    public static $USERCOMEROOMKEYS = [
        6 => self::HOME_NEW_USER_COME_ROOM,
        7 => self::HOME_Old_USER_COME_ROOM,
    ];
}

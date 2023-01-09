<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */
namespace app\admin\service;

use app\admin\model\MemberGuildModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberSocityModel;
use app\admin\model\UserAssetLogModel;
use think\facade\Db;

class MemberGuildService extends MemberGuildModel
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberGuildService();
        }
        return self::$instance;
    }

    public function roomRunningWaterList($array)
    {
        $id = array_key_exists('id', $array) ? $array['id'] : '';
        $type = array_key_exists('type', $array) ? $array['type'] : '';
        $demo = array_key_exists('demo', $array) ? $array['demo'] : date('Y-m-d', strtotime('last week monday')) . ' - ' . date('Y-m-d', strtotime('this week monday'));
        list($start, $end) = getBetweenDate($demo);

        $table_name = getTable($start, $end);
        $list = [];
        if ($id && $type) {
            $column = $type == 1 ? 'uid' : 'room_id';
            $sql = "SELECT
                {$column} id,
                DATE_FORMAT( FROM_UNIXTIME( success_time ), '%Y-%m-%d' ) AS date,
                sum( ext_4 ) AS coin
            FROM
                {$table_name}
            WHERE
                event_id = 10003
                AND type = 5
                AND {$column} = $id
                AND success_time >= UNIX_TIMESTAMP( '$start' )
                AND success_time < UNIX_TIMESTAMP( '$end' )
            GROUP BY
                {$column},
                date";

            $list = Db::query($sql);

            if ($list) {
                foreach ($list as $k => $v) {
                    $giftWhere = [
                        ['event_id', '=', 10002],
                        ['type', '=', 4],
                        ['asset_id', '=', 'bean'],
                        ['success_time', '>=', strtotime($v['date'])],
                        ['success_time', '<', strtotime($v['date'] . ' 23:59:59')],
                    ];
                    $packgiftWhere = [
                        ['event_id', '=', 10002],
                        ['type', '=', 3],
                        ['success_time', '>=', strtotime($v['date'])],
                        ['success_time', '<', strtotime($v['date'] . ' 23:59:59')],
                    ];
                    if ($type == 1) {
                        $giftWhere[] = ['uid', '=', $id];
                        $packgiftWhere[] = ['uid', '=', $id];
                    } else {
                        $giftWhere[] = ['room_id', '=', $id];
                        $packgiftWhere[] = ['room_id', '=', $id];
                    }
                    $list[$k]['mall'] = (int) UserAssetLogModel::getInstance()->getModel()->where($giftWhere)->value('sum(abs(change_amount))');
                    $list[$k]['pack'] = (int) UserAssetLogModel::getInstance()->getModel()->where($packgiftWhere)->value('sum(abs(change_amount))');
                }
            }
        }
        return ['list' => $list, 'start' => $start, 'end' => $end, 'type' => $type, 'id' => $id, 'demo' => $demo];
    }

    /**统计当前数据
     * @param $where
     * @return mixed
     */
    public function countes($where)
    {
        $res = MemberGuildModel::getInstance()->getCount($where);
        return $res;
    }

    public function getCount($where, $uid = 0)
    {
        return getCount(MemberGuildModel::getInstance(), $where, $uid);
    }

    /**获取所有公会的列表接口
     * @param $where    where条件
     * @param $limit    limit条数
     * @return mixed
     */
    public function getList($where, $limit)
    {
        $res = MemberGuildModel::getInstance()->getList($where, $limit);
        return $res;
    }

    /**根据id获取字段值
     * @param $id
     * @param $field
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $res = GiftModel::getInstance()->getOneById($id, $field);
        return $res;
    }


    /**
     * 根据用户uids来获取用户的所属工会
     * @param $uids
     */
    public function getUserGuildByUid($uids){

       $memberSocityRes =  MemberSocityModel::getInstance()->getModel()
            ->field("user_id,guild_id")
            ->where("user_id","in",$uids)->where("status","=",1)
            ->select()->toArray();
        $guiids = array_column($memberSocityRes,"guild_id");
        $memberGuiRes = MemberGuildModel::getInstance()->getModel()
            ->where("id","in",$guiids)->where("status","=",1)
            ->column("id,nickname","id");

        $res = array_column($memberSocityRes,NULL,"user_id");
        $returnRes = [];

        foreach($res as $item){
            $returnRes[$item['user_id']]['uid'] = $item['user_id'];
            $returnRes[$item['user_id']]['g_id'] = $item['guild_id'];
            $returnRes[$item['user_id']]['g_nickname'] = $memberGuiRes[$item['guild_id']]['nickname'] ?? '';
        }

        return $returnRes;

    }

}

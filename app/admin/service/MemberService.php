<?php
/**
 * @author ly
 * 后台user操作
 * $date 2019
 */

namespace app\admin\service;

use app\admin\common\ApiUrlConfig;
use app\admin\model\MemberModel;
use app\admin\model\UserCardModel;
use app\admin\model\UserIdentityModel;
use app\common\RedisCommon;
use app\common\YunxinCommon;
use OSS\Core\OssException;

class MemberService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberService();
        }
        return self::$instance;
    }

    /**根据id获取字段值
     * @param $id
     * @param $field
     * @return mixed
     */
    public function getOneById($id, $field)
    {
        $res = MemberModel::getInstance()->getOneById($id, $field);
        return $res;
    }

    public function checkUser($id)
    {
        $res = $this->getOneById($id, '*');
        if (empty($res)) {
            throw new \Exception("用户信息不存在", 500);
        }
        return $res;
    }

    public function getCount($where, $uid = 0)
    {
        $org_model = MemberModel::getInstance();

        $count = getCount($org_model, $where, $uid);
        return $count;
    }

    /**获取所有用户的数据
     * @param $where    where条件
     * @param $offset   偏移量
     * @param $limit    条数
     * @return mixed    返回类型
     */
    public function getMembberListPage($where, $offset, $limit)
    {
        $res = MemberModel::getInstance()->getMembberListPage($where, $offset, $limit);
        return $res;
    }

    public function getMemberListPage($where, $offset, $limit)
    {
        $res = MemberModel::getInstance()->getMemberListPage($where, $offset, $limit);
        return $res;
    }

    public function getOneByWhere(array $where, $field = '')
    {
        return MemberModel::getInstance()->getModel()->where($where)->field($field)->find();
    }

    public function editOneByWhere(array $where, array $data)
    {
        return MemberModel::getInstance()->getModel()->where($where)->save($data);
    }

    public function editOneIncByWhere(array $where, array $data)
    {
        return MemberModel::getInstance()->getModel()->where($where)->inc($data[0], $data[1])->update();
    }
    public function editOneDecByWhere(array $where, array $data)
    {
        return MemberModel::getInstance()->getModel()->where($where)->dec($data[0], $data[1])->update();
    }

    public function saveAvatarurl($array)
    {
        try {
            if ($array['avatarurl']) {
                $uid = $array['user_id'];
                $result = parse_url($array['avatarurl']);

                // $data = ['avatar' => $result['path'], 'nickname' => "用户_{$uid}", 'pretty_id' => $uid];
                // $res = MemberModel::getInstance()->setMember($array['where'], $data);

                $nickname = "用户_{$uid}";
                $params = [
                    'operatorId' => $array['admin_token_info']['id'],
                    'token' => $array['admin_token_info']['admin_token'],
                    'userId' => (int) $uid,
                    'datas' => json_encode([
                        "prettyId" => (int) $uid, # 靓号
                        "nickname" => "用户_{$uid}", # 昵称
                        "avatar" => $result['path'], # 图像，不带域名
                    ]),
                ];

                $res = ApiService::getInstance()->curlApi(ApiUrlConfig::$set_user_info, $params);

                if ($res) {
                    //发送消息
                    $str = ['userId' => (int) $uid];
                    $socket_url = config('config.socket_url_base') . 'iapi/syncUserData';
                    $msgData = json_encode($str);
                    $res = curlData($socket_url, $msgData, 'POST', 'json');

                    $redis = RedisCommon::getInstance()->getRedis(['select' => 0]);
                    $redis->hset('userinfo_' . $uid, 'avatar', $result['path']);
                    $redis->hGet('user_current_room', $uid);
                    // //清除用户缓存
                    // HandleRedisService::getInstance()->delUserCache($uid);

                    $image_url = config('config.APP_URL_image');
                    $avatar = $image_url . $result['path'];
                    YunxinCommon::getInstance()->updateUinfo($uid, $nickname, $avatar);
                }
            }
            $code = 200;
        } catch (OssException $e) {
            $code = 500;
        }
        return $code;
    }

    public function getMemeberAttention($where)
    {
        $attention_info = [];
        if (empty($attention_info)) {
            $attention_info = UserCardModel::getInstance()->getModel()->where($where)->order('create_time desc')->findOrEmpty()->toArray();
        }

        if (empty($attention_info)) {
            $attention_info = UserIdentityModel::getInstance()->getModel()->where($where)->order('create_time desc')->findOrEmpty()->toArray();
        }
        return $attention_info;
    }



    public function getUserInfoFieldByUids($uids,$field){
          $res = [];
          if(!array_key_exists("id",$field)){
              $field[] = "id";
          }
          $models = MemberModel::getInstance()->getModels($uids);
          foreach($models as $model){
              $result = $model->getModel()->where("id","in",$model->getList())->field($field)->select()->toArray();
              $res = array_merge($res,$result);
          }

         return  array_column($res,NULL,"id");

    }
}
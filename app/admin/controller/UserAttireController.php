<?php

namespace app\admin\controller;

ini_set('memory_limit', '1024M');
use app\admin\common\AdminBaseController;
use app\admin\model\MemberModel;
use app\admin\model\UserPropsModel;
use app\common\RedisCommon;
use think\facade\Request;
use think\facade\View;

class UserAttireController extends AdminBaseController
{

    /**
     * UserAttire ：用户装扮列表展示
     */
    public function UserAttire()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('uid'); //礼物ID
        $status = Request::param('status'); //装扮的状态
        $where = [];

        if ($user_id) {
            $where['uid'] = $user_id;
        }

        $count = UserPropsModel::getInstance()->getModel($user_id)->where($where)->count();
        $props = UserPropsModel::getInstance()->getModel($user_id)->order('id', 'asc')->where($where)->limit($page, $pagenum)->select()->toArray();
        $image_url = config('config.APP_URL_image');
        foreach ($props as $k => $v) {
            $props[$k] = array_merge($v, $this->propConf($v['kind_id']));
            $props[$k]['image'] = $image_url . $props[$k]['image'];
            $props[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            if ($v['count'] > 0) {
                $props[$k]['expires_time'] = '/';
            } else {
                $props[$k]['expires_time'] = date('Y-m-d H:i:s', $v['expires_time']);
            }
        }
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $propsData = json_decode($redis->get('prop_conf'), true);
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('list', $props);
        View::assign('attire', $propsData);
        View::assign('uid', $user_id);
        View::assign('status', $status);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('attire/userattirelist');
    }

    public function propConf($kind_id)
    {
        if ($kind_id == true) {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $prop = json_decode($redis->get('prop_conf'), true);
            foreach ($prop as $k => $v) {
                if ($v['kindId'] == $kind_id) {
                    return $v;
                }
            }
        } else {
            return [];
        }
    }

    /**
     * addUserAttire : 用户装扮添加
     */
    public function addUserAttire()
    {
        $uid = Request::param('userid'); //用户id
        $attire_id = Request::param('attire_id'); //道具id
        $change = Request::param('endtime'); //天数

        $is_user = MemberModel::getInstance()->getModel($uid)->where(['id' => $uid])->value('id');
        if (empty($is_user) || empty($uid) || empty($attire_id) || empty($change)) { //验证
            echo json_encode(['code' => 500, 'msg' => '参数错误']);
            die;
        }

        $adminId = $this->getAdminIdByToken(Request::param('token'))['id'];
        echo $this->inner($uid, 'prop:' . $attire_id, $change, $adminId);
    }

}
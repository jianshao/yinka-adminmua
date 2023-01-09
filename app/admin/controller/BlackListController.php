<?php
namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\AdminUserModel;
use app\admin\model\BlackListModel;
use app\admin\model\MemberModel;
use app\admin\model\UserBlackModel;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class BlackListController extends AdminBaseController
{

    /**
     * @return mixed
     * 三封列表
     * dongbozhao
     * 2020/11/27
     * 18:09
     */
    public function UserBlack()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $user_id = Request::param('user_id'); //礼物ID
        $login_ip = Request::param('login_ip'); //礼物ID
        $status = Request::param('status'); //礼物ID
        $register_ip = Request::param('register_ip'); //礼物ID
        $device_id = Request::param('device_id'); //礼物ID
        $where = [];
        if ($status != '') {
            $where[] = ['status', '=', $status];
        }
        if ($user_id) {
            $where[] = ['user_id', '=', $user_id];
        }
        if ($login_ip) {
            $where[] = ['login_ip', '=', $login_ip];
        }
        if ($register_ip) {
            $where[] = ['register_ip', '=', $register_ip];
        }
        if ($device_id) {
            $where[] = ['device_id', '=', $device_id];
        }
        $count = UserBlackModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = UserBlackModel::getInstance()->getModel()->where($where)->order('id desc')->limit($page, $pagenum)->select()->toArray();
            foreach ($data as $k => $v) {
                $data[$k]['ctime'] = date('Y-m-d H-i-s', $v['ctime']);
                if ($data[$k]['admin_id'] != 0) {
                    $data[$k]['admin_name'] = AdminUserModel::getInstance()->getModel()->where('id', $data[$k]['admin_id'])->value('username');
                } else {
                    $data[$k]['admin_name'] = '';
                }
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('三封列表获取成功:操作人:' . $this->token['username'], 'UserBlack');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('login_ip', $login_ip);
        View::assign('uid', $user_id);
        View::assign('register_ip', $register_ip);
        View::assign('status', $status);
        View::assign('device_id', $device_id);
        return View::fetch('black/UserBlack');
    }

    public function SaveUserBlack()
    {
        echo json_encode(['code' => 500, 'msg' => '接口已停用']);die;

        $id = Request::param('ubid'); //id
        $type = Request::param('type'); //类型
        $status = Request::param('status'); //状态
        $login_ip = Request::param('login_ip'); //登录IP
        $device_id = Request::param('device_id'); //设备IP
        $register_ip = Request::param('register_ip'); //注册IP
        if ($type == '1') {
            $is = UserBlackModel::getInstance()->getModel()->where('id', $id)->save(['status' => 0]);
        }
        if ($type == '2') {
            $is = UserBlackModel::getInstance()->getModel()->where('id', $id)->save(['status' => $status, 'login_ip' => $login_ip, 'device_id' => $device_id, 'register_ip' => $register_ip]);
        }
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    /**禁麦接口调用
     * @param uid 用户id
     * @return mixed    返回值
     */
    public function addKickUser()
    {
        $token = Request::param('token');
        if (empty($token)) {
            return $this->return_json(\constant\CodeConstant::CODE_Token错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_Token错误]);
        }
        $admin_id = $this->getAdminIdByToken($token);
        if (empty($admin_id)) {
            return $this->return_json(\constant\CodeConstant::CODE_Token错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_Token错误]);
        }
        $user_id = Request::param('uid');
        $pretty = MemberModel::getInstance()->prettyUser($user_id);
        if ($pretty) {
            $uid = $pretty['id'];
        } else {
            $uid = $user_id;
        }
        if ($uid) {
            $url = "https://api.agora.io/dev/v1/kicking-rule/";
            $arr_header[] = "Content-Type:application/json";
            $arr_header[] = "Authorization: Basic " . base64_encode("4ea37f833b304704865429ff8a075de6:ec1222aaca99477793faebe4cc8b647e"); //http basic auth
            $Agora_appid = config('config.Agora_appid');
//            var_dump($Agora_appid);die();
            //            $testAppId = 'e468e99e3ebc424b9575797f0886e3d6';//测试服
            //            $appId = '1589cf256aab42549be6be4272d91cf2'; //正式服

            $data = array(
                "appid" => $Agora_appid,
                "uid" => $uid,
                "time" => 1440,
                "privileges" => ["join_channel"],
            );
            $data_json = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url); //request url
            curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //print
            if (!empty($arr_header)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $arr_header);
            }
            $response = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($response, true);
//            var_dump($result);die();
            if ($result['status'] == 'success') {
                $res = $this->userBan($uid, $result['id'], $admin_id);
                if ($res['code'] == 200) {
                    return json(['code' => $res['code'], 'msg' => $res['msg']]);
                } else {
                    return json(['code' => $res['code'], 'msg' => $res['msg']]);
                }
            } else {
                return $this->return_json(500, [], '声网封禁失败', 1);
            }
        } else {
            return $this->return_json(500, [], '用户ID不能为空', 1);
        }
    }

    /*
     * 进行封号数据操作
     * @param $uid 用户id
     * @param $id   封号的id 17231012
     * @param $admin_id     封禁平台id
     */
    public function userBan($uid, $id, $admin_id)
    {
        $result = BlackListModel::getInstance()->selectBan($uid);
        $pretty = MemberModel::getInstance()->getPretty($uid);
        if ($result) {
            if ($result['status'] == 1) {
                return ['code' => 201, 'msg' => '用户已被封禁，请勿重复操作'];
            } else {
                $data = [
                    'admin_id' => $admin_id,
                    'kick_id' => $id,
                    'time' => 1440,
                    'update_time' => time(),
                    'pretty_id' => $pretty['pretty_id'],
                    'status' => 1,
                ];
                $res = BlackListModel::getInstance()->getModel()->where(array('uid' => $uid))->update($data);
                if ($res) {
                    return ['code' => 200, 'msg' => '封禁成功'];
                } else {
                    return ['code' => 202, 'msg' => '封禁失败'];
                }
            }
        } else {
            $data = [
                'admin_id' => $admin_id,
                'kick_id' => $id,
                'uid' => $uid,
                'time' => 1440,
                'create_time' => time(),
                'pretty_id' => $pretty['pretty_id'],
                'status' => 1,
            ];
            $res = BlackListModel::getInstance()->getModel()->insert($data);
            if ($res) {
                return ['code' => 200, 'msg' => '封禁成功'];
            } else {
                return ['code' => 202, 'msg' => '封禁失败'];
            }
        }
    }

    /*
     * 解除禁麦数据操作
     * @param $token token值
     * @param $uid 用户id
     * @param $kick_id   封禁时id
     */
    public function delKickUser()
    {
        $token = Request::param('token');
        if (empty($token)) {
            return $this->return_json(\constant\CodeConstant::CODE_Token错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_Token错误]);
        }
        $admin_id = $this->getAdminIdByToken($token);
        if (empty($admin_id)) {
            return $this->return_json(\constant\CodeConstant::CODE_Token错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_Token错误]);
        }
        $uid = Request::param('uid');
        $kick_id = Request::param('kick_id');
        if ($uid && $kick_id) {
            //发送接触封禁
            $url = "https://api.agora.io/dev/v1/kicking-rule/";
            $arr_header[] = "Content-Type:application/json";
            $arr_header[] = "Authorization: Basic " . base64_encode("4ea37f833b304704865429ff8a075de6:ec1222aaca99477793faebe4cc8b647e"); //http basic auth
            //            $appId = '1589cf256aab42549be6be4272d91cf2'; //正式服
            //            $testAppId = 'e468e99e3ebc424b9575797f0886e3d6';//测试服
            $Agora_appid = config('config.Agora_appid');
            $data = array(
                "appid" => $Agora_appid,
                "id" => $kick_id, //封禁时id
            );
            $data_json = json_encode($data);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
            curl_setopt($ch, CURLOPT_HTTPHEADER, $arr_header);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            $output = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($output, true);
            if ($result['status'] == 'success') {
                $res = $this->userDelBan($uid, $kick_id);
                if ($res['code'] == 200) {
                    return json(['code' => $res['code'], 'msg' => $res['msg']]);
                } else {
                    return json(['code' => $res['code'], 'msg' => $res['msg']]);
                }
            } else {
                return json(['code' => 201, 'msg' => "声网解封失败"]);
            }
        } else {
            return json(['code' => 201, 'msg' => "ID不能为空"]);
        }
    }

    /*
     * 进行解封操作
     * @param $uid 用户id
     * @param $kick_id 封禁时id
     */
    public function userDelBan($uid, $kick_id)
    {
//        $bl = new BlackList;
        $result = BlackListModel::getInstance()->checkSw($uid, $kick_id);
        if ($result) {
            $data = ['time' => 0, 'status' => 2, 'update_time' => time()];
            $where = ['uid' => $uid, 'kick_id' => $kick_id];
            $res = BlackListModel::getInstance()->getModel()->where($where)->update($data);
            if ($res) {
                return ['code' => 200, 'msg' => '解封成功'];
            } else {
                return ['code' => 202, 'msg' => '解封失败'];
            }
        } else {
            return ['code' => 201, 'msg' => '该用户没有被封禁'];
        }
    }
//    /*
    //      * 修改封禁时间操作
    //      */
    //    public function updateTime($uid,$kick_id,$value,$field){
    //        $result = Db::table('zb_black_list')->where(array('uid'=>$uid,'kick_id'=>$kick_id));
    //        if($result){
    //            $data = [$field=>$value,'update_time'=>time()];
    //            $where = ['uid'=>$uid, 'kick_id'=>$kick_id];
    //            $res = Db::table('zb_black_list')->where($where)->update($data);
    //            return $res;
    //        }else{
    //            return json(['code'=>201,'msg'=>"该用户没有被封禁"]);
    //        }
    //    }
    //    //更新禁麦时间（最少1，最大1440）
    //    public function postKickUser(){
    //        $uid = input('uid'); //用户ID
    //        $kick_id = input('kick_id'); //声网ID
    //        $value = input('value');//修改的值
    //        $field = input('field');//修改的字段
    //        if($uid && $kick_id && $value && $field){
    //            if($value<1 || $value>1440){
    //                return json(['code'=>201,'msg'=>"最少1分钟，最多1440分钟"]);
    //            }
    //            //发送接触封禁
    //            $url = "https://api.agora.io/dev/v1/kicking-rule/";
    //            $arr_header[] = "Content-Type:application/json";
    //            $arr_header[] = "Authorization: Basic ".base64_encode("4ea37f833b304704865429ff8a075de6:ec1222aaca99477793faebe4cc8b647e"); //http basic auth
    ////            $testAppId = config('APPIDTEST');//测试服
    //            $testAppId = 'e468e99e3ebc424b9575797f0886e3d6';//测试服
    //            $data = array(
    //                "appid" => $testAppId,
    //                "id" => $kick_id,//封禁时id
    //                "time" => $value //更新禁麦时间
    //            );
    //            $data_json  = json_encode($data);
    //            $ch = curl_init(); //初始化CURL句柄
    //            curl_setopt($ch, CURLOPT_URL, $url);
    //            curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
    //            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    //            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"PUT");
    //            curl_setopt($ch, CURLOPT_HTTPHEADER, $arr_header);
    //            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    //            $output = curl_exec($ch);
    //            curl_close($ch);
    //            $result = json_decode($output,true);
    //            if($result['status'] == 'success'){
    //                $res = $this->updateTime($uid,$kick_id,$value,$field);
    //                if($res){
    //                    return json(['code'=>200,'msg'=>"修改封禁时间成功"]);
    //                }else{
    //                    return json(['code'=>201,'msg'=>"修改封禁时间失败"]);
    //                }
    //            }else{
    //                return json(['code'=>201,'msg'=>"声网修改封禁时间失败"]);
    //            }
    //        }else{
    //            return json(['code'=>201,'msg'=>"修改字段未接到"]);
    //        }
    //    }

}

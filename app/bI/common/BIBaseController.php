<?php


namespace app\bI\common;

use app\BaseController;
use think\facade\Request;
use Firebase\JWT\JWT;

class BIBaseController extends BaseController
{
    protected $jwtKet = "xin_yue_BI_jwt_key";
    protected $handler;
    public $code_ok = \constant\CodeConstant::CODE_成功;
    public $code_ok_map = \constant\CodeConstant::CODE_OK_MAP;
    public $code_parameter_err_map = \constant\CodeConstant::CODE_PARAMETER_ERR_MAP; //参数错误
    public $code_inside_err_map = \constant\CodeConstant::CODE_INSIDE_ERR_MAP; //内部错误
    public $url_map = \constant\RouterUrlConstant::URL_MAP;
    private $filterRouter = ['/bI/loginIndex', '/bI/login','/bI/indexConsole'];
    public $userinfo;

    public function initialize()
    {
        if (!in_array(explode('?', $this->request->server('REQUEST_URI'))[0], $this->filterRouter)) {
            $token = $this->request->param('token');
            if (!$token) {
                header('Location: /bI/loginIndex');
                die;
            }
            $userinfo = $this->getAdminIdByToken($token);
            if (empty($userinfo)) {
                header('Location: /bI/loginIndex');
                die;
//                return $this->return_json(\constant\CodeConstant::CODE_请重新登录, null, \constant\CodeConstant::CODE_PARAMETER_ERR_MAP[\constant\CodeConstant::CODE_请重新登录]);
            }
            if ($userinfo['ip'] != $this->request->ip()) {
                header('Location: /bI/loginIndex');
                die;
            }
            $this->userinfo = $userinfo;
            $this->userinfo['token'] = $token;
        }
    }

    public function _getUserRoleMenu($id, $field = '*')
    {
        //查询当前用户对应的角色
        $role_id = UserRoleService::getInstance()->getUserRole(array('user_id' => $id, 'status' => 1), 'role_id');
        if (empty($role_id)) {
            echo $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
            exit;
        }
        //查询角色对应的菜单
        $menu = RoleMenuService::getInstance()->getRoleToMenuLists(array('role_id' => $role_id->role_id, 'status' => 1), 'menu_id');
        if (empty($menu)) {
            echo $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
            exit;
        }
        return MenuService::getInstance()->getMenuItemsWhereIn(implode(',', array_column($menu, 'menu_id')), $field, 'id');
    }


    public function return_json($code = 200, $data = array(), $msg = '', $is_die = 0)
    {
        $out['code'] = $code ?: 0;
        $out['msg'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
        $out['data'] = $data ?: [];

        // $runtime    = round(microtime(true) - THINK_START_TIME, 10);
        // $out['runtime'] = $runtime;

        // return json_encode($out);
        if ($is_die) {
            echo json_encode($out);
            return;
        } else {
            return json_encode($out);
        }
    }


    public function getAdminIdByToken(string $token)
    {
        try {
            $decoded = JWT::decode($token, $this->jwtKet, array('HS256'));
            $tokenInfo = (array)$decoded;
        } catch (\Exception $exception) {
            return null;
        }

        if (empty($tokenInfo['id'])) {
            return null;
        }
        return $tokenInfo;
    }

    protected function getRedis($arr = [])
    {
        $redis_result = config('cache.stores.redis');
        $param['host'] = $redis_result['host'];
        $param['port'] = $redis_result['port'];
        $param['password'] = $redis_result['password'];
        $param['select'] = 0;
        if (!empty($arr)) {
            foreach ($arr as $v => $v) {
                $param[$v] = $v;
            }
        }

        $this->handler = new \Redis;
        $this->handler->connect($param['host'], $param['port'], 0);
        if ('' != $param['password']) {
            $this->handler->auth($param['password']);
        }

        if (0 != $param['select']) {
            $this->handler->select($param['select']);
        }
        return $this->handler;
    }

    //客户端ip
    public function getUserIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    //校验用户权限
//    public function checkUserRule($token = '')
//    {
//        $id = isset($token['id']) ? $token['id'] : $this->token['id'];
//        $where = array('user_id' => $id, 'status' => 1);
//
//        //查询当前用户权限
//        $user_role = UserRoleService::getInstance()->getUserRole($where, '*');
//        if (empty($user_role)) {
//            return $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
//        }
//        $role_id = $user_role->role_id;
//        //获取该用户权限
//        $menu_id = RoleMenuService::getInstance()->getRoleToMenuLists(array('role_id' => $role_id, 'status' => 1), 'menu_id as id');
//        if (empty($menu_id)) {
//            return $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
//        }
//        $getAppointMenuLists = $this->_getAppointMenuLists($menu_id);
//        $where_in = MenuService::getInstance()->getMenuItemsWhereIn(implode(',', $getAppointMenuLists), '*', 'id');
//        $list = $this->_getMenuLists($where_in);
//        return $list;
    //查询当前权限下的菜单
//        $role_menu = RoleMenuService::getInstance()->getRoleMenu($role_id,'*');
//        $router = array_column($role_menu, 'router');
//        if(empty($router) || !in_array($url_router,$router)){
//            return $this->return_json(\constant\CodeConstant::CODE_该用户没有权限, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_该用户没有权限]);
//        }
//        return true;
//    }

    /**
     * @param string $url get请求地址
     * @param int $httpCode 返回状态码
     * @return mixed
     */
    function curl_get($url, $time = 3)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 不做证书校验,部署在linux环境下请改为true
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time);
        curl_setopt($ch, CURLOPT_TIMEOUT, $time);

        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
    }

}

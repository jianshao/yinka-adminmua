<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\GiftModel;
use app\admin\model\UserToGiftModel;
use app\admin\service\GiftService;
use app\admin\service\MemberService;
use app\admin\service\UserToGiftService;
use think\facade\Log;
use think\facade\View;

class SmashCase extends AdminBaseController
{
    //银箱子奖池数量
    private $gift_drop_pool_key = "GiftDropPool";
    //银箱子奖池轮数
    private $has_gift_drop_pool_key = "HasGiftDropPool";

    //金箱子奖池数量
    private $gift_drop_pool_key_jin = "GiftDropPool1";
    //金箱子奖池轮数
    private $has_gift_drop_pool_key_jin = "HasGiftDropPool1";

    /*
     * 银箱子礼物列表
     * @param string $token token值
     * @return json
     */
    public function caseYinList()
    {
        /*$redis = $this->getRedis();
        //获取礼物砸蛋数量
        $redis->select('1');
        $list_len = $redis->LLEN($this->gift_drop_pool_key);
        $list = $redis->LRANGE($this->gift_drop_pool_key, 0, $list_len);
        $arrTmp = [];
        foreach ($list as $key => $value) {
        //将字符串分割为数组
        $arrTmp[] = explode("_", $value);
        }
        unset($list);
        $data = [];
        //获取当前砸蛋奖池轮数
        $data['frequency'] = (int)$redis->get($this->has_gift_drop_pool_key);
        foreach ($arrTmp as $k => $v) {
        //循环将数组的键值拼接起来
        if (!empty(GiftService::getInstance()->getOneById($v[0], 'gift_name'))) {
        $data['egg_list'][$k]['gift_name'] = GiftService::getInstance()->getOneById($v[0], 'gift_name')->toArray()['gift_name'];
        $data['egg_list'][$k]['number'] = (int)$v[1];
        }
        }
        if (!isset($data['egg_list'])) {
        $data['egg_list'] = [];
        }*/
        //查询所有银箱子数据上架
        $where[] = ['status', '=', 1];
        $where[] = ['one_weight', '>', 0];
        $data = GiftModel::getInstance()->getModel()->field('gift_name,one_weight as number')->where($where)->order('one_weight desc')->select()->toArray();
        Log::record('银箱子奖池列表:操作人:' . $this->token['username'], 'caseYinList');
        View::assign('data', $data);
        //View::assign('frequency', $data['frequency']);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('case/caseYinList');
    }

    /*
     * 添加指定用户绑定银箱子礼物
     * @param string $token token值
     * @param int $user_id 用户ID值
     * @param int $gift_id 礼物ID值
     * @return json
     */
    public function addYinGiftUserAssign()
    {
        $user_id = $this->request->param('user_id');
        $gift_id = $this->request->param('gift_id');
        if (!$user_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户ID错误]);
            die;
        }
        if (!$gift_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        //校验用户
        $user = MemberService::getInstance()->getOneById($user_id, 'id,nickname');
        if (empty($user)) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户ID错误]);
            die;
        }
        //校验礼物
        $gift = GiftService::getInstance()->getOneById($gift_id, 'id,gift_name');
        if (empty($gift)) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        //成功则将数据入库
        $data = array(
            'user_id' => $user_id,
            'nick_name' => $user->nickname,
            'gift_id' => $gift_id,
            'gift_name' => $gift->gift_name,
            'created' => time(),
            'create_user' => $this->token['username'],
            'updated' => time(),
            'update_user' => $this->token['username'],
            'status' => 1,
            'type' => 1,
        );
        $insert = UserToGiftService::getInstance()->addUserToGift($data);
        if (!$insert) {
            Log::record('指定用户砸银箱子指定礼物添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addYinGiftUserAssign');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, '', $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        } else {
            Log::record('指定用户砸银箱子指定礼物添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addYinGiftUserAssign');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        }
    }

    /*
     * 指定用户绑定银箱子礼物列表
     * @param string $token token值
     * @param int $page 页数
     * @return json
     */
    public function getYinUserToGiftLists()
    {
        $size = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $size;
        //查询总数
        $getUserToGiftNum = UserToGiftService::getInstance()->getUserToGiftNum(array('type' => 1));
        //当前页数据
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($getUserToGiftNum / $size);
        $list = [];
        if ($getUserToGiftNum > 0) {
            $field = 'id,user_id,nick_name,gift_id,gift_name,uuid,status,is_obtain';
            $list = UserToGiftService::getInstance()->getUserToGiftLists(array('type' => 1), $field, $page, $size);
            if (!empty($list)) {
                foreach ($list as $key => &$value) {
                    if ($value['is_obtain'] == 1) {
                        $list[$key]['is_obtain'] = '未中奖';
                    } elseif ($value['is_obtain'] == 2) {
                        $list[$key]['is_obtain'] = '中奖';
                    } elseif ($value['is_obtain'] == 3) {
                        $list[$key]['is_obtain'] = '已取消';
                    }
                    $value['status'] = $value['status'] == 2 ? '已取消' : '';
                }

            }
        }

        Log::record('银箱子指定列表列表:操作人:' . $this->token['username'], 'getYinUserToGiftLists');
        View::assign('data', $list);
        View::assign('page', $page_array);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('case/getYinUserToGiftLists');
    }

    /*
     * 指定用户绑定银箱子礼物取消
     * @param string $token token值
     * @param int $id 指定用户绑定砸蛋礼物唯一id
     * @return json
     */
    public function delYinUserToGiftAssign()
    {
        $id = $this->request->param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_ID错误]);
            die;
        }
        //校验id
        $where[] = ['id', '=', $id];
        $where[] = ['type', '=', 1];
        $where[] = ['is_obtain', 'in', '1,2'];
        $result = UserToGiftModel::getInstance()->getModel()->where($where)->value('id');
        //$num = UserToGiftService::getInstance()->getUserToGiftItem(array('id' => $id, 'status' => 1, 'type' => 1, 'is_obtain' => 1), 'uuid');
        if (!$result) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        //成功修改数据库
        $ok = UserToGiftModel::getInstance()->getModel()->where('id', $id)->save(['status' => 2, 'is_obtain' => 3]);
        if (!$ok) {
            Log::record('指定用户砸蛋指定礼物删除失败:操作人:' . $this->token['username'] . '@' . $id, 'delYinUserToGiftAssign');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, '', $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        } else {
            Log::record('指定用户砸蛋指定礼物删除成功:操作人:' . $this->token['username'] . '@' . $id, 'delYinUserToGiftAssign');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        }
    }

    /*
     * 金箱子礼物列表
     * @param string $token token值
     * @return json
     */
    public function caseJinList()
    {
        /*$redis = $this->getRedis();
        //获取礼物砸蛋数量
        $redis->select('1');
        $list_len = $redis->LLEN($this->gift_drop_pool_key_jin);
        $list = $redis->LRANGE($this->gift_drop_pool_key_jin, 0, $list_len);
        $arrTmp = [];
        foreach ($list as $key => $value) {
        //将字符串分割为数组
        $arrTmp[] = explode("_", $value);
        }
        unset($list);
        $data = [];
        //获取当前砸蛋奖池轮数
        $data['frequency'] = (int)$redis->get($this->has_gift_drop_pool_key_jin);
        foreach ($arrTmp as $k => $v) {
        //循环将数组的键值拼接起来
        if (!empty(GiftService::getInstance()->getOneById($v[0], 'gift_name'))) {
        $data['egg_list'][$k]['gift_name'] = GiftService::getInstance()->getOneById($v[0], 'gift_name')->toArray()['gift_name'];
        $data['egg_list'][$k]['number'] = (int)$v[1];
        }
        }
        if (!isset($data['egg_list'])) {
        $data['egg_list'] = [];
        }*/
        //查询所有金箱子数据上架
        $where[] = ['status', '=', 1];
        $where[] = ['color_weight', '>', 0];
        $data = GiftModel::getInstance()->getModel()->field('gift_name,color_weight as number')->where($where)->order('color_weight desc')->select()->toArray();
        Log::record('金箱子奖池列表:操作人:' . $this->token['username'], 'caseJinList');
        View::assign('data', $data);
        return View::fetch('case/caseJinList');
    }

    /*
     * 添加指定用户绑定金箱子礼物
     * @param string $token token值
     * @param int $user_id 用户ID值
     * @param int $gift_id 礼物ID值
     * @return json
     */
    public function addJinGiftUserAssign()
    {
        $user_id = $this->request->param('user_id');
        $gift_id = $this->request->param('gift_id');
        if (!$user_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户ID错误]);
            die;
        }
        if (!$gift_id) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        //校验用户
        $user = MemberService::getInstance()->getOneById($user_id, 'id,nickname');
        if (empty($user)) {
            echo $this->return_json(\constant\CodeConstant::CODE_用户ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户ID错误]);
            die;
        }
        //校验礼物
        $gift = GiftService::getInstance()->getOneById($gift_id, 'id,gift_name');
        if (empty($gift)) {
            echo $this->return_json(\constant\CodeConstant::CODE_礼物ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_礼物ID错误]);
            die;
        }
        //成功则将数据入库
        $data = array(
            'user_id' => $user_id,
            'nick_name' => $user->nickname,
            'gift_id' => $gift_id,
            'gift_name' => $gift->gift_name,
            'created' => time(),
            'create_user' => $this->token['username'],
            'updated' => time(),
            'update_user' => $this->token['username'],
            'status' => 1,
            'type' => 2,
        );
        $insert = UserToGiftService::getInstance()->addUserToGift($data);
        if (!$insert) {
            Log::record('指定用户砸金箱子指定礼物添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addJinGiftUserAssign');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, '', $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        } else {
            Log::record('指定用户砸金箱子指定礼物添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addJinGiftUserAssign');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        }
    }

    /*
     * 指定用户绑定金箱子礼物列表
     * @param string $token token值
     * @param int $page 页数
     * @return json
     */
    public function getJinUserToGiftLists()
    {
        $size = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $size;
        //查询总数
        $getUserToGiftNum = UserToGiftService::getInstance()->getUserToGiftNum(array('type' => 2));
        //当前页数据
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($getUserToGiftNum / $size);
        $list = [];
        if ($getUserToGiftNum > 0) {
            $field = 'id,user_id,nick_name,gift_id,gift_name,uuid,status,is_obtain,created';
            $list = UserToGiftService::getInstance()->getUserToGiftLists(array('type' => 2), $field, $page, $size);
            if (!empty($list)) {
                foreach ($list as $key => &$value) {
                    if ($value['is_obtain'] == 1) {
                        $list[$key]['is_obtain'] = '未中奖';
                    } elseif ($value['is_obtain'] == 2) {
                        $list[$key]['is_obtain'] = '中奖';
                    } elseif ($value['is_obtain'] == 3) {
                        $list[$key]['is_obtain'] = '已取消';
                    }
                    $value['status'] = $value['status'] == 2 ? '已取消' : '';
                    $list[$key]['created'] = date('Y-m-d H:i:s', $value['created']);
                }

            }
        }

        Log::record('金箱子指定列表列表:操作人:' . $this->token['username'], 'getJinUserToGiftLists');
        View::assign('data', $list);
        View::assign('page', $page_array);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('case/getJinUserToGiftLists');
    }

    /*
     * 指定用户绑定金箱子礼物取消
     * @param string $token token值
     * @param int $id 指定用户绑定砸蛋礼物唯一id
     * @return json
     */
    public function delJinUserToGiftAssign()
    {
        $id = $this->request->param('id');
        if (!$id) {
            echo $this->return_json(\constant\CodeConstant::CODE_ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_ID错误]);
            die;
        }
        //校验id
        $where[] = ['id', '=', $id];
        $where[] = ['type', '=', 2];
        $where[] = ['is_obtain', 'in', '1,2'];
        $result = UserToGiftModel::getInstance()->getModel()->where($where)->find();
        //$num = UserToGiftService::getInstance()->getUserToGiftItem(array('id' => $id, 'status' => 1, 'type' => 2, 'is_obtain' => 1), 'uuid');
        if (!$result) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        //成功修改数据库
        $ok = UserToGiftService::getInstance()->updateUserToGiftItem(array('status' => 2, 'is_obtain' => 3), array('id' => $id));
        if (!$ok) {
            Log::record('指定用户砸金箱子指定礼物删除失败:操作人:' . $this->token['username'] . '@' . $id, 'delJinUserToGiftAssign');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, '', $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        } else {
            Log::record('指定用户砸金箱子指定礼物删除成功:操作人:' . $this->token['username'] . '@' . $id, 'delJinUserToGiftAssign');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        }
    }

    public function caseObtain()
    {
        $uuid = $this->request->param('uuid');
        Log::record('指定用户砸箱子砸中指定礼物中奖接口接参:uuid:' . $uuid, 'caseObtain');
        if (strlen($uuid) != 36) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }

        $ok = UserToGiftService::getInstance()->updateUserToGiftItem(array('is_obtain' => 2), array('uuid' => $uuid));

        if ($ok) {
            Log::record('指定用户砸箱子砸中指定礼物中奖接口成功:uuid:' . $uuid, 'caseObtain');
            die;
        }
        Log::record('指定用户砸箱子砸中指定礼物中奖接口失败:uuid:' . $uuid, 'caseObtain');
        die;
    }
}

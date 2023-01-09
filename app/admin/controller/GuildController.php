<?php
/**
 * Created by PhpStorm.
 * User: pussycat
 * Date: 2019/7/23
 * Time: 21:01
 */

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\common\ApiUrlConfig;
use app\admin\model\LanguageroomModel;
use app\admin\model\MemberGuildModel;
use app\admin\service\ApiService;
use app\admin\service\MemberGuildService;
use app\admin\service\MemberService;
use app\admin\service\MemberSocityService;
use app\admin\service\UploadFileService;
use app\admin\service\VsitorExternnumberService;
use app\exceptions\ApiExceptionHandle;
use OSS\Core\OssException;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;
use Throwable;

class GuildController extends AdminBaseController
{
    /*
     * 阿里OSS
     */
    public function ossFile()
    {
        $image = request()->file('image');
        if (empty($image)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);die;
        }
        try {
            $res = UploadFileService::getInstance()->uplaodImg('/guildLogo', $image);
            Log::record('添加图片成功:操作人:' . $this->token['username'] . ':更新条件:' . ':内容:', 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, $res, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (OssException $e) {
            Log::record('添加图片失败:操作人:' . $this->token['username'] . ':更新条件:' . ':内容:', 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    public function roomRunningWaterList()
    {
        $list = MemberGuildService::getInstance()->roomRunningWaterList(Request::get());
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('list', $list['list']);
        View::assign('type', $list['type']);
        View::assign('id', $list['id']);
        View::assign('demo', $list['demo']);
        return View::fetch('guild/roomRunningWaterList');
    }

    public function rooomUserTime($data)
    {
//        $data = UserOnlineRoomCensusModel::getInstance()->getModel()->where($where)->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['hours'] = 0;
            if ($v['online_second'] >= 3600) {
                $data[$k]['hours'] = floor($v['online_second'] / 3600);
            }
//            $data[$k]['day'] = 0;
            //            if($v['online_second'] <=3){
            //                $data[$k]['day'] = 1;
            //            }
        }
        return array_sum(array_column($data, 'hours'));
    }

    /*
     * 公会列表
     * @param string $token token值
     * @param string $page  分页
     * @param string $guild_id 公会id
     */
    public function guildList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $id = Request::get('id/d'); //强制转换为整型类型
        $user_id = Request::get('user_id'); //强制转换为整型类型
        $nickname = Request::get('nickname');
        $upperlower = Request::param('upperlower', 1);

        $where = [];
        if ($upperlower == 1) {
            $where[] = ['status', '=', 1];
        } else {
            $where[] = ['status', '=', 0];
        }
        if ($id) { //公会ID
            $where[] = ['id', '=', $id];
        }
        if ($nickname) { //公会ID
            $where[] = ['nickname', 'like', '%' . $nickname . '%'];
        }
        if ($user_id) { //公会ID
            $where[] = ['user_id', '=', $user_id];
        }
        $list = MemberGuildService::getInstance()->getList($where, array($page, $pagenum));

        $count = 0;
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['totalmember'] = MemberSocityService::getInstance()->countes(['guild_id' => $value['id'], 'status' => 1]);
                $list[$key]['proportionally'] = ($value['proportionally'] * 100);

                $list[$key]['roomCount'] = LanguageroomModel::getInstance()->getWhereCount(['guild_id' => $value['id']]);

                $list[$key]['IndexMemberCount'] = LanguageroomModel::getInstance()->getWhereCount(['guild_index_id' => $value['id']]);
                $list[$key]['IndexRoomCount'] = LanguageroomModel::getInstance()->getWhereCount(['guild_index_id' => $value['id']]);

                $list[$key]['diamond'] = floor($value['diamond'] - $value['free_diamond']) / 100 . "元";
                $list[$key]['status_types'] = $value['status'];
                $list[$key]['nickname'] = addslashes($value['nickname']);
                $list[$key]['status'] = $value['status'] == 1 ? '正常' : '下架';
                $list[$key]['logo_url'] = $this->img_url . $value['logo_url'];
            }

            $count = MemberGuildService::getInstance()->countes($where);

        }
        Log::record('公会列表:操作人:' . $this->token['username'], 'guildList');
        $admin_url = config('config.admin_url');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('user_id', $user_id);
        View::assign('nickname', $nickname);
        View::assign('admin_url', $admin_url);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('search_id', $this->request->param('id'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('upperlower', $upperlower);
        return View::fetch('guild/index');
    }

    /*公会成员列表]
     * @param string $token token值
     * @param string $page 分页
     * @param string $guild_id 公会id
     * @param string $user_id 用户id
     * @return mixed
     */
    public function guildMember()
    {
        $pagenum = 99999;
        $page = $this->request->param('page', 1);
        $offset = ($this->request->param('page') - 1) * $pagenum;
        $guild_id = Request::param('guild_id'); //公会id
        $user_id = Request::get('user_id/d'); //强制转换为整型类型
        $where = [];
        $where[] = ['s.status', '=', 1];
        if ($user_id) { //公会ID
            $where[] = ['s.user_id', '=', $user_id];
            $where[] = ["s.guild_id", '=', $guild_id];
        } else {
            $where[] = ['s.guild_id', '=', $guild_id];
            $where[] = ['s.status', '=', 1];
        }
        $regex = "/\/|\～|\，|\。|\"|\？|\“|\”|\【|\】|\『|\』|\：|\；|\《|\》|\’|\‘|\ |\·|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";

        $list = MemberSocityService::getInstance()->getList($where, [$offset, $pagenum]);
        if (!empty($list)) {
            foreach ($list as $k => &$v) {
                $v['socity'] = $v['socity'] * 100;

                $user_info = MemberService::getInstance()->getOneById($v['user_id'], 'username,nickname,sex,avatar');

                $v['nickname'] = '';
                $v['avatar'] = '';
                $v['username'] = '';
                $v['sex'] = '';

                if ($user_info) {
                    $v['nickname'] = preg_replace($regex, "", $user_info->nickname);
                    $v['avatar'] = getavatar($user_info->avatar);
                    $v['username'] = $user_info->username;
                    if ($user_info->sex == 1) {
                        $v['sex'] = "男";
                    } else if ($user_info->sex == 2) {
                        $v['sex'] = "女";
                    } else {
                        $v['sex'] = "保密";
                    }
                }
            }
        }
        Log::record('公会成员列表:操作人:' . $this->token['username'], 'guildMember');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /*添加公会成员
     * @param string $token token值
     * @param string $guild_id 公会id
     * @param string $user_id 用户id
     * @return mixed
     */
    public function insertMember()
    {
        try {
            $guild_id = Request::param('guild_id'); //公会id
            $user_id = Request::param('user_id');

            if (!$guild_id && !$user_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, '公会与公会用户参数不能为空');
                die;
            }

            // $is = MemberModel::getInstance()->userRole($user_id);
            // $isSocity = MemberSocityModel::getInstance()->getUser($user_id);
            // $where1[] = ['user_id', '=', $user_id];
            // $where1[] = ['status', '=', 1];
            // $isGuild = MemberGuildModel::getInstance()->getModel()->field('id,user_id')->where($where1)->find();
            // if (empty($is)) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_用户ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户ID错误]);
            //     die;
            // } else if ($isSocity) {
            //     echo json_encode(['code' => 500, 'msg' => '用户已有公会']);
            //     die;
            // } else if (isset($isGuild)) {
            //     if ($isGuild['id'] != $guild_id) {
            //         echo $this->return_json(\constant\CodeConstant::CODE_用户已经创建了其他公会, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户已经创建了其他公会]);
            //         die;
            //     }
            // }
            // $data = [
            //     'guild_id' => $guild_id,
            //     "user_id" => $user_id,
            //     'socity' => 0.7,
            //     'status' => 1,
            //     'addtime' => date('Y-m-d H:i:s', time()),
            // ];
            // $value = [
            //     'guild_id' => $guild_id,
            //     'role' => 1,
            // ];
            // $where['id'] = $user_id;

            // MemberModel::getInstance()->startTrans();
            // MemberModel::getInstance()->setMember($where, $value);
            // MemberSocityModel::getInstance()->getModel()->save($data);
            // MemberModel::getInstance()->commit();

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => $user_id,
                'guildId' => $guild_id,

            ];
            ApiService::getInstance()->curlApi(ApiUrlConfig::$add_guild_member, $params, true);

            Log::record('公会成员添加成功:操作人:' . $this->token['username'] . '@' . json_encode($params), 'insertMember');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Exception $e) {
            Log::record('公会成员添加失败:操作人:' . $this->token['username'] . '@' . json_encode($params), 'insertMember');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);die;
        }
    }

    /*
     * 添加公会
     * @param string $token token值
     * @param string $user_id 用户id
     * @param string $nickname 昵称
     * @param string $password 密码
     * @param string $proportionally 分成比例
     */
    public function addGuilds()
    {
        try {
            $user_id = Request::param('user_id'); //获取会长id
            $nickname = Request::param('nickname'); //公会昵称
            $password = Request::param('password'); //公会密码
            $proportionally = Request::param('proportionally'); //公会分成比例

            // if (MemberGuildModel::getInstance()->getModel()->where('nickname', $nickname)->value('id')) {
            //     echo json_encode(['code' => 500, 'msg' => '工会名称已存在']);die;
            // }

            // if (MemberGuildModel::getInstance()->getModel()->where('user_id', $user_id)->value('id')) {
            //     echo json_encode(['code' => 500, 'msg' => '此用户创建过工会']);die;
            // }

            // if ($proportionally >= 100) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_公会分成比例不能超过百分比, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_公会分成比例不能超过百分比]);die;
            // }

            // $is = MemberModel::getInstance()->fieldFind($user_id, $field = 'id,username');
            // if (empty($is)) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_用户ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户ID错误]);
            //     die;
            // }
            // if ($is['id'] != $is['pretty_id']) {
            //     $user_id = $is['id'];
            // }

            // $search_guild_where1 = [
            //     ['status', '=', 1], ['user_id', '=', $user_id],
            // ];
            // $search_guild_where2 = [
            //     ['phone', '=', $is['username']],
            // ];
            // $isGuild = MemberGuildModel::getInstance()->getModel()->where([$search_guild_where1, $search_guild_where2])->value('id'); //判断当前是否公会
            // if ($isGuild) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_用户已经创建了其他公会, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户已经创建了其他公会]);die;
            // }
            // $isSocity = MemberSocityModel::getInstance()->getUser($user_id); //判断当前是否公会的成员
            // $isDai = DaichongModel::getInstance()->getModel()->where(['uid' => $user_id, 'status' => 1])->find();
            // if ($isSocity && $isDai) {
            //     echo json_encode(['code' => 500, 'msg' => '用户已有公会']);
            //     die;
            // }
            // $data = [
            //     'nickname' => $nickname,
            //     "phone" => $is['username'],
            //     "user_id" => $user_id,
            //     "password" => md5($password),
            //     'proportionally' => $proportionally / 100,
            // ];
            // MemberGuildModel::getInstance()->startTrans();
            // MemberGuildModel::getInstance()->getModel()->save($data);
            // $guild = MemberGuildModel::getInstance()->getModel()->where([['user_id', '=', $user_id], ['status', '=', 1]])->value('id');
            // $value = [
            //     'guild_id' => $guild,
            //     'user_id' => $data['user_id'],
            //     'addtime' => date('Y-m-d H:i:s', time()),
            //     'status' => 1,
            //     'audit_time' => time(),
            // ];
            // $res = MemberSocityModel::getInstance()->save($value);
            // MemberGuildModel::getInstance()->commit();

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => $user_id,
                'nickname' => $nickname,
                'password' => $password,
                'proportionally' => $proportionally,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$create_guild, $params, true);

            Log::record('公会添加成功:操作人:' . $this->token['username'] . '@' . json_encode($params), 'addGuilds');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());
            die;
        } catch (Exception $e) {
            MemberGuildModel::getInstance()->rollback();
            Log::record('公会添加失败:操作人:' . $this->token['username'] . '@' . json_encode($params), 'addGuilds');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /*删除公会成员(通过id删除公会成员)
     * @param string $token token值
     * @param string $user_id 用户id
     * @return mixed
     */
    public function delGuildMember()
    {
        try {
            $user_id = Request::param('user_id'); //获取成员id
            $guild_id = Request::param('guild_id'); //获取工会id

            if (!$user_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_用户ID错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_用户ID错误]);
                die;
            }

            // $data = [
            //     'role' => 2,
            //     'guild_id' => 0,
            //     'socity' => 0.7,
            // ];
            // $where['id'] = $user_id;

            // MemberModel::getInstance()->startTrans();
            // MemberModel::getInstance()->setMember($where, $data);
            // MemberSocityModel::getInstance()->getModel()->where(array("user_id" => $user_id))->delete();
            // MemberGuildModel::getInstance()->commit();

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => $user_id,
                'guildId' => $guild_id,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$remove_guild_member, $params, true);

            Log::record('公会成员删除成功:操作人:' . $this->token['username'] . '@' . json_encode($params), 'delGuildMember');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_删除成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Exception $e) {
            // MemberGuildModel::getInstance()->rollback();

            Log::record('公会成员删除失败:操作人:' . $this->token['username'] . '@' . json_encode($params), 'delGuildMember');
            echo $this->return_json(\constant\CodeConstant::CODE_删除失败, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_删除失败]);die;
        }
    }

    /*修改公会分成比例操作
     * @param string $token token值
     * @param string $id 公会id
     * @param string $field 修改的字段
     * @param stting $value 值
     * @return mixed
     */
    public function guildEdit()
    {
        try {
            $guild_id = Request::param('guild_id'); //公会ID
            $field = Request::param('field'); //修改字段
            $value = Request::param('value'); //新值
            if ($value < 0) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_公会分成比例不能为负数]);
                die;
            } else if ($value >= 100) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_公会分成比例不能超过百分比]);
                die;
            }

            $save = $value / 100;
            // $member = MemberSocityModel::getInstance()->getSocityUser($guild_id); //获取所有工会人员的分成比例
            // foreach ($member as $key => $val) {
            //     $socity = $val['socity'] * 100;
            //     if ($socity > $value) {
            //         //如果成员的值大于公会比例的值
            //         //吧公会成员比例比公会高的调成和公会一样的比例
            //         $dataes = ['socity' => $save];
            //         $wheres['guild_id'] = $guild_id;

            //         Db::transaction(function () use ($wheres, $dataes) {
            //             MemberSocityModel::getInstance()->setSocity($wheres, $dataes);
            //             MemberModel::getInstance()->setMember($wheres, $dataes);
            //         });
            //     }
            // }

            // $where['id'] = $guild_id;
            // $data = [$field => $save];
            // $res = MemberGuildModel::getInstance()->setGuild($where, $data);

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'guildId' => $guild_id,
                'profile' => json_encode([
                    'socity' => $save,
                ]),
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$edit_guild_info, $params, true);

            Log::record('工会修改分成比例:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'guildEdit');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('工会修改分成比例:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'guildEdit');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /*
     * 修改公会成员分成比例
     * @param string $token token值
     * @param string $user_id 用户id
     * @param string $guild_id 公会id
     * @param string $field  修改字段
     * @param string $value 新值
     */
    public function exitMember()
    {
        try {
            $user_id = Request::param('user_id'); //用户ID
            $guild_id = Request::param('guild_id'); //公会ID
            $field = Request::param('field'); //修改字段
            $value = Request::param('value'); //新值

            if ($value < 0) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_公会成员分成比例不能为负数]);
                die;
            } else if ($value >= 100) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_公会成员分成比例不能超过百分比]);
                die;
            }

            // $res = MemberGuildModel::getInstance()->proportionallyInfo($guild_id);
            // $proportionally = $res['proportionally'] * 100;
            // if ($value > $proportionally) {
            //     echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_公会成员分成比例不能超过公会比例]);
            // }

            // $savedata = $value / 100; //调整分成比例
            // $data = [$field => $savedata];
            // $where_socity = ['user_id' => $user_id];
            // $where = ['id' => $user_id];

            // Db::transaction(function () use ($where_socity, $where, $data) {
            //     MemberSocityModel::getInstance()->setSocity($where_socity, $data);
            //     MemberModel::getInstance()->setMember($where, $data);
            // });

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'userId' => $user_id,
                'guildId' => $guild_id,
                'field' => $field,
                'value' => $value,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$edit_guild_member, $params, true);

            Log::record('修改公会成员分成比例:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'guildEdit');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('修改公会成员分成比例:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($params), 'guildEdit');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }

    }

    /*
     * 房间公会列表
     * @param string $token token值
     * @param string $page  分页
     * @param string $guild_id 公会id
     */
    public function guildRoomList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $id = Request::get('id/d'); //强制转换为整型类型
        if ($id) { //公会ID
            $where = ['id' => $id];
        } else {
            $where = [];
        }
        $list = MemberGuildService::getInstance()->getList($where, array($page, $pagenum));
        $count = 0;

        $wheres = [];
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['totalmember'] = MemberSocityService::getInstance()->countes(['guild_id' => $value['id']]);
                $list[$key]['proportionally'] = ($value['proportionally'] * 100);
            }
            $count = MemberGuildService::getInstance()->countes($wheres);
        }
        Log::record('公会列表:操作人:' . $this->token['username'], 'guildRoomList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $daytime = date('Y-m-d', time());
        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('daytime', $daytime);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('search_id', $this->request->param('id'));
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('guild/guildlist');
    }

    /*
     * 公会房间详情列表
     * @param string $token token值
     * @param string $page  分页
     * @param string $guild_id 公会id
     */
    public function guildRoomdetail()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $guild_id = Request::get('guild_id/d'); //强制转换为整型类型
        $user_id = Request::get('user_id/d'); //强制转换为整型类型
        if (!$guild_id || !$user_id) {
            echo "请在公会房间列表操作";
            die;
        }
        $start_time = Request::get('start_time');
        $end_time = Request::get('end_time');
        if ($start_time && $end_time) {
            $start_time = $start_time . ' 00:00:00';
            $end_time = $end_time . " 00:00:00";
        } else {
            $start_time = date('Y-m-d');
            $end_time = date('Y-m-d', strtotime('+1 day'));
        }
        $guild_data = [];
        //根据公会id获取公会对应的房间room_id
        // $guild_data = LanguageroomModel::getInstance()->getModel()->field('id as room_id,room_name,guild_index_id')->where(['guild_id' => $guild_id])->whereOr(['guild_index_id' => $guild_id])->select()->toArray();
        $whereOr = [
            [['guild_id', '=', $guild_id]],
            [['guild_index_id', '=', $guild_id]],
        ];

        $guild_data = LanguageroomModel::getInstance()->getList($whereOr, 0, 'id as room_id,room_name,guild_index_id');

        // dd($guild_data);
        foreach ($guild_data as $gk => $gv) {
            $guild_data[$gk]['room_type'] = '工会房';
            if ($gv['guild_index_id'] != 0) {
                $guild_data[$gk]['room_type'] = '首页工会房';
            }
            $guild_data[$gk]['room_id'] = $gv['room_id'];
            $guild_data[$gk]['room_name'] = $gv['room_name'];
            $guild_data[$gk]['addtime'] = $start_time;
            $guild_data[$gk]['totailcoin'] = 0;
            $guild_data[$gk]['scale'] = "10";
            $guild_data[$gk]['commission'] = $guild_data[$gk]['totailcoin'] / 10;
        }

        $data = array_values($guild_data);
        $count = 100;
        Log::record('公会列表:操作人:' . $this->token['username'], 'guildRoomList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('guild_id', $guild_id);
        View::assign('user_id', $user_id);
        View::assign('strtime', $start_time);
        View::assign('endtime', $end_time);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('search_id', $this->request->param('id'));
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('guild/guildroomdetail');
    }

    /**
     * 取消公会房间id
     */
    public function delGuidRoom()
    {
        try {
            $room_id = Request::param('id'); //房间ID
            $guild_id = Request::param('guild_id'); //公会ID
            $room_type = Request::param('room_type');

            if (!$guild_id || !$guild_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            $where['id'] = $room_id;
            if ($room_type == "工会房") {
                $where['guild_id'] = $guild_id;
                $data['guild_id'] = 0;

                $guild_type = 1;
                $url = ApiUrlConfig::$del_guild_room;
            } else {
                $where['guild_index_id'] = $guild_id;
                $data['guild_index_id'] = 0;
                $guild_type = 0;

                $url = ApiUrlConfig::$del_guild_room_index;
            }

            //查询当前公会及房间Id
            $res = LanguageroomModel::getInstance()->getModel($room_id)->where($where)->find();
            if (!$res) {
                echo $this->return_json(\constant\CodeConstant::CODE_没有查询到数据, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_没有查询到数据]);
                die;
            }

            // $data['room_type'] = 9;
            // $data['background_image'] = PhotoWallModel::getInstance()->getModel()->where(array('room_mode' => 1, 'status' => 2, 'start' => 1))->order('id desc')->value('image');
            // //修改公会id
            // $result = LanguageroomModel::getInstance()->getModel($room_id)->where($where)->save($data);

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'room_id' => (int) $room_id,
                'guild_id' => (int) $guild_id,
                'guild_type' => (int) $guild_type,
            ];

            ApiService::getInstance()->curlApi($url, $params, true);
            Log::record('取消公会房间成功:操作人:' . $this->token['username'] . '@' . json_encode($room_id), 'delGuidRoom');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('取消公会房间失败:操作人:' . $this->token['username'] . '@' . json_encode($room_id), 'delGuidRoom');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**
     * 添加公会房间id
     */
    public function addGuidRoom()
    {
        try {
            $room_id = Request::param('room_id'); //房间ID
            $guild_id = $this->request->param('guild_id');
            if (!$room_id || !$guild_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            //查询当前公会ID是否存在
            $guildResult = MemberGuildModel::getInstance()->getModel()->where(array("id" => $guild_id))->find();
            if (empty($guildResult)) {
                echo $this->return_json(\constant\CodeConstant::CODE_公会ID不存在, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_公会ID不存在]);
                die;
            }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'room_id' => (int) $room_id,
                'guild_id' => (int) $guild_id,
                'guild_type' => 1,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$add_guild_room, $params, true);

            //修改房间热门热度值
            $this->_saveRoomNumber($room_id, 1);
            Log::record('修改房间类型:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params), 'exitRoomType');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('修改房间类型:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params), 'exitRoomType');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }

        // $where = ['id' => $room_id]; //条件
        // $pid = RoomModeModel::getInstance()->getModel()->where('id', $check_id)->value('pid');
        // $background_image = PhotoWallModel::getInstance()->getModel()->where(array('room_mode' => $pid, 'status' => 2, 'start' => 1))->order('id desc')->value('image');
        // $data = [
        //     "room_type" => $check_id,
        //     "background_image" => $background_image,
        //     "guild_id" => $guild_id,
        // ];
        // $roomInfo = LanguageroomModel::getInstance()->getModel($room_id)->where($where)->find();
        // //判断是否切分类
        // $typeRes = RoomModeModel::getInstance()->getModel()->where([['id', 'in', [$check_id, $roomInfo['room_type']]]])->column('pid', 'id');
        // $isChangeRoom = false;
        // if (!empty($typeRes)) {
        //     if ($typeRes[$check_id] != $typeRes[$roomInfo['room_type']]) {
        //         $isChangeRoom = true;
        //     }
        // }
        // $room_modeName = RoomModeModel::getInstance()->getModel()->where(array("id" => $check_id))->value("room_mode");
        // $res_type = LanguageroomModel::getInstance()->getModel($room_id)->where($where)->save($data);
        // if ($res_type) {
        //     //修改成功后发送消息操作
        //     $str = ['msgId' => 2050, 'room_name' => $roomInfo['room_name'], 'room_desc' => $roomInfo['room_desc'], 'room_welcomes' => $roomInfo['room_welcomes'], 'modeName' => $room_modeName, 'isChangeRoom' => $isChangeRoom, 'ModePid' => $check_id];
        //     $msg['msg'] = json_encode($str);
        //     $msg['roomId'] = (int) $room_id;
        //     $msg['toUserId'] = '0';
        //     $socket_url = config('config.socket_url');
        //     $msgData = json_encode($msg);
        //     $res = curlData($socket_url, $msgData, 'POST', 'json');
        //     Log::record("房间信息修改类型消息发送参数-----" . $msgData, "info");
        //     Log::record("房间信息修改类型消息发送-----" . $res, "info");
        //     if ($data['background_image']) {
        //         //发消息操作
        //         $str = ['msgId' => 2051, 'room_bg' => getavatar($data['background_image'])];
        //         $msg['msg'] = json_encode($str);
        //         $msg['roomId'] = (int) $room_id;
        //         $msg['toUserId'] = '0';
        //         $socket_url = config('config.socket_url');
        //         $msgData = json_encode($msg);
        //         $res = curlData($socket_url, $msgData, 'POST', 'json');
        //         Log::record("房间背景图发送参数-----" . $msgData, "info");
        //         Log::record("房间背景图发送-----" . $res, "info");
        //     }

        //     //发送公会与类型修改消息开始
        //     $modestr = ['roomId' => (int) $room_id, 'type' => 'mode'];
        //     $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
        //     $msgData = json_encode($modestr);
        //     $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
        //     Log::record("房间类型切换添加发送参数-----" . $msgData, "info");
        //     Log::record("房间类型切换添加发送-----" . $moderesmsg, "info");

        //     //发公会消息操作
        //     $modestr = ['roomId' => (int) $room_id, 'type' => 'guild'];
        //     $socket_url = config('config.socket_url_base') . 'iapi/syncRoomData';
        //     $msgData = json_encode($modestr);
        //     $moderesmsg = curlData($socket_url, $msgData, 'POST', 'json');
        //     Log::record("房间类型切换添加发送参数-----" . $msgData, "info");
        //     Log::record("房间类型切换添加发送-----" . $moderesmsg, "info");

        //     //修改房间热门热度值
        //     $this->_saveRoomNumber($room_id, 1);
        //     Log::record('修改房间类型:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitRoomType');
        //     echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
        //     die;
        // } else {
        //     Log::record('修改房间类型:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitRoomType');
        //     echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
        //     die;
        // }
    }

    /*
     * 修改公会信息
     */
    public function exitGuild()
    {
        try {
            $guild_id = Request::param('guild_id');
            $logo_url = Request::param('logo_url', '');

            $data = [
                'nickname' => Request::param('nickname'),
                'status' => Request::param('status'),
            ];

            if (!empty($logo_url)) {
                $data['logo_url'] = $logo_url;
            }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'guildId' => $guild_id,
                'profile' => json_encode($data),
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$edit_guild_info, $params, true);

            Log::record('修改公会数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($data), 'exitGuild');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('修改公会数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($params) . ':内容:' . json_encode($data), 'exitGuild');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);die;
        }
    }

    /**
     * 添加用户房间到公会房，但是该房间仍然在首页展示
     */
    public function addGuidRoomIndex()
    {
        try {
            $guild_id = Request::param('guild_id');
            $room_id = Request::param('room_id');
            if (!$guild_id || !$room_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }
            $roomInfo = LanguageroomModel::getInstance()->getModel($room_id)->where(['id' => $room_id])->findOrEmpty()->toArray();
            if (empty($roomInfo)) {
                echo $this->return_json(\constant\CodeConstant::CODE_房间ID不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_房间ID不能为空]);
                die;
            }
            if ($roomInfo['guild_id'] != 0) {
                echo $this->return_json(\constant\CodeConstant::CODE_此房间已加入其他公会, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此房间已加入其他公会]);
                die;
            }
            if ($roomInfo['guild_index_id'] != 0) {
                echo $this->return_json(\constant\CodeConstant::CODE_此房间已加入其他公会, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此房间已加入其他公会]);
                die;
            }
            // $data['guild_index_id'] = $guild_id;
            // $res = LanguageroomModel::getInstance()->getModel($room_id)->where(['id' => $room_id])->save($data);

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'room_id' => (int) $room_id,
                'guild_id' => (int) $guild_id,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$add_guild_room_index, $params, true);
            Log::record('添加工会房间首页展示成功:操作人:' . $this->token['username'] . '@' . json_encode($params), 'addUserRoomToGuild');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('添加工会房间首页展示失败:操作人:' . $this->token['username'] . '@' . json_encode($params), 'addUserRoomToGuild');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    public function delGuidRoomIndex()
    {
        try {
            $room_id = Request::param('room_id');
            if (!$room_id) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }
            $roomInfo = LanguageroomModel::getInstance()->getModel($room_id)->where(['id' => $room_id])->findOrEmpty()->toArray();
            if ($roomInfo['guild_index_id'] == 0) {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
                die;
            }

            $params = [
                'operatorId' => $this->token['id'],
                'token' => $this->token['admin_token'],
                'room_id' => (int) $room_id,
            ];

            ApiService::getInstance()->curlApi(ApiUrlConfig::$del_guild_room_index, $params, true);
            Log::record('删除工会房间首页展示成功:操作人:' . $this->token['username'] . '@' . json_encode($params), 'delUserRoomToGuild');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } catch (ApiExceptionHandle $e) {
            echo $this->return_json($e->getCode(), null, $e->getMessage());die;
        } catch (Throwable $th) {
            Log::record('删除工会房间首页展示失败:操作人:' . $this->token['username'] . '@' . json_encode($params), 'delUserRoomToGuild');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    private function _saveRoomNumber($room_id, $number)
    {
        VsitorExternnumberService::getInstance()->saveRoomNumber($room_id, $number);

    }
}
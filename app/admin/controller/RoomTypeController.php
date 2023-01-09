<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ConfigModel;
use app\admin\model\RoomModeModel;
use app\admin\service\ConfigService;
use app\common\RedisCommon;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class RoomTypeController extends AdminBaseController
{
    public function saveRoomModeSor()
    {
        $sid = Request::param('sid');
        $is_sort = Request::param('is_sort');
        $is = RoomModeModel::getInstance()->getModel()->where('id', $sid)->save(['is_sort' => $is_sort]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
        }
    }

    public function delRoomType()
    {
        $id = Request::param('id');
        $is = RoomModeModel::getInstance()->getModel()->where('id', $id)->delete();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
        }
    }

    /**
     * 修改房间分类图标
     */
    public function ossRoomType()
    {
        $array = Request::param();
        $id = $array['modeid'];
        if (array_key_exists('tab_icon', $array)) {
            $data['tab_icon'] = $array['tab_icon'];
        }
        if (array_key_exists('mua_icon', $array)) {
            $data['mua_icon'] = $array['mua_icon'];
        }
        $is = RoomModeModel::getInstance()->getModel()->where('id', $id)->save($data);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    /**
     * 房间类型列表
     */
    public function roomTypeList()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $typeRoom = Request::param('type');
        $where = [];
        if ($typeRoom) {
            $where[] = ['pid', '=', $typeRoom];
        }
        $count = RoomModeModel::getInstance()->getModel()->where($where)->count();
        $list = RoomModeModel::getInstance()->getModel()->where($where)->order('is_sort desc')->limit($page, $pagenum)->select()->toArray();
        $type = RoomModeModel::getInstance()->getModel()->where(['pid' => 0])->field(['id', 'room_mode name'])->select()->toArray();
        if (!empty($list)) {
            foreach ($list as $key => $vo) {
                $vo['tab_icon'] = isset($vo['tab_icon']) ? $vo['tab_icon'] : '';
                $vo['mua_icon'] = isset($vo['mua_icon']) ? $vo['mua_icon'] : '';
                $list[$key]['tab_icon'] = $this->img_url . $vo['tab_icon'];
                $list[$key]['mua_icon'] = $this->img_url . $vo['mua_icon'];
                if ($vo['pid'] == 1) {
                    $list[$key]['pid'] = "聊天";
                } else if ($vo['pid'] == 2) {
                    $list[$key]['pid'] = "游戏";
                } else if ($vo['pid'] == 100) {
                    $list[$key]['pid'] = "派对";
                }
            }
        }
        Log::record('房间类型列表:操作人:' . $this->token['username'], 'roomTypeList');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('type', $type);
        View::assign('typeRoom', $typeRoom);
        return View::fetch('roomtype/index');
    }

    public function addRoomTagRedis()
    {
        $key = 'room_mode_conf';
        $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
        $list = RoomModeModel::getInstance()->getModel()->select();
        $data = !empty($list) ? $list->toArray() : [];
        if ($data) {
            foreach ($data as $k => $v) {
                $data[] = [
                    'id' => (int) $v['id'],
                    'pid' => (int) $v['pid'],
                    'tag_name' => $v['room_mode'],
                    'status' => $v['status'],
                    'is_show' => $v['is_show'],
                    'is_sort' => $v['is_sort'],
                    'micnum' => $v['micnum'],
                    'type' => $v['type'],
                    'tag_img_mua' => $v['mua_icon'],
                    'tag_img_yinlian' => $v['tab_icon'],
                ];
            }
        }
        ConfigModel::getInstance()->getModel()->where('name', 'room_mode_conf')->save(['json' => json_encode($data)]);
        $is = $redis->set($key, json_encode($data));
        ConfigService::getInstance()->register();
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    /**
     * 分类状态修改
     */
    public function editTypeStatus()
    {
        $id = Request::param('id');
        $status = Request::param('status');
        if (empty($id) || !is_numeric($status)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }

        $data['status'] = $status;
        $where[] = ['id', '=', $id]; //条件
        $res = RoomModeModel::getInstance()->getModel()->where($where)->save($data);
        if ($res) {
            Log::record('修改分类状态成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' .
                json_encode($data), 'editTypeStatus');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改分类状态失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' .
                json_encode($data), 'editTypeStatus');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**
     * 分类是否显示修改
     */
    public function editTypeShow()
    {
        $id = Request::param('id');
        $is_show = Request::param('is_show');
        if (empty($id) || !is_numeric($is_show)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }

        $data['is_show'] = $is_show;
        $where = ['id' => $id]; //条件
        $res = RoomModeModel::getInstance()->getModel()->where($where)->save($data);
        if ($res) {
            Log::record('分类是否显示修改成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editTypeShow');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('分类是否显示修改失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'editTypeShow');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**
     * 添加分类
     */
    public function addRoomType()
    {
        $is_show = Request::param('is_show');
        $pid = Request::param('id');
        $name = Request::param('name');
        $status = Request::param('status');
        $tab_icon = Request::param('tab_icon', '');
        // if (!$tab_icon) {
        //     echo json_encode(['code' => 500, 'msg' => '图标不可空']);
        //     die;
        // }
        if (!$name) {
            echo json_encode(['code' => 500, 'msg' => '分类名称不可为空']);
            die;
        }
        if (!$pid) {
            echo json_encode(['code' => 500, 'msg' => '大分类不可为空']);
            die;
        }
        if ($pid == 2) {
            $micnum = 6;
        } else {
            $micnum = 9;
        }
        $if = RoomModeModel::getInstance()->getModel()->where(['room_mode' => $name])->select()->toArray();
        if (empty($if)) {
            $data = [
                'pid' => $pid,
                'creat_time' => time(),
                'room_mode' => $name,
                'is_show' => $is_show,
                'status' => $status,
                'micnum' => $micnum,
                'tab_icon' => $tab_icon,
            ];
            $is = RoomModeModel::getInstance()->getModel()->save($data);
            if ($is) {
                echo json_encode(['code' => 200, 'msg' => '添加成功！']);
                die;
            } else {
                echo json_encode(['code' => 500, 'msg' => '添加失败！']);
                die;
            }
        } else {
            echo json_encode(['code' => 500, 'msg' => '分类已存在！']);
            die;
        }

    }

    /**
     * 分类修改默认
     */
    public function updateType()
    {
        $id = Request::param('id');
        $type = Request::param('type');
        if (empty($id)) {
            echo json_encode(['code' => 500, 'msg' => '参数为空添加失败']);
            die;
        }
        if (!empty($type)) {
            if ($type == 2) {
                echo json_encode(['code' => 500, 'msg' => '默认分类不可切换，请选择非默认切换']);
            }

            // 启动事务
            RoomModeModel::startTrans();
            try {
                RoomModeModel::getInstance()->getModel()->where(['type' => 2])->save(['type' => 1]);
                RoomModeModel::getInstance()->getModel()->where(['id' => $id])->save(['type' => 2]);
                // 提交事务
                RoomModeModel::commit();
                echo json_encode(['code' => 200, 'msg' => '切换成功']);
            } catch (Exception $e) {
                // 回滚事务
                RoomModeModel::rollback();
                echo json_encode(['code' => 500, 'msg' => '切换失败']);
            }
        }
    }

    /**
     * 添加顶级房间分类
     */
    public function addRoomTypeFather()
    {
        $data = [
            'pid' => 0,
            'creat_time' => time(),
            'room_mode' => Request::param('name'),
            'is_show' => 0,
            'status' => 1,
            'micnum' => 9,
            'tab_icon' => '',
        ];
    }

}

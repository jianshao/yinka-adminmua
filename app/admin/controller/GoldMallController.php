<?php

namespace app\admin\controller;

use aliyuncs\src\OSS\Core\OssException;
use app\admin\common\AdminBaseController;
use app\admin\model\AttireModel;
use app\admin\model\AttireStartModel;
use app\admin\model\AttireTypeModel;
use app\admin\model\GiftModel;
use app\admin\model\SiteconfigModel;
use app\admin\service\AttireService;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class GoldMallController extends AdminBaseController
{
    /**
     * 装饰展示列表
     * @return string
     * @throws \Exception
     */
    public function attListGold()
    {

        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;

        $attire_id = Request::param('attire_id'); //礼物ID
        $status = Request::param('status'); //装扮的状态
        $is_show = Request::param('is_show');
        $is_show = empty($is_show) ? 2 : $is_show;
        $where = [];
        $where['is_goldmall'] = 1;

        if (!empty($status)) {
            if ($status == 1) {
                $where['status'] = 1;
            } else if ($status == 3) {
                $where['status'] = 0;
            }
        }

        if ($attire_id) {
            $where['id'] = $attire_id;
        }

        if ($is_show != 2) {
            if ($is_show == 1) {
                $where['is_show'] = 1;
            } else if ($is_show == 3) {
                $where['is_show'] = 0;
            }
        }
        $count = AttireModel::getInstance()->getModel()->where($where)->count();
        $list = AttireService::getInstance()->AttierList($where, $page, $pagenum);

        $attiretype = AttireTypeModel::getInstance()->getModel()->select()->toArray();
        foreach ($attiretype as $k => $v) {
            $type[] = $v;
        }
        if (!empty($list)) {
            foreach ($list as $key => $value) {

                $list[$key]['attire_image'] = config('config.APP_URL_image') . $value['attire_image'];
                foreach ($type as $k => $v) {
                    if ($list[$key]['son_id'] == $type[$k]['id']) {
                        $list[$key]['son_id_name'] = $type[$k]['name'];
                    }
                    if ($list[$key]['pid'] == $type[$k]['id']) {
                        $list[$key]['pid_name'] = $type[$k]['name'];
                    }
                }
                if (isset($list[$key]['is_vip'])) {
                    if ($list[$key]['is_vip'] == 0) {
                        $list[$key]['is_vip'] = '普通装扮';
                    } elseif ($list[$key]['is_vip'] == 1) {
                        $list[$key]['is_vip'] = 'vip装扮';
                    } elseif ($list[$key]['is_vip'] == 2) {
                        $list[$key]['is_vip'] = '超级vip装扮';
                    }
                } else {
                    $list[$key]['is_vip'] = '普通装扮';
                }

            }
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        View::assign('list', $list);
        View::assign('attire_id', $attire_id);
        View::assign('status', $status);
        View::assign('type', $type);
        View::assign('is_show', $is_show);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        return View::fetch('goldmall/goldattire');
    }

    /**装扮分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function attTypeGold()
    {
        $attiretype = AttireTypeModel::getInstance()->getModel()->select()->toArray();
        foreach ($attiretype as $k => $v) {
            $type[] = $v;
            $type[$k]['created_time'] = date("Y-m-d H:i:s", $attiretype[$k]['created_time']);
        }
        return json_encode($type);
    }

    /**多为数组根据指定字段排序
     * @param $array
     * @param $field
     * @param bool $desc
     * @return mixed
     */
    public function sortArrByField(&$array, $field, $desc = false)
    {
        $fieldArr = array();
        foreach ($array as $k => $v) {
            $fieldArr[$k] = $v[$field];
        }
        $sort = $desc == false ? SORT_ASC : SORT_DESC;
        array_multisort($fieldArr, $sort, $array);
        return $array;
    }
    /*
     * 阿里OSS
     */
    public function ossAttFileGold()
    {
        $savePath = '/attire';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $image = request()->file('image');
        if ($image != "") {
            $gift_imageSavename = \think\facade\Filesystem::putFile($savePath, $image);
            $gift_imageObject = str_replace("\\", "/", $gift_imageSavename);
            $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $gift_imageSavename);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ['image' => '/' . $gift_imageObject];
            }
            Log::record('添加装扮/装扮图片成功:操作人:' . $this->token['username'] . ':内容:' . json_encode($data), 'ossFile');
            return ['status' => 1, 'msg' => '上传成功', 'image' => '/' . $gift_imageObject];die;
        } catch (OssException $e) {
            Log::record('添加礼物/装备图片失败:操作人:' . $this->token['username'] . ':内容:' . json_encode($data), 'ossFile');
            return ['status' => 1, 'msg' => '上传失败'];die;
        }
    }

    /**
     * 装扮添加
     */
    public function addAttGold()
    {
        $data = [
            'pid' => Request::param('typeid'),
            'son_id' => Request::param('typepid'),
            'attire_describe' => Request::param('desc'),
            'reward_price' => Request::param('reward_price'),
            'goldmall_time' => Request::param('goldmall_time'),
            'attire_name' => Request::param('attire_name'),
            'status' => 1,
            'is_buy' => Request::param('is_buy'),
            'is_goldmall' => 1,
            'is_vip' => Request::param('is_vip'),
            'get_type' => Request::param('get_type'),
            'is_show' => Request::param('is_show'),
            'attire_image' => Request::param('image'),
            'created_time' => time(),
        ];
        $res = AttireStartModel::getInstance()->getModel()->save($data);
        if ($res) {
            Log::record('装扮添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('装扮添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGiftStart');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }

    }

    /**
     * 修改
     */
    public function attUpdGold()
    {
        $id = Request::param('attire_id'); //修改ID
        $image = Request::param('image');
        $pid = Request::param('typeid1');
        $son_id = Request::param('typepid');
        $data = [];
        if (!empty($son_id)) {
            $data['son_id'] = $son_id;
        }
        if (!empty($pid)) {
            $data['pid'] = $pid;
        }

        if (!empty($image)) {
            $data['attire_image'] = $image;
        }

        $where['id'] = $id;
        $data += [
            'get_type' => Request::param('get_type'),
            'reward_price' => Request::param('reward_price'),
            'goldmall_time' => Request::param('goldmall_time'),
            'attire_describe' => Request::param('desc'),
            'attire_name' => Request::param('attire_name'),
            'status' => Request::param('status'),
            'is_buy' => Request::param('is_buy'),
            'is_show' => Request::param('is_show'),
            'list_type' => Request::param('list_type'),
            'updated_time' => time(),
        ];
        $res = AttireModel::getInstance()->setAttire($where, $data);
        if ($res) {
            Log::record('修改礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /**
     * 装扮上下架切换
     */
    public function statusAttGold()
    {
        $id = Request::param('attireType_id');
        $status = Request::param('status');
        if ($status == 1) {
            $data['status'] = 0;
        } else {
            $data['status'] = 1;
        }
        $where['id'] = $id;
        $res = AttireModel::getInstance()->setAttire($where, $data);
        if ($res) {
            Log::record('修改礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    //查询砸蛋获得礼物
    public function getegggift()
    {
        $data = [];
        $rankconf = ['coin1' => '第一名', 'coin2' => '第二名', 'coin3' => '第三名'];
        $res = SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->findOrEmpty()->toArray();
        $eggjson = json_decode($res['eggjson'], true);
        $giftlist = [];
        $giftids = [];
        if (!empty($eggjson)) {
            foreach ($eggjson as $key => $value) {
                foreach ($value as $k => $v) {
                    $giftids[] = $v;
                }
            }
            $giftlist = GiftModel::getInstance()->getModel()->where([['id', 'in', $giftids]])->select();
            if (!empty($giftlist)) {
                $giftlist = $giftlist->toArray();
            }
            $tmp = [];
            foreach ($giftlist as $key => $value) {
                $tmp[$value['id']] = $value;
            }
            foreach ($eggjson as $key => $value) {
                foreach ($value as $k => $v) {
                    if ($v == $tmp[$v]['id']) {
                        @$data[$key][$k] = ['rank' => $rankconf[$k], 'giftid' => $v, 'giftname' => $tmp[$v]['gift_name']];
                    }
                }
            }
        }
        $jin = isset($data['jin']) ? $data['jin'] : [];
        $yin = isset($data['yin']) ? $data['yin'] : [];
        View::assign('resjin', $jin);
        View::assign('resyin', $yin);
        View::assign('token', $this->request->param('token'));
        return View::fetch('gift/giftdetail');
    }

    //配置砸蛋获得
    public function editegggift()
    {
        $coin1 = Request::param('coin1');
        $coin2 = Request::param('coin2');
        $coin3 = Request::param('coin3');
        $type = Request::param('type');
        if (empty($coin1) || empty($coin2) || empty($coin3)) {
            return rjsonadmin([], 500, '参数设置错误');
        }
        $giftlist = GiftModel::getInstance()->getModel()->where([['id', 'in', [$coin1, $coin2, $coin3]]])->select();
        if (!empty($giftlist)) {
            $giftlist = $giftlist->toArray();
            if (count($giftlist) < 3) {
                return rjsonadmin([], 500, '礼物不存在');
            }
        } else {
            return rjsonadmin([], 500, '礼物不存在');
        }
        $oldres = SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->findOrEmpty()->toArray();
        $olddata = json_decode($oldres['eggjson'], true);
        if (empty($olddata)) {
            $olddata = ['jin' => [], 'yin' => []];
        }
        if ($type == 1) { //金
            $olddata['jin'] = ['coin1' => $coin1, 'coin2' => $coin2, 'coin3' => $coin3];
            SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->save(['eggjson' => json_encode($olddata)]);
            return rjsonadmin([], 200, '设置成功');

        }
        if ($type == 2) { //银
            $olddata['yin'] = ['coin1' => $coin1, 'coin2' => $coin2, 'coin3' => $coin3];
            SiteconfigModel::getInstance()->getModel()->where(['id' => 1])->save(['eggjson' => json_encode($olddata)]);
            return rjsonadmin([], 200, '设置成功');

        }
        return rjsonadmin([], 500, '参数设置错误');
    }

    /*
     *礼物列表
     */
    public function goldGiftList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $type = Request::param('type'); //礼物类型
        $id = Request::param('gift_id'); //礼物ID
        $status = Request::param('status'); //礼物状态
        $isshow = Request::param('isshow');
        $isshow = empty($isshow) ? 3 : $isshow;
        $status = $status == 2 ? $status : 1;
        // if ($type && $id && $status) {
        //     $where = ['gift_type' => $type, 'id' => $id, 'status' => $status,'type'=>0];
        // } else if ($type && $status) {
        //     $where = ['gift_type' => $type, 'status' => $status,'type'=>0];
        // } else if ($type && $id) {
        //     $where = ['gift_type' => $type, 'id' => $id,'type'=>0];
        // } else if ($status && $id) {
        //     $where = ['status' => $status, 'id' => $id,'type'=>0];
        // } else if ($type) {
        //     $where = ['gift_type' => $type,'type'=>0];
        // } else if ($status) {
        //     $where = ['status' => $status,'type'=>0];
        // } else if ($id) {
        //     $where = ['id' => $id,'type'=>0];
        // } else {
        //     $where = ['type'=>0];
        // }
        $where = [];
        $where[] = ['type', '=', 0];
        $where[] = ['is_goldmall', '=', 1];
        if ($type) {
            $where[] = ['gift_type', '=', $type];
        }
        if ($status) {
            $where[] = ['status', '=', $status];
        }
        if ($id) {
            $where[] = ['id', '=', $id];
        }
        if ($isshow != 3) {
            if ($isshow == 2) {
                $where[] = ['is_show', '=', 0];
            } else {
                // $isshow = 1;
                $where[] = ['is_show', '=', 1];
            }
        }
        $count = GiftModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = GiftModel::getInstance()->giftList($where, $page, $pagenum);

            foreach ($data as $key => $val) {
                $data[$key]['is_show'] = $val['is_show'];
                $data[$key]['gift_types'] = $val['gift_type'];
                $data[$key]['class_types'] = $val['class_type'];
                $data[$key]['broadcasts'] = $val['broadcast'];
                $data[$key]['statuss'] = $val['status'];
                if ($data[$key]['animation'] != "") {
                    $data[$key]['animation'] = $url . $val['animation'];
                }
                if ($data[$key]['gift_animation'] != "") {
                    $data[$key]['gift_animation'] = $url . $val['gift_animation'];
                }
                if ($data[$key]['gift_image'] != "") {
                    $data[$key]['gift_image'] = $url . $val['gift_image'];
                }
                if ($val['is_show'] == 1) {
                    $data[$key]['is_shows'] = "是";
                } else {
                    $data[$key]['is_shows'] = "否";
                }
                if ($val['gift_type'] == 1) {
                    $data[$key]['gift_type'] = "普通礼物";
                } else if ($val['gift_type'] == 2) {
                    $data[$key]['gift_type'] = "动画礼物";
                } else if ($val['gift_type'] == 3) {
                    $data[$key]['gift_type'] = '免费礼物';
                } else if ($val['gift_type'] == 4) {
                    $data[$key]['gift_type'] = '猜拳礼物';
                }
                if ($val['class_type'] == 1) {$data[$key]['class_type'] = '礼物';} else { $data[$key]['class_type'] = '小礼物';}
                if ($val['broadcast'] == 1) {$data[$key]['broadcast'] = '广播';} else { $data[$key]['broadcast'] = '不广播';}
                if ($val['status'] == 1) {$data[$key]['status'] = '上架';} else { $data[$key]['status'] = '下架';}
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('礼物列表获取成功:操作人:' . $this->token['username'], 'giftList');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('search_id', $id);
        View::assign('status', $status);
        View::assign('search_type', $type);
        View::assign('search_status', $status);
        View::assign('isshow', $isshow);
        return View::fetch('goldmall/goldgift');
    }

    /*
     * 添加礼物
     */
    public function addGoldGift()
    {
        $getprize = (empty(Request::param('prize_rate')) || Request::param('prize_rate') == '0') ? '' : Request::param('prize_rate');
        $data = [
            'gift_name' => Request::param('gift_name'),
            'gift_number' => Request::param('gift_number'),
            'reward_price' => Request::param('reward_price'),
            'gift_gold' => 0,
            'gift_type' => Request::param('gift_type'),
            'class_type' => Request::param('class_type'),
            'broadcast' => Request::param('broadcast'),
            'status' => Request::param('status'),
            'one_weight' => Request::param('one_weight'),
            'color_weight' => Request::param('color_weight'),
            'is_sort' => Request::param('is_sort'),
            'is_show' => Request::param('is_show'),
            'prize_rate' => $getprize,
            'type' => 0,
            'is_goldmall' => 1,
        ];
        $res = GiftModel::getInstance()->addGifts($data);
        if ($res) {
            // $redis = $this->getRedis();
            // if($data['color_weight'] > 0){
            //     $poolKeyJin = 'gold_egg_pool';//金宝箱奖池key
            //     $poolKeyNumJin = 'gold_egg_pool_num';//金宝箱奖池总量
            //     $redis->DEL($poolKeyJin);
            //     $redis->DEL($poolKeyNumJin);
            // }
            // if($data['one_weight'] > 0){
            //     $poolKeyYin = 'silver_egg_pool';//银宝箱奖池key
            //     $poolKeyNumYin = 'silver_egg_pool_num';//银宝箱奖池总量
            //     $redis->DEL($poolKeyYin);
            //     $redis->DEL($poolKeyNumYin);
            // }
            Log::record('礼物添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
            die;
        } else {
            Log::record('礼物添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addGift');
            echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
            die;
        }
    }

    /*
     * 修改礼物信息
     */
    public function exitGoldGift()
    {
        $id = Request::param('gift_id'); //礼物ID
        $getprize = (empty(Request::param('prize_rate')) || Request::param('prize_rate') == '0') ? '' : Request::param('prize_rate');
        $where['id'] = $id;
        $data = [
            'gift_name' => Request::param('gift_name'),
            'gift_number' => Request::param('gift_number'),
            'reward_price' => Request::param('reward_price'),
            'gift_type' => Request::param('gift_type'),
            'class_type' => Request::param('class_type'),
            'broadcast' => Request::param('broadcast'),
            'status' => Request::param('status'),
            'one_weight' => Request::param('one_weight'),
            'color_weight' => Request::param('color_weight'),
            'is_sort' => Request::param('is_sort'),
            'is_show' => Request::param('is_show'),
            'prize_rate' => $getprize,
        ];
        $res = GiftModel::getInstance()->setGift($where, $data);
        if ($res) {
            // $redis = $this->getRedis();
            // $poolKeyJin = 'gold_egg_pool';//金宝箱奖池key
            // $poolKeyNumJin = 'gold_egg_pool_num';//金宝箱奖池总量
            // $redis->DEL($poolKeyJin);
            // $redis->DEL($poolKeyNumJin);
            // $poolKeyYin = 'silver_egg_pool';//银宝箱奖池key
            // $poolKeyNumYin = 'silver_egg_pool_num';//银宝箱奖池总量
            // $redis->DEL($poolKeyYin);
            // $redis->DEL($poolKeyNumYin);
            Log::record('修改礼物数据成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('修改礼物数据失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'exitGift');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }

    /*
     * 阿里OSS
     */
    public function ossGoldFile()
    {
        $gift_id = Request::param('id');
        $where['id'] = $gift_id;
        $savePath = '/gift';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $gift_image = request()->file('gift_image');
        $animation = request()->file('animation');
        $gift_animation = request()->file('gift_animation');
        if ($gift_image != "") {
            $gift_imageSavename = \think\facade\Filesystem::putFile($savePath, $gift_image);
            $gift_imageObject = str_replace("\\", "/", $gift_imageSavename);
            $gift_imageFile = STORAGE_PATH . str_replace("\\", "/", $gift_imageSavename);
        }
        if ($animation != "") {
            $animationSavename = \think\facade\Filesystem::putFile($savePath, $animation);
            $animationObject = str_replace("\\", "/", $animationSavename);
            $animationFile = STORAGE_PATH . str_replace("\\", "/", $animationSavename);
        }
        if ($gift_animation != "") {
            $gift_animationSavename = \think\facade\Filesystem::putFile($savePath, $gift_animation);
            $gift_animationObject = str_replace("\\", "/", $gift_animationSavename);
            $gift_animationFile = STORAGE_PATH . str_replace("\\", "/", $gift_animationSavename);
        }
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            if ($gift_image != "") {
                $gift_imageResult = $ossClient->uploadFile($bucket, $gift_imageObject, $gift_imageFile); //上传成功
                $data = ['gift_image' => '/' . $gift_imageObject];
                $res = GiftModel::getInstance()->setGift($where, $data);
            }
            if ($animation != "") {
                $animationResult = $ossClient->uploadFile($bucket, $animationObject, $animationFile); //上传成功
                $data = ['animation' => '/' . $animationObject];
                $res = GiftModel::getInstance()->setGift($where, $data);
            }
            if ($gift_animation != "") {
                $gift_animationResult = $ossClient->uploadFile($bucket, $gift_animationObject, $gift_animationFile); //上传成功
                $data = ['gift_animation' => '/' . $gift_animationObject];
                $res = GiftModel::getInstance()->setGift($where, $data);
            }
            Log::record('添加礼物/装备图片成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } catch (OssException $e) {
            Log::record('添加礼物/装备图片失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'ossFile');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

    /**
     * 清除缓存redis
     * 礼物列表接口
     */
    public function clearGoldCache()
    {
        $redis = $this->getRedis();
        $clearindex = $redis->del("list_gifts");
        $clearAll = $redis->del("All_gifts");

        $poolKeyJin = 'gold_egg_pool'; //金宝箱奖池key
        $poolKeyNumJin = 'gold_egg_pool_num'; //金宝箱奖池总量
        $redis->DEL($poolKeyJin);
        $redis->DEL($poolKeyNumJin);

        $poolKeyYin = 'silver_egg_pool'; //银宝箱奖池key
        $poolKeyNumYin = 'silver_egg_pool_num'; //银宝箱奖池总量
        $redis->DEL($poolKeyYin);
        $redis->DEL($poolKeyNumYin);

        if ($clearindex || $clearAll) {
            Log::record('清除礼物缓存成功:操作人:' . $this->token['username'], 'clearCache');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
            die;
        } else {
            Log::record('清除礼物缓存失败:操作人:' . $this->token['username'], 'clearCache');
            echo $this->return_json(\constant\CodeConstant::CODE_清除缓存失败或者没有缓存可以清除, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_清除缓存失败或者没有缓存可以清除]);
            die;
        }
    }

}
<?php
namespace app\admin\controller;

use aliyuncs\src\OSS\Core\OssException;
use app\admin\common\AdminBaseController;
use app\admin\model\NoticeModel;
use app\common\GetuiV2Common;
use think\facade\Config;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class NoticeController extends AdminBaseController
{
    /*
     *公告列表
     */
    public function noticeList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $id = Request::param('notice_id'); //公告ID
        $where = [];
        $now = time();
        if ($id) {
            $where['id'] = $id;
        }
        $count = NoticeModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = NoticeModel::getInstance()->noticeList($where, $page, $pagenum);
            foreach ($data as $key => $val) {
                if ($data[$key]['notice_status'] == 1) {
                    $data[$key]['notice_status'] = "已发布";
                } else if ($data[$key]['notice_status'] == 3) {
                    $data[$key]['notice_status'] = '已删除';
                } else if ($data[$key]['notice_status'] == 4) {
                    $data[$key]['notice_status'] = "已恢复";
                } else {
                    if ($now < $data[$key]['timing_time']) {
                        $old = date('Y-m-d H:i:s', $val['timing_time']);
                        $data[$key]['notice_status'] = $old . "发布";
                    } else if ($data[$key]['timing_time'] == 0) {
                        $data[$key]['notice_status'] = "未发布";
                    } else {
                        $data[$key]['notice_status'] = "未发布";
                    }
                }
                $data[$key]['timing_time'] = date('Y-m-d H:i:s', $val['timing_time']);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('公告列表:操作人:' . $this->token['username'], 'noticeList');
        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('search_id', $id);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('notice/index');
    }
    /*
     * 已发布公告列表
     */
    public function publishedList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $id = Request::param('notice_id'); //公告ID
        $where = [];
        $where['notice_status'] = 1;
        if ($id) {
            $where['id'] = $id;
        }
        $count = NoticeModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = NoticeModel::getInstance()->noticeList($where, $page, $pagenum);
            foreach ($data as $key => $val) {
                $data[$key]['notice_img'] = $url . $val['notice_img'];
                $data[$key]['notice_status'] = '已发布';
                $data[$key]['timing_time'] = date('Y-m-d H:i:s', $val['timing_time']);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('已发布公告列表:操作人:' . $this->token['username'], 'noticeList');
        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('search_id', $id);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('notice/publishedList');
    }
    /*
     * 草稿箱
     */
    public function unpublishedList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $id = Request::param('notice_id'); //公告ID
        $where = [];
        $where['notice_status'] = 2;
        $where = [['notice_status', 'not in', '1,3']];
        if ($id) {
            $where['id'] = $id;
        }
        $count = NoticeModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = NoticeModel::getInstance()->noticeList($where, $page, $pagenum);
            foreach ($data as $key => $val) {
                $data[$key]['notice_img'] = $url . $val['notice_img'];
                if ($data[$key]['notice_status'] == 2) {
                    $data[$key]['notice_status'] = '未发布';
                } else {
                    $data[$key]['notice_status'] = '已恢复';
                }

                $data[$key]['timing_time'] = date('Y-m-d H:i:s', $val['timing_time']);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('未发布公告列表:操作人:' . $this->token['username'], 'noticeList');
        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('search_id', $id);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('notice/unpublishedList');
    }
    /*
     * 已删除公告列表
     */
    public function deletedList()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $url = config('config.APP_URL_image');
        $id = Request::param('notice_id'); //公告ID
        $where = [];
        $where['notice_status'] = 3;
        if ($id) {
            $where['id'] = $id;
        }
        $count = NoticeModel::getInstance()->getModel()->where($where)->count();
        $data = [];
        if ($count > 0) {
            $data = NoticeModel::getInstance()->noticeList($where, $page, $pagenum);
            foreach ($data as $key => $val) {
                $data[$key]['notice_img'] = $url . $val['notice_img'];
                $data[$key]['notice_status'] = '已删除';
                $data[$key]['timing_time'] = date('Y-m-d H:i:s', $val['timing_time']);
            }
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('已删除公告列表:操作人:' . $this->token['username'], 'noticeList');
        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('search_id', $id);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        return View::fetch('notice/deletedList');
    }
    /*
     *公告列表详细信息
     */
    public function noticeListOne()
    {
        $id = Request::param('notice_id'); //公告ID
        $now = time();
        $where['id'] = $id;
        $url = config('config.APP_URL_image');
        $list = NoticeModel::getInstance()->noticeListOne($where);

        if ($list) {
            if ($list['notice_status'] == 1) {
                $list['notice_status'] = "已发布";
            } else if ($list['notice_status'] == 3) {
                $list['notice_status'] = '已删除';
            } else if ($list['notice_status'] == 4) {
                $list['notice_status'] = "已恢复";
            } else {
                if ($now < $list['timing_time']) {
                    $old = date('Y-m-d H:i:s', $list['timing_time']);
                    $list['notice_status'] = $old . "发布";
                } else if ($list['timing_time'] == 0) {
                    $list['notice_status'] = "未发布";
                } else {
                    $list['notice_status'] = "未发布";
                }
            }
            if ($list['updated_time'] == 0) {
                $list['updated_time'] = '暂无修改';
            } else {
                $list['updated_time'] = date('Y-m-d H:i:s', $list['updated_time']);
            }
            if ($list['updated_user'] == "") {
                $list['updated_user'] = '暂无修改';
            }
            $list['timing_time'] = date('Y-m-d H:i:s', $list['timing_time']);
            $list['created_time'] = date('Y-m-d H:i:s', $list['created_time']);

            $list['notice_img'] = $url . $list['notice_img'];
        }
        Log::record('公告详细列表:操作人:' . $this->token['username'], 'noticeListAll');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /*
     * 添加定时公告
     */
    public function addNotice()
    {
        $id = Request::param('notice_id');
        $url = config('config.APP_URL_image');
        $timing_time = Request::param('timing_time');
        $data = [];
        $redisData = [];
        //判断是否上传图片
        if (request()->file('notice_img')) {
            $file = request()->file('notice_img');
            $notice_img = $this->oneOssFile($file);
            $data['notice_img'] = $notice_img;
            $redisData['notice_img'] = $url . $notice_img;
        } else {
            $redisData['notice_img'] = "";
        }
        //判断是否定时
        if ($timing_time == "") {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        } else {
            $data['timing_time'] = strtotime($timing_time);
        }
        //放入数组
        $data['notice_title'] = Request::param('notice_title');
        $data['notice_content'] = Request::param('notice_content');
        $data['notice_status'] = 2;
        //存入redis数组
        $redis = $this->getRedis();
        $redisData['notice_title'] = Request::param('notice_title');
        $redisData['notice_content'] = Request::param('notice_content');
        $redisData['timing_time'] = strtotime($timing_time);
        //修改操作
        if ($id) {
            $where['id'] = $id;
            $status = NoticeModel::getInstance()->getById($where);
            if ($status['notice_status'] == 1) {
                echo $this->return_json(\constant\CodeConstant::CODE_此条公告已经上传过请勿重复上传, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此条公告已经上传过请勿重复上传]);
            }
            if (empty(request()->file('notice_img'))) {
                $redisData['notice_img'] = $url . $status['notice_img'];
            }
            $data['updated_user'] = $this->token['username'];
            $data['updated_time'] = time();
            $res = NoticeModel::getInstance()->editNotice($where, $data);
            if ($res) {
                $result = \constant\CommonConstant::NOTICE_TIMING_KEY . $id;
                $redis->set($result, json_encode($redisData));
                //GetuiCommon::getInstance()->pushMessageToApp($data['notice_title'],$redisData['notice_content']);
                Log::record('修改定时公告成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'addNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            } else {
                Log::record('修改定时公告失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'addNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
                die;
            }
        } else {
            //添加操作
            $data['created_user'] = $this->token['username'];
            $data['created_time'] = time();
            $lastId = NoticeModel::getInstance()->insertNoticeGetId($data);
            if ($lastId) {
                $result = \constant\CommonConstant::NOTICE_TIMING_KEY . $lastId;
                $redis->set($result, json_encode($redisData));
                //GetuiCommon::getInstance()->pushMessageToApp($data['notice_title'],$redisData['notice_content']);
                Log::info('定时公告添加成功:操作人:' . $this->token['username'] . '@lastId:' . $lastId);
                Log::record('定时公告添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                die;
            } else {
                Log::record('定时公告添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'addNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
                die;
            }
        }

    }
    /*
     * 添加立即发送公告
     */
    public function nowAddNotice()
    {
        $id = Request::param('notice_id');
        $url = config('config.APP_URL_image');
        $data = [];
        $redisData = [];
        //判断是否上传图片
        if (request()->file('notice_img')) {
            $file = request()->file('notice_img');
            $notice_img = $this->oneOssFile($file);
            $data['notice_img'] = $notice_img;
            $redisData['notice_img'] = $url . $notice_img;
        } else {
            $redisData['notice_img'] = "";
        }
        //放入数组
        $data['jump_url'] = Request::param('jump_url');
        $data['notice_title'] = Request::param('notice_title');
        $data['notice_content'] = Request::param('notice_content');
        $data['notice_status'] = 1;
        $data['timing_time'] = time();
        //存入redis数组
        $redis = $this->getRedis();
        $redisData['notice_title'] = Request::param('notice_title');
        $redisData['notice_content'] = Request::param('notice_content');
        $redisData['timing_time'] = time();
        //修改操作
        if ($id) {
            $where['id'] = $id;
            $status = NoticeModel::getInstance()->getById($where);
            if ($status['notice_status'] == 1) {
                echo $this->return_json(\constant\CodeConstant::CODE_此条公告已经上传过请勿重复上传, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此条公告已经上传过请勿重复上传]);
            }
            if (empty(request()->file('notice_img'))) {
                $redisData['notice_img'] = $url . $status['notice_img'];
            }
            $data['updated_user'] = $this->token['username'];
            $data['updated_time'] = time();
            $data['timing_time'] = 0;
            $res = NoticeModel::getInstance()->editNotice($where, $data);
            if ($res) {
                $result = \constant\CommonConstant::NOTICE_TIMING_KEY . $id;
                $redis->set($result, json_encode($redisData));
                $res1 = GetuiV2Common::getInstance('config')->toAppTransmission();
                $res2 = GetuiV2Common::getInstance('muaconfig')->toAppTransmission();
                Log::record('test1--------' . json_encode($res1));
                Log::record('test2--------' . json_encode($res2));
                Log::record('修改立即发送公告成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'nowAddNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            } else {
                Log::record('修改立即发送公告失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'nowAddNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
                die;
            }
        } else {
            //添加操作
            $data['created_user'] = $this->token['username'];
            $data['created_time'] = time();
            $lastId = NoticeModel::getInstance()->insertNoticeGetId($data);
            if ($lastId) {
                $result = \constant\CommonConstant::NOTICE_TIMING_KEY . $lastId;
                $redis->set($result, json_encode($redisData));
                //$redis->del('notice_msg_uid');
                $redis->setex('new_notice', 14400, 1);
                $res1 = GetuiV2Common::getInstance('config')->toAppTransmission();
                $res2 = GetuiV2Common::getInstance('muaconfig')->toAppTransmission();
                Log::record('yinlian response--------' . json_encode($res1));
                Log::record('mua     response--------' . json_encode($res2));
                Log::record('立即发送公告添加成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'nowAddNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                die;
            } else {
                Log::record('立即发送公告添加失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'nowAddNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
                die;
            }
        }
    }
    /*
     * 保存公告
     */
    public function saveNotice()
    {
        $id = Request::param('notice_id');
        $url = config('config.APP_URL_image');
        $data = [];
        //判断是否上传图片
        if (request()->file('notice_img')) {
            $file = request()->file('notice_img');
            $notice_img = $this->oneOssFile($file);
            $data['notice_img'] = $notice_img;
        }
        //放入数组
        $data['notice_title'] = Request::param('notice_title');
        $data['notice_content'] = Request::param('notice_content');
        $data['notice_status'] = 2;
        //修改操作
        if ($id) {
            $where['id'] = $id;
            $status = NoticeModel::getInstance()->getById($where);
            if ($status['notice_status'] == 1) {
                echo $this->return_json(\constant\CodeConstant::CODE_此条公告已经上传过请勿重复上传, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_此条公告已经上传过请勿重复上传]);
            }
            if (empty(request()->file('notice_img'))) {
                $redisData['notice_img'] = $url . $status['notice_img'];
            }
            $data['updated_user'] = $this->token['username'];
            $data['updated_time'] = time();
            $data['timing_time'] = 0;
            $data['notice_status'] = 2;
            $res = NoticeModel::getInstance()->editNotice($where, $data);
            if ($res) {
                Log::record('修改保存公告成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'saveNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
                die;
            } else {
                Log::record('修改保存公告失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'saveNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
                die;
            }
        } else {
            //添加操作
            $data['created_user'] = $this->token['username'];
            $data['created_time'] = time();
            $data['timing_time'] = strtotime(Request::param('timing_time'));
            $res = NoticeModel::getInstance()->addNotice($data);
            if ($res) {
                $redis = $this->getRedis();
                //$redis->del('notice_msg_uid');
                $redis->setex('new_notice', 14400, 1);
                Log::record('添加保存公告成功:操作人:' . $this->token['username'] . '@' . json_encode($data), 'saveNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                die;
            } else {
                Log::record('添加保存公告失败:操作人:' . $this->token['username'] . '@' . json_encode($data), 'saveNotice');
                echo $this->return_json(\constant\CodeConstant::CODE_插入失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_插入失败]);
                die;
            }
        }
    }
    /*
     * 删除公告
     */
    public function delNotice()
    {
        $id = Request::param('notice_id');
        if ($id == "") {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where['id'] = $id;
        $data['notice_status'] = 3;
        $data['updated_user'] = $this->token['username'];
        $data['updated_time'] = time();
        $res = NoticeModel::getInstance()->editNotice($where, $data);
        if ($res) {
            Log::record('删除公告成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'delNotice');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('删除公告失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'delNotice');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }
    /*
     * 恢复公告
     */
    public function recoverNotice()
    {
        $id = Request::param('notice_id');
        if ($id == "") {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_参数错误]);
            die;
        }
        $where['id'] = $id;
        $data['notice_status'] = 4;
        $data['updated_user'] = $this->token['username'];
        $data['updated_time'] = time();
        $res = NoticeModel::getInstance()->editNotice($where, $data);
        if ($res) {
            Log::record('恢复公告成功:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'recoverNotice');
            echo $this->return_json(\constant\CodeConstant::CODE_成功, null, $this->code_ok_map[\constant\CodeConstant::CODE_更新成功]);
            die;
        } else {
            Log::record('恢复公告失败:操作人:' . $this->token['username'] . ':更新条件:' . json_encode($where) . ':内容:' . json_encode($data), 'recoverNotice');
            echo $this->return_json(\constant\CodeConstant::CODE_更新失败, null, $this->code_inside_err_map[\constant\CodeConstant::CODE_更新失败]);
            die;
        }
    }
    /*
     * 上传图片
     */
    public function oneOssFile($file, $id = null)
    {
        $where[] = "";
        if ($id) {
            $where['id'] = $id;
        }
        $savePath = '/notice';
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID']; //阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET']; //阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT']; //阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $notice_imgSaveNmae = \think\facade\Filesystem::putFile($savePath, $file);
        $notice_imgObject = str_replace("\\", "/", $notice_imgSaveNmae);
        $notice_imgFile = STORAGE_PATH . str_replace("\\", "/", $notice_imgSaveNmae);
        try {
            $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $gift_animationResult = $ossClient->uploadFile($bucket, $notice_imgObject, $notice_imgFile); //上传成功
            Log::record('上传公告图片成功:操作人:' . $this->token['username'] . ':图片路径:' . json_encode($notice_imgObject), 'oneOssFile');
            return "/" . $notice_imgObject;
        } catch (OssException $e) {
            Log::record('上传公告图片失败:操作人:' . $this->token['username'] . ':图片路径:' . json_encode($notice_imgObject), 'oneOssFile');
            echo $this->return_json(\constant\CodeConstant::CODE_上传公告图片失败, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_上传公告图片失败]);
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

}
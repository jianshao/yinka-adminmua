<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\MemberModel;
use app\admin\model\MemberRecallDetailModel;
use app\admin\model\PushRecallConfModel;
use app\admin\model\PushReportModel;
use app\admin\model\PushTemplateModel;
use app\admin\model\RecallSmsInfoModel;
use app\admin\model\SmsReportModel;
use app\admin\service\ExportExcelService;
use think\facade\Log;
use think\facade\View;
use Throwable;

class PushRecallController extends AdminBaseController
{

    /*push活动的模板消息列表*/
    public function pushTemplateList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $origin_id = $this->request->param('origin_id', '', 'trim'); //模板id
        $where = [];
        if (strlen($origin_id) > 0) {
            $where[] = ['origin_id', '=', $origin_id];
        }

        if ($this->request->param("isRequest") == 1) {
            $res = PushTemplateModel::getInstance()->getModel()->where($where)->page($page, $limit)->select()->toArray();
            foreach ($res as $key => $item) {
                $res[$key]['create_time_format'] = date('Y-m-d H:i:s', $item['create_time']);
                $res[$key]['update_time_format'] = date('Y-m-d H:i:s', $item['update_time']);
            }
            $count = PushTemplateModel::getInstance()->getModel()->where($where)->count();
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('typeList', config("config.push_type"));
            return View::fetch('pushrecall/template');
        }
    }

    public function pushTemplateEdit()
    {
        $id = $this->request->param('id', '', 'trim');
        $origin_id = $this->request->param('origin_id', '', 'trim');
        $content = $this->request->param('content', '', 'trim');
        $type = $this->request->param('type', '', 'trim');
        $template_name = $this->request->param('template_name', '', 'trim');
        $title = $this->request->param('title', '', 'trim');
        $data = ["origin_id" => $origin_id, "content" => $content, "type" => $type, "template_name" => $template_name, "title" => $title];
        $currenttimestamp = time();
        try {
            if ($id > 0) {
                $data['id'] = $id;
                $data['update_time'] = $currenttimestamp;
                PushTemplateModel::getInstance()->getModel()->where("id",$id)->update($data);
            } else {
                $data['create_time'] = $currenttimestamp;
                $data['update_time'] = $currenttimestamp;
                PushTemplateModel::getInstance()->getModel()->insert($data);
            }
            Log::record('模板操作人:操作人:' . $this->token['username'] . "data:" . json_encode($data, JSON_UNESCAPED_UNICODE));
            echo 1;
            exit;
        } catch (Throwable $e) {
            echo 0;
            exit;
        }
    }

    /*push活动的模板消息列表*/
    public function pushConfigList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $push_type = $this->request->param('push_type', '', 'trim'); //模板id
        $where = [];
        if (strlen($push_type) > 0) {
            $where[] = ['push_type', '=', $push_type];
        }

        $templateList = PushTemplateModel::getInstance()->getModel()->select()->toArray();
        $formatTemplateList = [];
        foreach ($templateList as $temp) {
            $formatTemplateList[] = ["name" => $temp["template_name"] . "(" . $temp["type"] . ")", "value" => $temp['id'], "selected" => false];
        }

        if ($this->request->param("isRequest") == 1) {
            $res = PushRecallConfModel::getInstance()->getModel()->where($where)->page($page, $limit)->order("id desc")->select()->toArray();
            $count = PushRecallConfModel::getInstance()->getModel()->where($where)->count();
            foreach ($res as $key => $item) {
                $pushCondition = json_decode($item['push_when'], true);
                $res[$key]['charge_max'] = $pushCondition['charge_max'] ?? '';
                $res[$key]['charge_min'] = $pushCondition['charge_min'] ?? '';
                $res[$key]['time'] = $pushCondition['time'] ?? '';
            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('templateList', $formatTemplateList);
            View::assign('typeList', config("config.push_type"));
            return View::fetch('pushrecall/pushconfig');
        }
    }

    public function pushConfigEdit()
    {
        $id = $this->request->param('id', '', 'trim');
        $charge_max = $this->request->param('charge_max', '', 'trim');
        $charge_min = $this->request->param('charge_min', '', 'trim');
        $time = $this->request->param('time', 0, 'trim');
        $push_when = ["charge_max" => $charge_max, "charge_min" => $charge_min, "time" => $time];
        $is_delete = $this->request->param('is_delete', '', 'trim');
        if ($is_delete == "on") {
            $is_delete = 0;
        } else {
            $is_delete = 1;
        }

        $push_type = $this->request->param('push_type', 0, 'trim');
        $template_ids = $this->request->param('template_ids', 0, 'trim');
        $templateids = explode(",", $template_ids);
        $templateRes = PushTemplateModel::getInstance()->getModel()->where("id", "in", $templateids)->select()->toArray();

        if (empty($templateRes)) {
            Log::info("pushConfigEdit:error" . "模板无法找到");
            echo 0;
            exit;
        }

        $templateTypeList = array_column($templateRes, "type");

        if (count(array_unique($templateTypeList)) > 1) {
            Log::info("pushConfigEdit:error" . "选择模板的类型不一致");
            echo 0;
            exit;
        }

        if ($templateTypeList[0] != $push_type) {
            Log::info("pushConfigEdit:error" . "模板的类型与配置不一致");
            echo 0;
            exit;
        }

        $template_param = json_encode($templateids);
        $data = ["push_when" => json_encode($push_when), "is_delete" => $is_delete, "push_type" => $push_type, "template_ids" => $template_param];
        try {
            if ($id > 0) {
                $data['id'] = $id;
                PushRecallConfModel::getInstance()->getModel()->save($data);
            } else {
                PushRecallConfModel::getInstance()->getModel()->insert($data);
            }
            Log::record('模板操作人:操作人:' . $this->token['username'] . "data:" . json_encode($data, JSON_UNESCAPED_UNICODE));
            echo 1;
            exit;
        } catch (Throwable $e) {
            Log::info("pushConfigEdit:error" . $e->getMessage());
            echo 0;
            exit;
        }
    }

    /*用户召回列表数据*/
    public function memberRecallList()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit", 30);
        $date_b = $this->request->param("date_b", date('Ymd', strtotime("-1day")));
        $date_e = $this->request->param("date_e", date('Ymd', strtotime("+1day")));
        $type = $this->request->param('type', '', 'trim'); //类型多选 getuipush,xxxx,xxx
        $viewcharge = $this->request->param('viewcharge', '', 'trim'); //类型多选 getuipush,xxxx,xxx
        $daochu = $this->request->param('daochu', '', 'trim'); //是否导出
        $type_params = explode(",", $type);
        $types = array_filter($type_params);
        $where = [];
        if (count($types) > 0) {
            $where[] = ['type', 'in', $types];
        }

        if ($date_b && $date_e) {
            $where[] = ['create_time', '>=', strtotime($date_b)];
            $where[] = ['create_time', '<', strtotime($date_e)];
        }

        $typeList = config("config.push_type");
        $formatTypeList = [];
        foreach ($typeList as $typename) {
            $formatTypeList[] = ["name" => $typename, "value" => $typename, "selected" => false];
        }

        if ($viewcharge) {
            $pagemark = 1;
            $charge_sum = 0;
            $max_limit = 2000;
            $markUsers = [];
            while (true) {
                $recall_list_all = MemberRecallDetailModel::getInstance()->getModel()
                    ->where($where)
                    ->where("recall_login_status", "=", 1) //召回标识符
                    ->page($pagemark, $max_limit)
                    ->select()
                    ->toArray();
                if (empty($recall_list_all)) {
                    break;
                }
                foreach ($recall_list_all as $recall_item) {
                    if (!in_array($recall_item["user_id"], $markUsers)) {
                        $number = BiDaysUserChargeModel::getInstance()->getModel()
                            ->where("uid", $recall_item["user_id"])
                            ->where("date", ">=", date('Y-m-d', strtotime($date_b)))
                            ->where("date", "<", date('Y-m-d', strtotime($date_e)))
                            ->sum("amount");
                        $charge_sum += $number;
                        $markUsers[] = $recall_item["user_id"];
                    }
                }
                $pagemark++;
            }

            $charge_amount = round($charge_sum / 10, 2);
            echo $charge_amount;
            exit;
        }

        if ($this->request->param("isRequest") == 1) {
            ini_set('memory_limit', '1024M');
            if ($daochu == 1) { //导出
                $headerArray = [
                    'user_id' => '用户id',
                    'register_time' => '注册时间',
                    'origin_login_time' => '召回前登录时间',
                    'amount' => '充值豆',
                    'free_coin' => '消费豆',
                    'coin_balance' => '豆余额',
                    'recall_login_status' => '是否登录',
                    'type' => '召回方式',
                    'str_date' => '触发时间',
                ];
                $exportRes = MemberRecallDetailModel::getInstance()->getModel()
                    ->alias("A")
                    ->field("A.user_id,FROM_UNIXTIME(A.origin_login_time) as origin_login_time,A.amount,A.free_coin,A.coin_balance,A.recall_login_status,A.type,A.str_date")
                    ->where($where);

                $deal_funcion = function ($item) {
                    $item['register_time'] = MemberModel::getInstance()->getModel($item['user_id'])->where('id', $item['user_id'])->value('register_time');
                    return $item;
                };
                ExportExcelService::getInstance()->exportBigDataByFn($exportRes, $headerArray, $deal_funcion);
                exit;
            }

            if (empty($types)) {
                $hz = ["send_total" => 0, "client_total" => 0, "recall_total" => 0, "success_total" => 0];
                $data = ["msg" => '', "count" => 0, "code" => 0, "data" => [], "hz" => $hz];
                echo json_encode($data);
                exit;
            }

            //汇总数据的统计
            $send_total = MemberRecallDetailModel::getInstance()->getModel()
                ->where($where)
                ->count(); //发送量
            //根据snsid与回调数据来获取每天的点击量
            $snsids = MemberRecallDetailModel::getInstance()->getModel()->field('sns_id')
                ->where($where)
                ->where('sns_id', "<>", '')
                ->select()->toArray();
            $sns_ids = array_unique(array_column($snsids, "sns_id"));
            if ($sns_ids) {

                //*****************统计点击量开始*****************************************/
                $client_total = PushReportModel::getInstance()->getModel()
                    ->where("task_id", "in", $sns_ids)
                    ->where("status", "in", [10010, 60002, 60020, 120010, 130010, 60030, 60040, 10019])
                    ->count('DISTINCT mobile');
                //统计短信的点击量
                $rms_send_count = 0;
                if (in_array("rtdsms", $types)) {
                    $rms_send_count = RecallSmsInfoModel::getInstance()->getModel()->alias("A")->LEFTJOIN('zb_member_recall_detail B', "A.user_id=B.user_id")
                        ->where('A.create_time', '>', strtotime($date_b))
                        ->where('A.action', '=', 'click')
                        ->where('B.type', '=', 'rtdsms')
                        ->where("B.create_time", ">=", strtotime($date_b))
                        ->where("B.create_time", "<", strtotime($date_e))
                        ->count('distinct A.user_id');
                }
                $client_total += $rms_send_count;
                //*****************统计点击量结束*****************************************/

                $success_total = 0;
                $success_total_push = PushReportModel::getInstance()->getModel()
                    ->where("task_id", "in", $sns_ids)
                    ->where("status", "in", [0, 110000, 120000, 130000, 140000, 150000, 10009])
                    ->count('DISTINCT mobile');
                //因为短信发送成功量3天内才能回调完毕 所有时间往后延3天
                $success_total_sms = SmsReportModel::getInstance()->getModel()->where("bid", "in", $sns_ids)
                    ->where("sc", "=", "DELIVRD")
                    ->where('str_date', '>=', date('Ymd', strtotime($date_b)))
                    ->where('str_date', '<', date('Ymd', strtotime($date_e . "+3days")))
                    ->count();

                $success_total = intval($success_total_push) + intval($success_total_sms);

            } else {
                $client_total = 0;
                $success_total = 0;
            }

            $recall_total = MemberRecallDetailModel::getInstance()->getModel()
                ->where($where)
                ->where("recall_login_status", 1)
                ->count("distinct user_id"); //召回数量

            $collectRes = ["send_total" => $send_total, "client_total" => $client_total, "recall_total" => $recall_total, "success_total" => $success_total];
            $res = MemberRecallDetailModel::getInstance()->getModel()->where($where)->page($page, $limit)->order("id desc")->select()->toArray();
            $count = MemberRecallDetailModel::getInstance()->getModel()->where($where)->count();

            $pushTypeMark = config("config.push_type_mark");
            foreach ($res as $key => $item) {
                $charge_sum = 0;
                // $res[$key]['register_time'] = $memberInfo[$item['user_id']]['register_time'] ?? '';
                $res[$key]['register_time'] = MemberModel::getInstance()->getFieldValueById($item['user_id'], 'register_time');
                $res[$key]['origin_login_time'] = date('Y-m-d H:i:s', $item['origin_login_time']);
                $res[$key]['recall_charge_sum'] = round($charge_sum / 10, 2);
                $res[$key]['type_mark'] = $pushTypeMark[$item['type']] ?? '';
            }
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $res, "hz" => $collectRes];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('date_b', date('Y-m-d', strtotime($date_b)));
            View::assign('date_e', date('Y-m-d', strtotime($date_e)));
            View::assign('typeList', config("config.push_type"));
            View::assign('formatTypeList', $formatTypeList);
            return View::fetch('pushrecall/memberRecallList');
        }
    }

    //人工推送
    public function manSendConfig()
    {
        $apiUrl = config("config.app_api_url") . "api/inner/touchUsers";
        $configid = $this->request->param('id', 0);
        $haveRes = PushRecallConfModel::getInstance()->getModel()->where("id", $configid)->where("is_delete", 0)->find();
        if (empty($haveRes)) {
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, "参数错误");
            exit;
        }
        $data = ["id" => $configid];
        try {
            $res = curlData($apiUrl, json_encode($data), 'POST', 'json');
            Log::record('manSendConfig:手工推送配置:操作人:' . $this->token['username'] . "data:" . json_encode($data, JSON_UNESCAPED_UNICODE));
            $returnRew = json_decode($res, true);
            if ($returnRew["code"] == 200) {
                echo $this->return_json(\constant\CodeConstant::CODE_成功, '', $this->code_ok_map[\constant\CodeConstant::CODE_插入成功]);
                exit;
            } else {
                echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, $returnRes["desc"] ?? "请求接口错误");
                exit;
            }
        } catch (Throwable $e) {
            Log::info("manSendConfig:api:调用失败" . JSON_encode($res));
            echo $this->return_json(\constant\CodeConstant::CODE_参数错误, null, "请求失败");
            exit;
        }

    }

}

<?php

namespace app\admin\controller;

use app\admin\common\AdminBaseController;
use app\admin\model\ChargedetailModel;
use app\admin\model\MemberModel;
use app\admin\model\PayChannelModel;
use app\admin\service\ChargedetailService;
use app\admin\service\ExportExcelService;
use constant\OrderConstant;
use think\facade\Request;
use think\facade\View;

class ChargeController extends AdminBaseController
{
    /**
     * 充值记录
     * @param string $value [description]
     */
    public function rechargeList()
    {
        $statusconf = ['否', '是', '是'];
        $limit = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $default_date = date('Y-m-d', strtotime("-1 days")) . ' - ' . date('Y-m-d');
        $demo = $this->request->param('demo', $default_date);
        list($start_time, $end_time) = getBetweenDate($demo);

        $orderType = Request::param('orderType', 'all');

        $daochu = Request::param('daochu');
        $qdid = Request::param('qdid'); //充值平台类型
        $type = Request::param('type'); //充值类型
        $chargeuid = Request::param('chargeuid'); //用户ID
        $isstatus = Request::param('isstatus', 1); //充值类型状态(支付与未支付状态)
        $paixu = Request::param('paixu', 'desc');
        $dingdanhao = Request::param('dingdanhao');
        $where = [];
        if ($isstatus == 1) {
            $where[] = ['A.status', 'in', [1, 2]];
        } else {
            $where[] = ['A.status', '=', 0];
        }

        $where[] = ['A.addtime', '>=', $start_time];
        $where[] = ['A.addtime', '<', date('Y-m-d', strtotime($end_time) + 86400)];

        if (!empty($dingdanhao)) {
            $where[] = ['A.dealid|A.orderno', '=', $dingdanhao];
        }

        if (!empty($chargeuid)) {
            $where[] = ['A.uid', '=', $chargeuid];
        }

        if (!empty($qdid)) {
            $where[] = ['A.platform', '=', $qdid];
        }
        $whereBean = $whereVip = $whereRed = $where;
        if (!empty($orderType) && $orderType != 'all') {
            $where[] = ['A.type', '=', (int) $orderType];
        }

        $sum = ChargedetailModel::getInstance()->getModel()->alias('A')->where($where);
        $content = $sum->field('A.content,sum(A.rmb) rmb')->group('A.content')->select()->toArray();
        if ($content) {
            $content = array_column($content, null, 'content');
        }

        $count = ChargedetailModel::getInstance()->getModel()->alias('A')->where($where)->count();
        $deal_func = function ($item) use ($statusconf) {
            $item['statusname'] = $statusconf[(int) $item['status']];
            $item['nickname'] = MemberModel::getInstance()->getFieldValueById($item['uid'], 'nickname');
            return $item;
        };

        $stmp = ChargedetailModel::getInstance()->getModel()
            ->alias('A')
            ->field('A.uid,A.addtime,A.type,A.status,A.platform,A.rmb,A.orderno,A.dealid,A.channel,A.content')
            ->where($where);

        if ($daochu == 1) {
            $columns = [
                'uid' => '用户id',
                'nickname' => '用户名',
                'rmb' => '金额',
                'addtime' => '订单时间',
                'orderno' => '订单id',
                'dealid' => '三方id',
                'content' => '支付方式',
                'statusname' => '支付状态',
            ];
            ExportExcelService::getInstance()->exportBigDataByFn($stmp, $columns, $deal_func);
            return;
        } else {
            $data = $stmp->limit($offset, $limit)->order('A.addtime ' . $paixu)->select()->toArray();
        }
        //支付渠道列表
        $pay_type_list = PayChannelModel::getInstance()->getModel()->field('id,content')->select()->toArray();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $deal_func($value);
            }
        }
        $whereBean[] = ['A.type', 'in', '1'];
        $whereVip[] = ['A.type', 'in', '2,3'];
        $whereRed[] = ['A.type', 'in', '4'];

        $qian = ChargedetailModel::getInstance()->getModel()->alias('A')->field('A.rmb')->where($whereBean)->value('sum(A.rmb)');
        $vapRmb = ChargedetailModel::getInstance()->getModel()->alias('A')->field('A.rmb')->where($whereVip)->value('sum(A.rmb)');
        $redpackgets = ChargedetailModel::getInstance()->getModel()->alias('A')->field('A.rmb')->where($whereRed)->value('sum(A.rmb)');

        $totalPage = ceil($count / $limit);
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        View::assign('ordermap', OrderConstant::ORDER_TYPE_MAP);
        View::assign('list', $data);
        View::assign('rmb', intval($qian));
        View::assign('vipRmb', intval($vapRmb));
        View::assign('redpackgets', intval($redpackgets));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('orderType', $orderType);
        View::assign('start_time', $start_time);
        View::assign('end_time', $end_time);
        View::assign('demo', $demo);
        View::assign('isstatus', $isstatus);
        View::assign('qudao', $qdid);
        View::assign('daochu', $daochu);
        View::assign('paixu', $paixu);
        View::assign('chargeuid', $chargeuid);
        View::assign('dingdanhao', $dingdanhao);
        View::assign('pay_type_list', $pay_type_list);
        View::assign('content', $content);
        $admin_url = config('config.admin_url');
        View::assign('admin_url', $admin_url);
        return View::fetch('charge/rechargelist');

    }

    //导出csv
    public function putcsv($data)
    {
        $headerArray = ['用户id', '用户名', '金额', '订单时间', '订单id', '三方id', '支付方式', '支付状态'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['uid'] = $value['uid'];
            $outArray['nickname'] = $value['nickname'];
            $outArray['rmb'] = floor($value['rmb']);
            $outArray['addtime'] = $value['addtime'];
            $outArray['orderno'] = $value['orderno'] . "\t";
            $outArray['dealid'] = $value['dealid'] . "\t";
            $outArray['content'] = $value['content'];
            $outArray['statusname'] = $value['statusname'];
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = date('Ymd') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    /*
     *充值用户列表
     */
    public function chargeList()
    {
        $page = Request::param('page'); //分页
        $limit = Request::param('pagenum'); //条数
        if (!$page || !$limit) {
            return $this->return_json(\constant\CodeConstant::CODE_分页或条数不能为空, null, $this->code_parameter_err_map[\constant\CodeConstant::CODE_分页或条数不能为空]);
        }
        $platform = Request::param('platform'); //充值平台类型
        $user_id = Request::param('user_id'); //用户ID
        $status = Request::param('status'); //充值类型状态(支付与未支付状态)
        //搜索功能
        if ($platform && $user_id && $status) {
            $where = ['platform' => $platform, 'uid' => $user_id, 'status' => $status];
        } else if ($platform && $user_id) {
            $where = ['platform' => $platform, 'uid' => $user_id];
        } else if ($user_id && $status) {
            $where = ['status' => $status, 'uid' => $user_id];
        } else if ($user_id) {
            $where = ['uid' => $user_id];
        } else if ($status) {
            $where = ['status' => $status];
        } else if ($platform) {
            $where = ['platform' => $platform];
        } else {
            $where = [];
        }
        $offset = ($page - 1) * $limit;
        $data = ChargedetailService::getInstance()->getList($where, $offset, $limit);
        $count = ChargedetailModel::getInstance()->getModel()->getModel()->where($where)->count();
        $totalPage = ceil($count / $limit);
        $pageInfo = array("page" => $page, "pageNum" => $limit, "totalPage" => $totalPage, "count" => $count);
        foreach ($data as $key => $val) {
            if ($val['status'] == 1) {
                $data[$key]['status'] = "已支付";
            } else {
                $data[$key]['status'] = "未支付";
            }
            if ($val['platform'] == 0) {
                $data[$key]['platform'] = "支付宝";
            } else if ($val['platform'] == 1) {
                $data[$key]['platform'] = "微信";
            } else if ($val['platform'] == 2) {
                $data[$key]['platform'] = "苹果";
            } else if ($val['platform'] == 3) {
                $data[$key]['platform'] = "其他平台代充";
            }
        }
        if (empty($data)) {
            return $this->return_json(\constant\CodeConstant::CODE_成功, $data, $this->code_ok_map[\constant\CodeConstant::CODE_暂无数据]);
        }
        $result = [
            "list" => $data,
            "pageInfo" => $pageInfo,
        ];
        return $this->return_json(\constant\CodeConstant::CODE_成功, $result, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
    }

}

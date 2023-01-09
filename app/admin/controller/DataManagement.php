<?php

namespace app\admin\controller;

ini_set('memory_limit', '1024M');

use app\admin\common\AdminBaseController;
use app\admin\common\CommonConst;
use app\admin\model\BeancreditModel;
use app\admin\model\BiChannelDataModel;
use app\admin\model\BIDataModel;
use app\admin\model\BiDaysUserChargeModel;
use app\admin\model\BiDongModel;
use app\admin\model\BiOppoDailyDayModel;
use app\admin\model\BoxGiftModel;
use app\admin\model\ChannelPointsModel;
use app\admin\model\ChannelSourceDataModel;
use app\admin\model\ChargedetailModel;
use app\admin\model\DownloadStatsByDealerModel;
use app\admin\model\DukeModel;
use app\admin\model\FirstpayHammersModel;
use app\admin\model\GiftModel;
use app\admin\model\LanguageroomModel;
use app\admin\model\LogindetailModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\MemberModel;
use app\admin\model\ReYunModel;
use app\admin\model\RoomLogintimeModel;
use app\admin\service\ExportExcelService;
use app\admin\service\MemberGuildService;
use app\admin\service\RetentionService;
use app\common\RedisCommon;
use think\facade\Db;
use think\facade\Log;
use think\facade\Request;
use think\facade\View;

class DataManagement extends AdminBaseController
{
    //天 魅力榜
    public function roomConsumption()
    {
        $room_id = $this->request->param('roomid');
        $uid = $this->request->param('uid');
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');

        $date_list = getDateRange($start, $end);
        rsort($date_list);

        $total_score = 0;
        $data = [];
        foreach ($date_list as $_ => $date) {
            //获取redis数据
            $redis = RedisCommon::getInstance()->getRedis(["select" => 1]);
            $user_scores = $redis->ZREVRANGE('Like_Day_' . $room_id . "_" . date("Ymd", strtotime($date)), 0, -1, 'WITHSCORES');
            if (!empty($user_scores) && $date < $end) {
                foreach ($user_scores as $k => $score) {
                    $user_item['uid'] = $k;
                    $user_item['charm'] = $score;
                    $user_item['nickname'] = MemberModel::getInstance()->getModel($k)->where('id', $k)->value('nickname');
                    $user_item['room_id'] = $room_id;
                    $user_item['addtime'] = $date;

                    $data[] = $user_item;
                    $total_score += $score;
                }
            }
        }

        if ($daochu == 1) {
            $tilie = ['房间Id', '用户Id', '昵称', '财富', '日期'];
            $this->_Daochu($tilie, $data);
        }

        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('total_score', $total_score);
        View::assign('uid', $uid);
        View::assign('room_id', $room_id);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/roomConsumption');
    }

    //天 财富榜
    public function roomConsumptionRich()
    {
        $room_id = $this->request->param('roomid');
        $uid = $this->request->param('uid');
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');

        $time = $start;

        //获取redis数据
        $redis = RedisCommon::getInstance()->getRedis(["select" => 1]);
        $key = 'Rich_Day_' . $room_id . "_" . date("Ymd", strtotime($time));
        $userid = $redis->ZREVRANGE($key, 0, -1, 'WITHSCORES');
        $data = [];
        foreach ($userid as $k => $v) {
            $data[$k]['uid'] = $k;
            $data[$k]['charm'] = $v;
            $data[$k]['nickname'] = MemberModel::getInstance()->getModel($k)->where('id', $k)->value('nickname');
            $data[$k]['room_id'] = $room_id;
            $data[$k]['addtime'] = $time;
        }
        if ($daochu == 1) {
            $tilie = ['房间Id', '用户Id', '昵称', '财富', '日期'];
            $this->_Daochu($tilie, $data);
        }

        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('room_id', $room_id);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/roomConsumptionRich');
    }

    //周魅力榜
    public function roomConsumptionWeekLike()
    {
        $room_id = $this->request->param('roomid');
        $uid = $this->request->param('uid');
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');

        $date_list = getWeekRange($start, $end);
        rsort($date_list);

        $total_score = 0;
        $data = [];
        foreach ($date_list as $_ => $date) {
            //获取redis数据
            $redis = RedisCommon::getInstance()->getRedis(["select" => 1]);
            $user_scores = $redis->ZREVRANGE('Like7_Week_' . $room_id . "_" . date("Ymd", strtotime($date)), 0, -1, 'WITHSCORES');
            if (!empty($user_scores) && $date < $end) {
                foreach ($user_scores as $k => $score) {
                    $user_item['uid'] = $k;
                    $user_item['charm'] = $score;
                    $user_item['nickname'] = MemberModel::getInstance()->getModel($k)->where('id', $k)->value('nickname');
                    $user_item['room_id'] = $room_id;
                    $user_item['addtime'] = $date;

                    $data[] = $user_item;
                    $total_score += $score;
                }
            }
        }

        if ($daochu == 1) {
            $tilie = ['房间Id', '用户Id', '昵称', '魅力值', '日期'];
            $this->_Daochu($tilie, $data);
        }
        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('room_id', $room_id);
        View::assign('total_score', $total_score);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/roomConsumptionWeekLike');
    }

    //周财富榜
    public function roomConsumptionWeekRich()
    {
        $room_id = $this->request->param('roomid');
        $uid = $this->request->param('uid');
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');

        $time = $start;

        //获取redis数据
        $redis = RedisCommon::getInstance()->getRedis(["select" => 1]);
        $userid = $redis->ZREVRANGE('Rich7_Week_' . $room_id . "_" . date("Ymd", strtotime($time)), 0, -1, 'WITHSCORES');
        $data = [];
        foreach ($userid as $k => $v) {
            $data[$k]['uid'] = $k;
            $data[$k]['charm'] = $v;
            $data[$k]['nickname'] = MemberModel::getInstance()->getModel($k)->where('id', $k)->value('nickname');
            $data[$k]['room_id'] = $room_id;
            $data[$k]['addtime'] = $time;
        }
        if ($daochu == 1) {
            $tilie = ['房间Id', '用户Id', '昵称', '财富值', '日期'];
            $this->_Daochu($tilie, $data);
        }
        View::assign('data', $data);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('room_id', $room_id);
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/roomConsumptionWeekRich');
    }

    public function _Daochu($tilie, $data)
    {
        $regex = "/\"|\,|\\\|\|/";
//        $tilie = ['统计日期', '一级渠道', '二级渠道', '三级渠道', '登录账号', '注册账号', '消费人数', '消费金额', '新用户消费人数', '新用户消费金额', '消费率', '新用户消费率', '消费ARPU', '新用户消费ARPU', '登录ARPU'];
        $string = implode(",", $tilie) . "\n";
        foreach ($data as $key => $value) {
            $outArray['room_id'] = $value['room_id']; //统计日期
            $outArray['uid'] = $value['uid']; //一级渠道
            $outArray['nickname'] = preg_replace($regex, "", $value['nickname']); //二级渠道
            $outArray['charm'] = $value['charm']; //三级渠道
            $outArray['addtime'] = $value['addtime']; //登录账号
            $string .= implode(",", $outArray) . "\n";
        }
        $filename = '渠道分析导出时间：' . date('Y-m-d H:i:s') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    // 发布动态
    public function ReleaseTheDynamic()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '112')->field('id')->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '112')->field('id')->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'ReleaseTheDynamic');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    // 戳一下
    public function PokeThe()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '111')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '111')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'PokeThe');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //打招呼
    public function SayHhello()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '110')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '110')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'SayHhello');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //首页大厅
    public function HomePageThehall()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '109')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '109')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'HomePageThehall');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //首页去找他
    public function HomePageLooking()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '108')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '108')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'HomePageLooking');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //点击动态
    public function Dynamic()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '107')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '107')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'Dynamic');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //点击匹配
    public function Matching()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '106')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '106')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'Matching');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //cp和游戏 103
    public function CpThegame()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '103')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '103')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'CpThegame');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //Recommended 点击分类
    public function Classification()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '102')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '102')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'Classification');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //积分墙点击数据
    public function DealerData()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $where[] = [
            'date', '>=', $strtime,
            'date', '<', $endtime,
        ];

        $count = DownloadStatsByDealerModel::getInstance()->getModel()->where($where)->count();
        $data = DownloadStatsByDealerModel::getInstance()->getModel()->where($where)->select()->toArray();

        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'DealerData');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/DownloadPoints');
    }

    //Recommended 点击 推荐房间
    public function Recommended()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '101')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '101')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'Recommended');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    //点击隐藏显示青少年模式
    public function Teenagers()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '100')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '100')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'Teenagers');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    /**
     * @return mixed
     * 房间内首冲曝光和点击量
     */
    public function ChannelPoints()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            $where = [];
            $where[] = ['riq', '>=', strtotime($v . ' 00:00:00')];
            $where[] = ['riq', '<=', strtotime($v . ' 23:59:59')];
            $data[$k]['riq'] = $v;
            $data[$k]['exposure'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '104')->group('user_id')->field('id')->select()->count();
            $data[$k]['click_on_the'] = ChannelPointsModel::getInstance()->getModel()->where($where)->whereIn('type', '105')->group('user_id')->field('id')->select()->count();
        }
        View::assign('list', $data);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('function', 'ChannelPoints');
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/ChannelPoints');
    }

    /**
     * @return mixed
     * 用户留存
     */
    public function UsersRetainedDetails()
    {
        $strtime = $this->request->param('strtime');
        $demo = $this->request->param('demo');
        list($strtime, $_) = getBetweenDate($demo);
        $endtime = substr($strtime, 0, 10);
        $where[] = ['register_time', '>=', $strtime];
        $where[] = ['register_time', '<=', $endtime];

        $member_list = MemberModel::getInstance()->getWhereAllData($where, "id");
        $uid = array_column($member_list, 'id');
        $xzff = ChargedetailModel::getInstance()->getModel()->where([['status', 'in', [1, 2]], ['addtime', '>=', $strtime], ['addtime', '<=', $endtime]])->whereIn('uid', $uid)->field('uid,rmb')->select()->toArray();
        $list = array();
        if (count($xzff) > 0) {
            foreach ($xzff as $k => $v) {
                if (isset($list[$v['uid']])) {
                    $list[$v['uid']]['rmb'] += $v['rmb'];
                } else {
                    $list[$v['uid']]['rmb'] = $v['rmb'];
                    $list[$v['uid']]['uid'] = $v['uid'];
                    $list[$v['uid']]['rq'] = substr($strtime, 0, 10);
                }
            }
        }
        $admin_url = config('config.admin_url');
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/UsersRetainedDetails');
    }

    /**
     * @return mixed
     * 用户留存详情
     */
    public function UsersRetained()
    {
        $daochu = $this->request->param('daochu');
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);

        $list = [];
        $datearr = $this->getDateRange($strtime, $endtime);
        foreach ($datearr as $k => $v) {
            if ($v <= date('Y-m-d')) {
                if ($v != date('Y-m-d')) {
                    $bidong = BiDongModel::getInstance()->getModel()->where([['rq', '=', $v . ' 00:00:00'], ['desc', '=', '用户留存']])->select()->toArray();
                    if (count($bidong) <= 0) {
                        $list[$k] = $this->UsersRetainedExecute($v . ' 00:00:00', $v . ' 23:59:59');
                    } else {
                        foreach ($bidong as $kk => $vv) {
                            $list[$k] = json_decode($vv['data'], true);
                        }
                    }
                } else {
                    $bidong = BiDongModel::getInstance()->getModel()->where([['rq', '=', $v . ' 00:00:00'], ['desc', '=', '用户留存new']])->select()->toArray();
                    foreach ($bidong as $kk => $vv) {
                        $list[$k] = json_decode($vv['data'], true);
                    }
                }
            }
        }
        foreach ($list as $k => $v) {
            if ($list[$k]['d1'] == '') {
                $list[$k]['d1'] = 0;
            }
            if ($list[$k]['d2'] == '') {
                $list[$k]['d2'] = 0;
            }
            if ($list[$k]['d3'] == '') {
                $list[$k]['d3'] = 0;
            }
            if ($list[$k]['d4'] == '') {
                $list[$k]['d4'] = 0;
            }
            if ($list[$k]['d5'] == '') {
                $list[$k]['d5'] = 0;
            }
            if ($list[$k]['d6'] == '') {
                $list[$k]['d6'] = 0;
            }
            if ($list[$k]['d7'] == '') {
                $list[$k]['d7'] = 0;
            }
        }
        if ($daochu == 1) {
            $this->usersRetainedDaochu($list);
        }
        $admin_url = config('config.admin_url');
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/usersretained');
    }

    public function usersRetainedDaochu($data)
    {
        $headerArray = ['日期', '新增付费', '总付费数', 'D1', 'D2', 'D3', 'D4', 'D5', 'D6', 'D7'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['rq'] = $value['rq'];
            $outArray['xzff'] = $value['xzff'];
            $outArray['zff'] = $value['zff'];
            $outArray['d1'] = $value['d1'];
            $outArray['d2'] = $value['d2'];
            $outArray['d3'] = $value['d3'];
            $outArray['d4'] = $value['d4'];
            $outArray['d5'] = $value['d5'];
            $outArray['d6'] = $value['d6'];
            $outArray['d7'] = $value['d7'];
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

    /**
     * 獲取新增渠道用戶
     */
    public function ReYunUserTime($where = [], $channel = '', $spreadname = '')
    {
        if (empty($channel)) {
            $len = ReYunModel::getInstance()->getModel()->where('type', 1)->field('imei')->select()->count();
            $strlen = explode('.', $len / 1000);
            for ($i = 1; $i <= $strlen[0]; $i++) {
                $imei[] = ReYunModel::getInstance()->getModel()->where('type', 1)->limit($i . '000', 1000)->column('imei');
            }
            foreach ($imei as $k => $v) {
                $member_list = MemberModel::getInstance()->getWhereAllData([["imei", "=", $v]], "id");
                $uid[] = array_column($member_list, 'id');
            }
            foreach ($uid as $k => $v) {
                $user = $v;
            }
            return $user;
        } elseif (!empty($spreadname) && $channel == 1) {
            if ($spreadname == '空') {
                $imei = ReYunModel::getInstance()->getModel()->where([['spreadname', '=', ''], ['type', '=', 1]])->column('imei');
            } else {
                $imei = ReYunModel::getInstance()->getModel()->where([['spreadname', '=', $spreadname], ['type', '=', 1]])->column('imei');
            }
            $member_list = MemberModel::getInstance()->getWhereAllData([["imei", "in", $imei]], "id");
            $uid[] = array_column($member_list, 'id');

            return $uid;
        } else {
            if ($channel == '空') {
                $imei = ReYunModel::getInstance()->getModel()->where([['channel', '=', ''], ['type', '=', 1]])->column('imei');
            } else {
                $imei = ReYunModel::getInstance()->getModel()->where([['channel', '=', $channel], ['type', '=', 1]])->column('imei');
            }
            $member_list = MemberModel::getInstance()->getWhereAllData([["imei", "in", $imei]], "id");
            $uid[] = array_column($member_list, 'id');
            return $uid;
        }
    }

    public function ReyunUserId($where = [], $where2 = [], $channel = '', $spreadname = '')
    {
        if (empty($channel)) {
            $len = ReYunModel::getInstance()->getModel()->where('type', 1)->field('imei')->select()->count();
            $strlen = explode('.', $len / 1000);
            for ($i = 1; $i <= $strlen[0]; $i++) {
                $imei[] = ReYunModel::getInstance()->getModel()->where('type', 1)->limit($i . '000', 100)->column('imei');
            }
            foreach ($imei as $k => $v) {
                $imeis = MemberModel::getInstance()->getWhereAllData([["imei", "in", $v]], 'id');
                $uid[] = array_column($imeis, 'id');
            }
            foreach ($uid as $k => $v) {
                $user = $v;
            }
            return $user;
        } elseif (!empty($spreadname) && $channel == 1) {
            if ($spreadname == '空') {
                $imei = ReYunModel::getInstance()->getModel()->where([['spreadname', '=', ''], ['type', '=', 1]])->column('imei');
            } else {
                $imei = ReYunModel::getInstance()->getModel()->where([['spreadname', '=', $spreadname], ['type', '=', 1]])->column('imei');
            }
            $imeis = MemberModel::getInstance()->getWhereAllData([["imei", "in", $imei]], 'id');
            $log = array_column($imeis, 'id');
            return $log;
        } else {
            if ($channel == '空') {
                $imei = ReYunModel::getInstance()->getModel()->where([['channel', '=', ''], ['type', '=', 1]])->column('imei');
            } else {
                $imei = ReYunModel::getInstance()->getModel()->where([['channel', '=', $channel], ['type', '=', 1]])->column('imei');
            }
            $imeis = MemberModel::getInstance()->getWhereAllData([["imei", "in", $imei]], 'id');
            $log = array_column($imeis, 'id');
            return $log;
        }
    }

    public function reYunChannel()
    {
        $channel = ReYunModel::getInstance()->group('channel')->column('channel');
        return $channel;
    }

    /**
     * @param string $channel
     * @return mixed
     * 热云渠道
     */
    public function spread($channel = '')
    {
        if ($channel == '空') {
            $spreadname = ReYunModel::getInstance()->getModel()->where('channel', '')->group('spreadname')->column('spreadname');
        } else {
            $spreadname = ReYunModel::getInstance()->getModel()->where('channel', $channel)->group('spreadname')->column('spreadname');
        }
        return $spreadname;
    }

    /**
     * @return mixed
     * 热云渠道详情
     */
    public function SpreadName()
    {
        $strtime = $this->request->param('strtime');
        $endtime = substr($strtime, 0, 10) . ' 23:59:59';
        $channel = $this->request->param('channel');
        $where = [];
        if (!empty($strtime) && !empty($endtime)) {
            $where[] = ['register_time', '>=', $strtime];
            $where[] = ['register_time', '<=', $endtime];
            $where1[] = ['addtime', '>=', $strtime];
            $where1[] = ['addtime', '<=', $endtime];
            $time = substr($strtime, 0, 10);
            $logwhere[] = ['ctime', '>=', strtotime($strtime)];
            $logwhere[] = ['ctime', '<', strtotime($endtime)];
        } else {
            $strtime = date('Y-m-d') . ' 00:00:00';
            $endtime = date('Y-m-d') . ' 23:59:59';
            $time = substr($strtime, 0, 10);
            $where[] = ['register_time', '>=', $strtime];
            $where[] = ['register_time', '<=', $endtime];
            $where1[] = ['addtime', '>=', $strtime];
            $where1[] = ['addtime', '<=', $endtime];
            $logwhere[] = ['ctime', '>=', strtotime($strtime)];
            $logwhere[] = ['ctime', '<', strtotime($endtime)];
        }
        $channel = $this->spread($channel);
        //日期    日活    新增    新增充值总金额    新增充值人数    新增充值率    充值总金额    充值人数    充值率    ARPU    ARPPU
        $list = [];
        foreach ($channel as $kk => $vv) {
            if (empty($vv)) {
                $vv = '空';
            }
            $retained = $this->leaveRate($strtime, $endtime, 1, $vv);
            $list[$kk]['retained'] = 0;
            $list[$kk]['topup'] = 0;
            if (count($retained) > 1) {
                $list[$kk]['retained'] = round($retained['jt'], 2);
                $list[$kk]['topup'] = $retained['zt'];
            }
            $userid = $this->ReyunUserId($logwhere, $where, 1, $vv);
            $useridtime = $this->ReYunUserTime($where, 1, $vv);
            $list[$kk]['xinz'] = 0;
            $addrih = 0;
            $nczzje = 0;
            $nczrs = 0;
            $list[$kk]['rih'] = 0;
            $list[$kk]['czzje'] = 0;
            $list[$kk]['czrs'] = 0;
            $list[$kk]['riq'] = $time;
            $list[$kk]['channel'] = $vv == '' ? '空' : $vv;
            $list[$kk]['xinz'] = count($useridtime);
            $chargewhere[] = ['status', 'in', [1, 2]];
            $cwhere = array_merge($where1, $chargewhere);
            if (count($userid) > 0) {
                $list[$kk]['rih'] = LogindetailModel::getInstance()->getModel()->where($logwhere)->whereIn('user_id', $userid)->field('user_id')->group('user_id')->select()->count();
                $czzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $userid)->field('rmb,uid')->select()->toArray();
                $list[$kk]['czzje'] = array_sum(array_column($czzje, 'rmb'));
                $list[$kk]['czrs'] = count(array_unique(array_column($czzje, 'uid')));
            }
            $list[$kk]['nczzje'] = 0;
            $list[$kk]['nczrs'] = 0;
            if (count($useridtime) > 0) {
                $logindetailModels = LogindetailModel::getInstance()->getModels($useridtime);
                $addrih = 0;
                foreach ($logindetailModels as $logindetailModel) {
                    $addrih += $logindetailModel->getModel()->where([["user_id", "in", $logindetailModel->getList()]])->field('id')->group('user_id')->select()->count();
                }
                //$addrih = LogindetailModel::getInstance()->getModel()->('user_id', $useridtime)->field('id')->group('user_id')->select()->count();

                $nczzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $useridtime)->field('rmb,uid')->select()->toArray();
                $nczrs = count(array_unique(array_column($nczzje, 'uid')));
                $list[$kk]['nczzje'] = array_sum(array_column($nczzje, 'rmb'));
                $list[$kk]['nczrs'] = $nczrs;
            }

            if ($list[$kk]['czzje'] <= 1 || $list[$kk]['rih'] <= 1 || $list[$kk]['czrs'] <= 1) {
                $list[$kk]['czl'] = 0;
                $list[$kk]['arpu'] = 0;
                $list[$kk]['arppu'] = 0;
            } else {
                $list[$kk]['czl'] = round($list[$kk]['czrs'] / $list[$kk]['rih'] * 100, 2);
                $list[$kk]['arpu'] = round($list[$kk]['czzje'] / $list[$kk]['rih'], 2);
                $list[$kk]['arppu'] = round($list[$kk]['czzje'] / $list[$kk]['czrs'], 2);
            }
            if ($nczrs < 1 || $addrih < 1) {
                $list[$kk]['nczl'] = 0;
            } else {
                $list[$kk]['nczl'] = round(($nczrs / $addrih) * 100, 2);
            }

        }

        $admin_url = config('config.admin_url');
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/spreadname');
    }

    /**
     * @return mixed
     * 热云渠道
     */
    public function ReYunlist()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $endtime = substr($strtime, 0, 10) . ' 23:59:59';
        $list = [];
        if ($strtime != date('Y-m-d') . ' 00:00:00') {
            $bidong = BiDongModel::getInstance()->getModel()->where([['rq', '=', $strtime], ['desc', '=', 'reyunlist']])->select()->toArray();
            if (count($bidong) <= 0) {
                $bidong = BiDongModel::getInstance()->getModel()->where([['rq', '=', $strtime], ['desc', '=', 'reyunlistnew']])->select()->toArray();
                if (count($bidong) <= 0) {
                    $list = $this->ReYunlistExecute($strtime, $endtime);
                }
            } else {
                foreach ($bidong as $kk => $vv) {
                    $list = json_decode($vv['data'], true);
                }
            }
        } else {
            $bidong = BiDongModel::getInstance()->getModel()->where([['rq', '=', $strtime], ['desc', '=', 'reyunlistnew']])->select()->toArray();
            foreach ($bidong as $kk => $vv) {
                $list = json_decode($vv['data'], true);
            }
        }

        $admin_url = config('config.admin_url');
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/reyunlist');
    }

    /**
     * @return mixed
     * 热云
     */
    public function ReYun()
    {
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);

        $datearr = $this->getDateRange($strtime, $endtime);
        $list = [];
        foreach ($datearr as $k => $v) {
            if ($v <= date('Y-m-d')) {
                if ($v != date('Y-m-d')) {
                    $bidong = BiDongModel::getInstance()->getModel()->where([['rq', '=', $v . ' 00:00:00'], ['desc', '=', 'reyun']])->select()->toArray();
                    if (count($bidong) <= 0) {
                        $bidong = BiDongModel::getInstance()->getModel()->where([['rq', '=', $v . ' 00:00:00'], ['desc', '=', 'reyunnew']])->select()->toArray();
                        foreach ($bidong as $kk => $vv) {
                            $list[$k] = json_decode($vv['data'], true);
                        }
                        if (count($bidong) <= 0) {
                            $list[$k] = $this->execute($v . ' 00:00:00', $v . ' 23:59:59');
                        }
                    } else {
                        foreach ($bidong as $kk => $vv) {
                            $list[$k] = json_decode($vv['data'], true);
                        }
                    }

                } else {
                    $bidong = BiDongModel::getInstance()->getModel()->where([['rq', '=', $v . ' 00:00:00'], ['desc', '=', 'reyunnew']])->select()->toArray();
                    foreach ($bidong as $kk => $vv) {
                        $list[$k] = json_decode($vv['data'], true);
                    }
                }
            }
        }

        $admin_url = config('config.admin_url');
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/reyun');
    }

    public function ReYunlistExecute($strtime, $endtime)
    {
        $where = [];
        $where[] = ['register_time', '>=', $strtime];
        $where[] = ['register_time', '<=', $endtime];
        $where1[] = ['addtime', '>=', $strtime];
        $where1[] = ['addtime', '<=', $endtime];
        $logwhere[] = ['ctime', '>=', strtotime($strtime)];
        $logwhere[] = ['ctime', '<', strtotime($endtime)];
        $channel = $this->reYunChannel();
        $list = [];
        foreach ($channel as $kk => $vv) {
            if (empty($vv)) {
                $vv = '空';
            }
            $retained = $this->leaveRate($strtime, $endtime, $vv);
            $list[$kk]['retained'] = 0;
            $list[$kk]['topup'] = 0;
            if (count($retained) > 1) {
                $list[$kk]['retained'] = round($retained['jt'], 2);
                $list[$kk]['topup'] = $retained['zt'];
            }
            $userid = $this->ReyunUserId($logwhere, $where, $vv);
            $useridtime = $this->ReYunUserTime($where, $vv);
            $list[$kk]['xinz'] = 0;
            $addrih = 0;
            $nczzje = 0;
            $nczrs = 0;
            $list[$kk]['rih'] = 0;
            $list[$kk]['czzje'] = 0;
            $list[$kk]['czrs'] = 0;
            $list[$kk]['riq'] = substr($strtime, 0, 10);
            $list[$kk]['channel'] = $vv == '' ? '空' : $vv;
            $list[$kk]['xinz'] = count($useridtime);
            $chargewhere[] = ['status', 'in', [1, 2]];
            $cwhere = array_merge($where1, $chargewhere);
            if (count($userid) > 0) {
                $list[$kk]['rih'] = LogindetailModel::getInstance()->getModel()->where($logwhere)->whereIn('user_id', $userid)->field('user_id')->group('user_id')->select()->count();
                $czzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $userid)->field('rmb,uid')->select()->toArray();
                $list[$kk]['czzje'] = array_sum(array_column($czzje, 'rmb'));
                $list[$kk]['czrs'] = count(array_unique(array_column($czzje, 'uid')));
            }
            $list[$kk]['nczzje'] = 0;
            $list[$kk]['nczrs'] = 0;
            if (count($useridtime) > 0) {
                $logindetailModels = LogindetailModel::getInstance()->getModels($useridtime);
                foreach ($logindetailModels as $logindetailModel) {
                    $addrih += $logindetailModel->getModel()->where([["user_id", "in", $logindetailModel->getList()]])->field('id')->group('user_id')->select()->count();
                }
                $nczzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $useridtime)->field('rmb,uid')->select()->toArray();
                $nczrs = count(array_unique(array_column($nczzje, 'uid')));
                $list[$kk]['nczzje'] = array_sum(array_column($nczzje, 'rmb'));
            }
            if ($list[$kk]['czzje'] <= 1 || $list[$kk]['rih'] <= 1 || $list[$kk]['czrs'] <= 1) {
                $list[$kk]['czl'] = 0;
                $list[$kk]['arpu'] = 0;
                $list[$kk]['arppu'] = 0;
            } else {
                $list[$kk]['czl'] = round($list[$kk]['czrs'] / $list[$kk]['rih'] * 100, 2);
                $list[$kk]['arpu'] = round($list[$kk]['czzje'] / $list[$kk]['rih'], 2);
                $list[$kk]['arppu'] = round($list[$kk]['czzje'] / $list[$kk]['czrs'], 2);
            }
            if ($nczrs < 1 || $addrih < 1) {
                $list[$kk]['nczl'] = 0;
            } else {
                $list[$kk]['nczl'] = round(($nczrs / $addrih) * 100, 2);
            }

            $list[$kk]['nczrs'] = $nczrs;
        }
        $data['rq'] = $strtime;
        $data['desc'] = 'reyunlist';
        $data['data'] = json_encode($list);
        BiDongModel::getInstance()->getModel()->insert($data);
        return $list;
    }

    public function execute($strtime, $endtime)
    {
        $where = [];
        $where1 = [];
        $logwhere = [];
        $chargewhere = [];
        $where[] = ['register_time', '>=', $strtime];
        $where[] = ['register_time', '<=', $endtime];
        $where1[] = ['addtime', '>=', $strtime];
        $where1[] = ['addtime', '<=', $endtime];
        $logwhere[] = ['ctime', '>=', strtotime($strtime)];
        $logwhere[] = ['ctime', '<', strtotime($endtime)];
        $uidtime = $this->ReYunUserTime($where); //新增渠道用戶
        $uid = $this->ReyunUserId($logwhere, $where); //渠道用戶
        $chargewhere[] = ['status', 'in', [1, 2]];
        $cwhere = array_merge($where1, $chargewhere);
        $retained = $this->leaveRate($strtime, $endtime);
        $list['retained'] = 0;
        $list['topup'] = 0;
        if (count($retained) > 1) {
            $list['retained'] = $retained['jt'];
            $list['topup'] = $retained['zt'];
        }
        $list['riq'] = substr($strtime, 0, 10);
        $list['rih'] = 0;
        $list['czzje'] = 0;
        $list['czrs'] = 0;
        if (count($uid) > 0) {
            $rih = LogindetailModel::getInstance()->getModel()->where($logwhere)->group('user_id')->column('user_id');
            $list['rih'] = count(array_intersect_assoc($uid, $rih));
            $czzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->field('rmb,uid')->select()->toArray();
            $info = [];
            foreach ($czzje as $k => $v) {
                if (isset($info[$v['uid']])) {
                    $info[$v['uid']] += $v['rmb'];
                } else {
                    $info[$v['uid']] = $v['rmb'];
                }
            }
            $list['czzje'] = array_sum(array_intersect_key($info, $uid));
            $list['czrs'] = count(array_intersect_key($info, $uid));
        }
        $addrih = 0;
        $nczzje[0]['rmb'] = 0;
        $nczrs = 0;
        $list['nczrs'] = 0;
        $list['nczzje'] = 0;
        $list['xinz'] = count($uidtime);

        if (count($uidtime) > 0) {
            $logindetailModels = LogindetailModel::getInstance()->getModels($uidtime);
            foreach ($logindetailModels as $logindetailModel) {
                $addrih += $logindetailModel->where([["user_id", "in", $logindetailModel->getList()]])->field('id')->group('user_id')->select()->count();
            }
            $nczzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $uidtime)->field('sum(rmb) rmb')->select()->toArray();
            $nczrs = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $uidtime)->field('id')->group('uid')->select()->count();
            $list['nczzje'] = $nczzje[0]['rmb'] == '' ? 0 : $nczzje[0]['rmb'];
            $list['nczrs'] = $nczrs;
        }

        if ($nczrs < 1 || $addrih < 1) {
            $list['nczl'] = 0;
        } else {
            $list['nczl'] = round(($nczrs / $addrih) * 100, 2);
        }
        if ($list['czzje'] == 0) {
            $list['czl'] = 0;
            $list['arpu'] = 0;
            $list['arppu'] = 0;
        } else {
            $list['czl'] = round(($list['czrs'] / $list['rih']) * 100, 2);
            $list['arpu'] = $list['rih'] < 1 ? 0 : round($list['czzje'] / $list['rih'], 2);
            $list['arppu'] = $list['czrs'] < 1 ? 0 : round($list['czzje'] / $list['czrs'], 2);
        }
        $data['rq'] = $strtime;
        $data['desc'] = 'reyun';
        $data['data'] = json_encode($list);
        BiDongModel::getInstance()->getModel()->insert($data);
        return $list;
    }

    /**
     * 获取指定日期段内每一天的日期
     * @date 2017-02-23 14:50:29
     *
     * @param $startdate 起始日期
     * @param $enddate   结束如期
     *
     * @return array
     */
    public function getDateRange($startdate, $enddate)
    {
        $stime = strtotime($startdate);
        $etime = strtotime($enddate);
        $datearr = [];
        while ($stime <= $etime) {
            $datearr[] = date('Y-m-d', $stime); //得到dataarr的日期数组。
            $stime = $stime + 86400;
        }
        return $datearr;
    }

    public function leaveRate($strtime = '', $endtime = '', $channel = '', $spreadname = '')
    {

        $where1[] = ['register_time', '>=', date('Y-m-d', strtotime("-1 day", strtotime($strtime))) . ' 00:00:00'];
        $where1[] = ['register_time', '<=', date('Y-m-d', strtotime("-1 day", strtotime($endtime))) . ' 23:59:59'];
        $where2[] = ['ctime', '>=', strtotime($strtime)];
        $where2[] = ['ctime', '<=', strtotime($endtime)];
        $where3[] = ['ctime', '>=', strtotime(date('Y-m-d', strtotime("-1 day", strtotime($strtime))) . ' 00:00:00')];
        $where3[] = ['ctime', '<=', strtotime(date('Y-m-d', strtotime("-1 day", strtotime($endtime))) . ' 23:59:59')];
        $where4[] = ['addtime', '>=', date('Y-m-d', strtotime("-1 day", strtotime($strtime))) . ' 00:00:00'];
        $where4[] = ['addtime', '<=', date('Y-m-d', strtotime("-1 day", strtotime($endtime))) . ' 23:59:59'];

        if (empty($channel)) {
            $uidtime = $this->ReYunUserTime($where1); //查询昨日注册用户
        } elseif (!empty($spreadname) && $channel == 1) {
            $uidtime = $this->ReYunUserTime($where1, $channel, $spreadname); //查询昨日注册用户
        } else {
            $uidtime = $this->ReYunUserTime($where1, $channel); //查询昨日注册用户
        }
        $useridtime = implode(",", $uidtime);
        $logidstr = LogindetailModel::getInstance()->getModel()->where($where3)->whereIn('user_id', $useridtime)->group('user_id')->column('user_id'); //查询昨日注册登录用户
        $ppyuserstr = ChargedetailModel::getInstance()->getModel()->where($where4)->whereIn('uid', $useridtime)->group('uid')->column('uid'); //查询昨日注册消费用户

        $Today = LogindetailModel::getInstance()->getModel()->where($where2)->whereIn('user_id', $logidstr)->group('user_id')->column('user_id'); //查询昨日用户今日留存
        $Tuser = LogindetailModel::getInstance()->getModel()->where($where2)->whereIn('user_id', $ppyuserstr)->group('user_id')->column('user_id'); //查询昨日用户今日留存
        if (count($Today)) {
            foreach ($Today as $k => $v) {
                $Todaystr[] = $v['user_id'];
            }
        }
        if (count($Tuser)) {
            foreach ($Tuser as $k => $v) {
                $Tuserstr[] = $v['user_id'];
            }
        }
        $jt = 0;
        $zt = 0;
        if (count($Tuser) > 0 || count($ppyuserstr) > 0) {
            $zt = round(count($Tuser) / count($ppyuserstr) * 100, 2); //昨日用户今天留存;//付费留存率
        }
        if (count($Today) > 0 || count($logidstr) > 0) {
            $jt = round(count($Today) / count($logidstr) * 100, 2); //昨日用户今天留存
        }
        $data = ['zt' => $zt, 'jt' => $jt];
        return $data; //响应请求
    }

    /**
     * @return mixed
     * 删除页面
     */
    public function delshow()
    {
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/delshow');
    }

    /**
     * 删除执行
     */
    public function delete()
    {
        $from = $this->request->param('from');
        $id = $this->request->param('id');
        $sql = "DELETE FROM " . $from . " WHERE id in (" . $id . ")";
        $is = Db::query($sql);
        if (empty($is)) {
            echo json_encode(['code' => 200, 'msg' => '删除成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '删除失败']);
            die;
        }
    }

    /**
     * 修改
     */
    public function dataSave()
    {
        $from = $this->request->param('from');
        $id = $this->request->param('id');
        $column = $this->request->param('column');
        $var = $this->request->param('var');
        $wid = $this->request->param('wid');
        $sql = "UPDATE " . $from . " SET " . $column . " = " . $var . " WHERE " . $wid . " = " . $id;
        $is = Db::query($sql);
        if (empty($is)) {
            echo json_encode(['code' => 200, 'msg' => '修改成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '修改失败']);
            die;
        }
    }

    /**
     * @return mixed
     * 修改展示
     */
    public function dataSaveShow()
    {
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/dataSaveShow');
    }

    /**
     * @return mixed
     * sql展示
     */
    public function sqlShwo()
    {
        View::assign('token', $this->request->param('token'));
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/sqlShwo');
    }

    public function sqlQuery()
    {
        $sql = $this->request->param('sql');
        $is = Db::query($sql);
        if (empty($is)) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);
            die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);
            die;
        }
    }

    /**
     * 用户许愿石详情
     */
    public function UserStone()
    {
        $pagenum = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $strtime = $this->request->param('strtime');
        $endtime = $this->request->param('endtime');
        $uid = $this->request->param('uid');
        $demo = $this->request->param('demo', $this->default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $where = [];
        $where[] = ['createTime', '>=', strtotime($strtime)];
        $where[] = ['createTime', '<', strtotime($endtime)];
        if (!empty($uid)) {
            $where[] = ['uid', '=', $uid];
        }
        $count = FirstpayHammersModel::getInstance()->getModel()->where($where)->field('id')->select()->count();
        $data = FirstpayHammersModel::getInstance()->getModel()->where($where)->limit($page, $pagenum)->select()->toArray();
        foreach ($data as $k => $v) {
            $data[$k]['gift'] = '';
            $data[$k]['gift_coin'] = 0;
            if ($data[$k]['status'] == 0) {
                $data[$k]['status'] = '已使用';
                $giftid = BoxGiftModel::getInstance()->getModel()->where([['type', '=', 2], ['uid', '=', $v['uid']]])->limit(1)->field('giftid')->select()->toArray();
                if (isset($giftid[0]['giftid'])) {
                    $gift = GiftModel::getInstance()->getModel()->where('id', $giftid[0]['giftid'])->field('gift_name,gift_coin')->select()->toArray();
                    if (isset($gift[0]['gift_name']) && isset($gift[0]['gift_coin'])) {
                        $data[$k]['gift'] = $gift[0]['gift_name'];
                        $data[$k]['gift_coin'] = $gift[0]['gift_coin'];
                    }
                }
            } else {
                $data[$k]['status'] = '未使用';
            }
            $data[$k]['createTime'] = date('Y-m-d H:i:s', $data[$k]['createTime']);
            $data[$k]['updateTime'] = date('Y-m-d H:i:s', $data[$k]['updateTime']);
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('list', $data);
        View::assign('uid', $uid);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/userstone');

    }

    //数据管理列表
    public function indexOld()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;

        $default_start = date('Y-m-d', strtotime("-60 days"));
        $default_end = date('Y-m-d');
        $strtime = $this->request->param('strtime', $default_start);
        $endtime = $this->request->param('endtime', $default_end);

        $daochu = $this->request->param('daochu');
        $where = [];
        $where[] = ['date', '>=', $strtime];
        $where[] = ['date', '<=', $endtime];
        $where[] = ['date', '=', '2021-07-01'];

        if ($daochu == 1) {
            $list = Db::table('bi_days_stats_by_day')->where($where)->where('`date`=`retention_date`')->order('id desc')->select()->toArray();
        } else {
            $list = Db::table('bi_days_stats_by_day')->where($where)->where('`date`=`retention_date`')->limit($page, $pagenum)->order('id desc')->select()->toArray();
        }

        $num = 0;
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['scharge'] = 0;
                $list[$k]['qcharge'] = 0;
                $list[$k]['swcharge'] = 0;

                $xinz_user_id = json_decode($v['reg_users']);
                if (!empty($xinz_user_id)) {
                    //获取十五日内的每天的付费金额
                    $days_15 = date('Y-m-d', strtotime("{$v['date']} +15day"));

                    $charge_sql = "select reg_pay_amount as amount,retention_date date from bi_days_stats_by_day where date = '{$v['date']}' and retention_date >= '{$v['date']}' and retention_date < '{$days_15}'";
                    $charge_datas = Db::query($charge_sql);

                    //三日付费
                    $days_3 = date('Y-m-d', strtotime("{$v['date']} +3day"));
                    //七日付费
                    $days_7 = date('Y-m-d', strtotime("{$v['date']} +7day"));

                    $scharge = 0;
                    $qcharge = 0;
                    $swcharge = 0;

                    foreach ($charge_datas as $charge_data) {
                        $charge_date = $charge_data['date'];
                        $amount = $charge_data['amount'];
                        if ($charge_date < $days_3) {
                            $scharge += $amount;
                        }
                        if ($charge_date < $days_7) {
                            $qcharge += $amount;
                        }
                        if ($charge_date < $days_15) {
                            $swcharge += $amount;
                        }

                    }

                    $list[$k]['scharge'] = $scharge;
                    $list[$k]['qcharge'] = $qcharge;
                    $list[$k]['swcharge'] = $swcharge;
                }
                if ($daochu == 1) {
                    $lcdata = Db::table('bi_days_stats_by_day')->where($where)->where('`date`=`retention_date`')->where(['date' => $v['date']])->find();
                    $list[$k]['cirlc'] = $lcdata['cirlc'];
                    $list[$k]['sanrlc'] = $lcdata['sanrlc'];
                    $list[$k]['qirlc'] = $lcdata['qirlc'];
                }
            }
            //查询总数
            $num = Db::table('bi_days_stats_by_day')->where($where)->where('`date`=`retention_date`')->count();
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
            $this->putcsv($list);
        }
        return View::fetch('dataManagement/dayDatas');
    }

    //数据管理列表
    public function indexNew()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;

        $default_start = date('Y-m-d', strtotime("-7 days"));
        $default_end = date('Y-m-d');
        $demo = $this->request->param('demo', $default_start . ' - ' . $default_end);
        list($strtime, $endtime) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');
        $where = [];
        $where[] = ['date', '>=', $strtime];
        $where[] = ['date', '<', $endtime];

        $count = Db::table('bi_new_daily_day')->field('*')->where($where)->count();
        if ($daochu == 1) {
            $list = Db::table('bi_new_daily_day')->field('*')->where($where)->order('date desc')->select()->toArray();
        } else {
            $list = Db::table('bi_new_daily_day')->field('*')->where($where)->limit($page, $pagenum)->order('date desc')->select()->toArray();
        }

        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $list[$k]['time'] = strtotime($v['date']);
                $list[$k]['reg_pay_rate'] = round($v['register_user_charge_num'] * 100 / ($v['register_people_num'] == 0 ? 1 : $v['register_people_num']), 2);
                $list[$k]['pay_rate'] = round($v['charge_people_sum'] * 100 / ($v['daily_life'] == 0 ? 1 : $v['daily_life']), 2);
                $list[$k]['arpu'] = round($v['directcharge_money_sum'] / ($v['daily_life'] == 0 ? 1 : $v['daily_life']), 2);
                $list[$k]['arppu'] = round($v['directcharge_money_sum'] / ($v['charge_people_sum'] == 0 ? 1 : $v['charge_people_sum']), 2);
                $list[$k]['retention_1'] = round($v['keep_login_1'] * 100 / ($v['register_people_num'] == 0 ? 1 : $v['register_people_num']), 2);
                $list[$k]['retention_3'] = round($v['keep_login_3'] * 100 / ($v['register_people_num'] == 0 ? 1 : $v['register_people_num']), 2);
                $list[$k]['retention_7'] = round($v['keep_login_7'] * 100 / ($v['register_people_num'] == 0 ? 1 : $v['register_people_num']), 2);
                $list[$k]['retention_15'] = round($v['keep_login_15'] * 100 / ($v['register_people_num'] == 0 ? 1 : $v['register_people_num']), 2);
                $list[$k]['retention_30'] = round($v['keep_login_30'] * 100 / ($v['register_people_num'] == 0 ? 1 : $v['register_people_num']), 2);
            }
        }

        if ($daochu == 1) {
            $columns = [
                'date' => '日期',
                'daily_life' => '日活',
                'register_people_num' => '新增',
                'register_user_charge_amount' => '新增充值',
                'register_user_charge_num' => '新充值人',
                'reg_pay_rate' => '新充值率',
                'directcharge_money_sum' => '直充金值',
                'directcharge_people_num' => '直充人数',
                'agentcharge_amount' => '代充金额',
                'agentcharge_people_num' => '代充人数',
                'vip_money_sum' => 'vip',
                'pay_rate' => '充值率',
                'arpu' => 'ARPU',
                'arppu' => 'ARPPU',
                'fee_register_3' => '三日付费',
                'fee_register_7' => '七日付费',
                'fee_register_15' => '十五日付费',
                'retention_1' => '次日留存',
                'retention_3' => '三日留存',
                'retention_7' => '七日留存',
                'retention_15' => '十五日留存',
                'retention_30' => '三十日留存',
            ];
            ExportExcelService::getInstance()->export($list, $columns);
        }
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);

        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
            $this->putcsv($list);
        }
        return View::fetch('dataManagement/indexnew');
    }

    //Top充值用户列表
    public function userPayStats()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $uid = $this->request->param('uid');
        $daochu = $this->request->param('daochu');
        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);

        $where = [];
        $where[] = ['date', '>=', $start];
        $where[] = ['date', '<', $end];
        if ($uid) {
            $where[] = ['uid', 'in', [$uid]];
        }

        //充值用户数据
        $query = Db::table('bi_days_user_charge')->field('uid,ROUND(sum(amount/10),2) amount')->where($where)->group('uid');

        //合计充值数据
        $sum_charge_data = Db::table('bi_days_user_charge')->field('ROUND(sum(amount/10),2) amount')->where($where)->find();

        $count = $query->count();
        if ($daochu == 1) {
            $list = $query->select()->toArray();
        } else {
            $list = $query->order('amount desc')->limit($page, $pagenum)->select()->toArray();
        }
        $historyChargeRes = [];

        $uids = array_column($list, 'uid');
        if (!empty($uids)) {
            $where[] = ['uid', 'in', $uids];
        }

        //用户信息
        $users = MemberModel::getInstance()->getWhereAllData([['id', 'in', $uids]], 'id,username,nickname,avatar,lv_dengji,duke_id,chargecoin');

        $userInfo_map = array_column($users, null, 'id');
        //用户游戏数据
        $game_data = Db::table('bi_days_user_activity_datas')
            ->field('uid,activity,ROUND(sum(consume_amount),2) consume_amount,sum(output_amount/10) output_amount')
            ->where($where)
            ->group('uid,activity')
        // ->fetchSql(true)
            ->order('uid')
            ->select()
            ->toArray();

        $user_game_data = [];
        foreach ($game_data as $key => $user_game) {
            $user_game_data[$user_game['uid']][$user_game['activity']] = $user_game;
        }

        //合计充值数据
        $sum_game_data = Db::table('bi_days_user_activity_datas')
            ->field('activity,ROUND(sum(consume_amount),2) consume_amount,sum(output_amount/10) output_amount')
            ->where($where)
            ->group('activity')
            ->select()
            ->toArray();

        $sum_game_data = array_column($sum_game_data, null, 'activity');

        //直刷豆数
        $where[] = ['type', '=', 1];
        $sendgift_data = Db::table('bi_days_user_gift_datas_bysend_type')
            ->field('uid,ROUND(sum(consume_amount/10),2) consume_amount')
            ->where($where)
            ->group('uid')
            ->select()
            ->toArray();

        $sendgift_data = array_column($sendgift_data, null, 'uid');
        $sum_sendgift_data = Db::table('bi_days_user_gift_datas_bysend_type')
            ->field('ROUND(sum(consume_amount/10),2) consume_amount')
            ->where($where)
            ->find();

        $sum_info = [
            'total_charge_amount' => isset($sum_charge_data['amount']) ? $sum_charge_data['amount'] : 0,
            'total_box2_amount' => isset($sum_game_data['box2']) ? $sum_game_data['box2']['consume_amount'] : 0,
            'total_taojin_amount' => isset($sum_game_data['taojin']) ? $sum_game_data['taojin']['consume_amount'] : 0,
            'total_turntable_amount' => isset($sum_game_data['turntable']) ? $sum_game_data['turntable']['consume_amount'] : 0,
            'total_gopher_amount' => isset($sum_game_data['gopher']) ? $sum_game_data['gopher']['consume_amount'] : 0,
            'total_consume_amount' => isset($sum_sendgift_data['consume_amount']) ? $sum_sendgift_data['consume_amount'] : 0,
        ];

        $duke_map = DukeModel::getInstance()->getModel()->column('duke_name', 'duke_id');
        if (!empty($list)) {
            $hischarge = Db::table('bi_days_user_charge')
                ->field('uid,ROUND(sum(amount/10),2) amount')
                ->where('uid', 'in', array_column($list, "uid"))
                ->group('uid')
                ->select()
                ->toArray();
            $hisres = array_column($hischarge, null, "uid");

            foreach ($list as $k => &$payInfo) {
                $user_id = $payInfo['uid'];

                $payInfo['username'] = '';
                $payInfo['nickname'] = '';
                $payInfo['avatar'] = '';
                $payInfo['lv_dengji'] = '';
                $payInfo['duke_id'] = '';
                $payInfo['chargecoin'] = '';
                $payInfo['sendgift_amount'] = 0;
                $payInfo['game_amount'] = 0;

                if (isset($userInfo_map[$user_id])) {
                    $userInfo = $userInfo_map[$user_id];

                    $payInfo['username'] = $userInfo['username'];
                    $payInfo['nickname'] = $userInfo['nickname'];
                    $payInfo['avatar'] = config('config.APP_URL_image') . $userInfo['avatar'];
                    $payInfo['lv_dengji'] = $userInfo['lv_dengji'];
                    $payInfo['duke_id'] = isset($duke_map[$userInfo['duke_id']]) ? $duke_map[$userInfo['duke_id']] : '';
                    //$payInfo['chargecoin'] = $userInfo['chargecoin'];
                    $payInfo['chargecoin'] = $hisres[$user_id]['amount'] ?? 0;
                }

                if (isset($sendgift_data[$user_id])) {
                    $send_gift = $sendgift_data[$user_id];
                    $payInfo['sendgift_amount'] = $send_gift['consume_amount'];
                }

                foreach (CommonConst::$game_map as $game => $_) {
                    $consume_amount = 0;
                    $payInfo[$game] = 0;
                    if (isset($user_game_data[$user_id])) {
                        $game_data = $user_game_data[$user_id];
                        if (isset($game_data[$game])) {
                            $consume_amount = $game_data[$game]['consume_amount'];
                        }
                        $payInfo[$game] = $consume_amount;
                        $payInfo['game_amount'] += $consume_amount;
                    }
                }
            }
        }

        if ($daochu == 1) {
            $columns = [
                'avatar' => '用户头像',
                'nickname' => '用户名',
                'uid' => '用户ID',
                'lv_dengji' => '等级',
                'duke_id' => '贵族',
                'amount' => '充值金额',
                'chargecoin' => '历史充值金额',
                'box2' => '开宝箱积分',
                'box' => '开旧宝箱积分',
                'turntable' => '转盘积分',
                'taojin' => '飞行棋积分',
                'gopher' => '打地鼠积分',
                'game_amount' => '抽奖类总积分',
                'sendgift_amount' => '直刷豆数',
            ];
            ExportExcelService::getInstance()->export($list, $columns);
        }
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);

        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('data', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('sum_info', $sum_info);
        View::assign('demo', $demo);
        View::assign('uid', $uid);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/userPayStats');
    }

    /**
     * 獲取所有渠道用戶
     */
    public function userid($where = [], $where2 = [], $channel = '')
    {
        $loguser = LogindetailModel::getInstance()->getWhereAllData($where, "user_id", "user_id");
        $id = [];
        if (count($loguser) > 0) {
            foreach ($loguser as $k => $v) {
                $id[] = $v['user_id'];
            }
        }
        $useridt = implode(",", $id);
        $log = [];
        if (empty($channel)) {
            $channellist = $this->channeltype($where2);
            $channelarr = [];
            foreach ($channellist as $k => $v) {
                $channelarr[] = $v['register_channel'] ?? '';
            }
            $channelstr = implode(",", $channelarr);
            $uid = MemberModel::getInstance()->getWhereAllData([["id", "in", $useridt], ["register_channel", "in", $channelstr]], 'id');
            foreach ($uid as $k => $v) {
                $log[] = $v['id'];
            }
        } else {
            $where1[] = ['register_channel', '=', $channel];
            $uid = MemberModel::getInstance()->getWhereAllData([["id", "in", $useridt], ["register_channel", "in", $channel]], 'id');
            foreach ($uid as $k => $v) {
                $log[] = $v['id'];
            }
        }
        return $log;
    }

    /**
     * 獲取新增渠道用戶
     */
    public function useridtime($where = [], $channel = '')
    {
        $uid1 = [];
        if (!empty($channel)) {
            $where[] = ['register_channel', 'in', $channel];
        }
        $uid = MemberModel::getInstance()->getWhereAllData($where, "id");

        foreach ($uid as $k => $v) {
            $uid1[] = $v['id'];
        }
        return $uid1;
    }

    public function channeltype($where)
    {
        $channel_list = MemberModel::getInstance()->getWhereAllData($where, "register_channel");
        $channel = array_unique(array_column($channel_list, 'register_channel'));
        return $channel;
    }

/*    //线上渠道数据管理列表
public function channel()
{
echo $this->return_json(5000, null, '请联系运营');
die;
}

public function channelPay()
{
echo $this->return_json(5000, null, '请联系运营');
die;
}

//渠道详情
public function channellist2()
{
echo $this->return_json(5000, null, '请联系运营');
die;
}*/

    //线上渠道数据管理列表
    public function channel()
    {
        try {
            $pagenum = 20;
            $master_page = $this->request->param('page', 1);
            $page = ($master_page - 1) * $pagenum;
            $demo = $this->request->param('demo', $this->default_date);
            list($strtime, $endtime) = getBetweenDate($demo);
            $daochu = $this->request->param('daochu');
            $where = [];
            $where[] = ['register_time', '>=', $strtime];
            $where[] = ['register_time', '<', $endtime];
            $where1[] = ['addtime', '>=', $strtime];
            $where1[] = ['addtime', '<', $endtime];
            $time = substr($strtime, 0, 10);
            $logwhere[] = ['ctime', '>=', strtotime($strtime)];
            $logwhere[] = ['ctime', '<', strtotime($endtime)];

            $uidtime = $this->useridtime($where); //新增渠道用戶
            $uid = $this->userid($logwhere, $where); //渠道用戶
            $useridtime = implode(",", $uidtime);
            $userid = implode(",", $uid);
            $chargewhere[] = ['status', 'in', [1, 2]];
            $cwhere = array_merge($where1, $chargewhere);
            //日期    日活    新增    新增充值总金额    新增充值人数    新增充值率    充值总金额    充值人数    充值率    ARPU    ARPPU
            $retained = $this->leave($strtime, $endtime);
            $list[0]['retained'] = 0;
            $list[0]['topup'] = 0;
            if (count($retained) > 1) {
                $list[0]['retained'] = $retained['jt'];
                $list[0]['topup'] = $retained['zt'];
            }
            $list[0]['riq'] = $time;
            $list[0]['rih'] = 0;
            $list[0]['czzje'] = 0;
            $list[0]['czrs'] = 0;
            if (count($uid) > 0) {
                $list[0]['rih'] = count(LogindetailModel::getInstance()->getUidsByWhere($logwhere));
                $czzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->field('sum(rmb) rmb')->select()->toArray();
                $list[0]['czzje'] = $czzje[0]['rmb'] == '' ? 0 : $czzje[0]['rmb'];
                $list[0]['czrs'] = ChargedetailModel::getInstance()->getModel()->where($cwhere)->field('id')->group('uid')->select()->count();
            }
            $addrih = 0;
            $nczzje[0]['rmb'] = 0;
            $nczrs = 0;
            $list[0]['nczrs'] = 0;
            $list[0]['nczzje'] = 0;
            $list[0]['xinz'] = count($uidtime);

            if (count($uidtime) > 0) {
                //$addrih = LogindetailModel::getInstance()->getModel()->whereIn('user_id', $useridtime)->field('id')->group('user_id')->select()->count();
                $addrih = count(LogindetailModel::getInstance()->getWhereAllData([["user_id", "in", $useridtime]], "id", "user_id"));
                $nczzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $useridtime)->field('sum(rmb) rmb')->select()->toArray();
                $nczrs = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $useridtime)->field('id')->group('uid')->select()->count();
                $list[0]['nczzje'] = $nczzje[0]['rmb'] == '' ? 0 : $nczzje[0]['rmb'];
                $list[0]['nczrs'] = $nczrs;
            }

            if ($nczrs < 1 || $addrih < 1) {
                $list[0]['nczl'] = 0;
            } else {
                $list[0]['nczl'] = round(($nczrs / $addrih) * 100, 2);
            }
            if ($list[0]['czzje'] == 0) {
                $list[0]['czl'] = 0;
                $list[0]['arpu'] = 0;
                $list[0]['arppu'] = 0;
            } else {
                $list[0]['czl'] = round(($list[0]['czrs'] / $list[0]['rih']) * 100, 2);
                $list[0]['arpu'] = $list[0]['rih'] < 1 ? 0 : round($list[0]['czzje'] / $list[0]['rih'], 2);
                $list[0]['arppu'] = $list[0]['czrs'] < 1 ? 0 : round($list[0]['czzje'] / $list[0]['czrs'], 2);
            }

            $num = 1;
            $page_array = [];
            $page_array['page'] = $master_page;
            $page_array['total_page'] = ceil($num / $pagenum);
            $admin_url = config('config.admin_url');
            View::assign('page', $page_array);
            View::assign('list', $list);
            View::assign('token', $this->request->param('token'));
            View::assign('demo', $demo);
            View::assign('user_role_menu', $this->user_role_menu);
            View::assign('admin_url', $admin_url);
            View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
            if ($daochu == 1) {
                $this->putcsv($list);
            }
            return View::fetch('dataManagement/channel');
        } catch (\Throwable $e) {
            Log::error($e->getMessage() . $e->getLine() . $e->getFile());
        }
    }

    public function channelPay()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $riq = Request::param('riq');
        $memberWhere[] = ['a.register_time', '>', $riq];
        $memberWhere[] = ['a.register_time', '<', $riq . ' 23:59:59'];
        $memberWhere[] = ['b.status', 'in', [1, 2]];
        $num = MemberModel::getInstance()->getModel()->alias('a')->join('zb_chargedetail b', 'a.id=b.uid')->where($memberWhere)->group('b.uid')->count();
        $list = MemberModel::getInstance()->getModel()->alias('a')->join('zb_chargedetail b', 'a.id=b.uid')->where($memberWhere)->group('b.uid')->limit($page, $pagenum)->field('a.id,nickname')->select();
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        $admin_url = config('config.admin_url');
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('riq', $riq);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('admin_url', $admin_url);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/channelPay');
    }

    //渠道详情
    public function channellist2()
    {
        try {
            $strtime = $this->request->param('strtime');
            $endtime = $this->request->param('endtime');
            $where = [];
            if (!empty($strtime) && !empty($endtime)) {
                $where[] = ['register_time', '>=', $strtime];
                $where[] = ['register_time', '<=', $endtime];
                $where1[] = ['addtime', '>=', $strtime];
                $where1[] = ['addtime', '<=', $endtime];
                $time = substr($strtime, 0, 10);
                $logwhere[] = ['ctime', '>=', strtotime($strtime)];
                $logwhere[] = ['ctime', '<', strtotime($endtime)];
            } else {
                $strtime = date('Y-m-d') . ' 00:00:00';
                $endtime = date('Y-m-d') . ' 23:59:59';
                $time = substr($strtime, 0, 10);
                $where[] = ['register_time', '>=', $strtime];
                $where[] = ['register_time', '<=', $endtime];
                $where1[] = ['addtime', '>=', $strtime];
                $where1[] = ['addtime', '<=', $endtime];
                $logwhere[] = ['ctime', '>=', strtotime($strtime)];
                $logwhere[] = ['ctime', '<', strtotime($endtime)];
            }
            $channellist = $this->channeltype($where);
            //日期    日活    新增    新增充值总金额    新增充值人数    新增充值率    充值总金额    充值人数    充值率    ARPU    ARPPU
            $channel = [];
            foreach ($channellist as $k => $v) {
                $channel[] = $v;
            }
            $list = [];
            foreach ($channel as $kk => $vv) {
                $retained = $this->leave($strtime, $endtime, $vv);
                $list[$kk]['retained'] = 0;
                $list[$kk]['topup'] = 0;
                if (count($retained) > 1) {
                    $list[$kk]['retained'] = $retained['jt'];
                    $list[$kk]['topup'] = $retained['zt'];
                }
                $uid = $this->userid($logwhere, $where, $vv);
                $uidtime = $this->useridtime($where, $vv);
                $useridtime = implode(",", $uidtime);
                $userid = implode(",", $uid);
                $list[$kk]['xinz'] = 0;
                $addrih = 0;
                $nczzje = 0;
                $nczrs = 0;
                $list[$kk]['rih'] = 0;
                $list[$kk]['czzje'] = 0;
                $list[$kk]['czrs'] = 0;
                $list[$kk]['riq'] = $time;
                $list[$kk]['channel'] = $vv == '' ? '无' : $vv;
                $list[$kk]['xinz'] = count($uidtime);
                $chargewhere[] = ['status', 'in', [1, 2]];
                $cwhere = array_merge($where1, $chargewhere);
                if (count($uid) > 0) {
                    $list[$kk]['rih'] = LogindetailModel::getInstance()->getModel()->where($logwhere)->whereIn('user_id', $userid)->field('user_id')->group('user_id')->select()->count();
                    $czzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $userid)->field('sum(rmb) rmb')->select()->toArray();
                    $list[$kk]['czzje'] = $czzje[0]['rmb'] == '' ? 0 : $czzje[0]['rmb'];
                    $list[$kk]['czrs'] = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $userid)->field('id')->group('uid')->select()->count();
                }
                if (count($uidtime) > 0) {
                    $addrih = LogindetailModel::getInstance()->getModel()->whereIn('user_id', $useridtime)->field('id')->group('user_id')->select()->count();
                    $nczzje = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $useridtime)->field('sum(rmb) rmb')->select()->toArray();
                    $nczrs = ChargedetailModel::getInstance()->getModel()->where($cwhere)->whereIn('uid', $useridtime)->field('id')->group('uid')->select()->count();
                }
                if ($list[$kk]['czzje'] <= 1 || $list[$kk]['rih'] <= 1 || $list[$kk]['czrs'] <= 1) {
                    $list[$kk]['czl'] = 0;
                    $list[$kk]['arpu'] = 0;
                    $list[$kk]['arppu'] = 0;
                } else {
                    $list[$kk]['czl'] = round($list[$kk]['czrs'] / $list[$kk]['rih'], 2);
                    $list[$kk]['arpu'] = round($list[$kk]['czzje'] / $list[$kk]['rih'], 2);
                    $list[$kk]['arppu'] = round($list[$kk]['czzje'] / $list[$kk]['czrs'], 2);
                }
                if ($nczrs < 1 || $addrih < 1) {
                    $list[$kk]['nczl'] = 0;
                } else {
                    $list[$kk]['nczl'] = round(($nczrs / $addrih) * 100, 2);
                }
                $list[$kk]['nczrs'] = $nczrs;
                $list[$kk]['nczzje'] = $nczzje[0]['rmb'] == '' ? 0 : $nczzje[0]['rmb'];
            }

            $admin_url = config('config.admin_url');
            View::assign('list', $list);
            View::assign('token', $this->request->param('token'));
            View::assign('strtime', $strtime);
            View::assign('endtime', $endtime);
            View::assign('user_role_menu', $this->user_role_menu);
            View::assign('admin_url', $admin_url);
            View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
            return View::fetch('dataManagement/channellist');
        } catch (\Throwable $e) {
            echo $e->getMessage() . $e->getLine() . $e->getFile();
        }
    }

    //导出csv
    public function putcsv($data)
    {
        $headerArray = ['日期', '日活', '新增', '次留1日', '次留3日', '次留7日', '新增充值总金额', '新增充值人数', '新增充值率', '充值总金额', '充值人数', '充值率', 'ARPU', 'ARPPU', "三日付费", "七日付费", "十五日付费"];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['riq'] = $value['riq'];
            $outArray['rih'] = $value['rih'];
            $outArray['xinz'] = $value['xinz'];
            $outArray['cirlc'] = ($value['cirlc'] / 100) . "%";
            $outArray['sanrlc'] = ($value['sanrlc'] / 100) . "%";
            $outArray['qirlc'] = ($value['qirlc'] / 100) . "%";
            $outArray['nczzje'] = $value['nczzje'];
            $outArray['nczrs'] = $value['nczrs'];
            $outArray['nczl'] = ($value['nczl'] / 100) . "%";
            $outArray['czzje'] = $value['czzje'];
            $outArray['czrs'] = $value['czrs'];
            $outArray['czl'] = ($value['czl'] / 100) . "%";
            $outArray['arpu'] = $value['arpu'] / 100;
            $outArray['arppu'] = $value['arppu'] / 100;
            $outArray['scharge'] = $value['scharge'];
            $outArray['qcharge'] = $value['qcharge'];
            $outArray['swcharge'] = $value['swcharge'];
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
     * 房间统计列表
     * @param string $token　token值
     * @param string $limit  limit条数
     * @param string $page  page分页
     */
    public function roomList()
    {
        $limit = 20;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $default_date = $this->end_time2 . ' - ' . $this->start_time;
        $demo = $this->request->param('demo', $default_date);
        list($start_time, $end_time) = getBetweenDate($demo);
        $room_id = Request::param('room_id'); //房间id
        $gonghui_id = Request::param('gonghui_id');
        $isgh = Request::param('isgh');
        $daochu = Request::param('daochu');
        $isgh = $isgh ? $isgh : 1;
        $where = [];
        $guildWhere = [];
        if ($isgh == 1) {
            $guildWhere[] = ['guild_id', '>', 0];
        } else {
            $guildWhere[] = ['guild_id', '=', 0];
        }
        if (!empty($gonghui_id)) {
            $ghArr = LanguageroomModel::getInstance()->getWhereAllData([["guild_id", '=', $gonghui_id]], 'id,room_name');
        } else {
            $ghArr = LanguageroomModel::getInstance()->getWhereAllData($guildWhere, 'id,room_name');
        }
        $ids = [];
        if (!empty($ghArr)) {
            $ghidArr = $ghArr->toArray();
            foreach ($ghidArr as $key => $value) {
                array_push($ids, $value['id']);
            }
        }
        $where[] = ['room_id', 'in', $ids];

        if (!empty($room_id)) {
            $where[] = ['room_id', '=', $room_id];
        }
        $where[] = ['riqi', '>=', strtotime($start_time)];
        $where[] = ['riqi', '<', strtotime($end_time)];

        if ($daochu == 1) {
            $data = RoomLogintimeModel::getInstance()->getModel()->where($where)->order('riqi desc')->select();
        } else {
            $data = RoomLogintimeModel::getInstance()->getModel()->where($where)->limit($offset, $limit)->order('riqi desc')->select();
        }
        //$data = RoomLogintimeModel::getInstance()->getModel()->where($where)->limit($offset,$limit)->order('riqi desc')->select();
        $totalPage = 0;
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $data[$key]['riqi'] = date('Y-m-d', $value['riqi']);
                $data[$key]['room_name'] = LanguageroomModel::getInstance()->getModel($room_id)->where(['id' => $value['room_id']])->value('room_name');
                $data[$key]['longstaytime'] = Sec2Time($value['longstaytime']);
            }
            $count = RoomLogintimeModel::getInstance()->getModel()->where($where)->count();
        }
        $totalPage = ceil($count / $limit);
        Log::record('房间统计列表:操作人:' . $this->token['username'], 'roomList');
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        $admin_url = config('config.admin_url');
        //获取当前时间
        $search_end_time = date("Y-m-d", time());
        //查公会
        $gonghui = MemberGuildModel::getInstance()->getghData([['status', 'in', [1, 2]]], "id,nickname");
        View::assign('list', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('room_id', $room_id);
        View::assign('demo', $demo);
        View::assign('admin_url', $admin_url);
        View::assign('search_end_time', $search_end_time);
        View::assign('gonghui_id', $gonghui_id);
        View::assign('gonghui', $gonghui);
        View::assign('isgh', $isgh);
        if ($daochu == 1) {
            $this->roomcsv($data);
        }
        return View::fetch('dataManagement/roomlist');
    }

    //导出csv
    public function roomcsv($data)
    {
        $headerArray = ['房间Id', '房主Id', '房间名称', '房间总消费', '房间在线人数', '房间在线总人数', '日期', '平均用户在线时间长'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['room_id'] = $value['room_id'];
            $outArray['user_id'] = $value['user_id'];
            $outArray['room_name'] = trim($value['room_name'], ',');
            $outArray['room_totail'] = $value['room_totail'];
            $outArray['online_num'] = $value['online_num'];
            $outArray['number'] = $value['number'];
            $outArray['riqi'] = $value['riqi'];
            $outArray['longstaytime'] = $value['longstaytime'];
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
     * 房间用户统计列表
     * @param string $token　token值
     * @param string $limit  limit条数
     * @param string $page  page分页
     */
    public function roomdmember()
    {
        $room_id = Request::get('room_id/d'); //强制转换为整型类型
        $daytime = Request::get('riqi'); //日期
        if (!$room_id || !$daytime) {
            echo "请在房间统计列表点击详情操作展示";
            die;
        }
        $limit = 5;
        $master_page = $this->request->param('page', 1);
        $offset = ($master_page - 1) * $limit;
        $user_id = Request::param('user_id'); //用户id
        $where = [];
        if (!empty($user_id)) {
            $where[] = ['user_id', '=', $user_id];
        }
        if (!empty($room_id)) {
            $where[] = ['room_id', '=', $room_id];
        }

        $starttime = date("Y-m-d 00:00:00", strtotime($daytime));
        $endtime = date("Y-m-d 23:59:59", strtotime($daytime) + 84239);

        if (!empty($daytime)) {
            $where[] = ['addtime', '>=', $starttime];
            $where[] = ['addtime', '<=', $endtime];
        }
        //获取redis数据
        $arr = [
            "select" => 4,
        ];
        $redistime = date("Ymd", strtotime($daytime));
        $redis = RedisCommon::getInstance()->getRedis($arr);
        $room_time_redis = "RoomEnterOrExit_";
        $userData = $redis->LRANGE($room_time_redis . $room_id . "_" . $redistime, 0, -1);
        $logintime_string = 0;
        $user_id_string = 0;
        $tmpes = [];
        $list = [];
        $newarrTmp = [];
        $newarrTmps = [];
        if ($userData) {
            foreach ($userData as $keys => $values) {
                $tmp[$keys] = explode("_", $values);
                $tmpes[] = $tmp[$keys][0];
            }
            $newarrTmps = array_values(array_unique($tmpes)); //用户id longstaytime
            if (in_array($user_id, $newarrTmps)) {
                $user_id_string = $user_id;
            } else {
                $newarrTmp = array_slice($newarrTmps, $offset, $limit);
                if (!empty($newarrTmp)) {
                    $user_id_string = implode(',', $newarrTmp);
                }
            }
            $user_info = MemberModel::getInstance()->getWhereAllData([["id", "in", $user_id_string]], "id,username,nickname");

            if ($user_info) {
                foreach ($user_info as $key => $value) {
                    //根据用户查询当前及房间查询当前数据结构
                    $list[$key]['room_id'] = $room_id;
                    $list[$key]['user_id'] = $value['id'];
                    $list[$key]['username'] = $value['username'];
                    $list[$key]['nickname'] = $value['nickname'];
                    //消费总金额
                    $sqlcoin = 'select uid,sum(coin) as totailcoins from zb_coindetail where uid =   ' . "'" . $value['id'] . "'" . ' and room_id =  ' . "'" . $room_id . "'" . ' and addtime >= ' . "'" . $starttime . "'" . ' and addtime <= ' . "'" . $endtime . "'";
                    $totailcoin = Db::query($sqlcoin);
                    $list[$key]['totailcoin'] = isset($totailcoin[0]['totailcoins']) ? $totailcoin[0]['totailcoins'] : 0;
                    //收礼总数
                    $sqlcome = 'select get_uid,sum(bean) as totailbeans from zb_beandetail where  get_uid =  ' . "'" . $value['id'] . "'" . ' and room_id =  ' . "'" . $room_id . "'" . ' and addtime >= ' . "'" . $starttime . "'" . ' and addtime <= ' . "'" . $endtime . "'";
                    $totailbean = Db::query($sqlcome);
                    $list[$key]['totailbean'] = isset($totailbean[0]['totailbeans']) ? $totailbean[0]['totailbeans'] : 0;
                    //在线时间长
                    $arrTmp = $this->roomUserTime($room_id, $redistime);
                    if (array_key_exists($value['id'], $arrTmp)) {
                        $logintime_string = $arrTmp[$value['id']];
                    }
                    $list[$key]['longstaytime'] = $logintime_string;
                    $list[$key]['riqi'] = $daytime;
                }
            }

        }
        if ($user_id) {
            $count = 1;
        } else {
            $count = count($newarrTmps);
        }
        $totalPage = ceil($count / $limit);
        Log::record('房间用户统计列表:操作人:' . $this->token['username'], 'roomdmember');
        $page_array['page'] = $master_page;
        $page_array['total_page'] = $totalPage;
        $admin_url = config('config.admin_url');
        View::assign('list', $list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        View::assign('token', $this->request->param('token'));
        View::assign('page', $page_array);
        View::assign('user_id', $user_id);
        View::assign('room_id', $room_id);
        View::assign('daytime', $daytime);
        View::assign('admin_url', $admin_url);
        return View::fetch('dataManagement/roomdmember');
    }

    /**渠道数据统计
     * @return mixed
     */
    public function channelindex()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $default_date = $this->end_time2 . ' - ' . $this->start_time;
        $demo = $this->request->param('demo', $default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $channel = $this->request->param('channel');
        $daochu = $this->request->param('daochu');
        $type = $this->request->param('type');
        $where = [];
        if ($channel) {
            $where[] = ['channel', '=', $channel];
        }
        if ($type) {
            $where[] = ['type', '=', 2];
        }
        $where[] = ['riq', '>=', strtotime($strtime)];
        $where[] = ['riq', '<', strtotime($endtime)];

        $query = BiChannelDataModel::getInstance()->getModel()->field('*')->where($where);
        if ($daochu == 1) {
            $list = $query->order('id asc')->select()->toArray();
        } else {
            $list = $query->limit($page, $pagenum)->order('riq desc')->select()->toArray();
        }
        $num = 0;
        if (!empty($list)) {
            //查询日期
            foreach ($list as $k => &$v) {
                $retention_list = $this->getRetention(1, $v['channel'], $v['id'], $v['riq']);
                $v['retention_2'] = $retention_list['day_1'];
                $v['retention_3'] = $retention_list['day_2'];
                $v['retention_7'] = $retention_list['day_6'];
                $v['retention_15'] = $retention_list['day_14'];
                $v['retention_30'] = $retention_list['day_29'];

                $v['riq'] = date('Y-m-d', $v['riq']);
                $v['scharge'] = $v['pay_retention_sum_3'];
                $v['qcharge'] = $v['pay_retention_sum_7'];
                $v['swcharge'] = $v['pay_retention_sum_15'];
                $v['sanshi'] = $v['pay_retention_sum_30'];
                $v['liushi'] = 0;
                $v['yibaier'] = 0;
                $reg_users = explode(',', $v['today_reg_mebs']);

                //累计充值人数
                $v['total_pay_count'] = 0;
                $v['total_pay_users'] = [];

                $users = Db::table('bi_days_user_charge')->where('date', '>=', $v['riq'])->where('uid', 'in', $reg_users)->column('uid');
                if (!empty($users)) {
                    $v['total_pay_count'] = count($users);
                    $v['total_pay_users'] = array_values($users);
                }
            }
            //查询总数
            $num = BiChannelDataModel::getInstance()->getBIDataByWhereCount($where);
        }

        //获取渠道列表
        $channel_lists = config('config.channelconf');
        $channel_list = [];
        foreach ($channel_lists as $ck => $cv) {
            $channel_list[$ck]['channel_name'] = $cv;
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('channel', $channel);
        View::assign('type', $type);
        View::assign('channel_list', $channel_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        if ($daochu == 1) {
            $this->channel_putcsv($list);
        }

        if ($daochu == 2) {
            $sql = "SELECT
                DATE_FORMAT( FROM_UNIXTIME( riq ), '%Y-%m-%d' ) AS date,
                sum( pay_retention_sum ) AS amount
            FROM
                bi_channel_data
            WHERE
                riq >= UNIX_TIMESTAMP( '{$strtime}' )
                AND riq < UNIX_TIMESTAMP( '{$endtime}' )
            GROUP BY
                date DESC";
            $list = Db::query($sql);
            $columns = ['date' => '日期', 'amount' => '充值总额'];
            ExportExcelService::getInstance()->export($list, $columns);
            $this->channel_putcsv($list);
        }
        return View::fetch('dataManagement/channelindex');
    }

    /**渠道数据统计区分source
     * @return mixed
     */
    public function channelSourceData()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $default_date = $this->end_time2 . ' - ' . $this->start_time;
        $demo = $this->request->param('demo', $default_date);
        list($strtime, $endtime) = getBetweenDate($demo);
        $channel = $this->request->param('channel');
        $daochu = $this->request->param('daochu');
        $type = $this->request->param('type');
        $exportRegister = $this->request->param('exportRegister', 0);
        $where = [];
        if ($channel) {
            $where[] = ['channel', '=', $channel];
        }

        if ($type) {
            $where[] = ['source', '=', $type];
        }

        $where[] = ['riq', '>=', strtotime($strtime)];
        $where[] = ['riq', '<', strtotime($endtime)];

        if ($exportRegister == 1) {
            $headerArray = [
                'uid' => '用户ID',
                'register_time' => '注册时间',
                'register_channel' => '注册时间',
                'source' => '包源',
                'addup_charge' => '累计充值',
            ];

            $date = $this->request->param('date', date('Y-m-d', strtotime("-1 days")));
            $source = $this->request->param('source', '');
            $register_channel = $this->request->param('register_channel', '');

            $where = [];

            if ($date) {
                $where[] = ["date", "=", $date];
                $where[] = ["register_time", ">=", $date . " 00:00:00"];
                $where[] = ["register_time", "<", date('Y-m-d', strtotime("+1days")) . " 00:00:00"];
            }

            if ($source) {
                $where[] = ["register_channel", "=", $register_channel];
            }

            if ($source) {
                $where[] = ["source", "=", $source];
            }

            $where[] = ["promote_channel", "=", 0];

            $res = Db::table('bi_user_stats_1day')
                ->where($where)
                ->select()
                ->toArray();

            $chargeColumn = [];

            if ($res) {
                $uids = [];
                $uids = array_column($res, "uid");
                $chargeRes = Db::table("bi_days_user_charge")->field("sum(amount)/10 as money,uid")
                    ->where("uid", "in", $uids)
                    ->group("uid")
                    ->select()
                    ->toArray();

                $chargeColumn = array_column($chargeRes, null, "uid");
            }

            foreach ($res as $key => $item) {
                $res[$key]['addup_charge'] = $chargeColumn[$item['uid']]["money"] ?? 0;
            }
            $this->exportcsv($res, $headerArray);
            exit;
        }

        $query = ChannelSourceDataModel::getInstance()->getModel()->field('*')->where($where);
        if ($daochu == 1) {
            $list = $query->order('id asc')->select();
        } else {
            $list = $query->limit($page, $pagenum)->order('riq desc')->select();
        }
        $num = 0;
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $k => &$v) {
                $retention_list = $this->getRetention(2, $v['channel'], $v['id'], $v['riq']);
                $v['riq'] = date('Y-m-d', $v['riq']);
                $v['scharge'] = $v['pay_retention_sum_3'];
                $v['qcharge'] = $v['pay_retention_sum_7'];
                $v['swcharge'] = $v['pay_retention_sum_15'];
                $v['sanshi'] = $v['pay_retention_sum_30'];
                $v['liushi'] = 0;
                $v['yibaier'] = 0;
                $v['retention_2'] = $retention_list['day_1'];
                $v['retention_3'] = $retention_list['day_2'];
                $v['retention_7'] = $retention_list['day_6'];
                $v['retention_15'] = $retention_list['day_14'];
                $v['retention_30'] = $retention_list['day_29'];

                $reg_users = explode(',', $v['today_reg_mebs']);
                //累计充值人数
                $v['total_pay_count'] = 0;
                $v['total_pay_users'] = [];

                $users = Db::table('bi_days_user_charge')->where('date', '>=', $v['riq'])->where('uid', 'in', $reg_users)->column('uid');
                if (!empty($users)) {
                    $v['total_pay_count'] = count($users);
                    $v['total_pay_users'] = array_values($users);
                }
            }
            //查询总数
            $num = ChannelSourceDataModel::getInstance()->getBIDataByWhereCount($where);
        }
        //获取渠道列表
        $channel_lists = config('config.channelconf');
        $channel_list = [];
        foreach ($channel_lists as $ck => $cv) {
            $channel_list[$ck]['channel_name'] = $cv;
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('channel', $channel);
        View::assign('type', $type);
        View::assign('app_types', array_column(config('config.APP_TYPE_MAP'), null, 'source'));
        View::assign('demo', $demo);
        View::assign('channel_list', $channel_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));

        if ($daochu == 1) {
            $outArray = [];
            foreach ($list as $key => $value) {
                $outArray['source'] = $value['source'];
                $outArray['channel'] = $value['channel'];
                $outArray['riq'] = $value['riq'];
                $outArray['rih'] = $value['rih'];
                $outArray['xinz'] = $value['xinz'];
                $outArray['cirlc'] = ($value['cirlc'] / 100) . "%";
                $outArray['sanrlc'] = ($value['sanrlc'] / 100) . "%";
                $outArray['qirlc'] = ($value['qirlc'] / 100) . "%";
                $outArray['swrlc'] = ($value['swrlc'] / 100) . "%";
                $outArray['ssrlc'] = ($value['ssrlc'] / 100) . "%";
                $outArray['nczzje'] = $value['nczzje'];
                $outArray['nczrs'] = $value['nczrs'];
                $outArray['nczl'] = ($value['nczl'] / 100) . "%";
                $outArray['pay_retention_sum'] = $value['pay_retention_sum'];
                $outArray['total_pay_count'] = $value['total_pay_count'];
                $outArray['czzje'] = $value['czzje'];
                $outArray['czrs'] = $value['czrs'];
                $outArray['czl'] = ($value['czl'] / 100) . "%";
                $outArray['arpu'] = $value['arpu'] / 100;
                $outArray['arppu'] = $value['arppu'] / 100;
                $outArray['scharge'] = $value['scharge'];
                $outArray['qcharge'] = $value['qcharge'];
                $outArray['swcharge'] = $value['swcharge'];
                $outArray['sanshi'] = $value['sanshi'];
                $outArray['liushi'] = $value['liushi'];
                $outArray['yibaier'] = $value['yibaier'];

                $outArray['retention_2'] = $value['retention_2'];
                $outArray['retention_3'] = $value['retention_3'];
                $outArray['retention_7'] = $value['retention_7'];
                $outArray['retention_15'] = $value['retention_15'];
                $outArray['retention_30'] = $value['retention_30'];
            }

            $columns = [
                'source' => '包源',
                'channel' => '渠道',
                'riq' => '日期',
                'rih' => '日活',
                'xinz' => '新增',
                'cirlc' => '次留1日',
                'sanrlc' => '次留3日',
                'qirlc' => '次留7日',
                'swrlc' => '次留15日',
                'ssrlc' => '次留30日',
                'nczzje' => '新增充值总金额',
                'nczrs' => '新增充值人数',
                'nczl' => '新增充值率',
                'pay_retention_sum' => "新增累计充值",
                'total_pay_count' => "新增累计充值人数",
                'czzje' => '充值总金额',
                'czrs' => '充值人数',
                'czl' => '充值率',
                'arpu' => 'ARPU',
                'arppu' => 'ARPPU',
                'scharge' => "三日付费",
                'qcharge' => "七日付费",
                'swcharge' => "十五日付费",
                'sanshi' => "三十日付费",
                'liushi' => "六十日付费",
                'yibaier' => "一百二日付费",
                'retention_2' => '次日留存率',
                'retention_3' => '三日留存率',
                'retention_7' => '七日留存率',
                'retention_15' => '十五日留存率',
                'retention_30' => '三十日留存率',
            ];
            ExportExcelService::getInstance()->export($list, $columns);
        }
        return View::fetch('dataManagement/channelSourceData');
    }

    /**渠道数据统计区分source
     * @return mixed
     */
    public function channelSourceHwData()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $start = date('Y-m-d', strtotime("-1 days"));
        $end = date('Y-m-d');
        $strtime = $this->request->param('strtime', $start);
        $endtime = $this->request->param('endtime', $end);
        $channel = $this->request->param('channel');
        $daochu = $this->request->param('daochu');
        $type = $this->request->param('type', '');
        $where = [];
        /* if ($channel) {
        $where[] = ['register_channel', '=', $channel];
        }*/
        $channel = 'HuaWei';

        if ($type) {
            $where[] = ['source', '=', $type];
        }

        $where[] = ['date', '>=', $strtime];
        $where[] = ['date', '<', $endtime];

        $query = Db::table("bi_hw_daily_day")->field('*')->where($where);
        if ($daochu == 1) {
            $list = $query->order('id asc')->select();
        } else {
            $list = $query->limit($page, $pagenum)->order('date desc')->select();
        }
        $num = 0;
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $list_k => $list_item) {

                //$list[$list_k]['charge_rate'] = $this->divedFunc($list_item['register_user_charge_num'],$list_item['register_people_num']);
                //充值率 当日新增付费人数/当日新增人数
                $list[$list_k]['register_rate'] = $this->divedFunc($list_item['register_user_charge_num'], $list_item['register_people_num']);
                //当日新增充值金额/当日新增
                $list[$list_k]['arpu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_people_num']);
                //当日新增充值金额/新增充值人数
                $list[$list_k]['arppu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_user_charge_num']);
            }
            //查询总数
            $num = Db::table("bi_hw_daily_day")->where($where)->count();
        }
        //获取渠道列表
        $channel_lists = config('config.channelconf');
        $channel_list = [];
        foreach ($channel_lists as $ck => $cv) {
            $channel_list[$ck]['channel_name'] = $cv;
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('channel', $channel);
        View::assign('type', $type);
        View::assign('app_types', array_column(config('config.APP_TYPE_MAP'), null, 'source'));
        View::assign('channel_list', $channel_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));

        $headerArray = [
            'source' => '包源',
            'date' => '日期',
            'register_channel' => '渠道',
            'hw_channel' => '华为渠道',
            'hw_taskid' => '华为任务id',
            'daily_life' => '日活',
            'register_people_num' => '新增人数',
            'charge_money_sum' => '充值总金额',
            'charge_people_sum' => '充值总人数',
            'register_user_charge_amount' => '新增充值额',
            'register_user_charge_num' => '新增充值人数',
            'register_rate' => '新增充值率',
            'pay_amount_up_now' => '累计充值',
            'arpu' => 'ARPU',
            'arppu' => 'ARPPU',
            'fee_register_7' => "七日付费",
            'fee_register_30' => "三十日付费",
            'fee_register_60' => "六十日付费",
            'fee_register_90' => "九十日付费",
            'keep_login_1' => "一日留存",
            'keep_login_7' => "七日留存",
            'keep_login_15' => "十五日留存",
            'keep_login_30' => '三十日留存',
        ];

        if ($daochu == 1) {
            $this->exportcsv($list, $headerArray);
        }
        return View::fetch('dataManagement/channelSourceHwData');
    }

    /**渠道数据统计区分source
     * @return mixed
     */
    public function channelSourceAppstoreData()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $start = date('Y-m-d', strtotime("-1 days"));
        $end = date('Y-m-d');
        $strtime = $this->request->param('strtime', $start);
        $endtime = $this->request->param('endtime', $end);
        $channel = $this->request->param('channel');
        $daochu = $this->request->param('daochu');
        $type = $this->request->param('type', '');
        $where = [];
        /* if ($channel) {
        $where[] = ['register_channel', '=', $channel];
        }*/
        $channel = 'appStore';

        if ($type) {
            $where[] = ['source', '=', $type];
        }

        $where[] = ['date', '>=', $strtime];
        $where[] = ['date', '<', $endtime];

        $query = Db::table("bi_appstore_daily_day")->field('*')->where($where);
        if ($daochu == 1) {
            $list = $query->order('id asc')->select();
        } else {
            $list = $query->limit($page, $pagenum)->order('date desc')->select();
        }
        $num = 0;
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $list_k => $list_item) {

                //$list[$list_k]['charge_rate'] = $this->divedFunc($list_item['register_user_charge_num'],$list_item['register_people_num']);
                //充值率 当日新增付费人数/当日新增人数
                $list[$list_k]['register_rate'] = $this->divedFunc($list_item['register_user_charge_num'], $list_item['register_people_num']);
                //当日新增充值金额/当日新增
                $list[$list_k]['arpu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_people_num']);
                //当日新增充值金额/新增充值人数
                $list[$list_k]['arppu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_user_charge_num']);
            }
            //查询总数
            $num = Db::table("bi_appstore_daily_day")->where($where)->count();
        }
        //获取渠道列表
        $channel_lists = config('config.channelconf');
        $channel_list = [];
        foreach ($channel_lists as $ck => $cv) {
            $channel_list[$ck]['channel_name'] = $cv;
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('channel', $channel);
        View::assign('type', $type);
        View::assign('app_types', array_column(config('config.APP_TYPE_MAP'), null, 'source'));
        View::assign('channel_list', $channel_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));

        $headerArray = [
            'source' => '包源',
            'date' => '日期',
            'register_channel' => '渠道',
            'iad_adgroup_id' => '广告组id',
            'iad_adgroup_name' => '广告组名称',
            'iad_campaign_id' => '广告系列id',
            'iad_campaign_name' => '广告系列名称',
            'iad_keyword_id' => '关键词id',
            'iad_keyword' => '关键词名称',
            'daily_life' => '日活',
            'register_people_num' => '新增人数',
            'charge_money_sum' => '充值总金额',
            'charge_people_sum' => '充值总人数',
            'register_user_charge_amount' => '新增充值额',
            'register_user_charge_num' => '新增充值人数',
            'register_rate' => '新增充值率',
            'pay_amount_up_now' => '累计充值',
            'arpu' => 'ARPU',
            'arppu' => 'ARPPU',
            'fee_register_7' => "七日付费",
            'fee_register_30' => "三十日付费",
            'fee_register_60' => "六十日付费",
            'fee_register_90' => "九十日付费",
            'keep_login_1' => "一日留存",
            'keep_login_7' => "七日留存",
            'keep_login_15' => "十五日留存",
            'keep_login_30' => '三十日留存',
        ];

        if ($daochu == 1) {
            $this->exportcsv($list, $headerArray);
        }
        return View::fetch('dataManagement/channelSourceAppstoreData');
    }

    /**
     * @param $data
     * @渠道详情
     * @dongbozhao
     * @2021-02-03
     */
    public function channelDetails()
    {
        echo $this->return_json(5000, null, '请联系运营');
        die;
        $pagenum = 20;
        $id = Request::param('channel_id'); //渠道id
        $channel = Request::param('channel'); //渠道
        $demo = Request::param('demo', $this->default_date); //结束时间
        list($strtime, $endtime) = getBetweenDate($demo); //结束时间
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $data = BiChannelDataModel::getInstance()->getModel()->where('id', $id)->field('rih,xinz,nczzje,nczrs,nczl,czzje,czrs,czl')->find()->toArray();
        $where[] = ['register_time', '>=', $strtime . ' 00:00:00'];
        $where[] = ['register_time', '<=', $endtime . ' 23:59:59'];
        $where[] = ['register_channel', '=', $channel];
//        $where[] = ['invitcode','=',''];
        $num = MemberModel::getInstance()->getWhereCount($where);
        $uidList = MemberModel::getInstance()->getWhereAllData($where, "id");
        $uid = array_column($uidList, "id");
        //$sumRmb = ChargedetailModel::getInstance()->getModel()->whereIn('uid', $uid)->where([['status', 'in', [1, 2]]])->value('sum(rmb)');
        $sumRmb = BiDaysUserChargeModel::getInstance()->getModel()->whereIn('uid', $uid)->value('sum(amount)/10');
        $list = MemberModel::getInstance()->getDataByWherePage($where, $master_page, $pagenum, "id,nickname,sex,avatar,username,totalcoin,freecoin,diamond,exchange_diamond,free_diamond");

        $uids = array_column($list, "id");
        $userchargeList = BiDaysUserChargeModel::getInstance()->getModel()->where("uid", "in", $uids)
            ->field("uid,sum(amount)/10 as rmb")
            ->group("uid")->select()->toArray();

        $userchargeInfo = array_column($userchargeList, null, "uid");

        $url = config('config.APP_URL_image');
        foreach ($list as $k => $v) {
            $list[$k]['avatar'] = $url . $v['avatar'];
            if ($v['sex'] == 1) {
                $list[$k]['sex'] = '男';
            } elseif ($v['sex'] == 2) {
                $list[$k]['sex'] = '女';
            } else {
                $list[$k]['sex'] = '保密';
            }
            $list[$k]['zuan'] = intval(($v['diamond'] - ($v['exchange_diamond'] + $v['free_diamond'])) * 0.0001);
            $list[$k]['dou'] = intval($v['totalcoin'] - $v['freecoin']);
            $list[$k]['chongzhi'] = $userchargeInfo[$v['id']]['rmb'] ?? 0;
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('sumRmb', $sumRmb);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('channel', $channel);
        View::assign('data', $data);
        View::assign('channel_id', $id);
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('num', $num);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));
        return View::fetch('dataManagement/channelDetails');
    }

    // data是数据  headerarray是表头
    public function exportcsv($data, $headerArray)
    {
        $string = implode(",", array_values($headerArray)) . "\n";
        $table_key = array_keys($headerArray);
        foreach ($data as $key => $value) {
            $outArray = [];
            foreach ($table_key as $key) {
                if (array_key_exists($key, $value)) {
                    $outArray[$key] = $value[$key];
                }
            }

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

    // 渠道导出csv
    public function channel_putcsv($data)
    {
        $headerArray = [
            '渠道',
            '日期',
            '日活',
            '新增',
            '次留1日',
            '次留3日',
            '次留7日',
            '次留15日',
            '次留30日',
            '新增充值总金额',
            '新增充值人数',
            '新增充值率',
            '充值总金额',
            '充值人数',
            '充值率',
            'ARPU',
            'ARPPU',
            "三日付费",
            "七日付费",
            "十五日付费",
            "三十日付费",
            "六十日付费",
            "一百二日付费",
            "新增累计充值金额",
            "新增累计充值人数",
            '次日留存率',
            '三日留存率',
            '七日留存率',
            '十五日留存率',
            '三十日留存率'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['channel'] = $value['channel'];
            $outArray['riq'] = $value['riq'];
            $outArray['rih'] = $value['rih'];
            $outArray['xinz'] = $value['xinz'];
            $outArray['cirlc'] = ($value['cirlc'] / 100) . "%";
            $outArray['sanrlc'] = ($value['sanrlc'] / 100) . "%";
            $outArray['qirlc'] = ($value['qirlc'] / 100) . "%";
            $outArray['swrlc'] = ($value['swrlc'] / 100) . "%";
            $outArray['ssrlc'] = ($value['ssrlc'] / 100) . "%";
            $outArray['nczzje'] = $value['nczzje'];
            $outArray['nczrs'] = $value['nczrs'];
            $outArray['nczl'] = ($value['nczl'] / 100) . "%";
            $outArray['czzje'] = $value['czzje'];
            $outArray['czrs'] = $value['czrs'];
            $outArray['czl'] = ($value['czl'] / 100) . "%";
            $outArray['arpu'] = $value['arpu'] / 100;
            $outArray['arppu'] = $value['arppu'] / 100;
            $outArray['scharge'] = $value['scharge'];
            $outArray['qcharge'] = $value['qcharge'];
            $outArray['swcharge'] = $value['swcharge'];
            $outArray['sanshi'] = $value['sanshi'];
            $outArray['liushi'] = $value['liushi'];
            $outArray['yibaier'] = $value['yibaier'];
            $outArray['pay_retention_sum'] = $value['pay_retention_sum'];
            $outArray['total_pay_count'] = $value['total_pay_count'];
            $outArray['retention_2'] = $value['retention_2'];
            $outArray['retention_3'] = $value['retention_3'];
            $outArray['retention_7'] = $value['retention_7'];
            $outArray['retention_15'] = $value['retention_15'];
            $outArray['retention_30'] = $value['retention_30'];
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
     * 房间在线时间
     */
    public function roomUserTime($roomId, $date)
    {
        $tmp = [];
        $res = [];
        $today = strtotime(date("Ymd"), time());
        //$yesterday = strtotime('yesterday');
        //$yestdate = date("Ymd",strtotime("-1 day"));
        $yesterday = strtotime($date);
        $yestdate = $date;
        $arr = [
            "select" => 4,
        ];
        $redis = RedisCommon::getInstance()->getRedis($arr);
        $redisKey = 'RoomEnterOrExit_' . $roomId . '_' . $date;
        $data = $redis->LRANGE($redisKey, 0, -1);
        if (empty($data)) {
            return [];
        }
        foreach ($data as $key => $value) {
            $arr = explode('_', $value);
            $tmp[$arr[0]][] = ['type' => $arr[1], 'time' => strtotime($yestdate . $arr[2])];
        }

        foreach ($tmp as $key => $value) {
            $count = count($value);
            for ($i = 0; $i < $count; $i++) {
                if ($i == 0 && $value[$i]['type'] == 1) {
                    array_unshift($tmp[$key], ['type' => 0, 'time' => $yesterday]);
                }
                if ($i == ($count - 1) && $value[$i]['type'] == 0) {
                    array_push($tmp[$key], ['type' => 1, 'time' => strtotime(date("Y-m-d"))]);
                }
            }
        }

        $ret = [];
        foreach ($tmp as $key => $value) {
            $count = count($value);
            $m = 0;
            for ($i = 0; $i < $count; $i++) {
                if ($value[$i]['type'] == $m) {
                    $ret[$key][] = $value[$i];
                    if ($m == 1) {
                        $m = 0;
                    } else {
                        $m = 1;
                    }
                }
            }
        }

        foreach ($ret as $key => $value) {
            $count = count($value);
            for ($i = 0; $i < $count; $i++) {
                if ($value[$i]['type'] == 1) {
                    @$res[$key] += $value[$i]['time'] - $value[$i - 1]['time'];
                }
            }
        }
        return $res;
    }

    /**
     * 数据详情
     */
    public function retention()
    {
        $riq = strtotime(Request::param('riq'));
        //根据Id查询数据的详情
        $where[] = ["riq", '=', $riq];
        $field = 'id,cirlc,sanrlc,qirlc,riq';
        $listes = [];
        $list = BIDataModel::getInstance()->getModel()->field($field)->where($where)->select();
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $key => $value) {
                $listes['cirlc'] = ($value['cirlc'] / 100) . "%";
                $listes['sanrlc'] = ($value['sanrlc'] / 100) . "%";
                $listes['qirlc'] = ($value['qirlc'] / 100) . "%";
            }
        }
        $listes['id'] = BIDataModel::getInstance()->getModel()->where(array("riq" => $riq))->value('id');
        Log::record('数据详情列表:操作人:' . $this->token['username'], 'retention');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $listes, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die;
    }

    /**
     * 渠道数据详情
     */
    public function channelRetention()
    {
        $riq = strtotime(Request::param('riq'));
        $channel = Request::param('channel');
        $type = Request::param('type', 1);
        $id = Request::param('id');

        $list = $this->getRetention($type, $channel, $id, $riq);
        $list['id'] = $id;

        Log::record('数据详情列表:操作人:' . $this->token['username'], 'channelRetention');
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $list, $this->code_ok_map[\constant\CodeConstant::CODE_成功]);
        die();
    }

    public function getRetention($type, $channel, $id, $riq)
    {
        $where[] = ["channel", '=', $channel];
        $where[] = ["id", '=', $id];
        if ($type == 1) {
            $model = BiChannelDataModel::class;
        } else {
            $model = ChannelSourceDataModel::class;
        }
        $users = $model::getInstance()->getModel()->where($where)->value('today_reg_mebs');
        $list = RetentionService::getInstance()->getRetention($users, date("Y-m-d", $riq));

        return $list;
    }

    // 渠道导出csv
    public function user_putcsv($data)
    {
        //过滤特殊字符
        $regex = "/\/|\～|\，|\。|\！|\？|\"|\”|\【|\】|\『|\』|\：|\；|\《|\》|\’|\‘|\ |\·|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";

        $headerArray = ['用户id', '用户昵称', '手机号', '充值金额', '身份'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            if ($value['nickname'] == "\"") {
                $value['nickname'] = '没有昵称的用户';
            }
            $outArray['user_id'] = $value['user_id'];
            $outArray['nickname'] = preg_replace($regex, "", $value['nickname']);
            $outArray['username'] = $value['username'];
            $outArray['rmb'] = $value['rmb'];
            $outArray['guild_id'] = $value['guild_id'];
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

    /**
     * 充值人數userPay
     */
    public function userPay()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $demo = Request::param('demo', $this->default_date);
        list($start_time, $end_time) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');
        $where = [];
        $where[] = ['addtime', '>=', $start_time];
        $where[] = ['addtime', '<', $end_time];
        $where[] = ['status', 'in', [1, 2]];
        $where[] = ['type', '=', 1];
        //统计
        $count = ChargedetailModel::getInstance()->getModel()->where($where)->group('uid')->count();

        //获取数据
        if ($daochu == 1) {
            $data = ChargedetailModel::getInstance()->getModel()->where($where)->field('uid,sum(rmb) rmb,sum(coin) coin')->group('uid')->select()->toArray();
        } else {
            $data = ChargedetailModel::getInstance()->getModel()->where($where)->field('uid,sum(rmb) rmb,sum(coin) coin')->group('uid')->limit($page, $pagenum)->select()->toArray();
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('billDetail列表获取成功:操作人:' . $this->token['username'], 'billDetail');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('count', $count);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('demo', $demo);
        View::assign('funtion', 'userPay');
        if ($daochu == 1) {
            $this->_biDetailcsv($data);
        }
        return View::fetch('dataManagement/userpay');

    }

    /**
     * 充值人數userPay
     */
    public function userPayBeancredit()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $demo = Request::param('demo', $this->default_date);
        list($start_time, $end_time) = getBetweenDate($demo);
        $daochu = $this->request->param('daochu');
        $where = [];
        $where[] = ['createtime', '>=', strtotime($start_time)];
        $where[] = ['createtime', '<', strtotime($end_time)];
        //统计
        $count = BeancreditModel::getInstance()->getModel()->where($where)->group('touid')->count();
        //获取数据
        if ($daochu == 1) {
            $data = BeancreditModel::getInstance()->getModel()->where($where)->field('touid uid,sum(beannum) as rmb,sum(beannum) as coin')->group('touid')->select()->toArray();
        } else {
            $data = BeancreditModel::getInstance()->getModel()->where($where)->field('touid uid,sum(beannum) as rmb,sum(beannum) as coin')->group('touid')->limit($page, $pagenum)->select()->toArray();
        }
        foreach ($data as $k => $v) {
            $data[$k]['rmb'] = $v['rmb'] * 0.0001;
            $data[$k]['coin'] = $v['coin'] * 0.001;
        }

        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pagenum);
        Log::record('billDetail列表获取成功:操作人:' . $this->token['username'], 'billDetail');
        View::assign('page', $page_array);
        View::assign('data', $data);
        View::assign('count', $count);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('funtion', 'userPayBeancredit');
        View::assign('demo', $demo);
        if ($daochu == 1) {
            $this->_biDetailcsv($data);
        }
        return View::fetch('dataManagement/userpay');

    }

    /**
     *
     *导出
     */
    public function _BoxErDetailcsv($data)
    {
        $headerArray = ['统计日期', '用户ID', '产出', '消耗', '爆率', '类型'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['time'] = $value['time']; //统计日期
            $outArray['uid'] = $value['uid']; //用户ID
            $outArray['output'] = $value['output']; //产出
            $outArray['consumption'] = $value['consumption']; //消耗
            $outArray['Er'] = $value['Er']; //爆率
            $outArray['type'] = $value['type']; //类型
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

    public function sortArr($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
    {
        $key_arrays = array();
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        return $arrays;
    }

    /**
     * @return mixed
     * 幸运盒子爆率
     */
    public function luckyBox()
    {
        $pageNew = 10;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pageNew;

        $demo = $this->request->param('demo', $this->default_date);
        list($start, $end) = getBetweenDate($demo);
        $uid = $this->request->param('uid');
        $daochu = $this->request->param('daochu');

        $time1 = strtotime($start);
        $time2 = strtotime($end);

        $where[] = ['success_time', '>=', $time1];
        $where[] = ['success_time', '<', $time2];

        if (!empty($uid)) {
            $where[] = ['uid', '=', $uid];
        }

        $where[] = ['event_id', '=', 10002];
        $where[] = ['ext_1', '<>', 'ext_2'];
        $where[] = ['type', '=', 4];

        $coin = Db::query("SELECT sum(consume_amount) as coin, sum(reward_amount) as gift_coin FROM (SELECT *
        FROM
            bi_days_user_gift_datas_bysend_type
        WHERE
            type = 2 and send_type in (1,3)
            AND date >= '{$start}'
            AND date < '{$end}' having (consume_amount/`count`) in (100,200,300) ) B");

        //获取数据
        // $coin = BiDayUserGiftDatasBysendTypeModel::getInstance()
        //     ->getModel()
        //     ->field('consume_amount,count,sum(consume_amount) as coin, sum(reward_amount) as gift_coin')
        //     ->where(
        //         [
        //             // ['consume_amount', '<>', 'reward_amount'],
        //             ['date', '>=', $start],
        //             ['date', '<', $end],
        //             ['type', '=', 2],
        //             ['send_type', 'in', [1, 3]],
        //         ]
        //     )
        //     ->having('(consume_amount/count) in (100,200,300)')
        //     ->fetchSql(true)->select();

        $page_array = [];
        View::assign('page', $page_array);
        if ($coin[0]['coin'] == false) {
            View::assign('Ttr', 0);
        } else {
            View::assign('Ttr', round($coin[0]['gift_coin'] / $coin[0]['coin'], 4));
        }

        $countUid = Db::query("SELECT uid,sum(consume_amount) as coin, sum(reward_amount) as gift_coin FROM (SELECT *
        FROM
            bi_days_user_gift_datas_bysend_type
        WHERE
            type = 2 and send_type in (1,3)
            AND date >= '{$start}'
            AND date < '{$end}' having (consume_amount/`count`) in (100,200,300) ) B group by uid");

        $count = count($countUid);
        if ($daochu == 1) {
            $id = array_column($countUid, 'uid');
        } else {
            if ($uid) {
                $id = [$uid];
            } else {
                $id = array_slice(array_column($countUid, 'uid'), $page, $pageNew);
            }
        }

        $data = [];
        if ($count > 0) {
            $uidStr = implode(',', $id);

            $coinData1 = Db::query("SELECT
            uid,
            sum( consume_amount ) AS coin,
            sum( reward_amount ) AS gift_coin
            FROM
                (
                SELECT
                    *
                FROM
                    bi_days_user_gift_datas_bysend_type
                WHERE
                    type = 2
                    AND send_type IN ( 1, 3 )
                    AND date >= '{$start}'
                    AND date < '{$end}'
                    AND uid IN ($uidStr)
                HAVING
                    ( consume_amount / `count` ) IN ( 100, 200, 300 )
                ) B
            GROUP BY
                uid");

            foreach ($coinData1 as $k => $v) {
                $data[$k]['uid'] = $v['uid'];
                $data[$k]['consumption'] = $v['coin'];
                $data[$k]['output'] = $v['gift_coin'];
                $data[$k]['Er'] = round($v['gift_coin'] / $v['coin'], 2);
                $data[$k]['time'] = $time1 . '-' . $time2;
                $data[$k]['type'] = '幸运盒子';
            }
        }

        $data = $this->sortArr($data, 'uid');
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($count / $pageNew);
        if ($daochu == 1) {
            $this->_BoxErDetailcsv($data);
        }

        View::assign('page', $page_array);
        View::assign('giftInt', $coin[0]['gift_coin']);
        View::assign('coin', $coin[0]['coin']);
        View::assign('demo', $demo);
        View::assign('data', $data);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('token', $this->request->param('token'));
        View::assign('Boxtype', '这是幸运盒子爆率');
        View::assign('uid', $uid);
        return View::fetch('dataManagement/luckyBox');
    }

    /**
     * @param $data
     * 充值人数数据导出
     */
    private function _biDetailcsv($data)
    {
        $headerArray = ['用户ID', '人民币', '豆'];
        $string = implode(",", $headerArray) . "\n";
        foreach ($data as $key => $value) {
            $outArray['uid'] = $value['uid']; //后充总豆
            $outArray['rmb'] = $value['rmb']; //公充总豆
            $outArray['coin'] = $value['coin']; //送礼收钻石
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

    //相除
    private function divedFunc($param1, $param2, $decimal = 2)
    {
        $param2 = $param2 == false ? 1 : floatval($param2);
        return round($param1 / $param2, $decimal);
    }

    /**渠道数据统计区分source
     * @return mixed
     */
    public function channelSourceIosData()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param("limit") ?? 30;
        $begintime = $this->request->param('begintime', date('Y-m-d', strtotime("-1 days")));
        $endtime = $this->request->param('endtime', date('Y-m-d'));
        $daochu = $this->request->param('daochu', 0);
        $type = $this->request->param('source', 2);

        $where = [];
        if ($type == 1) {
            $where[] = ['source', '=', 'mua'];
        } elseif ($type == 2) {
            $where[] = ['source', 'in', ['yinlian', 'fanqie']];
        }
        $where[] = ['date', '>=', $begintime];
        $where[] = ['date', '<', $endtime];

        /*
        新增用户 （时间段内 新注册用户量）
        累计新增充值金额 (时间段注册并首次充值的累计)
        累计新增充值用户（时间段注册并且充值的用户 排重）
        累计充值金额（时间段内注册的所有用户 到当前充值总金额)
         */
        $registerList = Db::table("bi_appstore_daily_day")
            ->where($where)->field('today_register_user')->select()->toArray();
        $registerListRes = [];
        foreach ($registerList as $regItem) {
            if ($regItem && $regItem['today_register_user']) {
                $registerListRes = array_merge(explode(",", $regItem['today_register_user']), $registerListRes);
            }
        }
        //时间段内的注册总人数
        $collectRes['register_user_total'] = count($registerListRes);
        //累计新增充值金额 (时间段注册并首次充值的累计)
        $collectRes['regiser_user_add_money'] = 0;
        //累计新增充值用户
        $collectRes['regiser_user_charge'] = 0;
        //累计充值金额
        $collectRes['regiser_user_money'] = 0;
        $charge_register_add = [];
        $chargeres = [];

        if ($registerListRes) {
            $chargeres = Db::table("bi_days_user_charge")
                ->field("uid,date,sum(amount)/10 as money")
                ->where("date", ">=", $begintime)
                ->where("date", "<", $endtime)
                ->where("uid", "in", $registerListRes)
                ->group("uid,date")
                ->select()->toArray();
            foreach ($chargeres as $item) {
                $uniq = $item['uid'] . "-" . $item['date'];
                if (!isset($charge_register_add[$uniq])) {
                    $charge_register_add[$uniq] = $item['money'];
                }
            }
        }

        $collectRes['regiser_user_add_money'] = round(array_sum($charge_register_add), 2);
        $collectRes['regiser_user_charge'] = count(array_unique(array_column($chargeres, "uid")));

        $chargetotleres = Db::table("bi_days_user_charge")
            ->field("sum(amount)/10 as money")
            ->where("date", ">=", $begintime)
            ->where("uid", "in", $registerListRes)
            ->find();
        $collectRes['regiser_user_money'] = round($chargetotleres['money'], 2);

        if ($daochu == 1) {
            $headerArray = [
                'source' => '包源',
                'date' => '日期',
                'register_channel' => '渠道',
                'iad_adgroup_id' => '广告组id',
                'iad_adgroup_name' => '广告组名称',
                'iad_campaign_id' => '广告系列id',
                'iad_campaign_name' => '广告系列名称',
                'iad_keyword_id' => '关键词id',
                'iad_keyword' => '关键词名称',
                'daily_life' => '日活',
                'register_people_num' => '新增人数',
                'charge_money_sum' => '充值总金额',
                'charge_people_sum' => '充值总人数',
                'register_user_charge_amount' => '新增充值额',
                'register_user_charge_num' => '新增充值人数',
                'register_rate' => '新增充值率',
                'pay_amount_up_now' => '累计充值',
                'arpu' => 'ARPU',
                'arppu' => 'ARPPU',
                'fee_register_7' => "七日付费",
                'fee_register_30' => "三十日付费",
                'fee_register_60' => "六十日付费",
                'fee_register_90' => "九十日付费",
                'keep_login_1' => "一日留存",
                'keep_login_7' => "七日留存",
                'keep_login_15' => "十五日留存",
                'keep_login_30' => '三十日留存',
            ];
            $exportRes = Db::table("bi_appstore_daily_day")->where($where)->select()->toArray();
            foreach ($exportRes as $list_k => $list_item) {
                //$list[$list_k]['charge_rate'] = $this->divedFunc($list_item['register_user_charge_num'],$list_item['register_people_num']);
                //充值率 当日新增付费人数/当日新增人数
                $exportRes[$list_k]['register_rate'] = $this->divedFunc($list_item['register_user_charge_num'], $list_item['register_people_num']);
                //当日新增充值金额/当日新增
                $exportRes[$list_k]['arpu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_people_num']);
                //当日新增充值金额/新增充值人数
                $exportRes[$list_k]['arppu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_user_charge_num']);
            }

            $this->exportcsv($exportRes, $headerArray);
        }

        if ($this->request->param("isRequest") == 1) {
            $list = Db::table("bi_appstore_daily_day")->where($where)->page($page, $limit)->select()->toArray();
            foreach ($list as $list_k => $list_item) {
                //$list[$list_k]['charge_rate'] = $this->divedFunc($list_item['register_user_charge_num'],$list_item['register_people_num']);
                //充值率 当日新增付费人数/当日新增人数
                $list[$list_k]['register_rate'] = $this->divedFunc($list_item['register_user_charge_num'], $list_item['register_people_num']);
                //当日新增充值金额/当日新增
                $list[$list_k]['arpu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_people_num']);
                //当日新增充值金额/新增充值人数
                $list[$list_k]['arppu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_user_charge_num']);
            }
            //查询总数
            $count = Db::table("bi_appstore_daily_day")->where($where)->count();
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $list, "hz" => $collectRes];
            echo json_encode($data);
        } else {
            View::assign('collectRes', $collectRes);
            View::assign('token', $this->request->param('token'));
            View::assign('begintime', $begintime);
            View::assign('endtime', $endtime);
            View::assign('type', $type);
            View::assign('user_role_menu', $this->user_role_menu);
            View::assign('user_role_menu_input', implode(',', $this->user_role_menu));

            return View::fetch('dataManagement/csios');
        }

    }

    /** 用户在麦时长
     * @return mixed
     */
    public function userOnlineMic()
    {
        //$page = $this->request->param('page', 1);
        //$limit = $this->request->param("limit") ?? 30;
        $begintime = $this->request->param('begintime', date('Y-m-d', strtotime("-1 days")));
        $endtime = $this->request->param('endtime', date('Y-m-d'));
        $user_id = $this->request->param('uid', 0);
        $guild_id = $this->request->param('guild_id', -1);
        $room_id = $this->request->param('room_id', 0);
        $s_ok = $this->request->param('s_ok', 0); //是否有效
        $maxsecondLimit = 3600 * 3;

        $where = [];
        $where[] = ['date', '>=', $begintime];
        $where[] = ['date', '<', $endtime];
        if ($user_id > 0) {
            $where[] = ['uid', '=', $user_id];
        }

        $where[] = ['micid', '=', 999];

        if ($guild_id > 0) {
            $where[] = ['guild_id', '=', $guild_id];
        }

        if ($room_id > 0) {
            $where[] = ['room_id', '=', $room_id];
        }

        // $room_users = Db::name("zb_member_guild")->column('user_id');
        // $where[] = ['uid', 'in', array_values($room_users)];

        $where[] = ["is_owner", "=", 1];

        /*
         *
         *
         *  select uid,room_id,date,sum(durations) as durations_sum,guild_id,is_owner,sum(counts) as rate from (
        select uid,room_id,date,sum(duration) as durations,count(1) as counts,guild_id,is_owner   from   bi_user_online_mic  where  date >= '2021-12-21'  and '2021-12-22' and is_owner = 1 and micid = 999   GROUP BY uid,room_id,date
        having durations > 3000) t GROUP BY uid,room_id
         *
         *
         *
         * */

        if ($this->request->param("isRequest") == 1) {
            $buildSql = Db::table("bi_user_online_mic")
                ->field("uid,micid,room_id,date,sum(duration) as durations,guild_id,is_owner,(case when sum(duration) > {$maxsecondLimit} then 1 else 0 end) rate")
                ->where($where)
                ->group("uid,room_id,date")
                ->buildSql();

            $res = Db::table($buildSql . "T")
                ->field("uid,room_id,date,sum(durations) as durations_sum,guild_id,is_owner,sum(rate) as rate,micid")
                ->group("uid,room_id")
            //->fetchSql(true)
                ->select()->toArray();

            //$guilds = array_column($res, "guild_id");
            $roomids = array_column($res, "room_id");
            $uids = array_column($res, "uid");

            $roomInfo = LanguageroomModel::getInstance()->getWhereAllData([["id", "in", $roomids]], 'id,room_name');
            //$guildInfo = MemberGuildModel::getInstance()->getWhereAllData([["id", "in", $guilds]], 'id,nickname');
            $memberInfo = MemberModel::getInstance()->getWhereAllData([["id", "in", $uids]], 'id,nickname');

            $roomNameList = array_column($roomInfo, null, "id");
            //$guildNameList = array_column($guildInfo, null, "id");
            $memberNameList = array_column($memberInfo, null, "id");
            $guildNameList = MemberGuildService::getInstance()->getUserGuildByUid($uids);

            $send_gift_data = [];
            if (!empty($uids) && !empty($roomids)) {
                $send_gift_data = Db::name("bi_days_user_gift_datas")
                    ->field("CONCAT_WS('_',uid,room_id) uid_roomid_key,sum(amount) amount")
                    ->where('date', '>=', $begintime)
                    ->where('date', '<', $endtime)
                    ->where('type', 2)
                    ->where('uid', 'in', $uids)
                    ->where('room_id', 'in', $roomids)
                    ->group("CONCAT_WS('_',uid,room_id)")
                // ->fetchSql(true)
                    ->select()
                    ->toArray();
                $send_gift_data = array_column($send_gift_data, 'amount', 'uid_roomid_key');
            }

            $returnData = [];
            foreach ($res as $key => $item) {
                $returnData[$key] = $item;
                $returnData[$key]['nickname'] = $memberNameList[$item['uid']]['nickname'] ?? '';
                $returnData[$key]['room_name'] = $roomNameList[$item['room_id']]['room_name'] ?? '';
                //$returnData[$key]['guild_name'] = $guildNameList[$item['guild_id']]['nickname'] ?? '';
                $returnData[$key]['guild_name'] = $guildNameList[$item['uid']]['g_nickname'] ?? '';
                $returnData[$key]['is_mic'] = ($item['micid'] == 999) ? '是' : '否';
                $returnData[$key]['is_master'] = ($item['is_owner'] == 1) ? '是' : '否';
                $returnData[$key]['durations_sum'] = round($item['durations_sum'] / 3600, 2);
                $user_roomid_key = $item['uid'] . '_' . $item['room_id'];
                $returnData[$key]['receive_gift_count'] = arrayIntVal($send_gift_data, $user_roomid_key);

                if ($item['is_owner'] == 1 && $item['rate'] >= 1) {
                    $returnData[$key]['status'] = $item['rate'];
                } else {
                    $returnData[$key]['status'] = 0;
                }
            }

            if ($s_ok > 0) {
                if ($s_ok == 1) {
                    $data = array_filter($returnData, function ($v) {
                        if ($v['status'] == 1) {
                            return true;
                        } else {
                            return false;
                        }

                    });
                } elseif ($s_ok == 2) {
                    $data = array_filter($returnData, function ($v) {
                        if ($v['status'] == 0) {
                            return true;
                        } else {
                            return false;
                        }

                    });
                }

                $returnData = $data;
            }

            //查询总数
            $count = count($returnData);
            $data = ["msg" => '', "count" => $count, "code" => 0, "data" => $returnData];
            echo json_encode($data);
        } else {
            View::assign('token', $this->request->param('token'));
            View::assign('begintime', $begintime);
            View::assign('endtime', $endtime);
            return View::fetch('dataManagement/onlinemic');
        }

    }

    public function leave($strtime = '', $endtime = '', $channel = '')
    {

        $where1[] = ['register_time', '>=', date('Y-m-d', strtotime("-1 day", strtotime($strtime))) . ' 00:00:00'];
        $where1[] = ['register_time', '<=', date('Y-m-d', strtotime("-1 day", strtotime($endtime))) . ' 23:59:59'];
        $where2[] = ['ctime', '>=', strtotime($strtime)];
        $where2[] = ['ctime', '<=', strtotime($endtime)];
        $where3[] = ['ctime', '>=', strtotime(date('Y-m-d', strtotime("-1 day", strtotime($strtime))) . ' 00:00:00')];
        $where3[] = ['ctime', '<=', strtotime(date('Y-m-d', strtotime("-1 day", strtotime($endtime))) . ' 23:59:59')];
        $where4[] = ['addtime', '>=', date('Y-m-d', strtotime("-1 day", strtotime($strtime))) . ' 00:00:00'];
        $where4[] = ['addtime', '<=', date('Y-m-d', strtotime("-1 day", strtotime($endtime))) . ' 23:59:59'];

        if (empty($channel)) {
            $uidtime = $this->useridtime($where1); //查询昨日注册用户
        } else {
            $uidtime = $this->useridtime($where1, $channel); //查询昨日注册用户
        }

        $useridtime = implode(",", $uidtime);
        //$Yesterday = LogindetailModel::getInstance()->getModel()->where($where3)->whereIn('user_id', $useridtime)->group('user_id')->column('user_id'); //查询昨日注册登录用户
        $Yesterday = LogindetailModel::getInstance()->getUidsByWhere($where3, $uidtime);
        $Ppyuser = ChargedetailModel::getInstance()->getModel()->where($where4)->whereIn('uid', $useridtime)->group('uid')->column('uid'); //查询昨日注册消费用户

        //$Today = LogindetailModel::getInstance()->getModel()->where($where2)->whereIn('user_id', $Yesterday)->field('user_id')->group('user_id')->select()->toArray(); //查询昨日用户今日留存
        $Today = LogindetailModel::getInstance()->getUidsByWhere($where2, $Yesterday);
        $Tuser = LogindetailModel::getInstance()->getModel()->where($where2)->whereIn('user_id', $Ppyuser)->field('user_id')->group('user_id')->select()->toArray(); //查询昨日用户今日留存
        if (count($Today)) {
            foreach ($Today as $k => $v) {
                $Todaystr[] = $v['user_id'];
            }
        }
        if (count($Tuser)) {
            foreach ($Tuser as $k => $v) {
                $Tuserstr[] = $v['user_id'];
            }
        }
        $jt = 0;
        $zt = 0;
        if (count($Tuser) > 0 || count($Ppyuser) > 0) {
            $zt = round(count($Tuser) / count($Ppyuser) * 100, 2); //昨日用户今天留存;//付费留存率
        }
        if (count($Today) > 0 || count($Yesterday) > 0) {
            $jt = round(count($Today) / count($Yesterday) * 100, 2); //昨日用户今天留存
        }
        $data = ['zt' => $zt, 'jt' => $jt];
        return $data; //响应请求
    }

    public function channelSourceOppoData()
    {
        $pagenum = 20;
        $master_page = $this->request->param('page', 1);
        $page = ($master_page - 1) * $pagenum;
        $start = date('Y-m-d', strtotime("-1 days"));
        $end = date('Y-m-d');
        $strtime = $this->request->param('strtime', $start);
        $endtime = $this->request->param('endtime', $end);
        $channel = $this->request->param('channel');
        $daochu = $this->request->param('daochu');
        $type = $this->request->param('type', '');
        $where = [];
        $channel = 'oppo';

        if ($type) {
            $where[] = ['source', '=', $type];
        }

        $where[] = ['date', '>=', $strtime];
        $where[] = ['date', '<', $endtime];

        $query = BiOppoDailyDayModel::getInstance()->getModel()->field('*')->where($where);
        if ($daochu == 1) {
            $list = $query->order('id asc')->select();
        } else {
            $list = $query->limit($page, $pagenum)->order('date desc')->select();
        }
        $num = 0;
        if (!empty($list)) {
            $list = $list->toArray();
            foreach ($list as $list_k => $list_item) {
                //$list[$list_k]['charge_rate'] = $this->divedFunc($list_item['register_user_charge_num'],$list_item['register_people_num']);
                //充值率 当日新增付费人数/当日新增人数
                $list[$list_k]['register_rate'] = $this->divedFunc($list_item['register_user_charge_num'], $list_item['register_people_num']);
                //当日新增充值金额/当日新增
                $list[$list_k]['arpu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_people_num']);
                //当日新增充值金额/新增充值人数
                $list[$list_k]['arppu'] = $this->divedFunc($list_item['register_user_charge_amount'], $list_item['register_user_charge_num']);
            }
            //查询总数
            $num = BiOppoDailyDayModel::getInstance()->getModel()->where($where)->count();
        }
        //获取渠道列表
        $channel_lists = config('config.channelconf');
        $channel_list = [];
        foreach ($channel_lists as $ck => $cv) {
            $channel_list[$ck]['channel_name'] = $cv;
        }
        $page_array = [];
        $page_array['page'] = $master_page;
        $page_array['total_page'] = ceil($num / $pagenum);
        View::assign('page', $page_array);
        View::assign('list', $list);
        View::assign('token', $this->request->param('token'));
        View::assign('strtime', $strtime);
        View::assign('endtime', $endtime);
        View::assign('channel', $channel);
        View::assign('type', $type);
        View::assign('app_types', array_column(config('config.APP_TYPE_MAP'), null, 'source'));
        View::assign('channel_list', $channel_list);
        View::assign('user_role_menu', $this->user_role_menu);
        View::assign('user_role_menu_input', implode(',', $this->user_role_menu));

        $headerArray = [
            'source' => '包源',
            'date' => '日期',
            'register_channel' => '渠道',
            'taskid' => '任务id',
            'daily_life' => '日活',
            'register_people_num' => '新增人数',
            'charge_money_sum' => '充值总金额',
            'charge_people_sum' => '充值总人数',
            'register_user_charge_amount' => '新增充值额',
            'register_user_charge_num' => '新增充值人数',
            'register_rate' => '新增充值率',
            'pay_amount_up_now' => '累计充值',
            'arpu' => 'ARPU',
            'arppu' => 'ARPPU',
            'fee_register_7' => "七日付费",
            'fee_register_30' => "三十日付费",
            'fee_register_60' => "六十日付费",
            'fee_register_90' => "九十日付费",
            'keep_login_1' => "一日留存",
            'keep_login_7' => "七日留存",
            'keep_login_15' => "十五日留存",
            'keep_login_30' => '三十日留存',
        ];

        if ($daochu == 1) {
            $this->exportcsv($list, $headerArray);
        }
        return View::fetch('dataManagement/channelSourceOppoData');
    }

}

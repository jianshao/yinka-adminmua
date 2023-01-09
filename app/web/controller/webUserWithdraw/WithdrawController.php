<?php
namespace app\web\controller\webUserWithdraw;

use app\web\common\WebBaseController;
use app\web\controller\webUserWithdrawal\WebMemberController;
use think\facade\Log;
use think\facade\View;

class WithdrawController extends WebBaseController
{

    public function withdrawalLogin()
    {
        return View::fetch('withdrawal/login');
    }

    public function userWithdrawalOption()
    {
        $res = new WebMemberController($this->app);
        $user_info = $res->webUserWithdrawalItem();
        View::assign('user_info', $user_info);
        return view::fetch('withdrawal/withdrawal');
    }

    public function userWithdrawalDetail()
    {
        $detail = new WebMemberController($this->app);
        $user_detail = $detail->webUserWithdrawalLists();
        View::assign('user_detail', $user_detail['data']);
        View::assign('user_money_count', $user_detail['user_money_count']);
        return view::fetch('withdrawal/user-detail');
    }

    //提现信息页
    public function userWithdrawInfo()
    {
        $username = $this->request->param('username');
        $res = new WebMemberController($this->app);
        $user_info = $res->webUserWithdrawalItem();

        var_dump($username);die;
        Log::record('web用户:' . json_encode($user_info));
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $user_info, \constant\CodeConstant::CODE_OK_MAP[\constant\CodeConstant::CODE_成功]);
        die;
    }

    //提现详情页
    public function userWithdrawDetail()
    {
        $detail = new WebMemberController($this->app);
        $user_detail = $detail->webUserWithdrawalLists();
        Log::record('web用户:' . json_encode($user_detail));
        echo $this->return_json(\constant\CodeConstant::CODE_成功, $user_detail, \constant\CodeConstant::CODE_OK_MAP[\constant\CodeConstant::CODE_成功]);
        die;
    }
}
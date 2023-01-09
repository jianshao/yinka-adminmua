<?php
namespace app\web\controller\webUserWithdrawal;


use app\web\common\WebBaseController;
use app\web\controller\webUserWithdrawal\WebMemberController;
use think\facade\View;
use think\helper\Arr;
use think\facade\Session;

class WithdrawalController extends WebBaseController
{

public function withdrawalLogin(){
    return View::fetch('withdrawal/login');
}
public function userWithdrawalOption(){
    $res = new WebMemberController($this->app);
    $user_info = $res->webUserWithdrawalItem();
    View::assign('user_info', $user_info);
    return view::fetch('withdrawal/withdrawal');
}
public function userWithdrawalDetail(){
    $detail = new WebMemberController($this->app);
    $user_detail = $detail->webUserWithdrawalLists();
    View::assign('user_detail', $user_detail['data']);
    View::assign('user_money_count', $user_detail['user_money_count']);
    return view::fetch('withdrawal/user-detail');
}







}

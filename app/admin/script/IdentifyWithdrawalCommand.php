<?php


namespace app\admin\script;

use app\admin\model\LanguageroomModel;
use app\admin\model\MemberCardidCallbackModel;
use app\admin\model\RoomHideModel;
use app\admin\model\UserWithdrawInfoModel;
use app\admin\service\WithdrawalService;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use think\facade\Log;

ini_set('set_time_limit', 0);

class IdentifyWithdrawalCommand extends Command
{

    protected function configure()
    {
        $this->setName('IdentifyWithdrawalCommand')->setDescription('RoomHideCommand');
    }

    /**
     *执行方法
     */
    protected function execute(Input $input, Output $output)
    {
        $maxLimit = 100;
        $page = 1;
        $result = UserWithdrawInfoModel::getInstance()->getModel()->where(['status' => 0])->page($page, $maxLimit)->select()->toArray();
        while ($result) {
            foreach($result as $item){
                 $this->identifyUserCard($item);
            }
            $page++;
            $result = UserWithdrawInfoModel::getInstance()->getModel()->where(['status' => 0])->page($page, $maxLimit)->select()->toArray();
        }
    }


    private function identifyUserCard($userIdentify)
    {
        try{
            $card_front = $userIdentify['identity_card_front'];
            $card_opposite = $userIdentify['identity_card_opposite'];
            //数据库里面存储的图片都是阿里云obs的远程地址 在这里替换内网地址提高效率
            $replace  = "http://muayuyin.oss-cn-beijing-internal.aliyuncs.com";
            $card_front =  substr_replace($card_front,$replace,0,strpos($card_front,".com")+4);
            $card_opposite =  substr_replace($card_opposite,$replace,0,strpos($card_opposite,".com")+4);
            $card_front_decry = WithdrawalService::getInstance()->decryImage($card_front,true);
            $card_opposite_decry = WithdrawalService::getInstance()->decryImage($card_opposite,true);
            $identifyInfo = [
                'truename' => $userIdentify['real_name'],     //姓名
                'phone' => $userIdentify['real_phone'],      //手机号
                'card' => $userIdentify['identity_number'], //身份号
                'infoPage' => urlencode(WithdrawalService::getInstance()->base64EncodeImage($card_front_decry)),                //身份证正面
                'emblemPage' => urlencode(WithdrawalService::getInstance()->base64EncodeImage($card_opposite_decry)),             // 身份证反面
                'mode' => 0         //上传身份证的模式 0 base64 1 图片地址
            ];
            $identifyReturnRes = WithdrawalService::getInstance()->executeApi("employees.insp.free.add", $identifyInfo);
            MemberCardidCallbackModel::getInstance()->getModel()->insert([
                'uid'=>$userIdentify['user_id'],
                "create_time"=>time(),
                "content"=>json_encode($identifyReturnRes)
            ]);
            $updateRes=[];
            if (isset($identifyReturnRes['code']) && $identifyReturnRes['code'] == 200) {
                $employeesId = $identifyReturnRes['data']['employeesId'] ?? 0;
                if($employeesId>0){
                    $updateRes['sns_user_id'] = $employeesId;
                    $updateRes['status'] = 1;
                    $updateRes['update_time'] = time();
                    $updateRes['message_detail'] = '';
                }
            }else{
                $updateRes['status'] = 2;
                $updateRes['message_detail'] = $identifyReturnRes['msg'] ?? '';
                $updateRes['update_time'] = time();
            }

            UserWithdrawInfoModel::getInstance()->getModel()->where(["id"=>$userIdentify['id']])->update($updateRes);
        }catch (\Throwable $e){
            Log::error("identifywithdrawalcommand:identifyusercard:".$e->getMessage());
        } finally {
            file_exists($card_front_decry) && unlink($card_front_decry);
            file_exists($card_opposite_decry) && unlink($card_opposite_decry);
        }


    }


}

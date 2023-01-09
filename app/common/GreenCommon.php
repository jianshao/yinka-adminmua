<?php
namespace app\common;
use think\facade\Log;
use Green\Request\V20180509 as Green;
use Green\Request\Extension\ClientUploader;
use DefaultProfile;
use DefaultAcsClient;

class GreenCommon
{
	protected static $instance;
	 //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GreenCommon();
        }
        return self::$instance;
    }

    //阿里云文本验证 true成功 false 失败
    public function checkText($content)
    {
    	$ali = config('config.ALIGREEN');
        $accessKeyID = $ali['AccessKeyID'];
	    $accessKeySecret = $ali['AccessKeySecret'];

    	$iClientProfile = DefaultProfile::getProfile("cn-shanghai", $accessKeyID, $accessKeySecret);
		DefaultProfile::addEndpoint("cn-zhangjiakou", "cn-zhangjiakou", "Green", "green.cn-zhangjiakou.aliyuncs.com");
		$client = new DefaultAcsClient($iClientProfile);
		$request = new Green\TextScanRequest();
		$request->setMethod("POST");
		$request->setAcceptFormat("JSON");
		$task1 = array('dataId' =>  uniqid(),
		    'content' => $content
		);
		$request->setContent(json_encode(array("tasks" => array($task1),
		    "scenes" => array("antispam"),"bizType"=>"nickname")));
		try {
		    $response = $client->getAcsResponse($request);
		    if(200 == $response->code){
		        $taskResults = $response->data;
		        foreach ($taskResults as $taskResult) {
		            if(200 == $taskResult->code){
		                $sceneResults = $taskResult->results;
		                foreach ($sceneResults as $sceneResult) {
		                    // $scene = $sceneResult->scene;
		                    $suggestion = $sceneResult->suggestion;
		                    if ($suggestion == 'block') {
		                    	return true;
		                    }
		                }
		                return false;
		            }else{
		                return true;
		            }
		        }
		    }else{
		        return true;
		    }
		} catch (Exception $e) {
		    return true; 
		}
		return true;
    }
  
}

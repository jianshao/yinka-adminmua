<?php

namespace app\admin\script;

use app\admin\model\ChannelDataModel;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

//未使用的脚本 jobby没此任务脚本
//统计每天的日常数据量 日活 新增 充值人数 充值总金额
class NewHuaweiChannel extends Command
{


    const  UPDATE_TABLE_NAME_HW = 'bi_channel_huawei'; //华为买量数据
    const  UPDATE_TABLE_NAME_APPSTORE = 'bi_channel_appstore'; //ios买量数据
    const  SYNC_CONF_TABLE = 'sync_data_conf';
    const  UPDATE_LIMIT = 200;


    protected function configure()
    {
        // 指令配置
        $this->setName('NewHuaweiChannel')
            ->setDescription('NewHuaweiChannel');
    }


    protected function execute(Input $input, Output $output)
    {
        //获取同步信息里面的
        $sync_table = Db::name(self::SYNC_CONF_TABLE)->where("deal_func", 'adv_pay_channel')->find();
        $where = [];
        $where[] = ['id', '>', $sync_table['sync_id']];
        $where[] = ['data', '<>', ''];
        $res = ChannelDataModel::getInstance()->getModel()->where($where)->limit(self::UPDATE_LIMIT)->select()->toArray();
        if ($res) {
            $insertdata = [];
            $insertdataios = [];
            $max_id = max(array_column($res, "id"));
            foreach ($res as $item) {
                if ($item['channel'] == 'HuaWei') {
                    $parseRes = json_decode($item['data'], true);
                    $insertdata[] = [
                        "user_id" => $item['user_id'],
                        "device_id" => $item['device_id'] ?? '',
                        "hw_taskid" => $parseRes['taskid'] ?? '',
                        "hw_channel" => $parseRes['channel'] ?? '',
                        "ctime" => $item['ctime'] ?? time(),
                    ];
                }

                if ($item['channel'] == 'appStore') {

                    if (isset($item['user_id']) && empty($item['user_id'])) {
                        continue;
                    }
                    /*
                     * 1. {"attribution":false}
                     * 2. {"keywordId":886131817,"conversionType":"Download","orgId":2786530,"campaignId":884384280,"adGroupId":886097725,"countryOrRegion":"CN","attribution":true}
                     * 3. {
                     "error" : "1"
                       }

                      4. {"Version3.1":{"iad-attribution":"false"}}
                      5. {"Version3.1":{"iad-purchase-date":"2021-11-15T20:20:08Z","iad-keyword":"hello语音","iad-adgroup-id":"886097725","iad-campaign-id":"884384280","iad-lineitem-id":"886097725","iad-conversion-date":"2021-11-15T20:20:08Z","iad-org-id":"2786530","iad-keyword-id":"886131873","iad-conversion-type":"Download","iad-country-or-region":"CN","iad-org-name":"音恋语音","iad-campaign-name":"LT-音恋9.9（间接竞品）","iad-click-date":"2021-11-15T20:19:25Z","iad-attribution":"true","iad-adgroup-name":"音恋间接竞品","iad-keyword-matchtype":"Exact","iad-lineitem-name":"音恋间接竞品"}}
                      6. {}
                      7. {"creativeSetId":0,"conversionType":"Download","orgId":3011120,"campaignId":911321859,"adGroupId":913031623,"countryOrRegion":"CN","attribution":true}
                     * */

                    $parseRes = json_decode($item['data'], true);
                    if (isset($parseRes['attribution'])) {
                        if (!$this->isAttribution($parseRes['attribution'])) {
                            continue;
                        }
                    }

                    $params = current($parseRes);

                    if (is_array($params)) {
                        if (isset($params['iad-attribution'])) {
                            if (!$this->isAttribution($params['iad-attribution'])) {
                                continue;
                            }
                        }
                        $insertdataios[] = [
                            "user_id" => $item['user_id'],
                            "device_id" => $item['device_id'] ?? '',
                            "iad_adgroup_id" => $params['iad-adgroup-id'] ?? '',
                            "iad_campaign_id" => $params['iad-campaign-id'] ?? '',
                            "iad_keyword_id" => $params['iad-keyword-id'] ?? '',
                            "iad_adgroup_name" => $params['iad-adgroup-name'] ?? '',
                            "iad_campaign_name" => $params['iad-campaign-name'] ?? '',
                            "iad_keyword" => $params['iad-keyword'] ?? '',
                            "ctime" => $item['ctime'] ?? time(),
                        ];
                    } else {
                        $insertdataios[] = [
                            "user_id" => $item['user_id'],
                            "device_id" => $item['device_id'] ?? '',
                            "iad_adgroup_id" => $parseRes['adGroupId'] ?? '',
                            "iad_campaign_id" => $parseRes['campaignId'] ?? '',
                            "iad_keyword_id" => $parseRes['keywordId'] ?? '',
                            "iad_adgroup_name" => '',
                            "iad_campaign_name" => '',
                            "iad_keyword" => '',
                            "ctime" => $item['ctime'] ?? time(),
                        ];
                    }

                }
            }

            // 启动事务
            Db::startTrans();
            try {

                if ($insertdata) {
                    $this->insertOrUpdateMul($insertdata, self::UPDATE_TABLE_NAME_HW, ["user_id", "id"]);
                }

                if ($insertdataios) {
                    //$this->insertOrUpdateMul($insertdataios, self::UPDATE_TABLE_NAME_APPSTORE, ["user_id", "id"]);
                    $this->insertOrUpdateIgno($insertdataios, self::UPDATE_TABLE_NAME_APPSTORE);
                }

                //更新配置文件
                Db::table(self::SYNC_CONF_TABLE)->where('id', $sync_table['id'])->update(["sync_id" => $max_id]);

                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::error("Newhuawei-ios-channel:exception" . $e->getMessage());
                Db::rollback();
            }

        }

    }


    public function insertOrUpdateMul($data, $table, $unique = [])
    {
        $getfield = (Db::getFields($table));
        $updateFields = array_diff(array_keys($getfield), $unique);
        $exceptUniq = join(",", $updateFields);
        Db::table($table)->extra("IGNORE")->duplicate($exceptUniq)->insertAll($data);
    }


    public function isAttribution($attribution)
    {
        if (is_string($attribution)) {
            if (strtolower($attribution) == 'false') {
                return false;
            }
        } elseif (is_bool($attribution)) {
            if (!$attribution) {
                return false;
            }
        }
        return true;
    }


    public function insertOrUpdateIgno($data, $table)
    {
        Db::table($table)->extra("IGNORE")->insertAll($data);
    }


}

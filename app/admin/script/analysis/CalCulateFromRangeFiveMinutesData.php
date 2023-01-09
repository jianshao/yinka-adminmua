<?php
namespace app\admin\script\analysis;

use app\admin\model\BiUserStats5MinsModel;

class CalCulateFromRangeFiveMinutesData
{
    const TABLE = 'bi_user_stats_5mins';
    const LIMIT = 500;
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function calculate($where)
    {
        $count = AnalysisCommon::getStatsCount(self::TABLE, $where);
        $page = AnalysisCommon::getPage($count, self::LIMIT);

        $data = $obj = [];
        for ($i = 0; $i < $page; $i++) {
            $offset = $i * self::LIMIT;
            $res = AnalysisCommon::getStatsItems(BiUserStats5MinsModel::getInstance()->getModel(), $where, $offset);
            $this->calDayData($res, $data, $obj);
        }

        return $data;
    }

    protected function calDayData($res, &$data, &$obj)
    {
        foreach ($res as $mins5_data) {
            $uid = $mins5_data['uid'];
            $user_json = json_decode($mins5_data['json_data'], true);

            foreach ($user_json as $json_type => $json) {
                if (array_key_exists($json_type, AnslysisConst::CLASS_MAP) && !empty($json)) {
                    $class = AnslysisConst::CLASS_MAP[$json_type];

                    $user_obj = new $class($uid);
                    $user_obj->fromJson($json);

                    if (!isset($obj[$json_type])) {
                        $obj[$json_type] = $user_obj;
                    } else {
                        $obj[$json_type] = $obj[$json_type]->merge($user_obj);
                    }

                    $data[$json_type] = $obj[$json_type]->toJson();
                } elseif (!array_key_exists($json_type, AnslysisConst::CLASS_MAP) && !empty($json)) {
                    $data[$json_type] = $json;
                }
            }
        }
    }
}
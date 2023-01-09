<?php
namespace app\admin\script\analysis;

use app\admin\model\BiUserStats5MinsModel;

ini_set('memory_limit', -1);

class CalCulateFromFiveMinutesData
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

    public function calculate($where, $column)
    {
        $count = AnalysisCommon::getStatsCount(self::TABLE, $where);
        $page = AnalysisCommon::getPage($count, self::LIMIT);

        $data = $obj = [];
        for ($i = 0; $i < $page; $i++) {
            $offset = $i * self::LIMIT;
            $res = AnalysisCommon::getStatsItems(BiUserStats5MinsModel::getInstance()->getModel(), $where, $offset);
            $this->calDayData($res, $data, $obj, $column);
        }

        return $data;
    }

    protected function calDayData($res, &$data, &$obj, $column)
    {
        foreach ($res as $mins5_data) {
            $uid = $mins5_data['uid'];
            $user_json = json_decode($mins5_data['json_data'], true);
            $stats_column = "{$mins5_data[$column]}";

            if (!isset($data[$stats_column]['active_users'])) {
                $data[$stats_column]['active_users'] = [];
            }

            if (!in_array($uid, $data[$stats_column]['active_users'])) {
                array_push($data[$stats_column]['active_users'], $uid);
            }

            foreach ($user_json as $json_type => $json) {
                if (!empty($json)) {
                    if (array_key_exists($json_type, AnslysisConst::CLASS_MAP)) {
                        $class = AnslysisConst::CLASS_MAP[$json_type];

                        $user_obj = new $class($stats_column);
                        $user_obj->fromJson($json);

                        if (!isset($obj[$stats_column][$json_type])) {
                            $obj[$stats_column][$json_type] = $user_obj;
                        } else {
                            $obj[$stats_column][$json_type] = $obj[$stats_column][$json_type]->merge($user_obj);
                        }

                        $data[$stats_column][$json_type]['data'] = $obj[$stats_column][$json_type]->toJson();
                    } elseif (!array_key_exists($json_type, AnslysisConst::CLASS_MAP)) {
                        $data[$stats_column][$json_type]['data'] = $json;
                    }

                    if (!isset($data[$stats_column][$json_type]['users'])) {
                        $data[$stats_column][$json_type]['users'][] = $uid;
                    }
                    if (!in_array($uid, $data[$stats_column][$json_type]['users'])) {
                        array_push($data[$stats_column][$json_type]['users'], $uid);
                    }
                }
            }
        }
    }
}
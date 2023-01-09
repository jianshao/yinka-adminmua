<?php

namespace app\admin\service;

use app\admin\model\ConfigModel;

class Config2Service
{
    public static $roomtag = 'room_tag_conf';
    protected static $instance;
    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Config2Service();
        }
        return self::$instance;
    }

    public function getRoomTag($array)
    {
        $data = ConfigService::getInstance()->JsonEscape(self::$roomtag);
        if ($data) {
            echo json_encode(['code' => 200, 'msg' => '操作成功', 'data' => $data]);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }

    }

    public function roomTagList($array)
    {
        $w = config('config.APP_URL_image');
        $list = ConfigService::getInstance()->JsonEscape(self::$roomtag);
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['tag_img_mua'] = empty($v['tag_img_mua']) ? '' : $w . $v['tag_img_mua'];
                $list[$k]['tag_img_yinlian'] = empty($v['tag_img_yinlian']) ? '' : $w . $v['tag_img_yinlian'];
            }
        }

        return $list;
    }

    public function roomTagAdd($array)
    {
        $list = ConfigService::getInstance()->JsonEscape(self::$roomtag);
        if ($list) {
            $data[] = [
                'id' => ConfigService::getInstance()->bigId(self::$roomtag, 'id'),
                'tag_name' => $array['tag_name'],
                'tag_img_mua' => array_key_exists('tag_img_mua', $array) ? $array['tag_img_mua'] : '',
                'tag_img_yinlian' => array_key_exists('tag_img_yinlian', $array) ? $array['tag_img_yinlian'] : '',
            ];
            $data = array_merge($list, $data);
            $is = ConfigModel::getInstance()->getModel()->where('name', self::$roomtag)->save(['json' => json_encode($data)]);
        } else {
            $data[] = [
                'id' => 1,
                'tag_name' => $array['tag_name'],
                'tag_img_mua' => array_key_exists('tag_img_mua', $array) ? $array['tag_img_mua'] : '',
                'tag_img_yinlian' => array_key_exists('tag_img_yinlian', $array) ? $array['tag_img_yinlian'] : '',
            ];
            $is = ConfigModel::getInstance()->getModel()->save(['name' => self::$roomtag, 'json' => json_encode($data)]);
        }
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

    public function roomTagSave($array)
    {
        $list = ConfigService::getInstance()->JsonEscape(self::$roomtag);
        if (array_key_exists('delId', $array)) {
            foreach ($list as $k => $v) {
                if ($v['id'] == $array['delId']) {
                    unset($list[$k]);
                }
            }
        } else {
            foreach ($list as $k => $v) {
                if ($v['id'] == $array['id']) {
                    if (array_key_exists('tag_name', $array)) {
                        $list[$k]['tag_name'] = $array['tag_name'];
                    }
                    if (array_key_exists('tag_img_mua', $array)) {
                        $list[$k]['tag_img_mua'] = $array['tag_img_mua'];
                    }
                    if (array_key_exists('tag_img_yinlian', $array)) {
                        $list[$k]['tag_img_yinlian'] = $array['tag_img_yinlian'];
                    }
                }
            }
        }
        $is = ConfigModel::getInstance()->getModel()->where('name', self::$roomtag)->save(['json' => json_encode($list)]);
        if ($is) {
            echo json_encode(['code' => 200, 'msg' => '操作成功']);die;
        } else {
            echo json_encode(['code' => 500, 'msg' => '操作失败']);die;
        }
    }

}

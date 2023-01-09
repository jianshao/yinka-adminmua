<?php
use think\facade\Log;
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件

function rjson($data = array(), $code = 200, $msg = '')
{
    Log::write('返回值 : ' . json_encode($data), 'debug');
    Log::write("\n\r", 'debug');
    $out['code'] = $code ?: 0;
    $out['desc'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
    $out['data'] = $data ?: null;
    return json($out);
}

function return_json($code = 200, $data = array(), $msg = '', $is_die = 0)
{
    $out['code'] = $code ?: 0;
    $out['msg'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
    $out['data'] = $data ?: [];
    if ($is_die) {
        echo json_encode($out);
        return;
    } else {
        return json_encode($out);
    }
}

function rjsonadmin($data = array(), $code = 200, $msg = '')
{
    Log::write('返回值 : ' . json_encode($data), 'debug');
    Log::write("\n\r", 'debug');
    $out['code'] = $code ?: 0;
    $out['msg'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
    $out['data'] = $data ?: null;
    return json($out);
}

//返回毫秒级时间戳
function msectime()
{
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (int) sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}

function exportcsv($header, $data)
{
    ini_set('max_execution_time', 300);
    ini_set('memory_limit', '500M');
    $strBOMHEAD = "\xEF\xBB\xBF";
    //路径
    $fileName = date('YmdHis') . '.csv';
    $filePath = $fileName;
    $index = 0;
    $fp = fopen($filePath, 'w');
    chmod($filePath, 0777);
    @fwrite($fp, $strBOMHEAD);
    fputcsv($fp, $header);
    //处理导出数据
    foreach ($data as $key => &$val) {
        foreach ($val as $k => $v) {
            $val[$k] = $v . "\t";
            if ($index == 100) {
                $index = 0;
                ob_flush();
                flush();
            }
            $index++;
        }
        fputcsv($fp, $val);
    }
    ob_flush();
    fclose($fp);
    // header("Cache-Control: max-age=0");
    // header("Content-type:application/vnd.ms-excel;charset=UTF-8");
    // header("Content-Description: File Transfer");
    header('Content-disposition: attachment; filename=' . basename($fileName));
    header("Content-Type: text/csv");
    // header("Content-Transfer-Encoding: binary");
    // header('Content-Length: ' . filesize($filePath));
    @readfile($filePath);
    // unlink($filePath);
    echo $filePath;
    return;
}

/**
 * 随机生成token.
 * @param $salt
 * @return string
 */
function generateToken($salt)
{
    return md5(md5(generateRandomString(10)) . $salt);
}

/**
 * 生成指定长度的随机字符串.
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_len = strlen($characters);
    $random_str = '';
    for ($i = 0; $i < $length; ++$i) {
        $random_str .= $characters[rand(0, $characters_len - 1)];
    }

    return $random_str;
}

/**
 * curl请求
 * @param $url
 * @param $data
 * @param string $method
 * @param string $type
 * @return bool|string
 */
function curlData($url, $data, $method = 'GET', $type = 'json', $head = [])
{
    //初始化
    $ch = curl_init();
    $headers_type = [
        'form-data' => ['Content-Type: multipart/form-data'],
        'json' => ['Content-Type: application/json'],
    ];
    $headers = array_merge($headers_type[$type], $head);

    if ($method == 'GET') {
        if ($data) {
            $querystring = http_build_query($data);
            $url = $url . '?' . $querystring;
        }
    }
    // 请求头，可以传数组
    // $headers[]  =  "Authorization: Bearer ". $accessToken;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 执行后不直接打印出来
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // 请求方式
        curl_setopt($ch, CURLOPT_POST, true); // post提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // post的变量
    }
    if ($method == 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    if ($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
    curl_setopt($ch, CURLOPT_TIMEOUT,10);   //只需要设置一个秒的数量就可以
    $output = curl_exec($ch); //执行并获取HTML文档内容
    curl_close($ch); //释放curl句柄
    return $output;
}

/**获取用户头像
 * @param $avatar   头像地址
 * @return string   返回类型值
 */
function getavatar($avatar)
{
    $avatar_url = config('config.APP_URL_image');
    if (preg_match('/(http:\/\/)|(https:\/\/)/i', $avatar)) {
        $avatar = $avatar;
    } else if (empty($avatar)) {
        $avatar = $avatar_url . "/Public/Uploads/image/logo.png";
    } else {
        $avatar = $avatar_url . $avatar;
    }
    return $avatar;
}

/**处理空数组值
 * @param $arr  数组
 * @return mixed    返回值
 */
function dealnull($arr)
{
    foreach ($arr as $k => $v) {
        if ($v == null) {
            $arr[$k] = "";
        }
    }
    return $arr;
}

/**判断干支、生肖和星座
 * @param $birth        年月日
 * @return array|bool|string    返回类型
 */
function birthext($birth)
{
    if (strstr($birth, '-') === false && strlen($birth) !== 8) {
        $birth = date("Y-m-d", $birth);
    }
    if (strlen($birth) === 8) {
        if (eregi('([0-9]{4})([0-9]{2})([0-9]{2})$', $birth, $bir)) {
            $birth = "{$bir[1]}-{$bir[2]}-{$bir[3]}";
        }

    }
    if (strlen($birth) < 8) {
        return false;
    }
    $tmpstr = explode('-', $birth);
    if (count($tmpstr) !== 3) {
        return false;
    }
    $y = (int) $tmpstr[0];
    $m = (int) $tmpstr[1];
    $d = (int) $tmpstr[2];
    $result = array();
    $xzdict = array('摩羯', '水瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手');
    $zone = array(1222, 122, 222, 321, 421, 522, 622, 722, 822, 922, 1022, 1122, 1222);
    if ((100 * $m + $d) >= $zone[0] || (100 * $m + $d) < $zone[1]) {
        $i = 0;
    } else {
        for ($i = 1; $i < 12; $i++) {
            if ((100 * $m + $d) >= $zone[$i] && (100 * $m + $d) < $zone[$i + 1]) {
                break;
            }
        }
    }
    $result = $xzdict[$i] . '座';
    return $result;
}

/**
 * 时间格式化处理
 * @param $time 时间
 * @return string   字符类型
 */
function formatTimes($time)
{
    $nowTime = time();
    // 时间差.
    $cut = $nowTime - $time;
    $timeStr = "";
    if ($cut <= 60) { // 小于1分钟.
        $number = floor($cut / 1);
        $timeStr = $number . "秒前";
    } elseif ($cut > 60 && $cut < 3600) { // 1min-10min
        $number = floor($cut / 60);
        $timeStr = $number . "分钟前";
    } elseif (3600 <= $cut && $cut < 86400) {
        $number = floor($cut / 3600);
        $timeStr = $number . "小时前";
    } elseif ($cut >= 86400 && $cut < 259200) {
        $number = floor($cut / 86400);
        $timeStr = $number . "天前";
    } elseif ($cut >= 259200) {
        $number = 3;
        $timeStr = $number . "天前";
    }
    return $timeStr;

}

/**检查是否为数字格式
 * @param $str  字符类型
 * @return bool 返回类型
 */
function isNumber($str)
{
    if (preg_match('/^\d+$/', $str)) {
        return true;
    }
    return false;
}

/**检查是否为中文
 * @param $strchina 字符类型
 * @return bool     批回类型
 */
function checkchinese($str)
{
    if (preg_match("/[\x{4e00}-\x{9fa5}]+/u", $str)) {
        return true;
    }
    return false;
}

/**是给为空字符串
 * @param $str  字符类型
 * @return bool
 */
function isEmpty($str)
{
    $str = trim($str);
    return !empty($str) ? true : false;
}

/**检索给定键的所有值
 * @param $items    数组
 * @param $key      指定的键
 * @return array    返回新的数组
 */
function pluck($items, $key)
{
    return array_map(function ($item) use ($key) {
        return is_object($item) ? $item->$key : $item[$key];
    }, $items);
}

function arrayStringVal($arr, $column, $return = '')
{
    if (isset($arr[$column]) && !empty($arr[$column])) {
        return (string) $arr[$column];
    }
    return $return;
}

function arrayIntVal($arr, $column, $return = 0)
{
    if (isset($arr[$column]) && !empty($arr[$column])) {
        return (int) $arr[$column];
    }
    return $return;
}

function arrayFloatVal($arr, $column, $return = 0)
{
    if (isset($arr[$column]) && !empty($arr[$column])) {
        return (float) $arr[$column];
    }
    return $return;
}

function arrayKeyValue($arr, $column)
{
    if (isset($arr[$column]) && !empty($arr[$column])) {
        return true;
    }
    return false;
}

function getFirstDayOfMonth($date)
{
    $firstday = date('Y-m-01', strtotime($date));
    $nowday = date('Y-m-d', strtotime($date));
    return [$firstday, $nowday];
}

function getTable($start, $end)
{
    $month = empty($start) ? date('Ym') : date("Ym", strtotime($start));
    $table = 'zb_user_asset_log_' . $month;
    if (!empty($start) && !empty($end)) {
        $start_month = date("Ym", strtotime($start));
        $end_month = date("Ym", strtotime($end));

        $date_arr = getFirstDayOfMonth($end);
        if ($start_month == $end_month || $date_arr[0] == $date_arr[1]) {
            $table = 'zb_user_asset_log_' . $start_month;
        }
    }
    return $table;
}

/**
 * 通过图片的远程url，下载到本地
 * @param: $url为图片远程链接
 * @param: $filename为下载图片后保存的文件名
 * @param  $upload 本地地址操作
 */
function GrabImage($url, $filename, $upload)
{
    if ($url == ""): return false;endif;
    ob_start();
    readfile($url);
    $img = ob_get_contents();
    ob_end_clean();
    $size = strlen($img);
    $fp2 = @fopen($upload . $filename, "a");
    fwrite($fp2, $img);
    fclose($fp2);
    return $filename;
}

/**按键对数组或对象的集合排序
 * @param $items    数组
 * @param $attr     指定的键
 * @param $order    排序
 * @return array    返回新的数组
 */
function orderBy($items, $attr, $order)
{
    $sortedItems = [];
    foreach ($items as $item) {
        $key = is_object($item) ? $item->{$attr} : $item[$attr];
        $sortedItems[$key] = $item;
    }
    if ($order === 'desc') {
        krsort($sortedItems);
    } else {
        ksort($sortedItems);
    }
    return array_values($sortedItems);
}

/**获取文件后辍名
 * @param $filename 检测的字符串
 * @return mixed
 */
function getExtension($filename)
{
    $suffix = substr($filename, strrpos($filename, '.'));
    return str_replace('.', '', $suffix);
}

/**过滤数据中的空数组
 * @param $data
 * @return mixed
 */
function filter_data($data)
{
    foreach ($data as $key => $value) {
        if (empty($value)) {
            unset($data[$key]);
        }
    }
    return $data;
}

/**将数字转化为时间
 * @param $time
 * @return bool|string
 */
function Sec2Time($time)
{
    if (is_numeric($time)) {
        $value = array(
            "years" => 0, "days" => 0, "hours" => 0,
            "minutes" => 0, "seconds" => 0,
        );
        if ($time >= 31556926) {
            $value["years"] = floor($time / 31556926);
            $time = ($time % 31556926);
        }
        if ($time >= 86400) {
            $value["days"] = floor($time / 86400);
            $time = ($time % 86400);
        }
        if ($time >= 3600) {
            $value["hours"] = floor($time / 3600);
            $time = ($time % 3600);
        }
        if ($time >= 60) {
            $value["minutes"] = floor($time / 60);
            $time = ($time % 60);
        }
        $value["seconds"] = floor($time);
        //return (array) $value;
        $t = $value["hours"] . "小时" . $value["minutes"] . "分" . $value["seconds"] . "秒";
        return $t;

    } else {
        return (bool) false;
    }
}

/** 比较
 * @param $a
 * @param $b
 * @return int
 */
function myfunction_diff($a, $b)
{
    if ($a === $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;
}

//热度值数据格式化
function formatNumber($number)
{
    if ($number < 99999) {
        $newNumber = $number > 0 ? $number : 0;
        return $newNumber;
    } else {
        $newNumber = $number > 99999 ? $number / 10000 : $number;
        if (is_int($newNumber)) { //整数 15000    1.5w
            return $newNumber . 'w';
        } else { //有小数点 12457 1.2w
            $newNumber = explode('.', $newNumber);
            if (substr($newNumber[1], 0, 1) < 1) {
                return $newNumber[0] . 'w';
            } else {
                //return $newNumber[0].'.'.substr($newNumber[1],0,1).'w';
                return $newNumber[0] . 'w';
            }
        }
    }
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
function getDateRange($startdate, $enddate)
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

function getWeekRange($startdate, $enddate)
{
    $datearr = [];
    for ($i = strtotime('Monday', strtotime($startdate)); $i <= strtotime($enddate); $i = strtotime('+1 week', $i)) {
        $datearr[] = date('Y-m-d', $i);
    }

    return $datearr;
}

function getBetweenDate($between_date)
{
    if (!empty($between_date)) {
        $date_arr = explode(" - ", $between_date);
        return [$date_arr[0], $date_arr[1]];
    } else {
        return ['', ''];
    }
}

function getDefaultDate()
{
    return date('Y-m-d') . ' - ' . date('Y-m-d', strtotime('+1days'));
}

function formatDate($date_time, $format = 'Y-m-d')
{
    if (is_string($date_time) && strtotime($date_time) !== false) {
        return date($format, strtotime($date_time));
    } else {
        return date($format, $date_time);
    }
}

function arrayValues($data)
{
    if (!empty($data)) {
        return array_values($data);
    } else {
        return [];
    }
}

function strFilter($str)
{
    $str = str_replace('`', '', $str);
    // $str = str_replace('·', '', $str);
    // $str = str_replace('~', '', $str);
    // $str = str_replace('!', '', $str);
    // $str = str_replace('！', '', $str);
    // $str = str_replace('@', '', $str);
    $str = str_replace('#', '', $str);
    $str = str_replace('$', '', $str);
    $str = str_replace('￥', '', $str);
    $str = str_replace('%', '', $str);
    $str = str_replace('^', '', $str);
    // $str = str_replace('……', '', $str);
    $str = str_replace('&', '', $str);
    $str = str_replace('*', '', $str);
    $str = str_replace('(', '', $str);
    $str = str_replace(')', '', $str);
    $str = str_replace('（', '', $str);
    $str = str_replace('）', '', $str);
    // $str = str_replace('-', '', $str);
    // $str = str_replace('_', '', $str);
    $str = str_replace('——', '', $str);
    $str = str_replace('+', '', $str);
    $str = str_replace('=', '', $str);
    // $str = str_replace('|', '', $str);
    $str = str_replace('\\', '', $str);
    $str = str_replace('[', '', $str);
    $str = str_replace(']', '', $str);
    $str = str_replace('【', '', $str);
    $str = str_replace('】', '', $str);
    // $str = str_replace('{', '', $str);
    // $str = str_replace('}', '', $str);
    // $str = str_replace(';', '', $str);
    // $str = str_replace('；', '', $str);
    // $str = str_replace(':', '', $str);
    // $str = str_replace('：', '', $str);
    $str = str_replace('\'', '', $str);
    $str = str_replace('"', '', $str);
    $str = str_replace('“', '', $str);
    $str = str_replace('”', '', $str);
    // $str = str_replace(',', '', $str);
    // $str = str_replace('，', '', $str);
    // $str = str_replace('<', '', $str);
    // $str = str_replace('>', '', $str);
    $str = str_replace('《', '', $str);
    $str = str_replace('》', '', $str);
    // $str = str_replace('.', '', $str);
    // $str = str_replace('。', '', $str);
    $str = str_replace('/', '', $str);
    $str = str_replace('、', '', $str);
    // $str = str_replace('?', '', $str);
    // $str = str_replace('？', '', $str);
    return trim($str);
}

function checkConditionColumnExists($condition, $column)
{
    if (in_array($column, array_column($condition, 0))) {
        return true;
    }
    return false;
}

function getCount($org_model, $where, $uid = 0)
{
    $count = 0;
    if ($where) {
        if (checkConditionColumnExists($where, 'id')) {
            $count = $org_model->getCount($where, $uid);
        } else {
            $models = $org_model->getallModel();
            foreach ($models as $model) {
                $count += $model->getModel()->where($where)->count();
            }
        }
    }
    return $count;
}

function dealImages(string $images_str, $str_pos, $url)
{
    if (strpos($images_str, '[') !== false) {
        $images_str = implode(',', json_decode($images_str, true));
    }

    $images = [];
    if (!empty($images_str) && is_string($images_str)) {
        $images = explode(',', $images_str);
        foreach ($images as $_ => &$photo) {
            if (strpos($photo, $str_pos) === false) {
                $photo = $url . $photo;
            }
        }
    }
    return $images;
}
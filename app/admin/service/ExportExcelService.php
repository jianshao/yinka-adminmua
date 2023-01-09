<?php

namespace app\admin\service;

use think\facade\Log;

class ExportExcelService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //数据导出
    public function export($data, $columns)
    {
        $string = implode(",", array_values($columns)) . "\n";

        foreach ($data as $item) {
            $arr = [];
            foreach ($columns as $column => $_) {
                $arr[$column] = isset($item[$column]) && !empty($item[$column]) ? $item[$column] : 0;
            }
            $column_str = implode(',', array_values($arr));
            $string .= $column_str . "\n";
        }

        $filename = date('Ymd') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    public function exportBigData($query, $columns)
    {
        set_time_limit(120);
        $columns_desc = array_values($columns);

        $fileName = date('YmdHis') . '.csv';
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'a'); //打开output流
        // mb_convert_variables('GBK', 'UTF-8', $columns_desc);
        fputcsv($fp, $columns_desc); //将数据格式化为CSV格式并写入到output流中

        $clone = clone $query;
        $totalCount = $query->count(); //从数据总量

        $perSize = 1000; //每次查询的条数
        $pages = ceil($totalCount / $perSize);
        for ($i = 0; $i <= $pages; $i++) {
            $offset = $perSize * $i;
            $list = $clone->limit($offset, $perSize)->select()->toArray();

            foreach ($list as $item) {
                $rowData = [];
                foreach ($columns as $column => $_) {
                    $rowData[$column] = isset($item[$column]) && !empty($item[$column]) ? (String) $item[$column] : '';
                }
                $res = array_values($rowData);
                // mb_convert_variables('GBK', 'UTF-8', $res);

                fputcsv($fp, $rowData);
            }
            unset($list); //释放变量的内存
            //刷新输出缓冲到浏览器
            ob_flush();
            flush(); //必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        }
        fclose($fp);
        exit();
    }

    public function exportBigDataByFn($query, $columns, $function)
    {
        set_time_limit(120);
        $columns_desc = array_values($columns);

        $fileName = date('YmdHis') . '.csv';
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'a'); //打开output流
        // mb_convert_variables('GBK', 'UTF-8', $columns_desc);
        fputcsv($fp, $columns_desc); //将数据格式化为CSV格式并写入到output流中

        $clone = clone $query;
        $totalCount = $query->count(); //从数据总量

        $perSize = 1000; //每次查询的条数
        $pages = ceil($totalCount / $perSize);
        for ($i = 0; $i <= $pages; $i++) {
            $offset = $perSize * $i;
            $list = $clone->limit($offset, $perSize)->select()->toArray();

            foreach ($list as $item) {
                $new_item = $function($item);

                $rowData = [];
                foreach ($columns as $column => $_) {
                    $rowData[$column] = isset($new_item[$column]) && !empty($new_item[$column]) ? (String) $new_item[$column] : '';
                }

                $res = array_values($rowData);
                // mb_convert_variables('GBK', 'UTF-8', $res);

                fputcsv($fp, $rowData);
            }
            unset($list); //释放变量的内存
            //刷新输出缓冲到浏览器
            ob_flush();
            flush(); //必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        }
        fclose($fp);
        exit();
    }

    public function dataExpormetCsvByFormat($db, $columns, $callback = '')
    {
        set_time_limit(0);
        $fileName = date('YmdHis') . mt_rand(1000, 9999) . '.csv';
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'a'); //打开output流
        mb_convert_variables('GBK', 'UTF-8', $columns);
        $columnsHeader = array_values($columns);
        fputcsv($fp,$columnsHeader); //将数据格式化为CSV格式并写入到output流中
        $pageLimit = 2000;
        $page = 1;
        $res = $db->page($page, $pageLimit)->select()->toArray();
        while ($res) {
            if(!empty($res) && is_callable($callback)){
                $res = $callback($res);
            }
            foreach ($res as $items) {
                $rowData = [];
                foreach($columns as $columnkey=>$columnItem){
                    $rowData[] = isset($items[$columnkey]) ? $items[$columnkey] : '';
                }
                mb_convert_variables('GBK', 'UTF-8', $rowData);
                fputcsv($fp, $rowData);
            }
            $page++;
            $res = $db->page($page, $pageLimit)->select()->toArray();
            //刷新输出缓冲到浏览器
            ob_flush();
            flush(); //必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
        }
        fclose($fp);
        exit();
    }




    public function dataExportModlesCsvByFormat($dbs, $columns, $callback = '')
    {
        set_time_limit(0);
        $fileName = date('YmdHis') . mt_rand(1000, 9999) . '.csv';
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'a'); //打开output流
        mb_convert_variables('GBK', 'UTF-8', $columns);
        $columnsHeader = array_values($columns);
        fputcsv($fp,$columnsHeader); //将数据格式化为CSV格式并写入到output流中
        foreach($dbs as $db){
            $pageLimit = 2000;
            $page = 1;
            $res = $db->page($page, $pageLimit)->select()->toArray();
            if(!empty($res) && is_callable($callback)){
                $res = $callback($res);
            }
            while ($res) {
                foreach ($res as $items) {
                    $rowData = [];
                    foreach($columns as $columnkey=>$columnItem){
                        $rowData[] = isset($items[$columnkey]) ? $items[$columnkey] : '';
                    }
                    mb_convert_variables('GBK', 'UTF-8', $rowData);
                    fputcsv($fp, $rowData);
                }
                $page++;
                $res = $db->page($page, $pageLimit)->select()->toArray();
                if(!empty($res) && is_callable($callback)){
                    $res = $callback($res);
                }
                //刷新输出缓冲到浏览器
                ob_flush();
                flush(); //必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
            }
        }

        fclose($fp);
        exit();
    }



    //使用es里面的数据做导出
    public function dataElasExportCsv($elasModel, $columns, $callback = '')
    {
        set_time_limit(0);
        $fileName = date('YmdHis') . mt_rand(1000, 9999) . '.csv';
        //设置好告诉浏览器要下载excel文件的headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        $fp = fopen('php://output', 'w'); //打开output流
        mb_convert_variables('GBK', 'UTF-8',$columns);
        fputcsv($fp, array_values($columns)); //将数据格式化为CSV格式并写入到output流中
        $pageLimit = 10000;
        $page = 1;
        $es_from = ($page - 1) * $pageLimit;
        $sourceModel = $elasModel->page($es_from,$pageLimit);
        try {
            $selectResult = $sourceModel->select();
            while (isset($selectResult['data']) && count($selectResult['data']) > 1 ) {
                $res = $selectResult['data'];
                if(!empty($res) && is_callable($callback)){
                    $res = $callback($res);
                }
                $rowData=[];
                foreach ($res as $items) {
                    foreach($columns as $columnkey=>$columnItem){
                        $rowData[$columnkey] = isset($items[$columnkey]) ? $items[$columnkey] : '';
                    }
                    mb_convert_variables('GBK', 'UTF-8', $rowData);
                    fputcsv($fp, $rowData);
                }

                //刷新输出缓冲到浏览器
                ob_flush();
                flush(); //必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
                $page++;
                $sourceModel->page(($page - 1) * $pageLimit,$pageLimit);
                $selectResult = $sourceModel->select();
            }
        } catch (\Throwable $e) {
            fclose($fp);
            exit();
        } finally {
            fclose($fp);
        }

    }




}

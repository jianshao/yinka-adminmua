<?php
/**
 * User: baixin
 * Date: 2021/8/13
 * Time: 下午2:32
 */
namespace  app\common;

class ParseContainer{


    //json解析器

    public static $instance = NULL;

    public static function getInstance(){

        if(self::$instance == null){
            self::$instance = new self();
        }
        return  self::$instance;
    }



    const  CLASS_MAP = [
        //'register',
        'login',
        'charge',
        'agentcharge',
        //'firstcharge',
        'sendGift',
        'receiveGift',
        'activity',
        'sendRed',
        'receiveRed',
        'returnRed',
        'diamond',
        'withdraw',
    ];



    const CHANNEL = [
        'appStore',
        'Oppo',
        'GW',
        'HuaWei',
        'XiaoMi',
        'Vivo',
        'Q360',
        'YingYongBao',
        'Ali',
        'yidong2',
        'yidong1',
        'zhangyue',
        'MaiZu',
        'BaiDu',
        'MaXiaoEr',
        'KuaiShowDanDan',
        'WanDouJia',
        'KuaiShowDanDan01',
        'QQtf',
        'KuaiShowDanDan04',
        'BeiWanJLYQ01',
        'KuaiShowDanDan05',
        'KuaiShowDanDan06',
        'QingGe',
        'KuaiShowDanDan07',
        'Lenovo',
        'KuaiShowDanDan09',
        'KuaiShowDanDan08',
        'KuaiShowDanDan12',
        'KuaiShowDanDan13',
        'KuaiShowDanDan02',
        'KuaiShowDanDan03',
        'KuaiShowDanDan10',
        'KuaiShowDanDan15',
        'YingYongB',
        'BeiWanKuaiShou01',
        'KuaiShowDanDan11',
        'KuaiShowDanDan14',
        'KuaiShowDanDan16',
        'KuaiShowDanDan20',
        'KuaiShowDanDan23',
        'LingDongTS',
        'KuaiShowDanDan18',
        'LingDongBZ01',
        'LingDongBZ02',
        'LingDongBZ04',
        'BeiWanKuaiShou02',
        'LingDongBZ03',
        'LingDongBZ05',
        'KuaiShowDanDan26',
        'KuaiShowDanDan32',
        'KuaiShowDanDan44',
        'KuaiShowDanDan42',
        'KuaiShowDanDan41',
        'ZhiXiangKS01',
        'KuaiShowDanDan25',
        'KuaiShowDanDan27',
        'KuaiShowDanDan37',
        'KuaiShowDanDan38',
        'ZhiXiangKS13',
        'ZhiXiangKS14',
        'ZhiXiangKS15',
        'ZhiXiangKS04',
        'ZhiXiangKS03',
        'KuaiShowDanDan39',
        'ZhiXiangKS05',
        'ZhiXiangKS17',
        'ZhiXiangKS16',
        'KuaiShowDanDan45',
        'ZhiXiangKS49',
        'ZhiXiangKS32',
        'ZhiXiangKS21',
        'ZhiXiangKS02',
        'ZhiXiangKS06',
        'ZhiXiangKS08',
        'ZhiXiangKS07',
        'ZhiXiangKS11',
        'ZhiXiangKS20',
        'ZhiXiangKS18',
        'KuaiShowDanDan28',
        'ZhiXiangKS12',
        'ZhiXiangKS19',
        'BeiWanKuaiShou04',
        'ZhiXiangKS40',
        'ZhiXiangKS39',
        'ZhiXiangKS37',
        'ZhiXiangKS42',
        'DeXuanZuiYouTS',
        'ZhiXiangKS09',
        'DeXuanZuiYou01',
        'DeXuanZuiYou08',
        'DeXuanZuiYou05',
        'DeXuanZuiYou09',
        'ZhiXiangKS50',
        'TuSiKuaiShou02',
        'TuSiKuaiShou06',
        'TuSiKuaiShou10',
        'PinZhongKuaiShouTS',
        'DeXuanZuiYou04',
        'ZhiXiangKS48',
        'DeXuanZuiYou02',
        'DeXuanZuiYou06',
        'ZhiXiangKS43',
        'ZhiXiangKS44',
        'PengLaiYingKuaiShou01',
        'PengLaiYingKuaiShou08',
        'PengLaiYingKuaiShou03',
        'DeXuanZuiYou07',
        'DeXuanZuiYou03',
        'ZhiXiangKS31',
        'ZhiXiangKS38',
        'ZhiXiangKS36',
        'ZhiXiangKS10',
        'ZhiXiangKS46',
        'ZhiXiangKS41',
        'PinZhongKuaiShouKS',
        'ZhiXiangKS25',
        'ZhiXiangKS24',
        'ZhiXiangKS45',
        'ZhiXiangKS23',
        'ZhiXiangKS33',
        'ZhiXiangKS22',
        'ZhiXiangKS',
        'ShuangJiKuaiShou',
        'TuSiKuaiShou01',
        'TouFang',
        'ZhaoHui',
        'Beta',
    ];



    /**
     * 解析充值数据中的amount 返回和
     * @param $data
     * @param array $returnData
     * @return float|int
     */
    public function parseJsonDataAmount($data, &$returnData = [])
    {
        if(empty($data)){
            return 0;
        }

        foreach ($data as $key => $item) {
            if ($key == 'amount' && !is_array($item)) {
                $returnData[] = $item;
            } elseif (is_array($item)) {
                $this->parseJsonDataAmount($item, $returnData);
            }
        }
        return array_sum($returnData);
    }


    /**
     * @param $key
     * @param $data
     * @return float|int
     */
    public  function parseJsonDataAmountBykey($key,$data){
        $parseData  = $data[$key] ?? '';
        return $this->parseJsonDataAmount($parseData);
    }




    /**
     * 递归处理数组的key int -> float
     * @param $arr
     * @return array
     */
    public function formatArrKeyToString($arr)
    {
        $res = array();
        if (is_array($arr)) {
            foreach ($arr as $key => $item) {
                $key = is_int($key) ? sprintf("%.2f", $key) : $key;
                $res[$key] = $this->formatArrKeyToString($item);
            }
        } else {
            return $arr;
        }

        return $res;
    }



    /**
     * 数组的key float->int 原貌还原
     * sum里面的value count amount
     * @param $arr
     * @return array
     */

    public function formatterKeyAction($arr)
    {
        $res = array();
        if (is_array($arr)) {
            foreach ($arr as $key => $item) {
                $key = floatval($key) ? intval($key) : $key;
                $key = $key == '0.00' ? 0 :$key; //解决0转化成0.00无法用floatval
                if(($key == "count" || $key=='value' || $key=='amount' || $key=='v2.5' || $key=="v3.5")  && is_array($item)){
                    $res[$key] = array_sum($item);
                }else{
                    $res[$key] = $this->formatterKeyAction($item);
                }

            }
        }else{
            return $arr;
        }

        return $res;
    }




    //解析行为 返回合并好的数据
    public function anslysisBehavior($source,$targe,$except=["register","firstcharge"]){
        foreach($except as $item){
            if(isset($source[$item]) && isset($targe[$item])){
                unset($targe[$item]);
            }
        }
        $data = array_merge_recursive($this->formatArrKeyToString($source), $this->formatArrKeyToString($targe));
        return $this->formatterKeyAction($data);
    }

}

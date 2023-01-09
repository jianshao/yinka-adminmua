<?php

namespace app\admin\service;

use Elasticsearch\ClientBuilder;
use think\facade\Log;

class ElasticsearchService
{
    protected static $instance;
    public $where = [];
    private $client = null;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        self::$instance->where=[];
        return self::$instance;
    }

    private function __construct()
    {
        $params = config("config.es_config");
        $this->client = ClientBuilder::create()->setHosts($params)->build();
    }


    //单条写入
    public function bulk($index, $data)
    {
        $body = [];
        foreach ($data as $item) {
            $body[] = ['index' => ['_index' => $index, '_id' => $item['id']]];
            $body[] = $item;
        }
        $params = [
            'index' => $index,
            'body' => $body,
        ];
        try {
            $response = $this->client->bulk($params);
            return $response;
        } catch (\Throwable $e) {
            Log::info("elasticsearchservice:bulk" . $e->getMessage());
            throw $e;
        }
    }


    //多条写入
    public function bulkData($index, $body)
    {
        $params = [
            'index' => $index,
            'body' => $body,
        ];

        try {
            $response = $this->client->bulk($params);
            return $response;
        } catch (\Throwable $e) {
            Log::info("elasticsearchservice:bulk" . $e->getMessage());
            throw $e;
        }
    }

    //根据条件来读取数据
    public function search($params)
    {
        try {
            $documentRes = [];
            Log::info("elasticsearchservice:search" . json_encode($params));
            $response = $this->client->search($params);
            $total = $response['hits']['total']['value'] ?? 0;
            $result = $response['hits']['hits'] ?? [];
            $source = array_column($result, "_source");
            $documentRes = ['data' => $source, "total" => $total];
            return $documentRes;
        } catch (\Throwable $e) {
            throw $e;
            Log::info("elasticsearchservice:search" . $e->getMessage());
        }

    }

    public function searchWhere($index, $size = 1000)
    {
        $esparams = [
            'index' => $index,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [],
                        'filter' => [],
                        'should' => [],
                    ],
                ],
                'from' => 0,
                'size' => $size,
                'track_total_hits' => true,
            ],
        ];

        return $esparams;
    }




    //根据条件来聚合数据
    public function searchAggs($params)
    {
        try {
            Log::info("elasticsearchservice:search" . json_encode($params));
            $response = $this->client->search($params);
            return $response;
        } catch (\Throwable $e) {
            throw $e;
            Log::info("elasticsearchservice:search" . $e->getMessage());
        }
    }



    public  function must($params,$matchType='term'){
        $this->where['body']['query']['bool']['must'][] = [$matchType=>$params];
        return $this;
    }

    public  function filter($params){
        $this->where['body']['query']['bool']['filter'][] = $params;
        return $this;
    }


    public function page($offset,$limit){
        $this->where['body']['from'] = $offset;
        $this->where['body']['size'] = $limit;
        return $this;
    }


    public function order($field,$method='asc'){
        $this->where['body']['sort'] = [$field => ["order" => $method]];
        return $this;
    }

    public function fields($fields){
        $this->where['body']['_source'] = ["includes" =>$fields ];
        return $this;
    }

    public function select(){
        if(!isset($this->where['body']['query'])){
            $this->where['body']['query'] = [
                'bool' => [
                    'must' => [],
                    'filter' => [],
                    'should' => [],
                ],
            ];
        }

        if(!isset($this->where['body']['track_total_hits'])){
            $this->where['body']['track_total_hits'] = true;
        }
        return  $this->search($this->where);
    }


    public function index($index){
        $this->where['index'] = $index;
        return $this;
    }



    public  function mustNot($params,$matchType='term'){
        $this->where['body']['query']['bool']['must_not'][] = [$matchType=>$params];
        return $this;
    }

    public  function range($field,$params){
        $this->where['body']['query']['bool']['filter']['range'] = [$field=>$params];
        return $this;
    }


    public  function should($paramsMul,$matchType='term'){
        foreach($paramsMul as $params){
            $condition['bool']['must'] = [];
            foreach($params  as $key=>$item){
                $condition['bool']['must'][] = [$matchType=>[$key=>$item]];
            }
            $this->where['body']['query']['bool']['should'][]=$condition;
        }

        return $this;
    }


    public function getWhere(){
        return $this->where;
    }


}
<?php


namespace app\core\model;


use think\Model;

class BaseModelIds
{
    public $model = null;
    public $list = [];

    public function __construct(Model $model, $list) {
        $this->model = $model;
        $this->list = $list;
    }

    /**
     * @return BaseModel
     */
    public function getModel(){
        assert($this->model instanceof BaseModel);
        return $this->model;
    }

    public function getList(){
        return $this->list;
    }
}
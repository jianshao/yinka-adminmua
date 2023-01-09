<?php


namespace app\admin\controller;


class ErrorController
{
    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
        return 'error request!';
    }
}
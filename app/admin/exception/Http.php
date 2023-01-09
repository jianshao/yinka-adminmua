<?php


namespace app\admin\exception;


use HttpException;
use think\exception\ErrorException;
use think\exception\Handle;
use think\exception\ValidateException;
use think\facade\Log;
use think\Response;
use Throwable;

class Http extends Handle
{
    /** @noinspection PhpHierarchyChecksInspection */
    /**
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            return json($e->getError(), 422);
        }

        // 请求异常
        if ($e instanceof HttpException && $request->isAjax()) {
            return response($e->getMessage(), $e->getStatusCode());
        }


        if ($e instanceof ErrorException || $e instanceof ParseError || $e instanceof TypeError)  {
            $this->reportException($request, $e);
            return response($e->getMessage(),500);
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }

    protected function renderHttpException(\think\exception\HttpException $e): Response
    {
        $status   = $e->getStatusCode();
        $template = $this->app->config->get('app.http_exception_template');

        if (!$this->app->isDebug() && !empty($template[$status])) {
            return Response::create($template[$status], 'view', $status)->assign(['e' => $e]);
        } else {
            return $this->convertExceptionToResponse($e);
        }
    }

    //记录exception到日志
    private function reportException($request, Throwable $e):void {
        $errorStr = "url:".$request->host().$request->url()."\n";
        $errorStr .= "code:".$e->getCode()."\n";
        $errorStr .= "file:".$e->getFile()."\n";
        $errorStr .= "line:".$e->getLine()."\n";
        $errorStr .= "message:".$e->getMessage()."\n";
        //$errorStr .=  $e->getTraceAsString();
        Log::error($errorStr);
    }
}
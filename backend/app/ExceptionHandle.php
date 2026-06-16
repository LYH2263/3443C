<?php

namespace app;

use app\log\AppLogger;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

class ExceptionHandle extends Handle
{
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    public function report(Throwable $exception): void
    {
        if (!$this->isIgnoreReport($exception)) {
            $request = request();
            $context = [
                'request_id'    => $request->request_id ?? ($GLOBALS['_request_id'] ?? ''),
                'http_method'   => $request ? $request->method() : '',
                'http_url'      => $request ? $request->url() : '',
                'http_ip'       => $request ? $request->ip() : '',
                'user_id'       => $request->uid ?? null,
                'username'      => $request->username ?? null,
            ];

            if ($exception instanceof \PDOException) {
                AppLogger::exception($exception, 'database_exception', $context);
            } else {
                AppLogger::exception($exception, 'system_exception', $context);
            }
        }

        parent::report($exception);
    }

    public function render($request, Throwable $e): Response
    {
        $requestId = $request->request_id ?? ($GLOBALS['_request_id'] ?? '');

        if ($e instanceof ValidateException) {
            return json([
                'code'        => 422,
                'message'     => $e->getError(),
                'data'        => [],
                'request_id'  => $requestId,
            ]);
        }

        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $message = $this->getHttpMessage($statusCode);
            return json([
                'code'        => $statusCode,
                'message'     => $message,
                'data'        => [],
                'request_id'  => $requestId,
            ], $statusCode);
        }

        if ($e instanceof ModelNotFoundException || $e instanceof DataNotFoundException) {
            return json([
                'code'        => 404,
                'message'     => '数据不存在',
                'data'        => [],
                'request_id'  => $requestId,
            ], 404);
        }

        if ($e instanceof \PDOException) {
            $errorCode = $e->getCode();
            if ($errorCode == 23000) {
                return json([
                    'code'        => 400,
                    'message'     => '数据存在关联，无法执行此操作',
                    'data'        => [],
                    'request_id'  => $requestId,
                ]);
            }
            return json([
                'code'        => 500,
                'message'     => '数据库操作异常，请稍后重试',
                'data'        => [],
                'request_id'  => $requestId,
            ]);
        }

        return json([
            'code'        => 500,
            'message'     => '服务器内部错误，请稍后重试',
            'data'        => [],
            'request_id'  => $requestId,
        ]);
    }

    private function getHttpMessage(int $code): string
    {
        $messages = [
            400 => '请求参数错误',
            401 => '未授权，请先登录',
            403 => '没有权限执行此操作',
            404 => '请求的资源不存在',
            405 => '不支持的请求方法',
            500 => '服务器内部错误',
        ];
        return $messages[$code] ?? '未知错误';
    }
}

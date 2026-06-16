<?php

namespace app\middleware;

use think\Request;
use think\Response;

class RequestIdMiddleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?: $this->generateRequestId();

        $request->request_id = $requestId;

        $GLOBALS['_request_id'] = $requestId;

        $response = $next($request);

        $response->header([
            'X-Request-Id' => $requestId,
            'Access-Control-Expose-Headers' => 'X-Request-Id',
        ]);

        return $response;
    }

    private function generateRequestId(): string
    {
        return 'req_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 16);
    }
}

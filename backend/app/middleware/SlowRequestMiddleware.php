<?php

namespace app\middleware;

use app\log\AppLogger;
use think\Request;
use think\Response;

class SlowRequestMiddleware
{
    protected $slowThreshold = 1000;

    public function handle(Request $request, \Closure $next): Response
    {
        $startTime = microtime(true);
        $GLOBALS['_request_start_time'] = $startTime;
        $GLOBALS['_slow_threshold_ms'] = $this->slowThreshold;

        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        $context = [
            'http_method'   => $request->method(),
            'http_url'      => $request->url(),
            'http_status'   => $response->getCode(),
            'duration_ms'   => $durationMs,
            'is_slow'       => $durationMs > $this->slowThreshold,
            'controller'    => $request->controller() ?? '',
            'action'        => $request->action() ?? '',
        ];

        if ($durationMs > $this->slowThreshold) {
            AppLogger::slowRequest(
                $request->controller() . '@' . $request->action(),
                $durationMs,
                $context
            );
        } else {
            AppLogger::info(
                'request_complete',
                '',
                $context
            );
        }

        return $response;
    }
}

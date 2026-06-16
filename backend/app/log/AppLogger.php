<?php

namespace app\log;

use think\facade\Log;

class AppLogger
{
    private static function buildContext(string $action, array $context = []): array
    {
        $startTime = $GLOBALS['_request_start_time'] ?? null;
        $duration = $startTime ? round((microtime(true) - $startTime) * 1000, 2) : null;

        return array_merge([
            'action'   => $action,
            'duration_ms' => $duration,
        ], $context);
    }

    public static function debug(string $action, string $message = '', array $context = []): void
    {
        Log::debug($message, self::buildContext($action, $context));
    }

    public static function info(string $action, string $message = '', array $context = []): void
    {
        Log::info($message, self::buildContext($action, $context));
    }

    public static function notice(string $action, string $message = '', array $context = []): void
    {
        Log::notice($message, self::buildContext($action, $context));
    }

    public static function warning(string $action, string $message = '', array $context = []): void
    {
        Log::warning($message, self::buildContext($action, $context));
    }

    public static function error(string $action, string $message = '', array $context = []): void
    {
        Log::error($message, self::buildContext($action, $context));
    }

    public static function critical(string $action, string $message = '', array $context = []): void
    {
        Log::critical($message, self::buildContext($action, $context));
    }

    public static function alert(string $action, string $message = '', array $context = []): void
    {
        Log::alert($message, self::buildContext($action, $context));
    }

    public static function emergency(string $action, string $message = '', array $context = []): void
    {
        Log::emergency($message, self::buildContext($action, $context));
    }

    public static function slowRequest(string $action, float $durationMs, array $context = []): void
    {
        $context = array_merge([
            'action'       => $action,
            'duration_ms'  => $durationMs,
            'is_slow'      => true,
            'slow_threshold_ms' => $GLOBALS['_slow_threshold_ms'] ?? 1000,
        ], $context);

        Log::channel('slow')->warning('SLOW_REQUEST', $context);
        Log::warning('SLOW_REQUEST', $context);
    }

    public static function exception(\Throwable $e, string $action = 'exception', array $context = []): void
    {
        $trace = $e->getTraceAsString();
        $traceLines = explode("\n", $trace);
        $traceSummary = array_slice($traceLines, 0, 10);

        $context = array_merge([
            'action'        => $action,
            'is_exception'  => true,
            'exception'     => get_class($e),
            'message'       => $e->getMessage(),
            'file'          => $e->getFile(),
            'line'          => $e->getLine(),
            'code'          => $e->getCode(),
            'trace_summary' => $traceSummary,
        ], $context);

        Log::error('EXCEPTION', $context);
    }
}

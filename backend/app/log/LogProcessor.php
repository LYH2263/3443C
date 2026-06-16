<?php

namespace app\log;

class LogProcessor
{
    public function process(array $record): array
    {
        $record['request_id'] = $GLOBALS['_request_id'] ?? '';
        $record['timestamp'] = date('Y-m-d H:i:s');
        $record['microtime'] = microtime(true);

        if (!isset($record['context'])) {
            $record['context'] = [];
        }

        if (!is_array($record['context'])) {
            $record['context'] = ['message' => $record['context']];
        }

        if (function_exists('request') && ($req = request())) {
            if (!isset($record['context']['user_id'])) {
                $record['context']['user_id'] = $req->uid ?? null;
            }
            if (!isset($record['context']['username'])) {
                $record['context']['username'] = $req->username ?? null;
            }
            if (!isset($record['context']['role'])) {
                $record['context']['role'] = $req->role ?? null;
            }
            if (!isset($record['context']['ip'])) {
                $record['context']['ip'] = $req->ip();
            }
            if (!isset($record['context']['method'])) {
                $record['context']['method'] = $req->method();
            }
            if (!isset($record['context']['url'])) {
                $record['context']['url'] = $req->url();
            }
        }

        return $record;
    }
}

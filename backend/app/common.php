<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function get_current_request_id(): string
{
    $requestId = '';
    if (function_exists('request')) {
        $req = request();
        if ($req && isset($req->request_id)) {
            $requestId = $req->request_id;
        }
    }
    if (empty($requestId) && isset($GLOBALS['_request_id'])) {
        $requestId = $GLOBALS['_request_id'];
    }
    return $requestId;
}

function json_success($data = [], string $message = '操作成功', int $code = 200): \think\response\Json
{
    return json([
        'code'       => $code,
        'message'    => $message,
        'data'       => $data,
        'request_id' => get_current_request_id(),
    ]);
}

function json_error(string $message = '操作失败', int $code = 400, $data = []): \think\response\Json
{
    return json([
        'code'       => $code,
        'message'    => $message,
        'data'       => $data,
        'request_id' => get_current_request_id(),
    ]);
}

function create_token(array $payload): string
{
    $key = env('JWT_SECRET', 'flipbook_jwt_secret_key_2024');
    $payload['iat'] = time();
    $payload['exp'] = time() + 86400 * 7;
    return JWT::encode($payload, $key, 'HS256');
}

function verify_token(string $token): ?array
{
    try {
        $key = env('JWT_SECRET', 'flipbook_jwt_secret_key_2024');
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        return (array) $decoded;
    } catch (\Exception $e) {
        return null;
    }
}

function getRequestData(\think\Request $request): array
{
    $data = $request->post();
    if (empty($data)) {
        $input = $request->getContent();
        if (!empty($input)) {
            $decoded = json_decode($input, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
    }
    return $data;
}

function get_upload_url(string $path): string
{
    if (empty($path)) {
        return '';
    }
    if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
        return $path;
    }
    return '/uploads/' . ltrim($path, '/');
}

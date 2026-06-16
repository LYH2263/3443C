<?php

return [
    'alias'    => [
        'auth'       => \app\middleware\AuthMiddleware::class,
        'admin'      => \app\middleware\AdminMiddleware::class,
        'cors'       => \app\middleware\CorsMiddleware::class,
        'request_id' => \app\middleware\RequestIdMiddleware::class,
        'slow'       => \app\middleware\SlowRequestMiddleware::class,
    ],
    'priority' => [
        \app\middleware\RequestIdMiddleware::class,
        \app\middleware\CorsMiddleware::class,
        \app\middleware\SlowRequestMiddleware::class,
    ],
];

<?php

return [
    \app\middleware\RequestIdMiddleware::class,
    \app\middleware\CorsMiddleware::class,
    \app\middleware\SlowRequestMiddleware::class,
];

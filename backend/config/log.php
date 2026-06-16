<?php

return [
    'default'      => env('LOG_CHANNEL', 'file'),
    'channels'     => [
        'file' => [
            'type'           => 'File',
            'path'           => '',
            'single'         => false,
            'file_size'      => 20971520,
            'time_format'    => 'Y-m-d H:i:s.u',
            'apart_level'    => ['error', 'warning', 'info', 'debug'],
            'max_files'      => 30,
            'json'           => true,
            'format'         => '',
            'processor'      => [\app\log\LogProcessor::class],
        ],
        'slow' => [
            'type'           => 'File',
            'path'           => '',
            'single'         => false,
            'file_size'      => 20971520,
            'time_format'    => 'Y-m-d H:i:s.u',
            'apart_level'    => [],
            'max_files'      => 30,
            'json'           => true,
            'format'         => '',
            'processor'      => [\app\log\LogProcessor::class],
            'file_name'      => 'slow',
        ],
    ],
];

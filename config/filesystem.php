<?php

return [
    // 默认磁盘，原始为local，修改为public
    'default' => env('filesystem.driver', 'public'),
    // 磁盘列表
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => app()->getRuntimePath() . 'storage',
        ],
        // 使用public磁盘类型，而非local类型，便于在网站上可以正常访问该图片
        'public' => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/upload',
            // 磁盘路径对应的外部URL路径
            'url'        => '/upload',
            // 可见性
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
    ],
];

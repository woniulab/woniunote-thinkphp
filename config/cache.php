<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 设置默认缓存驱动，此处修改为redis
    'default' => env('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file' => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // 为redis缓存服务器指定连接信息
        'redis'   =>  [
            // 驱动方式
            'type'  => 'redis',
            // 服务器地址
            'host'  => '127.0.0.1',
            'port'  => '6379',
            'expire'    => 0    // 表示缓存数据永不过期
        ],
    ],
];

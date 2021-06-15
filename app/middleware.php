<?php
// 全局中间件定义文件
return [
    // 全局请求缓存
    // \think\middleware\CheckRequestCache::class,
    // 多语言加载
    // \think\middleware\LoadLangPack::class,
    // Session初始化，取消对该中间件的注释即可开户Session支持
     \think\middleware\SessionInit::class,
    // 加载自定义中间件Whole
    // \app\middleware\Whole::class
    // 注册自动登录中间件AutoLogin
    // \app\middleware\AutoLogin::class
];

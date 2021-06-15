<?php
use think\facade\Route;

// 定制404错误页面
Route::miss(function () {
    return view('../view/public/error_404.html');   // 出错后直接渲染一个错误页面
});

// 为首页Index控制器定义路由地址
Route::get('/', 'index/index')->middleware(app\middleware\AutoLogin::class);
Route::get('/page/:page', 'index/page')->middleware(app\middleware\AutoLogin::class);
Route::get('/type/:type-:page', 'index/type')->middleware(app\middleware\AutoLogin::class);
Route::get('/search/:page-:keyword', 'index/search');

// 为文章Article控制器定义路由地址
Route::get('/recommend', 'article/recommend');
Route::get('/article/:articleid$', 'article/read')->middleware(app\middleware\AutoLogin::class);
Route::post('/article/readall', 'article/readAll');
Route::get('/prepost', 'article/prepost');
Route::get('/article/edit/:articleid', 'article/preEdit');
Route::post('/article/edit$', 'article/edit');
Route::post('/article$', 'article/add')->middleware(app\middleware\PostCheck::class);

// 为用户User控制器定义路由地址
Route::get('/vcode', 'user/vcode');
Route::post('/ecode', 'user/ecode')->validate(['email'=>'require|email']);
Route::post('/user/reg', 'user/reg')->validate([
    'username'=>'require|email', 'password'=>'require|alphaNum|min:6'
]);
Route::post('/user/login', 'user/login');
Route::get('/user/logout', 'user/logout');
Route::get('/user/info', 'user/info');

// 为收藏文章定义路由地址
Route::post('/favorite', 'favorite/add');
Route::delete('/favorite/:articleid', 'favorite/cancel');

// 为评论定义路由地址
Route::post('/comment', 'comment/add')->middleware(app\middleware\CommentCheck::class);
Route::post('/reply', 'comment/reply')->middleware(app\middleware\CommentCheck::class);
Route::get('/comment/:articleid-:page', 'comment/paginate');
Route::post('/opinion', 'comment/opinion');
Route::delete('/comment/:commentid', 'comment/hide')->middleware(app\middleware\CommentCheck::class);

// 为UEditor定义路由地址
Route::rule('/uedit', 'ueditor/index');
Route::get('/uedit/test', 'ueditor/test');
Route::post('/doupload', 'ueditor/doUpload');
Route::get('/fileupload', function (){
    return view('../view/public/file_upload.html');
});
Route::get('/sms', 'ueditor/sms');

// 为用户中心定义路由地址
Route::get('/ucenter$', 'ucenter/index');
Route::get('/ucenter/favorite/:favoriteid', 'ucenter/favorite');
Route::get('/ucenter/post', 'ucenter/post');

// 为系统管理定义路由地址
// 由于地址前缀均为/system且请求类型均为Get，所以需要注意定义顺序
//Route::get('/system/article/:page', 'system/paginate');
//Route::get('/system', 'system/index');
// 也可以为路由地址添加 $，表示严格匹配，此时无需考虑路由顺序
Route::get('/system$', 'system/index');
Route::get('/system/article/:page$', 'system/paginate');
Route::get('/system/type/:type-:page', 'system/type');
Route::get('/system/search/:keyword', 'system/search');
Route::get('/system/article/hide/:articleid', 'system/hide');
Route::get('/system/article/recommend/:articleid', 'system/recommend');
Route::get('/system/article/check/:articleid', 'system/check');

// 查看phpinfo
Route::get('/phpinfo', function () {
    return phpinfo();
});
// 最简单的缓存使用，缓存时间为10秒钟
Route::get('/cache$', function () {
    return date('Y-m-d H:i:s');
})->cache(10);

use \think\facade\Cache;
Route::get('/redis', function (){
    // 使用门面直接调用
//    Cache::set('blogName','蜗牛笔记');
//    return Cache::get('blogName');

    // 使用Redis驱动类进行操作，并设置有效其为10秒
//    $redis = new think\cache\driver\Redis();
//    $redis->set('blogName', '蜗牛学院', 10);
//    return $redis->get('blogName');

    // 使用handler来实获取Cache实例进行操作
//    $handler = Cache::handler();
//    $handler->set('blogName', '蜗牛学院');
//    return $handler->get('blogName');

    // 也可以使用助手函数调用，也可以指定特定的时间过期
//    cache('blogName', '蜗牛学院', new DateTime('2020-03-24 15:21:05'));
//    return cache('blogName');
});

Route::get('/cache/1', 'cachedemo/test01');
Route::get('/cache/code', 'cachedemo/code');
Route::post('/cache/verify', 'cachedemo/verify');
Route::get('/cache/2', 'cachedemo/test02');
Route::get('/cache/3', 'cachedemo/test03');
Route::get('/cache/4', 'cachedemo/test04');
Route::get('/cache/5', 'cachedemo/test05');
Route::post('/cache/6', 'cachedemo/test06');
Route::get('/cache/7', 'cachedemo/test07');
Route::post('/cache/8', 'cachedemo/test08');
Route::get('/cache/9', 'cachedemo/test09');
Route::get('/cache/index', 'cachedemo/index');
Route::get('/cache/page/:page', 'cachedemo/paginate');

// 页面静态化处理
Route::get('/statics$', 'statics/index');
Route::get('/statics/all', 'statics/all');
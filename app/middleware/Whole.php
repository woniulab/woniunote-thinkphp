<?php
namespace app\middleware;

class Whole {
    // 中间件入口方法必须命名为handle，且必须传递两个固定参数
    public function handle($request, \Closure $next) {
        // 在中间件中判断判断当前用户是否登录，如果未登录则跳转到首页
        // 注意一定要判断当前URL地址不是首页地址，否则会出现死循环跳转
        if ($request->session('islogin') != 'true' && $request->url() != '/') {
            return redirect('/');
            // 也可以在进行拦截处理后，返回一个标准的Response页面
            //  return response('你还没有登录，无法访问该接口.');
            // 或者是直接在中间件里面渲染一个视图页面
            //  return view('../view/index/index.html');
        }

        // 如果未满足拦截条件，则正常将请求传输给路由地址进行处理
        return $next($request);
    }
}
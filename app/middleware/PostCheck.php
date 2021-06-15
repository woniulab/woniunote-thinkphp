<?php
namespace app\middleware;
use app\model\Users;

class PostCheck {
    public function handle($request, \Closure $next) {
        // 如果用户未登录，则不能发布文章
        if (session('islogin') != 'true') {
            // 中间件必须返回一个Response类型的对象
            return response('perm-denied');
        }
//        else {
//            // 如果用户已经登录，但是角色不对，也不能发布文章
//            $user = new Users();
//            $row = $user->find(session('userid'));
//            if ($row->role != 'editor') {
//                return response('perm-denied');
//            }
//        }
        return $next($request);
    }
}
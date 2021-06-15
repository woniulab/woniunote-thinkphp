<?php
namespace app\middleware;

class SystemCheck{
    public function handle($request, \Closure $next) {
        if ($request->session('islogin') != 'true' ||
            $request->session('role') != 'admin') {
            return response('perm-denied');
        }
        return $next($request);
    }
}
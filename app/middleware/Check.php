<?php
namespace app\middleware;

class Check{
    public function handle($request, \Closure $next) {
        if ($request->session('islogin') != 'true') {
            return redirect('/');
        }
        return $next($request);
    }
}
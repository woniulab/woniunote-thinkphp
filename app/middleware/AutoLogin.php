<?php
namespace app\middleware;
use app\model\Users;

class AutoLogin {
    public function handle($request, \Closure $next) {
        // 如果用户没有登录，才尝试自动登录，否则会导致频繁登录
        if (session('islogin') != 'true') {
            $url = $request->url();
            // 排除不作自动登录处理的接口地址，比如用户相关操作或静态资源
            if (!$this->contains($url, 'user') ||
                !$this->contains($url, '.jpg') ||
                !$this->contains($url, '.png') ||
                !$this->contains($url, '.js') ||
                !$this->contains($url, '.css')) {
                // 排除上述接口地址后，剩余接口均可以实现自动登录
                $username = cookie('username');
                $password = cookie('password');
                if ($username != null && $password != null) {
                    $user = new Users();
                    $rows = $user->findByUsername($username);
                    if (count($rows) == 1) {
                        if ($rows[0]['password'] == $password) {
                            session('islogin', 'true');
                            session('username', $username);
                            session('userid', $rows[0]['userid']);
                            session('role', $rows[0]['role']);
                            session('nickname', $rows[0]['nickname']);
                            // 代码与登录验证代码类似，但是此处不再写入Cookie
                        }
                    }
                }
            }
        }
        return $next($request);
    }

    // 判断某个字符串是否包含一个子字符串，用于对接口地址的判断
    // 由于在PHP中，false的值也是0，所以不能判断=0的情况
    // 所以在传递参数时，要确保$sub至少是$string的第二个位置及以后
    public function contains($string, $sub) {
        if (strpos($string, $sub) > 0) {
            return true;
        }
        return false;
    }
}
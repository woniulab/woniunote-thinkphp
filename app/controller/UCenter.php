<?php
namespace app\controller;
use app\BaseController;

class UCenter extends BaseController {
    // 用户中心入口，路由地址："/ucenter"，请求类型为Get
    public function index() {
        $favorite = new \app\model\Favorite();
        $result = $favorite->findMyFavorite();
        return view('index', ['result'=>$result]);
    }

    // 切换收藏状态，路由地址："/ucenter/favorite/:favoriteid"
    public function favorite($favoriteid) {
        $favorite = new \app\model\Favorite();
        $last = $favorite->switchFavorite($favoriteid);
        return $last;
    }

    // 用户投稿页面，路由地址："/ucenter/post"，请求类型为Get
    public function post() {
        if (session('islogin') != 'true') {
            return view('../view/public/no_perm.html');
        }
        return view('post');
    }
}
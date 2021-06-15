<?php
namespace app\controller;
use app\BaseController;
use think\Exception;

class Favorite extends BaseController {
    // 增加文章收藏，路由地址："/favorite"，请求类型为Post
    public function add() {
        $articleid = request()->post('articleid');
        if (session('islogin') != 'true') {
            return 'not-login';
        }
        else {
            try {
                $favorite = new \app\model\Favorite();
                $favorite->insertFavorite($articleid);
                return 'favorite-pass';
            }
            catch (Exception $e) {
                return 'favorite-fail';
            }
        }
    }

    // 取消文章收藏，路由地址："/favorite/:articleid"，请求类型为Delete
    public function cancel($articleid) {
        try {
            $favorite = new \app\model\Favorite();
            $favorite->cancelFavorite($articleid);
            return 'cancel-pass';
        }
        catch (Exception $e) {
            return 'cancel-fail';
        }
    }
}
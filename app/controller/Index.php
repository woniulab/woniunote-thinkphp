<?php
namespace app\controller;
use app\BaseController;
use app\model\Article;
use app\model\Users;

class Index extends BaseController {
    // 路由地址为： "/"，请求类型为Get
    /** 由于首页实现静态化，所以该接口代码先注释掉
    public function index() {
        $article = new Article();
        $result = $article->findLimitWithUser(0, 10);
        $total = ceil($article->getTotalCount()/10);

        // 先尝试读取Cookie，如果读取到则说明有保存登录信息，尝试完成自动登录
        /* 由于已经在中间件AutoLogin中实现自动登录，此处代码注释
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
        }/**

        return view("index", ['result'=>$result, 'total'=>$total, 'page'=>1]);
    }
    **/

    // 路由地址为： "/"，请求类型为Get，重构为静态化页面
    public function index() {
        // 先判断是否存在静态页面，如果存在则直接响应，否则正常查询数据库
        if (file_exists('../view/statics/index_1.html')) {
            return view('../view/statics/index_1.html');
        }

        // 如果不存在静态文件，则先查询数据，再渲染并生成一个静态文件
        $article = new Article();
        $result = $article->findLimitWithUser(0, 10);
        $total = ceil($article->getTotalCount()/10);

        $content = view("index", ['result'=>$result, 'total'=>$total, 'page'=>1])->getContent();
        file_put_contents('../view/statics/index_1.html', $content);
        return $content;    // 最后直接将$content响应给前端页面即可
    }

    // 分页接口，路由地址为： "/page/:page"，其中 :page为当前页码，请求类型为Get
    /**  由于首页实现静态化，所以该接口代码先注释掉
    public function page($page) {
        $pagesize = 10;
        $start = ($page - 1) * $pagesize;   // 根据当前页码定义数据的起始位置
        $article = new Article();
        // 获取分页查询后的结果
        $result = $article->findLimitWithUser($start, $pagesize);
        // 获取文章总数量，并计算分页总数
        $total = ceil($article->getTotalCount() / $pagesize);
        // 将数据写入给模板页面
        return view("index", ['result'=>$result, 'total'=>$total, 'page'=>$page]);
    }
    */

    // 分页接口静态化处理，路由地址为： "/page/:page"，请求类型为Get
    public function page($page) {
        // 根据参数page来判断当前分页面对应的静态文件是否存在
        if (file_exists("../view/statics/index_$page.html")) {
            return view("../view/statics/index_$page.html");
        }

        $pagesize = 10;
        $start = ($page - 1) * $pagesize;   // 根据当前页码定义数据的起始位置
        $article = new Article();
        // 获取分页查询后的结果
        $result = $article->findLimitWithUser($start, $pagesize);
        // 获取文章总数量，并计算分页总数
        $total = ceil($article->getTotalCount() / $pagesize);
        // 将数据写入给模板页面后生成静态文件
        $content = view("index", ['result'=>$result, 'total'=>$total, 'page'=>$page])->getContent();
        file_put_contents("../view/statics/index_$page.html", $content);
        return $content;
    }

    // 按类别搜索并进行分页，路由地址为："/type/:type-:page"，请求类型为Get
    public function type($type, $page) {
        $pagesize = 10;
        $start = ($page - 1) * $pagesize;   // 根据当前页码定义数据的起始位置
        $article = new Article();
        $result = $article->findByCategory($type, $start, $pagesize);
        $total = ceil($article->getCountByCategory($type) / $pagesize);
        return view("type", ['result'=>$result, 'total'=>$total,
                                     'page'=>$page, 'type'=>$type]);
    }

    // 根据关键字搜索标题，路由地址为："/search/:page-:keyword"，请求类型为Get
    public function search($page, $keyword) {
        // 对用户的搜索关键字规则进行校验
        $keyword = trim($keyword);
        if (strlen($keyword) < 1 || strpos($keyword, '%') || strlen($keyword)>10) {
            // 不满足搜索条件时，直接响应404页面
            return view('../view/public/error_404.html');
        }

        $pagesize = 10;
        $start = ($page - 1) * $pagesize;   // 根据当前页码定义数据的起始位置
        $article = new Article();
        $result = $article->findByHeadline($keyword, $start, $pagesize);
        $total = ceil($article->getCountByKeyword($keyword) / $pagesize);
        return view("search", ['result'=>$result, 'total'=>$total,
                                       'page'=>$page, 'keyword'=>$keyword]);
    }
}
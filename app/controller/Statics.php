<?php
namespace app\controller;
use app\BaseController;

class Statics extends BaseController {
    // 直接访问首页的静态页面，路由地址："/statics", 请求类型Get
    public function index() {
        return view('index');
    }

    // 将文章列表一次性静态化，路由地址："/statics/all，请求类型Get
    public function all() {
        $article = new \app\model\Article();
        // 先计算总页数，处理逻辑与分页接口一致
        $count = $article->getTotalCount();
        $total = ceil($count / 10);
        // 遍历每一页的内容，从数据库中查询出来，渲染到对应页面中
        for ($page=1; $page<=$total; $page++) {
            $start = ($page - 1) * 10;
            $result = $article->findLimitWithUser($start, 10);
            // 正常渲染index.html模板页面，但不响应给前端，而是将内容赋值给$content
            $content = view('../view/index/index.html', ['result'=>$result,
                           'total'=>$total, 'page'=>$page])->getContent();
            // 使用file_put_contents函数将$content的值根据页码写入HTML静态文件
            file_put_contents('../view/statics/index_' . $page . '.html', $content);
        }
        return '分页浏览静态化完成.';  // 最后简单响应给前端一个提示信息
    }
}



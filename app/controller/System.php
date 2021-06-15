<?php
namespace app\controller;
use app\BaseController;

class System extends BaseController {
    // 注册控制器中间件，以确保只有管理员可以操作系统管理接口
    protected $middleware = [\app\middleware\SystemCheck::class];

    // 系统管理入口，路由地址："/system"，请求类型为Get
    // 系统管理首页填充文章列表，并绘制分页栏
    public function index() {
        $article = new \app\model\Article();
        $result = $article->findAllExceptDraft(0, 50);
        $total = ceil($article->getCountExceptDraft() / 50);
        return view('index', ['result'=>$result, 'page'=>1, 'total'=>$total]);
    }

    // 为系统管理首页的文章列表进行分页查询
    // 路由地址为："/system/article/:page"，请求类型为Get
    public function paginate($page) {
        $start = ($page - 1) * 50;
        $article = new \app\model\Article();
        $result = $article->findAllExceptDraft($start, 50);
        $total = ceil($article->getCountExceptDraft() / 50);
        return view('index', ['result'=>$result, 'page'=>$page, 'total'=>$total]);
    }

    // 在后台进行文章类别搜索，路由地址："/system/type/:type-:page"
    public function type($type, $page) {
        $start = ($page - 1) * 50;
        $article = new \app\model\Article();
        if ($type == '0') {
            // 表示查找所有文章，与首页显示所有文章的数据一致
            $result = $article->findAllExceptDraft(0, 50);
            $total = ceil($article->getCountExceptDraft() / 50);
        }
        else {
            $result = $article->findByCategoryExceptDarft($type, $start, 50);
            $total = ceil($article->getCountByCategoryExceptDraft($type) / 50);
        }
        // 直接使用index模板页面进行渲染即可
        return view('index', ['result'=>$result, 'page'=>$page, 'total'=>$total]);
    }

    // 按标题模糊搜索，路由地址："/system/search/:keyword"
    public function search($keyword) {
        $article = new \app\model\Article();
        $result = $article->findByHeadlineExceptDraft($keyword);
        // 直接使用index模板页面，指定page=1，total=1，表示不分页
        return view('index', ['result'=>$result, 'page'=>1, 'total'=>1]);
    }

    // 文章隐藏切换，路由地址："/system/article/hide/:articleid"，请求类型为Get
    public function hide($articleid) {
        $article = new \app\model\Article();
        $last = $article->switchHidden($articleid);
        return $last;
    }

    // 文章推荐切换，路由地址："/system/article/recommend/:articleid"，请求类型为Get
    public function recommend($articleid) {
        $article = new \app\model\Article();
        $last = $article->switchRecommended($articleid);
        return $last;
    }

    // 文章审核切换，路由地址："/system/article/check/:articleid"，请求类型为Get
    public function check($articleid) {
        $article = new \app\model\Article();
        $last = $article->switchChecked($articleid);
        return $last;
    }
}
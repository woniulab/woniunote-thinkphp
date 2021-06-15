<?php
namespace app\controller;
use app\BaseController;
use app\model\Credit;
use app\model\Users;
use think\Exception;

class Article extends BaseController {
    // 将侧边栏文章推荐数据整合成JSON数据响应给前端
    // 路由地址为："/recommend"，不带参数，请求类型为Get
    public function recommend() {
        $article = new \app\model\Article();
        $last = $article->findLast9();
        $most = $article->findMost9();
        $recommended = $article->findRecommended9();
        $result = array();  // 创建数组并加入三类数据
        $result[] = $last;
        $result[] = $most;
        $result[] = $recommended;
        return json($result);   // 将JSON响应给前端
    }

    // 阅读文章接口，路由地址："/article/:articlid"，请求类型为Get
    /** 不用，备注于此
    public function read($articleid) {
        try {
            $article = new \app\model\Article();
            $result = $article->findByArticleId($articleid);
            if (count($result) != 1) {
                return view('../view/public/error_500.html');
            }
            // 更新阅读次数
            $article->updateReadCount($articleid);
            // 在view目录下新建article目录，并创建read.html模板页面
            return view('read', ['article'=>$result[0]]);
        }
        // 可能出现异常的情况就是文章ID不正确，则直接响应500页面
        catch (Exception $e) {
            return view('../view/public/error_500.html');
        }
    }
    */

    // 阅读文章接口，路由地址："/article/:articlid"，请求类型为Get
    // 如果文章需要消耗积分，则只预览前50%左右的内容，后续通过积分进行阅读
    public function read($articleid) {
        try {
            $article = new \app\model\Article();
            $result = $article->findByArticleId($articleid);
            if (count($result) != 1) {
                return view('../view/public/error_500.html');
            }
            // 更新阅读次数
            $article->updateReadCount($articleid);
            // 如果积分>0，则截取并处理文章内容
            $position = 0;
            $payed = true;  // 设置默认值，便于在渲染时确保$payed有值
            if ($result[0]['credit'] > 0) {
                // 如果用户没有消耗积分，才需要截取内容
                $credit = new Credit();
                $payed = $credit->checkPayedArticle($articleid);
                if (!$payed) {
                    $content = $result[0]['content'];
                    $temp = substr($content, 0, ceil(strlen($content) / 2));
                    $position = strrpos($temp, '</p>') + 4;
                    $result[0]['content'] = substr($content, 0, $position);
                }
            }
            // 检查该文章是否已经被用户收藏
            $favorite = new \app\model\Favorite();
            $favorited = $favorite->checkFavorite($articleid);
            // 获取当前文章的上一篇和下一篇
            $prev_next = $article->findPrevNextById($articleid);
            // 获取当前文章的评论(仅首页）
            $comment = new \app\model\Comment();
            $comment_list = $comment->getCommentReplyArray($articleid, 0, 10);
            // 获取当前文章的所有评论的总数量，以便于在前端显示分页栏
            $total = ceil($comment->getCommentCountByArticleId($articleid) / 10);
            // 在view目录下新建article目录，并创建read.html模板页面
            return view('read', ['article'=>$result[0], 'position'=>$position, 'payed'=>$payed,
                'favorited'=>$favorited, 'prev_next'=>$prev_next,
                'comment_list'=>$comment_list, 'total'=>$total]);
        }
            // 可能出现异常的情况就是文章ID不正确，则直接响应500页面
        catch (Exception $e) {
            return view('../view/public/error_500.html');
        }
    }

    // 阅读全文，路由地址："/article/readall"，请求类型为Post
    public function readAll() {
        $position = request()->post('position');
        $articleid = request()->post('articleid');
        $article = new \app\model\Article();
        $result = $article->findByArticleId($articleid);
        // 根据当前登录用户查询对应的积分，确认是否足够支付
        $user = new Users();
        $user_credit = $user->findByUserid(session('userid'))->credit;
        if ($user_credit < $result[0]['credit']) {
            return 'credit_lack';
        }
        // 读取剩余内容
        $content = substr($result[0]['content'], $position);
        // 扣除相应积分
        $credit = new Credit();
        $payed = $credit->checkPayedArticle($articleid);
        if (!$payed) {
            $credit->insertDetail('阅读文章', $articleid, -1 * $result['0']['credit']);
            // 同步更新用户表的剩余总积分
            $user->updateCredit(-1 * $result['0']['credit']);
        }
        return $content;
    }

    // 进入文章发布页面，路由地址为："/prepost"，请求类型为Get
    public function prepost() {
        // 如果用户没有登录，则无法进入该页面
        if (session('islogin') != true)
            return view('../view/public/no_perm.html');
        return view('post');
    }

    // 发布文章或处理草稿，路由地址为："/article"，请求类型为Post
    public function add() {
        $headline = request()->post('headline');
        $content = request()->post('content');
        // 使用 /d 将参数强制转换为整数
        $category = request()->post('type/d');
        $credit = request()->post('credit/d');
        $drafted = request()->post('drafted/d');
        $checked = request()->post('checked/d');
        $articleid = request()->post('articleid/d');

        $article = new \app\model\Article();    // 实例化Article模型类

        // 首先为文章生成缩略图，优先从内容中找，找不到则根据类型指定一张
        $urls = parse_image_url($content);
        if (count($urls) > 0) {
            $thumbname = generate_thumb($urls);
        }
        else {
            // 如果文章中没有图片，则根据文章类别指定缩略图
            $thumbname = $category . '.png';
        }

        // 用户未登录，则无法发布文章
        if (session('islogin') != 'true') {
            return 'perm-denied';
        }
        // 如果已经登录且角色是editor，则可以保存草稿也可以直接发布
        else if (session('role') == 'editor'){
            // 如果前端参数articleid为0，则表示是新增一篇文章
            if ($articleid == 0) {
                try {
                    $id = $article->insertArticle($category, $headline, $content, $thumbname, $credit, $drafted, $checked);
                    // 新增文章成功后，将已经静态化的文章列表页面全部删除，便于生成新的静态文件
                    // 使用scandir函数扫描statics目录，并返回所以文件列表（含.和..两个虚拟目录）
                    $list = scandir('../view/statics');
                    foreach ($list as $file) {
                        if ($file != '.' && $file != '..') {
                            unlink('../view/statics/' . $file);
                        }
                    }
                    return $id;
                } catch (Exception $e) {
                    return 'post-fail';
                }
            }
            // 如果articleid不为0，则表示是修改文章
            else {
                try {
                    $article->updateArticle($articleid, $category, $headline, $content, $thumbname, $credit, $drafted, $checked);
                    return $articleid;
                }
                catch (Exception $e) {
                    return 'post-fail';
                }
            }
        }
        // 如果用户已经登录，且角色是普通用户，则只能投稿
        // 注意为了防止用户直接绕开前端向本接口发数据，再确保checked=0才允许
        else if (session('role') == 'user' && $checked == 0) {
            try {
                $id = $article->insertArticle($category, $headline, $content, $thumbname, $credit, $drafted, $checked);
                return $id;
            } catch (Exception $e) {
                return 'post-fail';
            }
        }
        else {
            return 'perm-denied';
        }
    }

    // 编辑文章页面，路由地址："/article/edit/:articleid"，请求类型为Get
    public function preEdit($articleid) {
        $article = new \app\model\Article();
        $result = $article->find($articleid);
        return view('edit', ['article'=>$result]);
    }

    // 编辑文章，路由地址："/article/edit$"，请求类型为Post
    public function edit() {
        $articleid = request()->post('articleid/d');
        $headline = request()->post('headline');
        $content = request()->post('content');
        $category = request()->post('type/d');
        $credit = request()->post('credit/d');

        $article = new \app\model\Article();
        try {
            $article->updateArticle($articleid, $category, $headline, $content, $article->thumbnail, $content);
            return $articleid;
        }
        catch (Exception $e) {
            return 'edit-fail';
        }
    }
}
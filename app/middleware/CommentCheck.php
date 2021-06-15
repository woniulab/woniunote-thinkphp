<?php
namespace app\middleware;

class CommentCheck {
    public function handle($request, \Closure $next) {
        // 如果用户未登录，则不能发表、回复和隐藏评论
        if (session('islogin') != 'true') {
            // 中间件必须返回一个Response类型的对象
            return response('perm-denied');
        }
        /** 下述代码只是为了验证隐藏评论的权限，所以直接写在接口方法中即可，不需要经过中间件
        else {
            // 如果已经登录，则有权发表和回复评论，但还需进一步验证隐藏权限
            $url = request()->url();
            $method = request()->method();
            // 根据正则表达式判断当前请求的地址是否匹配 /comment/19 格式
            // 如果匹配成功并且请求类型为delete，则说明是隐藏评论的请求
            if (preg_match('/^\/comment\/\d+$/', $url) &&
                strtolower($method) == 'delete') {
                $commentid = request()->param('commentid');
                $article = new \app\model\Article();
                $comment = new \app\model\Comment();
                // 根据评论关联的articleid和userid找到对应的文章作者和评论者
                $row = $comment->find($commentid);
                $articleid = $row->articleid;
                $commenterid = $row->userid;
                $editorid = $article->find($articleid)->userid;
                $userid = session('userid');
                // 如果不是管理员，不是文章作者也不是评论者，则无法隐藏评论
                if (session('role') != 'admin' && $editorid != $userid
                    && $commenterid != $userid) {
                    return response('perm-denied');
                    // 当然也可以直接渲染一个无权限的提示页面，如：
                    // return view('../view/public/no_perm.html');
                }
            }
        }
        */
        return $next($request);
    }
}
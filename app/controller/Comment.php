<?php
namespace app\controller;
use app\BaseController;
use app\model\Credit;
use app\model\Opinion;
use app\model\Users;
use think\Exception;

class Comment extends BaseController {
    // 新增评论，路由地址："/comment"，请求类型为Post
    public function add() {
        $articleid = request()->post('articleid');
        $content = trim(request()->post('content'));

        // 如果评论的字数低于5个或多于1000个，均视为不合法
        // 此处也可使用路由验证器代替，但是路由验证器出错会返回500错误
        if (strlen($content) < 5 || strlen($content) > 1000) {
            return 'content-invalid';
        }

        $comment = new \app\model\Comment();
        $limited = $comment->checkLimitPerDay();
        if (!$limited) {    // 没有超出限制才能发表评论
            try {
                $comment->insertComment($articleid, $content);
                // 评论成功后，同步更新credit表明细、users表积分和article表回复数
                $credit = new Credit();
                $credit->insertDetail('添加评论', $articleid, 2);
                $user = new Users();
                $user->updateCredit(2);
                $article = new \app\model\Article();
                $article->updateReplyCount($articleid);
                return 'add-pass';
            }
            catch (Exception $e) {
                $e->getTrace();
                return 'add-fail';
            }
        }
        else {
            return 'add-limit';
        }
    }

    // 回复评论，路由地址："/reply"，请求类型为Post
    public function reply() {
        $articleid = request()->post('articleid');
        $commentid = request()->post('commentid');
        $content = trim(request()->post('content'));

        // 如果评论的字数低于5个或多于1000个，均视为不合法
        // 此处也可使用路由验证器代替，但是路由验证器出错会返回500错误
        if (strlen($content) < 5 || strlen($content) > 1000) {
            return 'content-invalid';
        }

        $comment = new \app\model\Comment();
        $limited = $comment->checkLimitPerDay();
        if (!$limited) {    // 没有超出限制才能发表评论
            try {
                $comment->insertReply($articleid, $commentid, $content);
                // 评论成功后，同步更新credit表明细、users表积分和article表回复数
                $credit = new Credit();
                $credit->insertDetail('回复评论', $articleid, 2);
                $user = new Users();
                $user->updateCredit(2);
                $article = new \app\model\Article();
                $article->updateReplyCount($articleid);
                return 'reply-pass';
            }
            catch (Exception $e) {
                $e->getTrace();
                return 'reply-fail';
            }
        }
        else {
            return 'reply-limit';
        }
    }

    // 为了使用Ajax分页，特创建此接口作为演示
    // 由于分页栏已经完成渲染，此接口仅根据前端的页码请求后台对应数据即可
    // 路由地址为："/comment/:articleid-:page"
    public function paginate($articleid, $page) {
        $start = ($page - 1) * 10;
        $comment = new \app\model\Comment();
        $result = $comment->getCommentReplyArray($articleid, $start, 10);
        return json($result);
    }

    // 用户点赞，路由地址为："/opinion"，请求类型为Post
    public function opinion() {
        $commentid = request()->post('commentid');
        $type = request()->post('type');
        // 判断是否已经点赞
        $opinion = new Opinion();
        $checked = $opinion->checkOpinion($commentid);
        if ($checked) {
            return 'already-opinion';   // 已经点赞，不能再次点赞
        }
        else {
            $opinion->insertOpinion($commentid, $type);
            $comment = new \app\model\Comment();
            $comment->updateAgreeOpposeCount($commentid, $type);
            return 'opinion-pass';
        }
    }

    // 隐藏评论，路由地址为："/comment/:commentid"，请求类型为Delete
    public function hide($commentid) {
        $article = new \app\model\Article();
        $comment = new \app\model\Comment();

        // 根据评论关联的articleid和userid找到对应的文章作者和评论者
        $row = $comment->find($commentid);
        $articleid = $row->articleid;
        $commenterid = $row->userid;
        $editorid = $article->find($articleid)->userid;

        $userid = session('userid');
        // 如果当前登录用户不是管理员，不是文章作者也不是评论者，则无法隐藏评论
        if (session('role') != 'admin' && $editorid != $userid && $commenterid != $userid) {
            return 'perm-denied';
        }
        $result = $comment->hideComment($commentid);
        if ($result == 'done') {
            return 'hide-pass';
        }
        else {
            return 'hide-limit';
        }
    }
}
<?php
namespace app\model;
use think\Model;

class Comment extends Model {
    protected $pk = 'commentid';

    // 新增一条评论
    public function insertComment($articleid, $content) {
        $now = date('Y-m-d H:i:s');
        $this->userid = session('userid');
        $this->articleid = $articleid;
        $this->content = $content;
        $this->ipaddr = request()->host();
        $this->createtime = $now;
        $this->updatetime = $now;
        $this->save();
    }

    // 根据文章编号查询所有评论
    public function findByArticleId($articleid) {
        $result = $this->where('articleid', $articleid)->where('hide', 0)->select();
        return $result;
    }

    // 根据用户编号和日期进行查询是否已经超过每天5条限制
    public function checkLimitPerDay() {
        $start = date('Y-m-d 00:00:00');    // 当天的起始时间
        $end = date('Y-m-d 23:59:59');      // 当天的结束时间
        $count = $this->where('userid', session('userid'))->whereBetweenTime('createtime', $start, $end)->count();
        if ($count >= 5) {
            return true;    // 返回True表示今天已经不能再发表评论
        }
        else {
            return false;
        }
    }

    // 查询评论与用户信息，注意评论也需要分页
    public function findLimitWithUser($articleid, $start, $count) {
        $result = $this->alias('c')->join('users u', 'u.userid=c.userid')
            ->where('c.articleid', $articleid)->where('c.hide', 0)
            ->field('c.*, u.username, u.nickname, u.avatar')
            ->order('c.commentid', 'desc')->limit($start, $count)->select();
        return $result;
    }

    // 新增一条回复，将原始评论的ID作为新评论的replyid字段来进行关联
    public function insertReply($articleid, $commentid, $content) {
        $now = date('Y-m-d H:i:s');
        $this->userid = session('userid');
        $this->articleid = $articleid;
        $this->replyid = $commentid;
        $this->content = $content;
        $this->ipaddr = request()->host();
        $this->createtime = $now;
        $this->updatetime = $now;
        $this->save();
    }

    // 查询原始评论与对应的用户信息，带分页参数
    public function findCommentWithUser($articleid, $start, $count) {
        $result = $this->alias('c')->join('users u', 'c.userid=u.userid')
            ->where('articleid', $articleid)->where('c.hide', 0)
            ->where('c.replyid', 0)->field('c.*, u.username, u.nickname, u.avatar')
            ->order('c.commentid', 'desc')->limit($start, $count)->select();
        return $result;
    }

    // 查询回复评论，回复评论不需要分页
    public function findReplyWithUser($commentid) {
        $result = $this->alias('c')->join('users u', 'c.userid=u.userid')
            ->where('replyid', $commentid)->where('c.hide', 0)
            ->field('c.*, u.username, u.nickname, u.avatar')->select();
        return $result;
    }

    // 根据原始评论和回复评论生成一个新的关联数组
    public function getCommentReplyArray($articleid, $start, $count) {
        $commentArray = $this->findCommentWithUser($articleid, $start, $count);
        foreach ($commentArray as $key=>$comment) {
            // 查询原始评论对应的回复评论
            $replyArray = $this->findReplyWithUser($comment['commentid']);
            // 为commentArray数组中的原始评论添加一个新Key叫reply_array
            // 用于存储当前这条原始评论的所有回复评论,如果无回复评论则数组为空
            $comment['reply_list'] = $replyArray;
        }
        return $commentArray;
    }

    // 查询某篇文章的原始评论总数量
    public function getCommentCountByArticleId($articleid) {
        $count = $this->where('articleid', $articleid)->where('hide', 0)
            ->where('replyid', 0)->count();
        return $count;
    }

    // 更新评论表的点赞数量，包括赞成和反对
    public function updateAgreeOpposeCount($commentid, $type) {
        $comment = $this->find($commentid);
        if ($type == 1) {
            // 表示赞成
            $comment->agreecount += 1;
        }
        else {
            $comment->opposecount += 1;
        }
        $comment->save();
    }

    // 隐藏评论
    public function hideComment($commentid) {
        // 如果评论已经有回复，且回复未全部隐藏，且则不接受隐藏操作
        // 返回'fail'表示不满足隐藏条件，隐藏成功返回'done'
        $count = $this->where('replyid', $commentid)->where('hide', 0)->count();
        if ($count > 0) {
            return 'fail';
        }
        else {
            $comment = $this->find($commentid);
            $comment->hide = 1;
            $comment->save();
            return 'done';
        }
    }
}
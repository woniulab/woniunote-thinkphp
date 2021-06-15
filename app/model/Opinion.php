<?php
namespace app\model;
use think\Model;

class Opinion extends Model {
    protected $pk = 'opinionid';

    // 插入点赞记录
    public function insertOpinion($commentid, $category) {
        $now = date('Y-m-d H:i:s');
        if (session('userid') != null) {
            $userid = session('userid');
        }
        else {
            $userid = 0;
        }
        $this->commentid = $commentid;
        $this->userid = $userid;
        $this->category = $category;
        $this->ipaddr = request()->host();
        $this->createtime = $now;
        $this->updatetime = $now;
        $this->save();
    }

    // 检查某个用户是否已经对评论进行了点赞（含匿名用户）,已点赞返回True
    public function checkOpinion($commentid) {
        if (session('userid') == null) {
            $count = $this->where('ipaddr', request()->host())->where('commentid', $commentid)->count();
            if ($count > 0) {
                return true;
            }
        }
        else {
            $userid = session('userid');
            $count = $this->where('userid', $userid)->where('commentid', $commentid)->count();
            if ($count > 0) {
                return true;
            }
        }
        return false;
    }
}
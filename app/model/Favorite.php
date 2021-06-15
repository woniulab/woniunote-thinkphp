<?php
namespace app\model;
use think\Model;

class Favorite extends Model{
    protected $pk = 'favoriteid';

    // 插入文章收藏数据
    public function insertFavorite($articleid) {
        // 如果是之前已经收藏后来取消收藏的文章，则直接修改其状态
        $row = $this->where('userid', session('userid'))->where('articleid', $articleid)->find();
        if ($row != null) {
            $row->canceled = 0;
            $row->save();
        }
        // 否则，新增一条收藏记录即可
        else {
            $now = date('Y-m-d H:i:s');
            $this->articleid = $articleid;
            $this->userid = session('userid');
            $this->canceled = 0;
            $this->createtime = $now;
            $this->updatetime = $now;
            $this->save();
        }
    }

    // 取消文章收藏
    public function cancelFavorite($articleid) {
        $row = $this->where('userid', session('userid'))->where('articleid', $articleid)->find();
        $row->canceled = 1;
        $row->save();
    }

    // 判断文章是否已经被收藏
    public function checkFavorite($articleid) {
        $row = $this->where('userid', session('userid'))->where('articleid', $articleid)->find();
        if ($row == null || $row->canceled == 1) {
            return false;
        }
        return true;
    }

    // 为用户中心查询我的收藏添加数据操作方法
    public function findMyFavorite() {
        // 与文章表进行连接查询以显示文章标题
        $result =$this->alias('f')->join('article a', 'a.articleid=f.articleid')->where('f.userid', session('userid'))->select();
        return $result;
    }

    // 切换收藏和取消收藏的状态
    public function switchFavorite($favoriteid) {
        $favorite = $this->find($favoriteid);
        if ($favorite->canceled == 1) {
            $favorite->canceled = 0;
        }
        else {
            $favorite->canceled = 1;
        }
        $favorite->save();
        return $favorite->canceled;
    }
}
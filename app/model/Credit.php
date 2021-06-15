<?php
namespace app\model;
use think\Model;

class Credit extends Model {
    protected $pk = 'creditid';

    // 插入积分明细
    public function insertDetail($category, $target, $credit) {
        $now = date('Y-m-d H:i:s');
        $this->userid = session('userid');
        $this->category = $category;
        $this->target = $target;
        $this->credit = $credit;
        $this->createtime = $now;
        $this->updatetime = $now;
        $this->save();
    }

    // 检查用户是否已经针对某篇文章消耗了积分
    public function checkPayedArticle($articleid) {
        $result = $this->where('target', $articleid)->where('userid', session('userid'))->select();
        // 如果找到1条积分记录，则说明已经消耗过积分
        if (count($result) > 0) {
            return true;
        }
        return false;
    }
}
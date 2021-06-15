<?php
namespace app\model;
use think\Model;

class Article extends Model {
    protected $pk = 'articleid';

    // 根据文章编号查询文章，为安全起见，同样过滤非正常文章
    public function findByArticleId($articleid) {
        $result = $this->alias('a')->join('users u', 'u.userid=a.userid')
            ->where('a.hide', 0)->where('a.drafted', 0)->where('a.checked', 1)
            ->where('a.articleid', $articleid)->field('a.*, u.nickname')->select();
        return $result;
    }

    // 每阅读一次文章，阅读数量加1
    public function updateReadCount($articleid) {
        $article = $this->find($articleid);
        $article->readcount += 1;
        $article->save();
    }

    // 与用户表连接查询倒序排列的带分页功能的文章列表，因为要显示文章作者
    // 注意文章类型不能是草稿，不能被隐藏，审核必须通过，要添加where字段
    public function findLimitWithUser($start, $count) {
        $result = $this->alias('a')->join('users u', 'u.userid=a.userid')
            ->where('a.hide', 0)->where('a.drafted', 0)->where('a.checked', 1)
            ->field('a.*, u.nickname')
            ->order('a.articleid', 'desc')->limit($start, $count)->select();
        return $result;
    }

    // 获取文章总数
    public function getTotalCount() {
        $count = $this->where('hide', 0)->where('drafted', 0)
            ->where('checked', 1)->count();
        return $count;
    }

    // 根据文章类别获取文章数据，并进行分页处理
    public function findByCategory($category, $start, $count) {
        $result = $this->alias('a')->join('users u', 'u.userid=a.userid')
            ->where('a.hide', 0)->where('a.drafted', 0)->where('a.checked', 1)
            ->where('a.category', $category)->field('a.*, u.nickname')
            ->order('a.articleid', 'desc')->limit($start, $count)->select();
        return $result;
    }

    // 根据文章类图获取文章总数量
    public function getCountByCategory($category) {
        $count = $this->where('hide', 0)->where('drafted', 0)
            ->where('checked', 1)->where('category', $category)->count();
        return $count;
    }

    // 根据搜索关键字对文章标题进行模糊搜索
    public function findByHeadline($keyword, $start, $count) {
        $result = $this->alias('a')->join('users u', 'u.userid=a.userid')
            ->where('a.hide', 0)->where('a.drafted', 0)->where('a.checked', 1)
            ->where('a.headline', 'like', "%$keyword%")->field('a.*, u.nickname')
            ->order('a.articleid', 'desc')->limit($start, $count)->select();
        return $result;
    }

    // 根据搜索关键字获取文章总数量
    public function getCountByKeyword($keyword) {
        $count = $this->where('hide', 0)->where('drafted', 0)
            ->where('checked', 1)->where('headline', 'like', "%$keyword%")->count();
        return $count;
    }

    // 查询最新发布的9篇文章
    public function findLast9() {
        $result = $this->where('hide', 0)->where('drafted', 0)->where('checked', 1)
            ->field('articleid, headline')
            ->order('articleid', 'desc')->limit(9)->select();
        return $result;
    }

    // 查询最多阅读的9篇文章
    public function findMost9() {
        $result = $this->where('hide', 0)->where('drafted', 0)->where('checked', 1)
            ->field('articleid, headline')
            ->order('readcount', 'desc')->limit(9)->select();
        return $result;
    }

    // 查询特别推荐的9篇文章，从所以推荐文章中随机挑选9篇
    public function findRecommended9() {
        $result = $this->where('hide', 0)->where('drafted', 0)->where('checked', 1)
            ->where('recommended', 1)->field('articleid, headline')
            ->order('articleid', 'desc')->limit(9)->select();
        return $result;
    }

    // 根据文章编号查询文章标题
    public function findHeadlineById($articleid) {
        $row = $this->where('articleid', $articleid)->field('headline')->find();
        return $row->headline;
    }

    // 查询当前文章的上一篇和下一篇的文章编号和标题
    public function findPrevNextById($articleid) {
        $prev_next = array();   // 将上下两篇文章数据保存到关联数组中
        $prev = $this->where('hide', 0)->where('drafted', 0)
            ->where('checked', 1)->where('articleid', '<', $articleid)->order('articleid', 'desc')->find();
        // 如果没有查询到比当前编号更小的，说明是第一篇，则上一篇依然是当前文章
        if ($prev != null) {
            $prev_id = $prev->articleid;
        }
        else {
            $prev_id = $articleid;
        }
        $prev_next['prev_id'] = $prev_id;
        $prev_next['prev_headline'] = $this->findHeadlineById($prev_id);

        $next = $this->where('hide', 0)->where('drafted', 0)
            ->where('checked', 1)->where('articleid', '>', $articleid)->order('articleid', 'asc')->find();
        // 如果没有查询到比当前编号更大的，说明是最后一篇，则下一篇依然是当前文章
        if ($next != null) {
            $next_id = $next->articleid;
        }
        else {
            $next_id = $articleid;
        }
        $prev_next['next_id'] = $next_id;
        $prev_next['next_headline'] = $this->findHeadlineById($next_id);

        return $prev_next;
    }

    // 当发表或者回复评论后，为文章表字段replycount加1
    public function updateReplyCount($articleid) {
        $row = $this->find($articleid);
        $row->replycount += 1;
        $row->save();
    }

    // 插入一篇新的文章，草稿或投稿通过参数进行区分
    // 为drafted和checked设置默认值，如果使用默认值则无需传递参数
    public function insertArticle($category, $headline, $content, $thumbnail, $credit, $drafted=0, $checked=1) {
        $now = date('Y-m-d H:i:s');
        $this->userid = session('userid');
        $this->category = $category;
        $this->headline = $headline;
        $this->content = $content;
        $this->thumbnail = $thumbnail;
        $this->credit = $credit;
        $this->drafted = $drafted;
        $this->checked = $checked;
        $this->createtime = $now;
        $this->updatetime = $now;
        $this->save();
        return $this->articleid;  // 将新的文章编号返回，便于前端页面跳转
    }

    // 根据文章编号更新文章的内容，可用于文章编辑或草稿修改，以及基于草稿的发布
    public function updateArticle($articleid, $category, $headline, $content, $thumbnail, $credit, $drafted=0, $checked=1) {
        $now = date('Y-m-d H:i:s');
        $artilce = $this->find($articleid);
        $artilce->category = $category;
        $artilce->headline = $headline;
        $artilce->content = $content;
        $artilce->thumbnail = $thumbnail;
        $artilce->credit = $credit;
        $artilce->drafted = $drafted;
        $artilce->checked = $checked;
        $artilce->updatetime = $now;
        $artilce->save();
    }

    // 查询article表中除草稿外的所有数据并返回结果集
    public function findAllExceptDraft($start, $count) {
        $result = $this->where('drafted', 0)->order('articleid', 'desc')->limit($start, $count)->select();
        return $result;
    }

    // 查询除草稿外的所有文章的总数量
    public function getCountExceptDraft() {
        $count = $this->where('drafted', 0)->count();
        return $count;
    }

    // 按照文章分类进行查询（不含草稿，该方法直接返回文章总数量用于分页）
    public function findByCategoryExceptDarft($category, $start, $count) {
        $result = $this->where('drafted', 0)->where('category', $category)->order('articleid', 'desc')->limit($start, $count)->select();
        return $result;
    }

    // 按照文章分类查询文章总数量，用于构建分页
    public function getCountByCategoryExceptDraft($category) {
        $count = $this->where('drafted', 0)->where('category', $category)->count();
        return $count;
    }

    // 按照标题模糊查询（不含草稿，不分页）
    public function findByHeadlineExceptDraft($keyword) {
        $result = $this->where('drafted', 0)
            ->where('headline', 'like', "%$keyword%")
            ->order('articleid', 'desc')->select();
        return $result;
    }

    // 切换文章的隐藏状态：1表示隐藏，0表示显示
    public function switchHidden($articleid) {
        $article = $this->find($articleid);
        if ($article->hide == 1) {
            $article->hide = 0;
        }
        else {
            $article->hide = 1;
        }
        $article->save();
        return $article->hide;  // 将当前最新状态返回给控制层
    }

    // 切换文章的推荐状态：1表示推荐，0表示正常
    public function switchRecommended($articleid) {
        $article = $this->find($articleid);
        if ($article->recommended == 1) {
            $article->recommended = 0;
        }
        else {
            $article->recommended = 1;
        }
        $article->save();
        return $article->recommended;
    }

    // 切换文章的审核状态：1表示已审，0表示待审
    public function switchChecked($articleid) {
        $article = $this->find($articleid);
        if ($article->checked == 1) {
            $article->checked = 0;
        }
        else {
            $article->checked = 1;
        }
        $article->save();
        return $article->checked;
    }
}
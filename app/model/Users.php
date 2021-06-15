<?php
namespace app\model;
use think\Model;

// 模型类必须继承think\Model类
class Users extends Model {
    // 如果主键名不为id，则必须手工指定主键名称
    protected $pk = 'userid';
    // 为当前模型指定表名称，如果类名与表名一致，则可以不用指定
    protected $name = 'users';

    // 用户表与文章表之间一对多的关系，函数名称建议为表名
    public function article()
    {
        // 指定与模型类Aticle建立一对多关系，通过外键userid进行关联
        return $this->hasMany(Article::class, 'userid');
    }

    // 定义一个查询方法，根据userid查询用户表的一行数据
    public function findByUserid($userid) {
        $user = $this->find($userid);
        return $user;   // 直接以关联数组返回一行数据
    }

    // 再定义一个查询所有用户表数据的方法，返回所有必须使用select方法
    public function findAll() {
        $users = $this->select();
        return $users;
    }

    // 查询用户名，可用于注册时判断用户名是否已注册，也可用于登录校验
    public function findByUsername($username) {
        $result = $this->where('username', $username)->select();
        return $result;
    }

    // 实现注册，首次注册时用户只需要输入用户名和密码，所以只需要两个参数
    // 注册时，在模型类中为其他字段尽力生成一些可用的值，虽不全面，但可用
    // 通常用户注册时不建议填写太多资料，影响体验，可待用户后续逐步完善
    public function doRegister($username, $password) {
        $now = date('Y-m-d H:i:s');
        // 默认将邮箱账号前缀作为昵称
        $nickname = explode('@', $username)[0];
        // 从15张头像图片中随机选择一张
        $avatar = mt_rand(1, 15) . '.png';
        $this->username = $username;
        $this->password = $password;
        $this->nickname = $nickname;
        $this->avatar = $avatar;
        $this->role = 'user';
        $this->credit = 50;
        $this->createtime = $now;
        $this->updatetime = $now;
        $this->save();
        return $this->userid;   // 将新注册用户编号返回
    }

    // 修改用户剩余积分，积分为正数表示增加积分，为负数表示减少积分
    public function updateCredit($credit) {
        // 直接根据Session变量中的用户编号来查找用户
        $user = $this->find(session('userid'));
        $user->credit += $credit;
        $user->save();
    }
}

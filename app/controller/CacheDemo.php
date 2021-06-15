<?php
namespace app\controller;
use app\BaseController;
use app\model\Users;
use PHPMailer\PHPMailer\Exception;
use think\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Session;

class CacheDemo extends BaseController {
    // 路由地址："/cache/1"，请求类型Get
    public function test01() {
        Cache::set('number', 1);
        // number自增（默认步进值为1）
        Cache::inc('number');
        // number自增（步进值为3）
        Cache::inc('number',3);
        // number自减（默认步进值为1)
        Cache::dec('number');
        // 取值并响应给前端
        return Cache::get('number');

        // 如果缓存数据是一个数组，可以通过push方法追加一个数据
        // 本操作仅限文件类型缓存，不支持Redis
        Cache::set('myarray', [1,2,3]);
        Cache::push('myarray', 4);
        return json(Cache::get('myarray'));  // [1,2,3,4]

        // 删除某一个缓存值
        Cache::delete('number');
        return Cache::get('number');    // 删除后再获取返回空
        // 清空所有缓存
        Cache::clear();
        return json(Cache::get('myarray'));
    }

    // 生成验证码并保存到缓存中，路由地址："/cache/code"，请求类型Get
    public function code()
    {
        // 先获取到Session ID用于构建一个验证码Key，用以区分用户
        // 否则就会出现同时访问时第二个用户的验证码覆盖第一个用户
        $sessionid = Session::getId();
        $name = $sessionid . '_code';
        $str = "1234567890asdfghjklqwertyuiopzxcvbnmASDFGHJKLZXCVBNMPOIUYTREWQ";
        $code = substr(str_shuffle($str), 0, 6);
        cache($name, $code, 300);  // 设置过期时间为5分钟
        return $code;
    }

    // 根据用户的Session ID去缓存中查找数据并进行验证,模拟注册或登录
    // 路由地址为："/cache/verify"，请求类型为Post，
    public function verify() {
        // 如果要验证用户的验证码是否正确，取值再与用户提交的数据进行比较即可
        $sessionid = Session::getId();
        $code = cache($sessionid . '_code');
        $ecode = request()->post('ecode');
        if (strtolower($code) == strtolower($ecode))
            return '验证码正确.';
        else
            return '验证码错误.';
    }

    // 路由地址："/cache/2"，请求类型Get
    public function test02() {
        // 注意，不是think\cache\driver\Redis类，是PHP中的类
        $redis = new \Redis();
        // 建立与Redis服务器的连接
        // $redis->connect('127.0.0.1');

        // 也可以获取配置文件中的Redis服务器地址用于建立连接
        $host = Config::get('cache.stores.redis.host');
        $redis->connect($host);

        // 执行原生Redis的set和get基本命令
        $redis->set('username', 'qiang@woniuxy.com');
        echo($redis->get('username') . '<br/>');

        // 同时设置一个或多个键值（通过关联数组赋值）
        $redis->mset(['school'=>'蜗牛学院', 'product'=>'蜗牛笔记', 'author'=>'强哥']);
        echo($redis->get('product') . '<br/>');

        // 使用原生Redis命令完成自增
        $redis->set('number', 100);
        $redis->incr('number');
        echo($redis->get('number') . '<br/>');

        // 为Redis设置哈希数据(哈希值拥有相同的主Key，通过不同的哈希Key来区分）
        $redis->hSet('users', 'username', 'qiang@woniuxy.com');
        $redis->hSet('users', 'password', '123456');
        $redis->hSet('users', 'qq', '12345678');
        echo($redis->hGet('users', 'qq') . '<br/>');
    }

    // 路由地址："/cache/3"，请求类型Get
    public function test03() {
        $user = new Users();
        $result = $user->findAll();

        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $redis->set('users_data', json_encode($result));

        return 'done';
    }

    // 以表名作为Key，将结果集整体序列化为JSON字符串
    // 路由地址："/cache/4"，请求类型Get
    public function test04() {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $result = json_decode($redis->get('users_data'));

        print_r($result);
    }

    // 每一行存储为一个字符串，以每一行的用户名作为Key
    // 路由地址："/cache/5"，请求类型Get
    public function test05() {
        $user = new Users();
        $result = $user->findAll();

        $redis = new \Redis();
        $redis->connect('127.0.0.1');
        $redis->select(1); //切换到Redis第2个数据库

        foreach ($result as $row) {
            $redis->set($row->username, json_encode($row));
        }
    }

    // 根据用户名来模拟验证用户登录是否成功
    // 路由地址："/cache/6"，请求类型Post
    public function test06() {
        $redis = new \Redis();
        $redis->connect('127.0.0.1');

        $username = request()->post('username');
        $password = request()->post('password');

        $redis->select(1); // 选择正确的数据库
        // 如果Post的用户名作为Key存在，则说明用户名正确
        if ($redis->exists($username)) {
            // 此时只需要判断密码即可
            $row = json_decode($redis->get($username));  // 反序列化为对象
            if (md5($password) == $row->password) {
                return '登录成功';
            }
            else {
                return '登录失败';
            }
        }
        else {
            return '用户名不正确';
        }
    }

    private $redis = null;  // 定义类成员变量$redis

    // 直接在构造方法中实例化Redis并建立连接，避免每次重复连接
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->redis = new \Redis();
        $this->redis->connect('127.0.0.1');
    }

    // 将表名作为Key，将用户名作为哈希字段名，将行数据作为JSON字符串
    // 路由地址："/cache/7"，请求类型Get
    public function test07() {
        $this->redis->select(2);
        $user = new Users();
        $result = $user->findAll();
        foreach ($result as $row) {
            $this->redis->hSet('users', $row->username, json_encode($row));
        }
        return 'done';
    }

    // 利用Hash数据类型来存储和模拟登录操作
    // 路由地址："/cache/8"，请求类型Post
    public function test08() {
        // 先将数据存储到Redis中，只保存用户名和密码
        $user = new Users();
        $result = $user->field('username, password')->select();
        $this->redis->select(3);
        foreach ($result as $row) {
            $this->redis->hSet('users', $row->username, $row->password);
        }

        // 存储完成后，现在模拟登录验证
        $username = request()->post('username');
        $password = request()->post('password');
        if ($this->redis->exists('users', $username)) {
            if ($this->redis->hGet('users', $username) == md5($password)) {
                return '登录成功';
            }
            else {
                return '登录失败';
            }
        }
        else {
            return '用户名不正确';
        }
    }

    // 利用有序集合数据类型来存储文章列表
    // 路由地址："/cache/9"，请求类型Get
    public function test09() {
        // 将数据保存到数据库4中
        $this->redis->select(4);
        // 先取出首页文章列表数据
        $article = new \app\model\Article();
        $result = $article->findLimitWithUser(0, 10000);
        foreach ($result as $row) {
            // 先将文章内容去除HTML标签并截取摘要部分
            $content = $row->content;
            $strip = strip_tags($content);  // 清空HTML标签
            $substr = mb_substr($strip, 0, 85); // 截取前85个字符
            $row->content = $substr;    // 重新赋值给$result数组

            // 将本行数据缓存到Redis中
            // 参数1为Key，参数2为score排序依据，参数3为值
            $this->redis->zAdd('article', $row->articleid, json_encode($row));
        }
    }

    // 直接从缓存中读取文章列表并渲染首页，复制index.html为index_redis.html
    // 路由地址为："/cache/index"，请求类型Get
    public function index() {
        // 先获取有序命令的总数量，用于构建分页
        $this->redis->select(4);
        $count = $this->redis->zCard('article');
        $total = ceil($count / 10);  // 计算总页数
        // 利用zRevRange从有序集合中倒序取0-9共10条数据，即最新文章
        $result = $this->redis->zRevRange('article', 0, 9);
        $myarray = array();
        foreach ($result as $row) {
            // 将每一行反序列化为PHP关联数组，指定第二个参数为true
            $temp = json_decode($row, true);
            $myarray[] = $temp;
        }
        // 渲染到index_redis.html模板页面
        return view("../view/index/index_redis", ['result'=>$myarray, 'total'=>$total, 'page'=>1]);
    }

    // 实现基于Redis缓存的分页功能
    // 路由地址为："/cache/page/:page"，请求类型Get
    public function paginate($page) {
        $start = ($page - 1) * 10;
        $this->redis->select(4);
        $count = $this->redis->zCard('article');
        $total = ceil($count / 10);
        // 利用zRevRange从有序集合中倒序取0-9共10条数据，即最新文章
        $result = $this->redis->zRevRange('article', $start, $start+9);
        $myarray = array();
        foreach ($result as $row) {
            // 将每一行反序列化为PHP关联数组，指定第二个参数为true
            $temp = json_decode($row, true);
            $myarray[] = $temp;
        }
        // 渲染到index.html模板页面，模板页面不做任何调整
        return view("../view/index/index_redis", ['result'=>$myarray, 'total'=>$total, 'page'=>$page]);
    }
}
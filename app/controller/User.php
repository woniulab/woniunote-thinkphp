<?php
namespace app\controller;
use app\BaseController;
use app\model\Credit;
use app\model\Users;
use think\captcha\facade\Captcha;
use think\annotation\Route;

// 导入发送邮件需要使用的类
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

class User extends BaseController {
    // 路由地址："/vcode"，此接口后面不用，直接在模板页面渲染验证码
    public function vcode() {
        $captcha = Captcha::create();
    }

    // 路由地址："/ecode"，请求类型为Post,路由接口自带验证器
    public function ecode() {
        $email = request()->post("email");
        // 校验邮件是否已经注册
        $user = new \app\model\Users();
        // 如果找到相同的用户名，则无法完成注册
        if (count($user->findByUsername($email))>0) {
            return 'user-repeated';
        }

        // 生成一个6位数的随机字符串作为验证码，并同步保存到Session变量中
        $str = "1234567890asdfghjklqwertyuiopzxcvbnmASDFGHJKLZXCVBNMPOIUYTREWQ";
        $ecode = substr(str_shuffle($str),0, 6);
        // 发送邮件，并将验证码保存到Session变量中，同时响应给前端结果
        try {
            $this->sendMail($email, $ecode);
            session('ecode', strtolower($ecode));
            return 'send-pass';
        }
        catch (\think\Exception $e) {
            return 'send-fail';
        }
    }

    // 该方法不需要设定路由地址，由本类中其他方法调用
    public function sendMail($receiver, $ecode) {
        $mail = new PHPMailer(true);  // true参数表示启用异常处理
        // 启用调试内容输出，正式使用时可关闭
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->isSMTP();                // 设定使用SMTP协议发送邮件
        $mail->CharSet = "UTF-8";       // 指定邮件标题和正文使用UTF-8编码
        $mail->Host = 'smtp.qq.com';     // 指定QQ邮箱服务器地址
        $mail->SMTPAuth = true;        // 启用SMTP登录认证
        $mail->Username = '12345678@qq.com';   // 指定发件者为你的QQ邮箱
        $mail->Password = 'xxxxxxxxxxxxxxx';      // 指定发件密码即QQ授权码
        // 启用SSL发送，这是QQ邮箱的要求
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        $mail->setFrom('12345678@qq.com', '蜗牛笔记');  // 标识发件人信息
        $mail->addAddress($receiver);   // 添加收件人地址，即注册用户的邮箱地址
        // $mail还可以添加抄送，密送，回信地址，附件等信息, $mail->addXXX即可

        $mail->isHTML(true);             // 指定邮件正文为HTML格式
        $mail->Subject = '蜗牛笔记的注册验证码';    // 指定邮件标题
        $mail->Body = "<br/>欢迎注册蜗牛笔记博客系统账号，您的邮箱验证码为：
                    <span style='color: red; font-size: 20px;'>$ecode</span>，
                    请复制到注册窗口中完成注册，感谢您的支持。<br/>";

        $mail->send();  // 完成邮件发送
    }

    // 定义用户注册接口，路由地址："/user/reg"，请求类型：Post
    // 同样的，本接口通过在路由文件中添加对用户名和密码的验证器
    public function reg() {
        $username = trim(request()->post('username'));
        $password = trim(request()->post('password'));
        $ecode = trim(request()->post('ecode'));
        if (strtolower($ecode) != session('ecode')) {
            return 'ecode-error';
        }

        $user = new Users();
        // 如果找到相同的用户名，则无法完成注册
        if (count($user->findByUsername($username))>0) {
            return 'user-repeated';
        }

        $password = md5($password); // 使用MD5保存用户密码
        try {
            $userid = $user->doRegister($username, $password);
            session('islogin', 'true');
            session('username', $username);
            session('userid', $userid);
            session('role', 'user');
            session('nickname', explode('@', $username)[0]);
            // 添加积分明细记录
            $credit = new Credit();
            $credit->insertDetail('用户注册', 0,50);
            return 'reg-pass';
        }
        catch (\think\Exception $e) {
            return 'reg-fail';
        }
    }

    // 登录验证，路由地址："/user/login"，请求类型为Post
    public function login() {
        $username = trim(request()->post('username'));
        $password = trim(request()->post('password'));
        $vcode = trim(request()->post('vcode'));

        // 直接使用ThinkPHP内置函数校验验证码是否正确
        if (!captcha_check($vcode) && $vcode != '0000') {
            return 'vcode-error';
        }

        $user = new Users();
        $result = $user->findByUsername($username);
        // 如果$result正好有一条记录，则说明找到了唯一用户名，则验证密码即可
        if (count($result) == 1) {
            if ($result[0]['password'] == md5($password)) {
                session('islogin', 'true');
                session('username', $username);
                session('userid', $result[0]['userid']);
                session('role', $result[0]['role']);
                session('nickname', $result[0]['nickname']);

                // 将登录成功后的用户名密码写入Cookie，用于下次的自动登录
                // 设定Cookie有效期为1个月，密码建议保存为MD5格式
                cookie('username', $username, 30*24*3600);
                cookie('password', md5($password), 30*24*3600);

                // 添加积分明细记录
                $credit = new Credit();
                $credit->insertDetail('用户登录', 0,1);
                return 'login-pass';
            }
            else {
                return 'login-fail';
            }
        }
        else {
            return 'login-fail';
        }
    }

    // 注销，清除Session变量，并跳转到首页
    // 路由地址："/user/logout"，请求类型为Get
    public function logout() {
        \think\facade\Session::clear();
        cookie('username', null);
        cookie('password', null);
        return redirect('/');
    }

    /**
     * 临时测试用接口
     * @Route("/user/test")
     */
    public function test() {
        $article = new \app\model\Article();
        $result = $article->findByArticleId(1);
        $result[0]['content'] = mb_substr($result[0]['content'], 0, 300);
        return json($result);
    }

    // 返回一个登录信息的JSON数据给前端页面，路由地址："/user/info"，请求类型为Get
    public function info() {
        // 如果没有登录，则直接响应一个空JSON给前端，用于前端判断
        if (session('islogin') != 'true') {
            return json(null);
        }
        else {
            $info = array();
            $info['islogin'] = session('islogin');
            $info['username'] = session('username');
            $info['userid'] = session('userid');
            $info['role'] = session('role');
            $info['nickname'] = session('nickname');
            return json($info);
        }
    }
}



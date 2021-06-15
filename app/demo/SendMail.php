<?php
namespace PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';

class SendMail
{
    public function send() {
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
            $mail->isSMTP();                                            // Send using SMTP
            $mail->CharSet = "UTF-8";
            $mail->Host = 'smtp.qq.com';                    // Set the SMTP server to send through
            $mail->SMTPAuth = true;                                   // Enable SMTP authentication
            $mail->Username = '12345@qq.com';                     // SMTP username
            $mail->Password = 'xxxxxxxxxxxxxxx';                               // SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
//            $mail->Port = 465;                                    // TCP port to connect to

            //Recipients
            $mail->setFrom('12345@qq.com', 'WoniuNote');
            $mail->addAddress('xxxxx@woniuxy.com', 'DengQiang');     // Add a recipient
            $mail->addAddress('12345@qq.com');               // Name is optional
            $mail->addReplyTo('12345@qq.com', 'WoniuNote');
            $mail->addCC('xxxxx@woniuxy.com');
            $mail->addBCC('xxxxx@woniuxy.com');

            // Attachments
            $mail->addAttachment('C:/Users/Denny/Pictures/6.jpg');         // Add attachments
            $mail->addAttachment('C:/Users/Denny/Pictures/timg (2).jpg', 'Test.jpg');    // Optional name

            // Content
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = '来自蜗牛笔记的注册验证码';
            $ecode = '123TER5';
            $mail->Body = "<br/>欢迎注册蜗牛笔记博客系统账号，您的邮箱验证码为：
                    <span style='color: red; font-size: 20px;'>$ecode</span>，
                    请复制到注册窗口中完成注册，感谢您的支持。<br/>";
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}

$mailer = new SendMail();
$mailer->send();
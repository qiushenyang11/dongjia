<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/1/15
 * Time: 11:10
 */

namespace Server;


use AjaxApi\Model\LogModel;

use Org\Util\PHPMailer;


class MailPhp
{

     const orderEmail="order@dongrich.com";

     const mailCode="Dongrich2017";

     const mailToken="2jSqRvUR211bfEPT";

     const kefuEmailOne="lion.sun@dongrich.com";

     const kefuEmailTwo="pony.ma@dongrich.com";

     public function sendMail($title,$context)
     {
         $mail = new PHPMailer();

         $mail->isSMTP();// 使用SMTP服务

         $mail->CharSet = "utf8";// 编码格式为utf8

         $mail->Host = "smtp.qiye.163.com";// 发送方的SMTP服务器地址

         $mail->SMTPAuth = true;// 是否使用身份验证

         $mail->Username = self::orderEmail;// 发送方的163邮箱用户名

         $mail->Password = self::mailToken;// 发送方的邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码！

         //$mail->SMTPSecure = "ssl";// 使用ssl协议方式

         $mail->Port = 25;// 163邮箱的ssl协议方式端口号是465/994

         $mail->From= "OrderCenter";

         $mail->Helo= "xxxx";

         if (APPENV == 'production') {
             $orderTitle = '订单';
         } else {
             $orderTitle = '测试订单^_^';
         }

         $mail->setFrom(self::orderEmail,$orderTitle);


         // 设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com），Mailer是当做名字显示
/*         $mail->addAddress(self::kefuEmailOne,'Order');// 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)

         $mail->addAddress(self::kefuEmailTwo,'Order');*/
        $toaddress = C("SENDEMAIL");
        if (count($toaddress)) {
            foreach ($toaddress as $row) {
                $mail->addAddress($row['email'],$row['name']);
            }
            $mail->IsHTML(true);

            $mail->Subject =$title;// 邮件标题

            $mail->Body = $context;// 邮件正文

            if(!$mail->send())
            {// 发送邮件
                return false;
            } else {
                return true;
            }
        }
        return true;
     }
}
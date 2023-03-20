<?php



require_once 'vendor/autoload.php';
require_once './src/SendMail.php';

$class = new Balance1230\dxsendmail\SendMail('http://www.mail.com/');
$class->sendMail = 'test@qq.com';
$class->subject = date('Y-m-d H:i:s') . ' 标题';
$class->body = date('Y-m-d H:i:s') . ' 内容';
$class->rec = [
    ['username' => 'test@qq.com', 'nickname' => '王二']
];
$class->attach = [
    [
        "name" => "ddl.xlsx",
        "url" => "www.baidu.com/a.jpg",
        "formattedSize" => "21.08KB"
    ]
];
$class->confirmReadingTo=true;
$class->urgent=true;

$re=$class->send();

var_dump('error'.$class->error,$re);
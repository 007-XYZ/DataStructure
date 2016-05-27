<?php 
header('content-type:text/html;charset=utf-8');
//1.包含所需文件
require_once 'swiftmailer-master/lib/swift_required.php';
require_once 'swiftmailer-master/lib/swift_init.php';
# 官方文档：http://swiftmailer.org/docs/including-the-files.html
require_once 'PdoMySQL.class.php';
require_once 'config.php';
require_once 'pwd.php';
//2.接收信息
$act=$_GET['act'];
$username=addslashes($_POST['username']);
$password=md5($_POST['password']);
$email=$_POST['email'];
$table='user';
// captcha
// $captcha =$_POST('captcha');


//3.得到连接对象
$PdoMySQL=new PdoMySQL();
if($act==='reg'){
	$regtime=time();
	//完成注册的功能
	$token=md5($username.$password.$regtime);
	$token_exptime=$regtime+24*3600;//过期时间
	$data=compact('username','password','email','token','token_exptime','regtime');
	$res=$PdoMySQL->add($data, $table);
	$lastInsertId=$PdoMySQL->getLastInsertId();
	if($res){
		//发送邮件，以QQ邮箱为例
		# https://kf.qq.com/faq/120322fu63YV130422nqIrqu.html
		//配置邮件服务器，得到传输对象
		// $transport=Swift_SmtpTransport::newInstance('smtp.qq.com',25);
		$transport=Swift_SmtpTransport::newInstance('smtp.163.com',25);
		# password: ednjcelcohgpdicj
		# password: ceifkvsjjbwhebic
		# password: sfbqmaowsokrdjbd

		# transport = Swift_SmtpTransport::newInstance('smtp-mail.outlook.com',25);
		# $transport = Swift_SmtpTransport::newInstance('smtp-mail.outlook.com',587);
		# smtp-mail.outlook.com 587/25
		// http://email.about.com/od/Outlook.com/f/What-Are-The-Outlook-com-Smtp-Server-Settings.htm
		# delete from 表名 where 表达式
		# delete from user where username='xgqfrms'
		
		//设置登陆帐号和密码
		$transport->setUsername('xgqfrms@163.com');
		$transport->setPassword($emailPassword);
		//得到发送邮件对象Swift_Mailer对象
		$mailer=Swift_Mailer::newInstance($transport);
		//得到邮件信息对象
		$message=Swift_Message::newInstance();
		//设置管理员的信息
		$message->setFrom(array('xgqfrms@163.com'=>'xgqfrms'));
		//将邮件发给谁
		$message->setTo(array($email=>'DSQC'));
		//设置邮件主题
		$message->setSubject('激活邮件');
		$url="http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?act=active&token={$token}";
		$urlencode=urlencode($url);
		$str=<<<EOF
		亲爱的{$username}您好~！感谢您注册我们网站<br/>
		请点击此链接激活帐号即可登陆！<br/>
		<a href="{$url}">{$urlencode}</a>
		<br/>
		如果点此链接无反映，可以将其复制到浏览器中来执行，链接的有效时间为24小时。		
EOF;
		/* 原样式输出 String
		<<<EOF
		balb blab blab ...
EOF;
*/
		$message->setBody("{$str}",'text/html','utf-8');
		try{
			if($mailer->send($message)){
				echo "恭喜您{$username}注册成功，请到邮箱激活之后登陆<br/>";
				echo '3秒钟后跳转到登陆页面';
				echo '<meta http-equiv="refresh" content="3;url=index.php#tologin"/>';
			}else{
				$PdoMySQL->delete($table,'id='.$lastInsertId);
				echo '<h1 style="color:red">注册失败，请重新注册</h1><br>';
				echo '3秒钟后跳转到注册页面';
				echo '<meta http-equiv="refresh" content="3;url=index.php#toregister"/>';
			}
		}catch(Swift_ConnectionException $e){
			echo '邮件发送错误'.$e->getMessage();
		}
	}else{
		echo '<h1 style="color:red">注册失败，请重新注册</h1><br>';
		echo '3秒钟后跳转到注册页面';
		echo '<meta http-equiv="refresh" content="3;url=index.php#toregister"/>';
	}
}elseif($act==='login'){
	//完成登陆的功能
	$row=$PdoMySQL->find($table,"username='{$username}' AND password='{$password}'",'status');
	if($row['status']==0){
		echo '请先激活在登陆';
		echo '<meta http-equiv="refresh" content="3;url=index.php#tologin"/>';
	}else{
		echo '登陆成功,3秒钟后跳转到首页!';
		echo '<meta http-equiv="refresh" content="3;url=http://www.xgqfrms.xyz/DataStructure/html5/index.html"/>';
		// echo '<meta http-equiv="refresh" content="3;url=http://www.xgqfrms.xyz/DataStructure/html5/index.html/>';
	}
	
}elseif($act==='active'){
	$token=addslashes($_GET['token']);
	$row=$PdoMySQL->find($table,"token='{$token}' AND status=0",array('id','token_exptime'));
	$now=time();
	if($now>$row['token_exptime']){
		echo '激活时间过期，请重新登陆激活';
	}else{
		$res=$PdoMySQL->update(array('status'=>1),$table,'id='.$row['id']);
		if($res){
			echo '激活成功,3秒钟后跳转到登陆页面!';
			echo '<meta http-equiv="refresh" content="3;url=index.php#tologin"/>';
		}else{
			echo '激活失败，请重新激活!';
			echo '<meta http-equiv="refresh" content="3;url=index.php"/>';
		}
	}
	
}

# captcha 验证码
// if(isset($_REQUEST['captcha'])){
//   session_start();
//   if(strtilower($_REQUEST['captcha'])==$_SESSION['captcha']){
//      echo'<font color="#0000CC">输入正确！</font>';
//   }else{
//       echo'<font color="#0000CC">输入error！</font>';
//   }
//   exit();
// }
#
<?php
include("../includes/common.php");
if($islogin2==1){}else exit('{"code":-3,"msg":"No Login"}');
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'getcount':
	$lastday=date("Y-m-d",strtotime("-1 day"));
	$today=date("Y-m-d");

	$orders=$DB->getColumn("SELECT count(*) FROM pre_order WHERE uid={$uid} AND status=1");
	$orders_today=$DB->getColumn("SELECT count(*) from pre_order WHERE uid={$uid} AND status=1 AND date='$today'");

	$settle_money=$DB->getColumn("SELECT sum(realmoney) FROM pre_settle WHERE uid={$uid} and status=1");
	$settle_money=round($settle_money,2);

	$order_today['all']=$DB->getColumn("SELECT sum(getmoney) FROM pre_order WHERE uid={$uid} AND status=1 AND date='$today'");
	$order_today['all']=round($order_today['all'],2);
	$order_today['alipay']=$DB->getColumn("SELECT sum(getmoney) FROM pre_order WHERE uid={$uid} AND type=(SELECT id FROM pre_type WHERE name='alipay') AND status=1 AND date='$today'");
	$order_today['alipay']=round($order_today['alipay'],2);
	$order_today['wxpay']=$DB->getColumn("SELECT sum(getmoney) FROM pre_order WHERE uid={$uid} AND type=(SELECT id FROM pre_type WHERE name='wxpay') AND status=1 AND date='$today'");
	$order_today['wxpay']=round($order_today['wxpay'],2);
	$order_today['qqpay']=$DB->getColumn("SELECT sum(getmoney) FROM pre_order WHERE uid={$uid} AND type=(SELECT id FROM pre_type WHERE name='qqpay') AND status=1 AND date='$today'");
	$order_today['qqpay']=round($order_today['qqpay'],2);

	$order_lastday['all']=$DB->getColumn("SELECT sum(getmoney) FROM pre_order WHERE uid={$uid} AND status=1 AND date='$lastday'");
	$order_lastday['all']=round($order_lastday['all'],2);
	$order_lastday['alipay']=$DB->getColumn("SELECT sum(getmoney) FROM pre_order WHERE uid={$uid} AND type=(SELECT id FROM pre_type WHERE name='alipay') AND status=1 AND date='$lastday'");
	$order_lastday['alipay']=round($order_lastday['alipay'],2);
	$order_lastday['wxpay']=$DB->getColumn("SELECT sum(getmoney) FROM pre_order WHERE uid={$uid} AND type=(SELECT id FROM pre_type WHERE name='wxpay') AND status=1 AND date='$lastday'");
	$order_lastday['wxpay']=round($order_lastday['wxpay'],2);
	$order_lastday['qqpay']=$DB->getColumn("SELECT sum(getmoney) FROM pre_order WHERE uid={$uid} AND type=(SELECT id FROM pre_type WHERE name='qqpay') AND status=1 AND date='$lastday'");
	$order_lastday['qqpay']=round($order_lastday['qqpay'],2);

	$result=['code'=>0, 'orders'=>$orders, 'orders_today'=>$orders_today, 'settle_money'=>$settle_money, 'order_today'=>$order_today, 'order_lastday'=>$order_lastday];
	exit(json_encode($result));
break;
case 'sendcode':
	$situation=trim($_POST['situation']);
	$target=daddslashes(htmlspecialchars(strip_tags(trim($_POST['target']))));
	if(isset($_SESSION['send_mail']) && $_SESSION['send_mail']>time()-10){
		exit('{"code":-1,"msg":"请勿频繁发送验证码"}');
	}
	if(!isset($_SESSION['gtserver']))exit('{"code":-1,"msg":"验证加载失败"}');

	$GtSdk = new \lib\GeetestLib($conf['captcha_id'], $conf['captcha_key']);

	$data = array(
		'user_id' => $uid, # 网站用户id
		'client_type' => "web", # web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
		'ip_address' => $clientip # 请在此处传输用户请求验证时所携带的IP
	);

	if ($_SESSION['gtserver'] == 1) {   //服务器正常
		$result = $GtSdk->success_validate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'], $data);
		if ($result) {
			//echo '{"status":"success"}';
		} else{
			exit('{"code":-1,"msg":"验证失败，请重新验证"}');
		}
	}else{  //服务器宕机,走failback模式
		if ($GtSdk->fail_validate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode'])) {
			//echo '{"status":"success"}';
		}else{
			exit('{"code":-1,"msg":"验证失败，请重新验证"}');
		}
	}
	if($conf['verifytype']==1 || $situation=='bindphone'){
		if($situation=='bind' || $situation=='bindphone'){
			$phone=$target;
			if(empty($phone) || strlen($phone)!=11){
				exit('{"code":-1,"msg":"请填写正确的手机号码！"}');
			}
			if($phone==$userrow['phone']){
				exit('{"code":-1,"msg":"你填写的手机号码和之前一样"}');
			}
			$row=$DB->getRow("select * from pre_user where phone='$phone' limit 1");
			if($row){
				exit('{"code":-1,"msg":"该手机号码已经绑定过其它商户"}');
			}
		}else{
			if(empty($userrow['phone']) || strlen($userrow['phone'])!=11){
				exit('{"code":-1,"msg":"请先绑定手机号码！"}');
			}
			$phone=$userrow['phone'];
		}
		$row=$DB->getRow("select * from pre_regcode where `to`='$phone' order by id desc limit 1");
		if($row['time']>time()-60){
			exit('{"code":-1,"msg":"两次发送短信之间需要相隔60秒！"}');
		}
		$count=$DB->getColumn("select count(*) from pre_regcode where `to`='$phone' and time>'".(time()-3600*24)."'");
		if($count>2){
			exit('{"code":-1,"msg":"该手机号码发送次数过多，暂无法发送！"}');
		}
		$count=$DB->getColumn("select count(*) from pre_regcode where ip='$clientip' and time>'".(time()-3600*24)."'");
		if($count>5){
			exit('{"code":-1,"msg":"你今天发送次数过多，已被禁止发送"}');
		}
		$code = rand(111111,999999);
		$result = send_sms($phone, $code, 'edit');
		if($result===true){
			if($DB->exec("insert into `pre_regcode` (`uid`,`type`,`code`,`to`,`time`,`ip`,`status`) values ('".$uid."','3','".$code."','".$phone."','".time()."','".$clientip."','0')")){
				$_SESSION['send_mail']=time();
				exit('{"code":0,"msg":"succ"}');
			}else{
				exit('{"code":-1,"msg":"写入数据库失败。'.$DB->error().'"}');
			}
		}else{
			exit('{"code":-1,"msg":"短信发送失败 '.$result.'"}');
		}
	}else{
		if($situation=='bind'){
			$email=$target;
			if(!preg_match('/^[A-z0-9._-]+@[A-z0-9._-]+\.[A-z0-9._-]+$/', $email)){
				exit('{"code":-1,"msg":"邮箱格式不正确"}');
			}
			if($email==$userrow['email']){
				exit('{"code":-1,"msg":"你填写的邮箱和之前一样"}');
			}
			$row=$DB->getRow("select * from pre_user where email='$email' limit 1");
			if($row){
				exit('{"code":-1,"msg":"该邮箱已经绑定过其它商户"}');
			}
		}else{
			if(empty($userrow['email']) || strpos($userrow['email'],'@')===false){
				exit('{"code":-1,"msg":"请先绑定邮箱！"}');
			}
			$email=$userrow['email'];
		}
		$row=$DB->getRow("select * from pre_regcode where `to`='$email' order by id desc limit 1");
		if($row['time']>time()-60){
			exit('{"code":-1,"msg":"两次发送邮件之间需要相隔60秒！"}');
		}
		$count=$DB->getColumn("select count(*) from pre_regcode where `to`='$email' and time>'".(time()-3600*24)."'");
		if($count>6){
			exit('{"code":-1,"msg":"该邮箱发送次数过多，请更换邮箱！"}');
		}
		$count=$DB->getColumn("select count(*) from pre_regcode where ip='$clientip' and time>'".(time()-3600*24)."'");
		if($count>10){
			exit('{"code":-1,"msg":"你今天发送次数过多，已被禁止发送"}');
		}
		$sub = $conf['sitename'].' - 验证码获取';
		$code = rand(1111111,9999999);
		if($situation=='settle')$msg = '您正在修改结算账号信息，验证码是：'.$code;
		elseif($situation=='mibao')$msg = '您正在修改密保邮箱，验证码是：'.$code;
		elseif($situation=='bind')$msg = '您正在绑定新邮箱，验证码是：'.$code;
		else $msg = '您的验证码是：'.$code;
		$result = send_mail($email, $sub, $msg);
		if($result===true){
			if($DB->exec("insert into `pre_regcode` (`uid`,`type`,`code`,`to`,`time`,`ip`,`status`) values ('".$uid."','2','".$code."','".$email."','".time()."','".$clientip."','0')")){
				$_SESSION['send_mail']=time();
				exit('{"code":0,"msg":"succ"}');
			}else{
				exit('{"code":-1,"msg":"写入数据库失败。'.$DB->error().'"}');
			}
		}else{
			file_put_contents('mail.log',$result);
			exit('{"code":-1,"msg":"邮件发送失败"}');
		}
	}
break;
case 'verifycode':
	$code=trim(daddslashes($_POST['code']));
	if($conf['verifytype']==1){
		$row=$DB->getRow("select * from pre_regcode where uid='$uid' and type=3 and `to`='{$userrow['phone']}' order by id desc limit 1");
	}else{
		$row=$DB->getRow("select * from pre_regcode where uid='$uid' and type=2 and `to`='{$userrow['email']}' order by id desc limit 1");
	}
	if (!$row) {
		exit('{"code":-1,"msg":"请重新获取验证码！"}');
    }elseif($row['time']<time()-3600 || $row['status']>0 || $row['errcount']>=5){
		exit('{"code":-1,"msg":"验证码已失效，请重新获取"}');
	}elseif($row['code']!=$code){
		$DB->exec("update `pre_regcode` set `errcount`=`errcount`+1 where `id`='{$row['id']}'");
		exit('{"code":-1,"msg":"验证码不正确！"}');
	}
	$_SESSION['verify_ok']=$uid;
	$DB->exec("update `pre_regcode` set `status` ='1' where `id`='{$row['id']}'");
	exit('{"code":1,"msg":"succ"}');
break;
case 'completeinfo':
	$type=intval($_POST['stype']);
	$account=daddslashes(htmlspecialchars(strip_tags(trim($_POST['account']))));
	$username=daddslashes(htmlspecialchars(strip_tags(trim($_POST['username']))));
	$email=daddslashes(htmlspecialchars(strip_tags(trim($_POST['email']))));
	$qq=daddslashes(htmlspecialchars(strip_tags(trim($_POST['qq']))));
	$url=daddslashes(htmlspecialchars(strip_tags(trim($_POST['url']))));

	if($account==null || $username==null || $qq==null || $url==null){
		exit('{"code":-1,"msg":"请确保每项都不为空"}');
	}
	if(!empty($userrow['account']) && !empty($userrow['username'])){
		exit('{"code":-1,"msg":"你已完善相关信息"}');
	}
	if($type==1 && strlen($account)!=11 && strpos($account,'@')==false){
		exit('{"code":-1,"msg":"请填写正确的支付宝账号！"}');
	}
	if($type==2 && strlen($account)<3){
		exit('{"code":-1,"msg":"请填写正确的微信"}');
	}
	if($type==3 && (strlen($account)<5 || strlen($account)>10 || !is_numeric($account))){
		exit('{"code":-1,"msg":"请填写正确的QQ号码"}');
	}
	if(strlen($qq)<5 || strlen($qq)>10 || !is_numeric($qq)){
		exit('{"code":-1,"msg":"请填写正确的QQ"}');
	}
	if(strlen($url)<4 || strpos($url,'.')==false){
		exit('{"code":-1,"msg":"请填写正确的网站域名！"}');
	}
	if($conf['verifytype']==1){
		if(!preg_match('/^[A-z0-9._-]+@[A-z0-9._-]+\.[A-z0-9._-]+$/', $email)){
			exit('{"code":-1,"msg":"邮箱格式不正确"}');
		}
		if($email!=$userrow['email']){
			$row=$DB->getRow("select * from pre_user where email='$email' limit 1");
			if($row){
				exit('{"code":-1,"msg":"该邮箱已经绑定过其它商户，如需找回，请退出登录后找回密码"}');
			}
			$sqls=",`email` ='{$email}'";
		}
	}
	$sqs=$DB->exec("update `pre_user` set `settle_id` ='{$type}',`account` ='{$account}',`username` ='{$username}',`qq` ='{$qq}',`url` ='{$url}'{$sqls} where `uid`='$uid'");
	if($sqs!==false){
		exit('{"code":1,"msg":"succ"}');
	}else{
		exit('{"code":-1,"msg":"保存失败！'.$DB->error().'"}');
	}
break;
case 'edit_settle':
	$type=intval($_POST['stype']);
	$account=daddslashes(htmlspecialchars(strip_tags(trim($_POST['account']))));
	$username=daddslashes(htmlspecialchars(strip_tags(trim($_POST['username']))));

	if($account==null || $username==null){
		exit('{"code":-1,"msg":"请确保每项都不为空"}');
	}
	if($type==1 && strlen($account)!=11 && strpos($account,'@')==false){
		exit('{"code":-1,"msg":"请填写正确的支付宝账号！"}');
	}
	if($type==2 && strlen($account)<3){
		exit('{"code":-1,"msg":"请填写正确的微信"}');
	}
	if($type==3 && (strlen($account)<5 || strlen($account)>10 || !is_numeric($account))){
		exit('{"code":-1,"msg":"请填写正确的QQ号码"}');
	}
	if($userrow['type']!=2 && !empty($userrow['account']) && !empty($userrow['username']) && ($userrow['account']!=$account || $userrow['username']!=$username) && $_SESSION['verify_ok']!==$uid){
		if($conf['verifytype']==1 && (empty($userrow['phone']) || strlen($userrow['phone'])!=11)){
			exit('{"code":-1,"msg":"请先绑定手机号码！"}');
		}elseif($conf['verifytype']==0 && (empty($userrow['email']) || strpos($userrow['email'],'@')===false)){
			exit('{"code":-1,"msg":"请先绑定邮箱！"}');
		}
		exit('{"code":2,"msg":"need verify"}');
	}
	$sqs=$DB->exec("update `pre_user` set `settle_id` ='{$type}',`account` ='{$account}',`username` ='{$username}' where `uid`='$uid'");
	if($sqs!==false){
		exit('{"code":1,"msg":"succ"}');
	}else{
		exit('{"code":-1,"msg":"保存失败！'.$DB->error().'"}');
	}
break;
case 'edit_info':
	$email=daddslashes(htmlspecialchars(strip_tags(trim($_POST['email']))));
	$qq=daddslashes(htmlspecialchars(strip_tags(trim($_POST['qq']))));
	$url=daddslashes(htmlspecialchars(strip_tags(trim($_POST['url']))));
	$keylogin=intval($_POST['keylogin']);

	if($qq==null || $url==null){
		exit('{"code":-1,"msg":"请确保每项都不为空"}');
	}
	if(strlen($qq)<5 || strlen($qq)>10 || !is_numeric($qq)){
		exit('{"code":-1,"msg":"请填写正确的QQ"}');
	}
	if(strlen($url)<4 || strpos($url,'.')==false){
		exit('{"code":-1,"msg":"请填写正确的网站域名！"}');
	}
	if($conf['verifytype']==1){
		if($email!=$userrow['email']){
			$row=$DB->getRow("select * from pre_user where email='$email' limit 1");
			if($row){
				exit('{"code":-1,"msg":"该邮箱已经绑定过其它商户，如需找回，请退出登录后找回密码"}');
			}
			if(!preg_match('/^[A-z0-9._-]+@[A-z0-9._-]+\.[A-z0-9._-]+$/', $email)){
				exit('{"code":-1,"msg":"邮箱格式不正确"}');
			}
		}
		$sqs=$DB->exec("update `pre_user` set `email` ='{$email}',`qq` ='{$qq}',`url` ='{$url}',`keylogin` ='{$keylogin}' where `uid`='$uid'");
	}else{
		$sqs=$DB->exec("update `pre_user` set `qq` ='{$qq}',`url` ='{$url}',`keylogin` ='{$keylogin}' where `uid`='$uid'");
	}
	if($sqs!==false){
		exit('{"code":1,"msg":"succ"}');
	}else{
		exit('{"code":-1,"msg":"保存失败！'.$DB->error().'"}');
	}
break;
case 'edit_channel_info':
	$setting=$_POST['setting'];
	$channelinfo = json_encode($setting);

	$sqs=$DB->exec("UPDATE `pre_user` SET `channelinfo`=:channelinfo WHERE `uid`='$uid'", [':channelinfo'=>$channelinfo]);
	if($sqs!==false){
		exit('{"code":1,"msg":"succ"}');
	}else{
		exit('{"code":-1,"msg":"保存失败！'.$DB->error().'"}');
	}
break;
case 'edit_mode':
	$mode=intval($_POST['mode']);

	$sqs=$DB->exec("update `pre_user` set `mode` ='{$mode}' where `uid`='$uid'");
	if($sqs!==false){
		exit('{"code":1,"msg":"succ"}');
	}else{
		exit('{"code":-1,"msg":"保存失败！'.$DB->error().'"}');
	}
break;
case 'edit_bind':
	$email=daddslashes(htmlspecialchars(strip_tags(trim($_POST['email']))));
	$phone=daddslashes(htmlspecialchars(strip_tags(trim($_POST['phone']))));
	$code=daddslashes(trim($_POST['code']));

	if($code==null || $email==null && $phone==null){
		exit('{"code":-1,"msg":"请确保每项都不为空"}');
	}
	if(empty($_SESSION['verify_ok']) || $_SESSION['verify_ok']!=$uid){
		if($conf['verifytype']==1 && !empty($userrow['phone']) && strlen($userrow['phone'])==11){
			exit('{"code":2,"msg":"请先完成验证"}');
		}elseif($conf['verifytype']==0 && !empty($userrow['email']) && strpos($userrow['email'],'@')!==false && !empty($email) && empty($phone)){
			exit('{"code":2,"msg":"请先完成验证"}');
		}
	}
	if($conf['verifytype']==1 || $conf['verifytype']==0 && empty($email) && !empty($phone)){
		$row=$DB->getRow("select * from pre_regcode where uid='$uid' and type=3 and `to`='$phone' order by id desc limit 1");
	}else{
		$row=$DB->getRow("select * from pre_regcode where uid='$uid' and type=2 and `to`='$email' order by id desc limit 1");
	}
	if (!$row) {
		exit('{"code":-1,"msg":"请重新获取验证码！"}');
    }elseif($row['time']<time()-3600 || $row['status']>0 || $row['errcount']>=5){
		exit('{"code":-1,"msg":"验证码已失效，请重新获取"}');
	}elseif($row['code']!=$code){
		$DB->exec("update `pre_regcode` set `errcount`=`errcount`+1 where `id`='{$row['id']}'");
		exit('{"code":-1,"msg":"验证码不正确！"}');
	}
	if($conf['verifytype']==1 || $conf['verifytype']==0 && empty($email) && !empty($phone)){
		$sqs=$DB->exec("update `pre_user` set `phone` ='{$phone}' where `uid`='$uid'");
	}else{
		$sqs=$DB->exec("update `pre_user` set `email` ='{$email}' where `uid`='$uid'");
	}
	if($sqs!==false){
		exit('{"code":1,"msg":"succ"}');
	}else{
		exit('{"code":-1,"msg":"保存失败！'.$DB->error().'"}');
	}
break;
case 'checkbind':
	if($conf['verifytype']==1 && (empty($userrow['phone']) || strlen($userrow['phone'])!=11)){
		exit('{"code":1,"msg":"bind"}');
	}elseif($conf['verifytype']==0 && (empty($userrow['email']) || strpos($userrow['email'],'@')===false)){
		exit('{"code":1,"msg":"bind"}');
	}elseif(isset($_SESSION['verify_ok']) && $_SESSION['verify_ok']===$uid){
		exit('{"code":1,"msg":"bind"}');
	}else{
		exit('{"code":2,"msg":"need verify"}');
	}
break;
case 'resetKey':
	if(isset($_POST['submit'])){
		$key = random(32);
		$sql = "UPDATE pre_user SET `key`='$key' WHERE uid='$uid'";
		if($DB->exec($sql)!==false)exit('{"code":0,"msg":"重置密钥成功","key":"'.$key.'"}');
		else exit('{"code":-1,"msg":"重置密钥失败['.$DB->error().']"}');
	}
break;
case 'edit_pwd':
	$oldpwd=trim($_POST['oldpwd']);
	$newpwd=trim($_POST['newpwd']);
	$newpwd2=trim($_POST['newpwd2']);

	if(!empty($userrow['pwd']) && $oldpwd==null || $newpwd==null || $newpwd2==null){
		exit('{"code":-1,"msg":"请确保每项都不为空"}');
	}
	if(!empty($userrow['pwd']) && getMd5Pwd($oldpwd, $uid)!=$userrow['pwd']){
		exit('{"code":-1,"msg":"旧密码不正确"}');
	}
	if($newpwd!=$newpwd2){
		exit('{"code":-1,"msg":"两次输入密码不一致！"}');
	}
	if($oldpwd==$newpwd){
		exit('{"code":-1,"msg":"旧密码和新密码相同！"}');
	}
	if (strlen($newpwd) < 6) {
		exit('{"code":-1,"msg":"新密码不能低于6位"}');
	}elseif ($newpwd == $userrow['email']) {
		exit('{"code":-1,"msg":"新密码不能和邮箱相同"}');
	}elseif ($newpwd == $userrow['phone']) {
		exit('{"code":-1,"msg":"新密码不能和手机号码相同"}');
	}elseif (is_numeric($newpwd)) {
		exit('{"code":-1,"msg":"新密码不能为纯数字"}');
	}
	$pwd = getMd5Pwd($newpwd, $uid);
	$sqs=$DB->exec("update `pre_user` set `pwd` ='{$pwd}' where `uid`='$uid'");
	if($sqs!==false){
		exit('{"code":1,"msg":"修改密码成功！请牢记新密码"}');
	}else{
		exit('{"code":-1,"msg":"修改密码失败！'.$DB->error().'"}');
	}
break;
case 'edit_codename':
	$codename=daddslashes(htmlspecialchars(strip_tags(trim($_POST['codename']))));

	$sqs=$DB->exec("update `pre_user` set `codename` ='{$codename}' where `uid`='$uid'");
	if($sqs!==false){
		exit('{"code":1,"msg":"保存成功！"}');
	}else{
		exit('{"code":-1,"msg":"保存失败！'.$DB->error().'"}');
	}
break;
case 'certificate':
	$certname=htmlspecialchars(strip_tags(trim($_POST['certname'])));
	$certno=htmlspecialchars(strip_tags(trim($_POST['certno'])));
	$certtype=intval($_POST['certtype']);
	if(!$_POST['csrf_token'] || $_POST['csrf_token']!=$_SESSION['csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	if($userrow['cert']==1 &&($certtype==0 || $certtype==1 && $userrow['certtype']==1))exit('{"code":-1,"msg":"你已完成实名认证"}');
	if($conf['cert_money']>0 && $userrow['money']<$conf['cert_money'])exit('{"code":-1,"msg":"账户余额不足'.$conf['cert_money'].'元，无法完成认证"}');
	if(empty($certname) || empty($certno))exit('{"code":-1,"msg":"请确保各项不能为空"}');
	if(strlen($certname)<3)exit('{"code":-1,"msg":"姓名填写错误"}');
	if(!is_idcard($certno))exit('{"code":-1,"msg":"身份证号不正确"}');
	/*$row=$DB->getRow("SELECT uid,phone,email FROM pre_user WHERE certname=:certname AND certno=:certno AND cert=1 LIMIT 1", [':certno'=>$certno, ':certname'=>$certname]);
	if($row){
		exit('{"code":-2,"msg":"账号:'.($row['phone']?$row['phone']:$row['email']).'(商户ID:'.$row['uid'].')已经使用此身份认证，是否将该认证信息关联到当前商户？关联需要输入商户ID '.$row['uid'].' 的商户密钥","uid":"'.$row['uid'].'"}');
	}*/
	if($certtype==1){
		$certcorpno=htmlspecialchars(strip_tags(trim($_POST['certcorpno'])));
		$certcorpname=htmlspecialchars(strip_tags(trim($_POST['certcorpname'])));
		if(empty($certcorpno) || empty($certcorpname))exit('{"code":-1,"msg":"公司名称和营业执照号码不能为空"}');
		$checkres = check_corp_cert($certcorpname, $certcorpno, $certname);
		if($checkres['code']!=0)exit('{"code":-1,"msg":"'.$checkres['msg'].'"}');
	}
	if($conf['cert_open'] == 1){ //支付宝身份验证
		if(!$conf['cert_channel'])exit('{"code":-1,"msg":"未配置支付宝身份验证通道"}');
		$channel = \lib\Channel::get($conf['cert_channel']);
		if(!$channel)exit('{"code":-1,"msg":"当前实名认证通道信息不存在"}');
		define("IN_PLUGIN", true);
		define("PAY_ROOT", PLUGIN_ROOT.'alipay/');
		require_once PAY_ROOT."inc/AlipayCertifyService.php";
		$config['cert_return_url'] = 'alipays://platformapi/startapp?appId=20000067&url='.urlencode($siteurl.'user/alipaycertok.php?state='.urlencode(authcode($uid, 'ENCODE', SYS_KEY)));
		$certify = new AlipayCertifyService($config);
		$outer_order_no = date("YmdHis").rand(000,999).$uid;
		$certifyResult = $certify->initialize($outer_order_no, $certname, $certno, 'SMART_FACE');
		if(isset($certifyResult['certify_id'])){
			$_SESSION[$uid.'_certify']=true;
			$sqs=$DB->exec("update `pre_user` set `cert`=0,`certtype`=:certtype,`certmethod`=:certmethod,`certno`=:certno,`certname`=:certname,`certtoken`=:certtoken where `uid`=:uid", [':certtype'=>$certtype, ':certmethod'=>0, ':certno'=>$certno, ':certname'=>$certname, ':certtoken'=>$certifyResult['certify_id'], ':uid'=>$uid]);
			if($sqs!==false){
				if ($certtype==1) {
					$DB->exec("update `pre_user` set `certcorpno`=:certcorpno,`certcorpname`=:certcorpname where `uid`=:uid", [':certcorpno'=>$certcorpno, ':certcorpname'=>$certcorpname, ':uid'=>$uid]);
				}
				exit(json_encode(['code'=>1, 'msg'=>'ok', 'certify_id'=>$certifyResult['certify_id']]));
			}else{
				exit('{"code":-1,"msg":"保存信息失败'.$DB->error().'"}');
			}
		}else{
			exit('{"code":-1,"msg":"支付宝接口返回异常['.$certifyResult['sub_code'].']'.$certifyResult['sub_msg'].'"}');
		}
	}elseif($conf['cert_open'] == 2){ //手机号三要素实名认证
		if(empty($userrow['phone']))exit('{"code":-1,"msg":"你还未绑定手机号码"}');
		$res = check_cert($certno, $certname, $userrow['phone']);
		if($res['code']==0){
			$sqs=$DB->exec("update `pre_user` set `cert`=1,`certtype`=:certtype,`certmethod`=:certmethod,`certno`=:certno,`certname`=:certname,`certtime`=NOW() where `uid`=:uid", [':certtype'=>$certtype, ':certmethod'=>2, ':certno'=>$certno, ':certname'=>$certname, ':uid'=>$uid]);
			if($conf['cert_money']>0){
				changeUserMoney($uid, $conf['cert_money'], false, '实名认证');
			}
			exit('{"code":2,"msg":"恭喜您成功提交实名认证！"}');
		}else{
			exit('{"code":-1,"msg":"认证结果：'.$res['msg'].'"}');
		}
	}elseif($conf['cert_open'] == 3){ //支付宝实名信息验证
		if(!$conf['cert_channel'])exit('{"code":-1,"msg":"未配置支付宝实名信息验证通道"}');
		$channel = \lib\Channel::get($conf['cert_channel']);
		if(!$channel)exit('{"code":-1,"msg":"当前实名认证通道信息不存在"}');
		define("IN_PLUGIN", true);
		define("PAY_ROOT", PLUGIN_ROOT.'alipay/');
		require_once PAY_ROOT."inc/AlipayCertdocService.php";
		$certdoc = new AlipayCertdocService($config);
		$result = $certdoc->preconsult($certname, $certno);
		if(isset($result['verify_id'])){
			$_SESSION[$uid.'_certify']=true;
			$sqs=$DB->exec("update `pre_user` set `cert`=0,`certtype`=:certtype,`certmethod`=:certmethod,`certno`=:certno,`certname`=:certname,`certtoken`=:certtoken where `uid`=:uid", [':certtype'=>$certtype, ':certmethod'=>0, ':certno'=>$certno, ':certname'=>$certname, ':certtoken'=>$result['verify_id'], ':uid'=>$uid]);
			if($sqs!==false){
				if ($certtype==1) {
					$DB->exec("update `pre_user` set `certcorpno`=:certcorpno,`certcorpname`=:certcorpname where `uid`=:uid", [':certcorpno'=>$certcorpno, ':certcorpname'=>$certcorpname, ':uid'=>$uid]);
				}
				exit(json_encode(['code'=>1, 'msg'=>'ok', 'verify_id'=>$result['verify_id']]));
			}else{
				exit('{"code":-1,"msg":"保存信息失败'.$DB->error().'"}');
			}
		}else{
			exit('{"code":-1,"msg":"支付宝接口返回异常['.$result['sub_code'].']'.$result['sub_msg'].'"}');
		}
	}elseif($conf['cert_open'] == 4){ //微信扫码实名认证
		if(!$conf['cert_qcloudid'] || !$conf['cert_qcloudkey'])exit('{"code":-1,"msg":"未配置腾讯云SecretId和SecretKey"}');
		$qcloud = new \lib\QcloudFaceid($conf['cert_qcloudid'], $conf['cert_qcloudkey']);
		$callbackurl = $siteurl.'user/alipaycertok.php?state='.$uid;
		$result = $qcloud->GetRealNameAuthToken($certname, $certno, $callbackurl);
		if(isset($result['AuthToken'])){
			$_SESSION[$uid.'_certify']=true;
			$_SESSION['qrcode_url'] = $result['RedirectURL'];
			$sqs=$DB->exec("update `pre_user` set `cert`=0,`certtype`=:certtype,`certmethod`=:certmethod,`certno`=:certno,`certname`=:certname,`certtoken`=:certtoken where `uid`=:uid", [':certtype'=>$certtype, ':certmethod'=>1, ':certno'=>$certno, ':certname'=>$certname, ':certtoken'=>$result['AuthToken'], ':uid'=>$uid]);
			if($sqs!==false){
				if ($certtype==1) {
					$DB->exec("update `pre_user` set `certcorpno`=:certcorpno,`certcorpname`=:certcorpname where `uid`=:uid", [':certcorpno'=>$certcorpno, ':certcorpname'=>$certcorpname, ':uid'=>$uid]);
				}
				exit(json_encode(['code'=>1, 'msg'=>'ok', 'wx_token'=>$result['AuthToken']]));
			}else{
				exit('{"code":-1,"msg":"保存信息失败'.$DB->error().'"}');
			}
		}else{
			exit('{"code":-1,"msg":"接口返回异常['.$result['Error']['Code'].']'.$result['Error']['Message'].'"}');
		}
	}elseif($conf['cert_open'] == 5){ //阿里云金融级实人认证
		if(!$conf['cert_aliyunid'] || !$conf['cert_aliyunkey'] || !$conf['cert_aliyunsceneid'])exit('{"code":-1,"msg":"未配置阿里云接口信息"}');
		$aliyun = new \lib\AliyunCertify($conf['cert_aliyunid'], $conf['cert_aliyunkey'], $conf['cert_aliyunsceneid']);
		$outer_order_no = date("YmdHis").rand(000,999).$uid;
		$return_url = 'alipays://platformapi/startapp?appId=20000067&url='.urlencode($siteurl.'user/alipaycertok.php?state='.urlencode(authcode($uid, 'ENCODE', SYS_KEY)));
		$result = $aliyun->initialize($outer_order_no, $certname, $certno, $return_url);
        if (isset($result['Code']) && $result['Code']==200) {
			$_SESSION[$uid.'_certify']=true;
			$_SESSION['qrcode_url'] = $result['Data']['certifyUrl'];
			$sqs=$DB->exec("update `pre_user` set `cert`=0,`certtype`=:certtype,`certmethod`=:certmethod,`certno`=:certno,`certname`=:certname,`certtoken`=:certtoken where `uid`=:uid", [':certtype'=>$certtype, ':certmethod'=>0, ':certno'=>$certno, ':certname'=>$certname, ':certtoken'=>$result['Data']['certifyId'], ':uid'=>$uid]);
			if($sqs!==false){
				if ($certtype==1) {
					$DB->exec("update `pre_user` set `certcorpno`=:certcorpno,`certcorpname`=:certcorpname where `uid`=:uid", [':certcorpno'=>$certcorpno, ':certcorpname'=>$certcorpname, ':uid'=>$uid]);
				}
				exit(json_encode(['code'=>1, 'msg'=>'ok', 'certify_id'=>$result['Data']['certifyId']]));
			}else{
				exit('{"code":-1,"msg":"保存信息失败'.$DB->error().'"}');
			}
        }else{
			exit('{"code":-1,"msg":"接口返回异常['.$result['Code'].']'.$result['Message'].'"}');
		}
	}else{
		exit('{"code":-1,"msg":"网站未开启实名认证功能"}');
	}
break;
case 'cert_geturl':
	if(!$_POST['csrf_token'] || $_POST['csrf_token']!=$_SESSION['csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	if(isset($_SESSION[$uid.'_certify'])){
		if($conf['cert_open'] == 1){
			$url = $siteurl.'user/alipaycert.php?uid='.$uid.'&certtoken='.$userrow['certtoken'];
			exit(json_encode(['code'=>1, 'msg'=>'ok', 'url'=>$url]));
		}elseif($conf['cert_open'] == 3){
			$channel = \lib\Channel::get($conf['cert_channel']);
			if(!$channel)exit('{"code":-1,"msg":"当前实名认证通道信息不存在"}');
			define("IN_PLUGIN", true);
			define("PAY_ROOT", PLUGIN_ROOT.'alipay/');
			require_once PAY_ROOT."inc/AlipayCertdocService.php";
			$certdoc = new AlipayCertdocService($config);
			$state = authcode($uid, 'ENCODE', SYS_KEY);
			$url = $certdoc->getOauthUrl($verify_id, $state);
			exit(json_encode(['code'=>1, 'msg'=>'ok', 'url'=>$url]));
		}else{
			$url = $_SESSION['qrcode_url'];
			if(!$url)exit('{"code":-1,"msg":"二维码图片不存在"}');
			exit(json_encode(['code'=>1, 'msg'=>'ok', 'url'=>$url]));
		}
	}else{
		exit('{"code":-1,"msg":"Access Denied"}');
	}
break;
case 'cert_query':
	if(!$_POST['csrf_token'] || $_POST['csrf_token']!=$_SESSION['csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	$cert = $DB->getColumn("select cert from pre_user where uid=$uid");
	if($cert == 1){
		unset($_SESSION[$uid.'_certify']);
		unset($_SESSION['qrcode_url']);
		exit('{"code":1,"msg":"succ","passed":true}');
	}else{
		exit('{"code":1,"msg":"succ","passed":false}');
	}
break;
/*case 'cert_bind':
	$touid=intval($_POST['touid']);
	$certname=daddslashes(htmlspecialchars(strip_tags(trim($_POST['certname']))));
	$certno=daddslashes(htmlspecialchars(strip_tags(trim($_POST['certno']))));
	if(!$_POST['csrf_token'] || $_POST['csrf_token']!=$_SESSION['csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	if($userrow['cert']==1)exit('{"code":-1,"msg":"你已完成实名认证"}');
	if(empty($certname) || empty($certno))exit('{"code":-1,"msg":"请确保各项不能为空"}');
	if(strlen($certname)<3)exit('{"code":-1,"msg":"姓名填写错误"}');
	if(!is_idcard($certno))exit('{"code":-1,"msg":"身份证号不正确"}');
	$row=$DB->getRow("SELECT uid,certname,certno,cert FROM pre_user WHERE uid='$touid' LIMIT 1");
	if($row && $row['cert']==1 && $row['certname']==$certname && $row['certno']==$certno){
		$sqs=$DB->exec("update `pre_user` set `cert`='1',`certno`='{$certno}',`certname`='{$certname}',`certtime`='{$date}' where `uid`='$uid'");
		if($sqs!==false){
			exit('{"code":1,"msg":"关联实名认证成功！"}');
		}else{
			exit('{"code":-1,"msg":"关联实名认证失败！'.$DB->error().'"}');
		}
	}else{
		exit('{"code":-1,"msg":"关联实名认证失败！"}');
	}
break;*/
case 'notify':
	$trade_no=daddslashes(trim($_POST['trade_no']));
	$row=$DB->getRow("select * from pre_order where trade_no='$trade_no' AND uid=$uid limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前订单不存在！"}');
	if($row['status']==0)exit('{"code":-1,"msg":"订单尚未支付，无法重新通知！"}');
	$url=creat_callback_user($row,$userrow['key']);
	if($row['notify']>0)
		$DB->exec("update pre_order set notify=0 where trade_no='$trade_no'");
	exit('{"code":0,"url":"'.($_POST['isreturn']==1?$url['return']:$url['notify']).'"}');
break;
case 'settle_result':
	$id=intval($_GET['id']);
	$row=$DB->getRow("select * from pre_settle where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前结算记录不存在！"}');
	$result = ['code'=>0,'msg'=>$row['result']];
	exit(json_encode($result));
break;
case 'recharge':
	$money=trim(daddslashes($_POST['money']));
	$typeid=intval($_POST['typeid']);
	$name = '充值余额 UID:'.$uid;
	if(!$_POST['csrf_token'] || $_POST['csrf_token']!=$_SESSION['csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	if($userrow['pay']==0)exit('{"code":-1,"msg":"当前商户已被封禁"}');
	//if($conf['cert_force']==1 && $userrow['cert']==0)exit('{"code":-1,"msg":"当前商户未完成实名认证，无法收款"}');
	if($money<=0 || !is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"金额不合法"}');
	if($conf['pay_maxmoney']>0 && $money>$conf['pay_maxmoney'])exit('{"code":-1,"msg":"最大支付金额是'.$conf['pay_maxmoney'].'元"}');
	if($conf['pay_minmoney']>0 && $money<$conf['pay_minmoney'])exit('{"code":-1,"msg":"最小支付金额是'.$conf['pay_minmoney'].'元"}');
	$trade_no=date("YmdHis").rand(11111,99999);
	$return_url=$siteurl.'user/recharge.php?ok=1&trade_no='.$trade_no;
	$domain=getdomain($return_url);
	if(!$DB->exec("INSERT INTO `pre_order` (`trade_no`,`out_trade_no`,`uid`,`tid`,`addtime`,`name`,`money`,`notify_url`,`return_url`,`domain`,`ip`,`status`) VALUES (:trade_no, :out_trade_no, :uid, 2, NOW(), :name, :money, :notify_url, :return_url, :domain, :clientip, 0)", [':trade_no'=>$trade_no, ':out_trade_no'=>$trade_no, ':uid'=>$uid, ':name'=>$name, ':money'=>$money, ':notify_url'=>$return_url, ':return_url'=>$return_url, ':domain'=>$domain, ':clientip'=>$clientip]))exit('{"code":-1,"msg":"创建订单失败，请返回重试！"}');
	unset($_SESSION['csrf_token']);
	$result = ['code'=>0, 'msg'=>'succ', 'url'=>'../submit2.php?typeid='.$typeid.'&trade_no='.$trade_no];
	exit(json_encode($result));
break;
case 'groupinfo':
	$gid=intval($_POST['gid']);
	$row=$DB->getRow("select * from pre_group where gid='$gid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前会员等级不存在！"}');
	if($row['isbuy']==0)
		exit('{"code":-1,"msg":"当前会员等级无法购买！"}');
	if($gid==$userrow['gid'])exit('{"code":-1,"msg":"你已购买此会员等级，请勿重复购买"}');
	if($row['expire']==0)$expire='永久';
	else $expire=$row['expire'].'个月';
	$result = ['code'=>0,'msg'=>'succ','gid'=>$gid,'name'=>$row['name'],'price'=>$row['price'],'expire'=>$expire];
	exit(json_encode($result));
break;
case 'groupbuy':
	$gid=intval($_POST['gid']);
	$row=$DB->getRow("select * from pre_group where gid='$gid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前会员等级不存在！"}');
	if($row['isbuy']==0)
		exit('{"code":-1,"msg":"当前会员等级无法购买！"}');
	if($gid==$userrow['gid'])exit('{"code":-1,"msg":"你已购买此会员等级，请勿重复购买"}');
	if(!$_POST['csrf_token'] || $_POST['csrf_token']!=$_SESSION['csrf_token'])exit('{"code":-1,"msg":"CSRF TOKEN ERROR"}');
	$money = $row['price'];
	$typeid=intval($_POST['typeid']);
	if($typeid==0){
		if($money>$userrow['money'])exit('{"code":-1,"msg":"余额不足，请选择其他方式支付"}');
		changeUserMoney($uid, $money, false, '购买会员');
		changeUserGroup($uid, $gid);
		unset($_SESSION['csrf_token']);
		$result = ['code'=>1, 'msg'=>'购买会员成功！'];
		exit(json_encode($result));
	}else{
		$name = '购买会员 #'.$gid.'#';
		$trade_no=date("YmdHis").rand(11111,99999);
		$return_url=$siteurl.'user/groupbuy.php?ok=1&trade_no='.$trade_no;
		$domain=getdomain($return_url);
		if(!$DB->exec("INSERT INTO `pre_order` (`trade_no`,`out_trade_no`,`uid`,`tid`,`addtime`,`name`,`money`,`notify_url`,`return_url`,`domain`,`ip`,`status`) VALUES (:trade_no, :out_trade_no, :uid, 4, NOW(), :name, :money, :notify_url, :return_url, :domain, :clientip, 0)", [':trade_no'=>$trade_no, ':out_trade_no'=>$trade_no, ':uid'=>$uid, ':name'=>$name, ':money'=>$money, ':notify_url'=>$return_url, ':return_url'=>$return_url, ':domain'=>$domain, ':clientip'=>$clientip]))exit('{"code":-1,"msg":"创建订单失败，请返回重试！"}');
		unset($_SESSION['csrf_token']);
		$result = ['code'=>0, 'msg'=>'succ', 'url'=>'../submit2.php?typeid='.$typeid.'&trade_no='.$trade_no];
		exit(json_encode($result));
	}
break;
case 'addDomain':
	if(!$conf['pay_domain_open']) exit('{"code":-1,"msg":"未开启授权支付域名添加"}');
	$domain = trim(daddslashes($_POST['domain']));
	if(empty($domain))exit('{"code":-1,"msg":"域名不能为空"}');
	if(!checkDomain($domain))exit('{"code":-1,"msg":"域名格式不正确"}');
	if($DB->getRow("select * from pre_domain where uid=:uid and domain=:domain limit 1", [':uid'=>$uid, ':domain'=>$domain]))
		exit('{"code":-1,"msg":"该域名已存在，请勿重复添加"}');
	if(!$DB->exec("INSERT INTO `pre_domain` (`uid`,`domain`,`status`,`addtime`) VALUES (:uid, :domain, 0, NOW())", [':uid'=>$uid, ':domain'=>$domain]))exit('{"code":-1,"msg":"添加失败'.$DB->error().'"}');
	exit(json_encode(['code'=>0, 'msg'=>'添加域名成功！']));
break;
case 'delDomain':
	if(!$conf['pay_domain_open']) exit('{"code":-1,"msg":"未开启授权支付域名添加"}');
	$id = intval($_POST['id']);
	if(!$DB->exec("DELETE FROM pre_domain WHERE id='$id' and uid='$uid'"))exit('{"code":-1,"msg":"删除失败'.$DB->error().'"}');
	exit(json_encode(['code'=>0, 'msg'=>'succ']));
break;

case 'orderList':
	$paytype = [];
	$paytypes = [];
	$rs = $DB->getAll("SELECT * FROM pre_type WHERE status=1");
	foreach($rs as $row){
		$paytype[$row['id']] = $row['showname'];
		$paytypes[$row['id']] = $row['name'];
	}
	unset($rs);

	$sql=" uid=$uid";
	if(isset($_POST['paytype']) && !empty($_POST['paytype'])) {
		$type = intval($_POST['paytype']);
		$sql.=" AND A.`type`='$type'";
	}elseif(isset($_POST['channel']) && !empty($_POST['channel'])) {
		$channel = intval($_POST['channel']);
		$sql.=" AND A.`channel`='$channel'";
	}
	if(isset($_POST['dstatus']) && $_POST['dstatus']>-1) {
		$dstatus = intval($_POST['dstatus']);
		$sql.=" AND A.status={$dstatus}";
	}
	if(!empty($_POST['starttime']) || !empty($_POST['endtime'])){
		if(!empty($_POST['starttime'])){
			$starttime = daddslashes($_POST['starttime']);
			$sql.=" AND A.addtime>='{$starttime} 00:00:00'";
		}
		if(!empty($_POST['endtime'])){
			$endtime = daddslashes($_POST['endtime']);
			$sql.=" AND A.addtime<='{$endtime} 23:59:59'";
		}
	}
	if(isset($_POST['kw']) && !empty($_POST['kw'])) {
		$kw=daddslashes($_POST['kw']);
		if($_POST['type']==1){
			$sql.=" AND A.`trade_no`='{$kw}'";
		}elseif($_POST['type']==2){
			$sql.=" AND A.`out_trade_no`='{$kw}'";
		}elseif($_POST['type']==3){
			$sql.=" AND A.`name` like '%{$kw}%'";
		}elseif($_POST['type']==4){
			$sql.=" AND A.`money`='{$kw}'";
		}elseif($_POST['type']==5){
			$sql.=" AND A.`realmoney`='{$kw}'";
		}elseif($_POST['type']==6){
			$sql.=" AND A.`domain`='{$kw}'";
		}
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_order A WHERE{$sql}");
	$list = $DB->getAll("SELECT A.*,B.plugin FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE{$sql} order by trade_no desc limit $offset,$limit");
	$list2 = [];
	foreach($list as $row){
		$row['typename'] = $paytypes[$row['type']];
		$row['typeshowname'] = $paytype[$row['type']];
		$list2[] = $row;
	}

	exit(json_encode(['total'=>$total, 'rows'=>$list2]));
break;
case 'recordList':
	$sql=" uid=$uid";
	if(isset($_POST['kw']) && !empty($_POST['kw'])) {
		$kw=daddslashes($_POST['kw']);
		if($_POST['type']==1){
			$sql.=" AND `type`='{$kw}'";
		}elseif($_POST['type']==2){
			$sql.=" AND `money`='{$kw}'";
		}elseif($_POST['type']==3){
			$sql.=" AND `trade_no`='{$kw}'";
		}
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_record WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_record WHERE{$sql} order by id desc limit $offset,$limit");

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}
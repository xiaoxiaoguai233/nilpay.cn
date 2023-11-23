<?php
@header('Content-Type: text/html; charset=UTF-8');

$admin_cdnpublic = 0;
if($admin_cdnpublic==1){
	$cdnpublic = 'https://lib.baomitu.com/';
}elseif($admin_cdnpublic==2){
	$cdnpublic = 'https://cdn.bootcdn.net/ajax/libs/';
}elseif($admin_cdnpublic==4){
	$cdnpublic = 'https://lf26-cdn-tos.bytecdntp.com/cdn/expire-1-M/';
}else{
	$cdnpublic = 'https://cdn.staticfile.org/';
}

switch($conf['user_style']){
	case 1: $style=['bg-black','bg-black','bg-white']; break;
	case 2: $style=['bg-dark','bg-white','bg-dark']; break;
	case 3: $style=['bg-dark','bg-dark','bg-light']; break;
	case 4: $style=['bg-info','bg-info','bg-black']; break;
	case 5: $style=['bg-info','bg-info','bg-white']; break;
	case 6: $style=['bg-primary','bg-primary','bg-dark']; break;
	case 7: $style=['bg-primary','bg-primary','bg-white']; break;
	default: $style=['bg-black','bg-white','bg-black']; break;
}

?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
  <meta charset="utf-8"/>
  <meta name="renderer" content="webkit">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?php echo $title ?></title>
  <link href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet"/>
  <script src="<?php echo $cdnpublic?>modernizr/2.8.3/modernizr.min.js"></script>
  <script src="<?php echo $cdnpublic?>jquery/2.1.4/jquery.min.js"></script>
  <script src="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
  
  
  <link rel="stylesheet" href="<?php echo $cdnpublic?>twitter-bootstrap/3.3.7/css/bootstrap.min.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $cdnpublic?>animate.css/3.5.2/animate.min.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $cdnpublic?>simple-line-icons/2.4.1/css/simple-line-icons.min.css" type="text/css" />
  
  <link rel="stylesheet" href="./assets/css/font.css" type="text/css" />
  <link rel="stylesheet" href="./assets/css/app.css" type="text/css" /><!-- Latest compiled and minified CSS -->
  
  <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-table/1.13.1/bootstrap-table.min.css">
  <!--[if lt IE 9]>
    <script src="<?php echo $cdnpublic?>html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="<?php echo $cdnpublic?>respond.js/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body>

<?php if($islogin==1){?>

<div class="app app-header-fixed  ">
  <!-- header -->
  <header id="header" class="app-header navbar" role="menu">
          <!-- navbar header -->
      <div class="navbar-header <?php echo $style[0]?>">
        <button class="pull-right visible-xs dk" ui-toggle="show" target=".navbar-collapse">
          <i class="glyphicon glyphicon-cog"></i>
        </button>
        <button class="pull-right visible-xs" ui-toggle="off-screen" target=".app-aside" ui-scroll="app">
          <i class="glyphicon glyphicon-align-justify"></i>
        </button>
        <!-- brand -->
        <a href="./" class="navbar-brand text-lt">
          <i class="fa fa-btc"></i>
          <span class="hidden-folded m-l-xs"><?php echo $conf['sitename']?></span>
        </a>
        <!-- / brand -->
      </div>
      <!-- / navbar header -->

      <!-- navbar collapse -->
      <div class="collapse pos-rlt navbar-collapse box-shadow <?php echo $style[1]?>">
        <!-- buttons -->
        <div class="nav navbar-nav hidden-xs">
          <a href="#" class="btn no-shadow navbar-btn" ui-toggle="app-aside-folded" target=".app">
            <i class="fa fa-dedent fa-fw text"></i>
            <i class="fa fa-indent fa-fw text-active"></i>
          </a>
        </div>
        <!-- / buttons -->

        <!-- nabar right -->
        <ul class="nav navbar-nav navbar-right">
          <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="dropdown-toggle clear" data-toggle="dropdown">
              <span class="thumb-sm avatar pull-right m-t-n-sm m-b-n-sm m-l-sm">
                <img src="<?php echo ($userrow['qq'])?'//q2.qlogo.cn/headimg_dl?bs=qq&dst_uin='.$userrow['qq'].'&src_uin='.$userrow['qq'].'&fid='.$userrow['qq'].'&spec=100&url_enc=0&referer=bu_interface&term_type=PC':'assets/img/user.png'?>">
                <i class="on md b-white bottom"></i>
              </span>
              <span class="hidden-sm hidden-md" style="text-transform:uppercase;"><?php echo $uid?></span> <b class="caret"></b>
            </a>
            <!-- dropdown -->
            <ul class="dropdown-menu animated fadeInRight w">
              <li>
                <a href="index.php">
                  <span>用户中心</span>
                </a>
              </li>
              <li>
                <a href="#">
                  <span>修改资料</span>
                </a>
              </li>
			  <li>
                <a href="#">
                  <span>修改密码</span>
                </a>
              </li>
              <li class="divider"></li>
              <li>
                <a ui-sref="access.signin" href="./login.php?logout">退出登录</a>
              </li>
            </ul>
            <!-- / dropdown -->
          </li>
        </ul>
        <!-- / navbar right -->
      </div>
      <!-- / navbar collapse -->
  </header>
  <!-- / header -->
  <!-- aside -->
  <aside id="aside" class="app-aside hidden-xs <?php echo $style[2]?>">
      <div class="aside-wrap">
        <div class="navi-wrap">

          <!-- nav -->
          <nav ui-nav class="navi clearfix">
            <ul class="nav">
              <li class="hidden-folded padder m-t m-b-sm text-muted text-xs">
                <span>导航按钮</span>
              </li>
              <li class="<?php echo checkIfActive('index,')?>">
                <a href="./">
                  <i class="glyphicon glyphicon-home icon text-primary-lt"></i>
                  <span class="font-bold">平台首页</span>
                </a>
              </li>
              
              <li class="<?php echo checkIfActive('order')?>">
                <a href="./order.php">
                  <i class="glyphicon  glyphicon-list-alt icon text-success-lt"></i>
                  <span class="font-bold">订单管理</span>
                </a>
              </li>
              
              
              <li class="<?php echo checkIfActive('slist')?>">
                <a href="./slist.php">
                  <i class="glyphicon  glyphicon glyphicon-jpy icon text-danger-lt"></i>
                  <span class="font-bold">结算管理</span>
                </a>
              </li>
              
              
		     <li class="<?php echo checkIfActive('ulist,glist,group,record,uset,domain')?>">
                <a hre="#" class="auto">      
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="glyphicon glyphicon glyphicon-user icon text-info-lt"></i>
                  <span>商户管理</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li><a href="./ulist.php">用户列表</a></li>
    			  <li><a href="./glist.php">用户组设置</a></li>
    			  <li><a href="./group.php">用户组购买</a></li>
    			  <li><a href="./record.php">资金明细</a></li>
                </ul>
              </li>
              
              
             <li class="<?php echo checkIfActive('pay_channel,pay_roll,pay_type,pay_plugin,pay_weixin')?>">
                <a hre="#" class="auto">      
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="glyphicon glyphicon glyphicon-dashboard icon text-warning-lt"></i>
                  <span>支付接口</span>
                </a>
                <ul class="nav nav-sub dk">
				  <li><a href="./pay_channel.php">支付通道</a></li>
			      <li><a href="./pay_type.php">支付方式</a></li>
			      <li><a href="./pay_plugin.php">支付插件</a></li>
                  <li><a href="./pay_roll.php">支付通道轮询</a></li>
                  <li><a href="./pay_weixin.php">公众号小程序</a></li>
                </ul>
              </li>
              
              
              <li class="<?php echo checkIfActive('set,gonggao')?>">
                <a hre="#" class="auto">      
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="glyphicon glyphicon-cog icon text-dark-lt"></i>
                  <span>系统设置</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li><a href="./set.php?mod=site">网站信息配置</a></li>
    			  <li><a href="./set.php?mod=pay">支付与结算配置</a><li>
    			  <li><a href="./set.php?mod=transfer">企业付款配置</a><li>
    			  <li><a href="./set.php?mod=oauth">快捷登录配置</a><li>
    			  <li><a href="./set.php?mod=certificate">实名认证配置</a><li>
    			  <li><a href="./gonggao.php">网站公告配置</a></li>
    			  <li><a href="./set.php?mod=template">首页模板配置</a><li>
    			  <li><a href="./set.php?mod=mail">邮箱与短信配置</a><li>
    			  <li><a href="./set.php?mod=upimg">网站Logo上传</a><li>
    			  <li><a href="./set.php?mod=cron">计划任务配置</a><li>
                </ul>
              </li>
              
              <li class="<?php echo checkIfActive('clean,log,transfer,risk,alipayrisk')?>">
                <a hre="#" class="auto">      
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="glyphicon glyphicon-flag icon text-success-dker"></i>
                  <span>其他功能</span>
                </a>
                <ul class="nav nav-sub dk">
    			  <li><a href="./transfer.php">企业付款</a><li>
    			  <li><a href="./risk.php">风控记录</a><li>
    			  <li><a href="./log.php">登录日志</a><li>
    			  <li><a href="./clean.php">数据清理</a><li>
                </ul>
              </li>
              
          </nav>
          <!-- nav -->

          <!-- aside footer -->
        
          <!-- / aside footer -->
        </div>
      </div>
  </aside>
  
  <script src="<?php echo $cdnpublic?>jquery/3.4.1/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="./assets/js/ui-load.js"></script>
<script src="./assets/js/ui-jp.config.js"></script>
<script src="./assets/js/ui-jp.js"></script>
<script src="./assets/js/ui-nav.js"></script>
<script src="./assets/js/ui-toggle.js"></script>

  <!-- / aside -->
  <!-- content -->
  
  <?php }?>
<?php /*a:2:{s:68:"C:\wwwroot\www.12tla.com\application\admin\view\index\backstage.html";i:1576504542;s:64:"C:\wwwroot\www.12tla.com\application\admin\view\common\menu.html";i:1574248198;}*/ ?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
	<title>管理后台</title>
	<link rel="stylesheet" type="text/css" href="/static/twelvet/backstage/backstage.css" />
	<link rel="stylesheet" type="text/css" href="/static/lib/font-awesome.css" />
	<script type="text/javascript">
		var config = <?php echo json_encode($config); ?>;
	</script>
</head>

<body class='fixed-sidebar gray-bg skin-1'>
	<div id="ctn">
		<!-- 侧侧导航 -->
		<nav class="navbar-side" role='navigation' id='side-right' data-switch='on'>
    <div id='slim-right'>
        <div id='wap-nav'>
            <i class='fa fa-times-circle'></i>
        </div>
        <!-- 账号资料开始 -->
        <div class='profile-ctn'>
            <div class="dropdown">
                <span><img alt="portrait" class="img-circle" width='64px' height="64px"
                        src="/static/logo.png" /></span>
                <a href="javascript:;" id="info-label" data-toggle="dropdown">
                    <span class="d-block m-t-xs"><strong class="font-bold">TwelveT</strong></span>
                    <span class='text-muted'>超级管理员<i class="fa fa-angle-down" aria-hidden="true"></i></span>
                </a>

                <div class="dropdown-menu" t-label="info-label">
                    <a class="dropdown-item" href="form_avatar.html">修改头像</a>
                    <a class="dropdown-item addtabs" href="javascript:;" data-url="<?php echo url('/user/index'); ?>">个人资料</a>
                    <a class="dropdown-item" href="contacts.html">联系我们</a>
                    <a class="dropdown-item" href="mailbox.html">信箱</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.html"><i class='fa fa-reply-all'></i> 安全退出</a>
                </div>
            </div>
        </div>
        <!-- 账号资料结束 -->
        <ul id='side-menu'>
            <?php echo $menu; ?>

            <!--以下连接可自行修改（建议为twelvet留下一个连接）-->
            <li id="related-links">相关连接</li>
            <li data-rel='external'><a href="https://jq.qq.com/?_wv=1027&k=5pN2BG8" target="_blank">
                    <i class="fa fa-qq text-aqua" style='color:#13a6b1'></i>
                    <span>交流群</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
		<!-- 侧侧导航结束 -->
		<!-- 右侧部分开始 -->
		<div id="page-ctn" class="gray-bg" role="container">
			<!-- 顶部导航开始 -->
			<nav class='navbar bg-primary' role="navigation">
				<div class='navbar-header'>
					<button class='btn btn-switch ml-20' id='switch-nav'>
						<i class='fa fa-bars'></i>
					</button>

					<a href='javascript:;' class='btn btn-light ml-20 T-menuItem' id='switch-nav'>
						<i class='fa fa-shopping-cart'></i>插件商城
					</a>
				</div>

				<ul class="d-none d-sm-flex justify-content-end nav">
					<li class="nav-item">
						<a href='/' class="" target='_blank' title='首页'>
							<i class="fa fa-home"></i>
						</a>
					</li>
					<li class="nav-item">
						<a href='javascript:;' data-toggle="checkupdate" data-url="<?php echo url('/system/update'); ?>" title='在线更新'>
							<i class="fa fa-refresh fa-spin"></i>
						</a>
					</li>
					<li class="nav-item">
						<a href='javascript:;' target='_blank' title='全屏' data-toggle="fullscreen">
							<i class="fa fa-arrows-alt"></i>
						</a>
					</li>
					<li class="nav-item dropdown">
						<a href="">
							<i class="fa fa-cog fa-spin"></i> 系统中心
						</a>
					</li>
				</ul>
			</nav>
			<!-- 顶部导航结束 -->
			<!-- 选项卡开始 -->
			<nav id='window' class='d-none d-sm-block'>
				<button type='button' class='p-s-left' id='window-tab-left'>
					<i class='fa fa-backward'></i>
				</button>

				<div id='window-container' class='pull-left'>
					<div id='window-content'>
						<a class='T-window-tab window-active' addtabs="0" data-url="<?php echo url("","",true,false);?>">
							控制台
						</a>
					</div>
				</div>

				<button type='button' id='window-tab-right' class='p-s-right'>
					<i class='fa fa-forward'></i>
				</button>

				<div class="dropdown f-right">
					<button class="dropdown-toggle" type="button" id="tabAdministration" data-toggle="dropdown">
						管理
					</button>
					<div class="dropdown-menu lr-auto" t-label="tabAdministration">
						<a href="javascript:void(0)" class='dropdown-item' id='window-load'><i
								class='fa fa-refresh fa-spin'></i> 刷新当前页面</a>
						<div class="dropdown-divider"></div>
						<a href="javascript:void(0)" class='dropdown-item' id='window-closeAll'><i
								class='fa fa-power-off'></i> 关闭全部选项卡</a>
						<a href="javascript:void(0)" class='dropdown-item' id='window-closeOter'><i
								class='fa fa-power-off'></i> 关闭其他选项卡</a>
					</div>
				</div>
			</nav>
			<!-- 选项卡结束 -->
			<!-- 内容容器开始 -->
			<div id='content'>
				<iframe class="iframe" src="" data-index='index.html' width='100%' height='100%'
					frameborder="0"></iframe>
			</div>
			<!-- 内容容器结束 -->
			<footer id="footer">
				<div class="pull-right">© <a href="http://www.twelvet.cn/" target="_blank">TwelveT</a>
				</div>
			</footer>
		</div>
		<!-- 右侧部分结束 -->
	</div>
	<script type="text/javascript" src="/static/lib/jquery-3.3.1.js"></script>
	<script type="text/javascript" src="/static/lib/layui/layui.js"></script>
	<script type="text/javascript" src='/static/lib/jquery.slimscroll.min.js'></script>
	<script type="text/javascript" src='/static/twelvet/backstage/backstage.js'></script>
</body>

</html>
<?php /*a:1:{s:64:"C:\wwwroot\www.12tla.com\application\admin\view\index\login.html";i:1578144829;}*/ ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>后台登录</title>
    <link rel="stylesheet" href="/static/lib/twelvet.css">
    <script type="text/javascript" src='/static/lib/vue.js'></script>
    <script type="text/javascript" src='/static/lib/axios.js'></script>
    <script type="text/javascript" src="/static/lib/layui/layui.js"></script>
    <style>
        body{height:100%;background:url(/static/twelvet/login/loginBG.jpg) no-repeat center center fixed;}
        #container{position:absolute;top:0;right:0;bottom:0;left:0;z-index:-1}
        .container{max-width:350px}
        .transparent{background:transparent;color:#eee;}
        .img-responsive{margin:0 auto;margin-top:75pt;max-width:90pt}
        #welcome{padding:20px;color:#eee;text-align:center;font-weight:700}
        .copyright{text-align:center}
        .copyright > a{color:#fff;text-decoration:none}
        .copyright > a:hover{color:#72afd2}
    </style>
</head>

<body>
    <div id='container'></div>
    <div class='container'>
        <div class='row'>
            <div class='col-xs-12'>
                <img src="/static/logo.png" class='img-responsive' alt="logo">
                <p id='welcome'>
                    TwelveT 极速后台管理系统
                </p>
                <form id='login' action='javascript:;'>
                    <div class='form-group'>
                        <input type="text" class='form-control transparent' v-model='userInfo.name' name='name'
                            placeholder='账号' id='user'>
                    </div>
                    <div class='form-group'>
                        <input type="password" class='form-control transparent' v-model='userInfo.password'
                            name='password' placeholder='密码' id='password'>
                    </div>
                    <?php if($config['twelvet']['login_captcha']): ?>
                    <div class='input-group'>
                        <input type="text" class='form-control transparent' v-model='userInfo.captcha' name='captcha'
                            placeholder='验证码' id='password'>
                        
                        <div class='input-group-append'>
                            <img :src="captcha" alt="captcha" title='看不清换一张' @click='captchaDo'
                                style='width:100px;height:33.5px;cursor:pointer' />
                        </div>
                    </div>
                    <?php endif; ?>
                    <input type="hidden" v-model="userInfo.token" />
                    <div class='form-group'>
                        <input type="submit" class='btn btn-twelvet' style='width:100%' value='登录' @click='login'>
                    </div>
                </form>
                <!-- 请为TwelveT保留版权标识，尊重劳动成果，谢谢 -->
                <div class='copyright'>
                    <a href="https://www.twelvet.cn" target="_blank">Powered By TwelveT</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        layui.use(['notice', 'layer'], function () {
            var notice = layui.notice,
                layer = layui.layer;
            //实例化vue
            var vue = new Vue({
                el: '.container',
                data: {
                    userInfo: {
                        name: '',
                        password: '',
                        token: '<?php echo htmlentities(app('request')->token()); ?>',
                        captcha: '',
                    },
                    captcha: '<?php echo captcha_src(); ?>'
                },
                mounted: function () {
                    //添加请求拦截器
                    axios.interceptors.request.use(function(config) {
                        //开始请求，开始动画
                        window.loading = layer.load();
                        //返回操作信息
                        return config
                    }, error => {
                        //返回错误信息
                        return Promise.reject(error)
                    })
                    //添加响应拦截器
                    axios.interceptors.response.use(function(response) {
                        //更新验证码
                        vue.$options.methods.captchaDo();
                        //请求完成结束动画
                        layer.close(window.loading);
                        //返回操作信息
                        return response
                    }, error => {
                        //更新验证码
                        vue.$options.methods.captchaDo();
                        //关闭动画
                        layer.close(window.loading);
                        //返回错误信息
                        return Promise.reject(error)
                    })
                },
                methods: {
                    //更新验证码
                    captchaDo: function () {
                        vue.captcha = "<?php echo captcha_src(); ?>?t=" + Date.parse(new Date()) / 1000;
                    },
                    login: function () {
                        //发送登录请求[地址，参数]
                        axios.post(window.location.pathname, this.userInfo).then((r) => {
                            let res = r.data;
                            if (res.code) {
                                // 根据后台返回的地址进行跳转
                                window.location.href = res.data.backstage;
                            } else {
                                notice.error(res.msg);
                                //更新token
                                vue.userInfo.token = res.data.token
                            }
                        }).catch(e => {
                            //捕获错误
                            notice.error(e)
                        })
                    }
                }
            })
        })
    </script>
</body>

</html>
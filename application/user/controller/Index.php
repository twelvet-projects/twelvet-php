<?php

namespace app\user\controller;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 前台权限控制器
 */

use think\Validate;
use think\facade\Config;
use think\facade\Cookie;
use think\facade\Hook;

class Index extends TwelveT
{
    protected $noLoginRequired = ['index'];
    protected $noRightRequired = ['backstage', 'destroy'];

    public function initialize()
    {
        parent::initialize();

        //监听注册登录注销的事件
        Hook::add('user_login_successed', function ($user) {
            // 是否需要保持7天登陆时间
            $expire = $this->request->post('keeplogin') ? 86400 * 7 : 0;
            Cookie::set('token', $this->_token, $expire);
        });
        Hook::add('user_register_successed', function ($user) {
            Cookie::set('uid', $user->id);
            //Cookie::set('token', $auth->getToken());
        });
        Hook::add('user_delete_successed', function ($user) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
        Hook::add('user_logout_successed', function ($user) {
            Cookie::delete('uid');
            Cookie::delete('token');
        });
    }
    /**
     * 后台主页
     */
    public function backstage()
    {
        return $this->fetch('backstage');
    }
    /**
     * 后台登录
     */
    public function index()
    {
        //自动生成后台地址，适配二级目录
        $url = url('index/backstage');
        //禁止重复登录
        if ($this->isLogin()) {
            $this->success('您已登录, 即将为您跳转', $url);
        }
        //判断是否登录请求
        if ($this->request->isPost()) {
            //获取信息
            $name       = $this->request->post('name');
            $password   = $this->request->post('password');
            $keeplogin  = '';
            $token      = $this->request->post('__token__');
            //定义规则
            $rule = [
                'name'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'token',
            ];
            //设置数据
            $data = [
                'name'  => $name,
                'password'  => $password,
                '__token__' => $token,
            ];
            //动态添加验证码字段
            if (Config::get('twelvet.login_captcha')) {
                $data['captcha'] = $this->request->post('captcha');
                $rule['captcha'] = 'require|captcha';
            }
            //执行验证与判断
            $validate = new Validate($rule, [], ['username' => '账号', 'password' => '密码', 'captcha' => '验证码']);
            if (!$validate->check($data)) {
                return tjson($validate->getError(), 0, ['token' => $this->request->token()]);
            }
            // 开始鉴权信息
            $result = $this->login($name, $password, $keeplogin ? 86400 * 7 : 0);
            if ($result['status'] === true) {
                // 登录成功后的扩展
                Hook::listen("user_login_after", $this->request);
                // 成功返回后台地址
                return tJson('', '', [
                    'state' => true,
                    'backstage' => $url
                ]);
            } else {
                //错误回应，带上token
                return tJson($result['msg'], 0, ['token' => $this->request->token()]);
            }
        }
        //渲染
        return $this->fetch('login');
    }
    /**
     * 注销后台登录
     */
    public function destroy()
    {
        $this->logout();
        // 退出成功后的扩展
        Hook::listen("user_login_after", $this->request);
        $this->success('成功退出', 'index/index');
    }
}

<?php

namespace app\admin\controller;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 后台基础视图控制器
 */

use think\Validate;
use think\facade\Config;
use think\facade\Hook;
use app\admin\model\AdminLog;

class Index extends TwelveT
{
    protected $noLoginRequired = ['index'];
    protected $noRightRequired = ['backstage', 'destroy'];

    public function initialize()
    {
        parent::initialize();
    }

    /**
     * 后台主页
     *
     * @return void
     */
    public function backstage()
    {
        //左侧菜单
        list($menulist, $navlist, $fixedmenu, $referermenu) = $this->auth->getSidebar([
            'dashboard' => 'hot',
            'addon'     => ['new', 'blue', 'badge'],
            'auth/rule' => '菜单',
            'general'   => ['new', 'purple'],
        ], 'fixedpage');
        $this->view->assign('menu', $menulist);
        return $this->fetch('backstage');
    }

    /**
     * 后台登录
     *
     * @return void
     */
    public function index()
    {
        //禁止重复登录
        $isAuth = $this->auth->isLogin();
        //自动生成后台地址，适配二级目录
        $url = url('index/backstage');
        if ($isAuth['status']) {
            $this->success('您已登录, 即将为您跳转', $url);
        }
        //判断是否登录请求
        if ($this->request->isPost()) {
            // 获取信息
            $name       = $this->request->post('name');
            $password   = $this->request->post('password');
            $keeplogin  = '';
            $token      = $this->request->post('token');
            //定义规则
            $rule = [
                'name'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                '__token__' => 'token',
            ];
            // 设置数据
            $data = [
                'name'  => $name,
                'password'  => $password,
                '__token__' => $token,
            ];
            // 动态添加验证码字段
            if (Config::get('twelvet.login_captcha')) {
                $data['captcha'] = $this->request->post('captcha');
                $rule['captcha'] = 'require|captcha';
            }
            // 判断是否验证通过
            $validate = new Validate($rule, [], ['name' => '账号', 'password' => '密码', 'captcha' => '验证码']);
            if (!$validate->check($data)) {
                return tjson(0, (String) $validate->getError(), ['token' => $this->request->token()]);
            }
            // 写入记录信息标题
            AdminLog::setTitle(__('Login'));
            // 开始鉴权信息
            $result = $this->auth->login($name, $password, $keeplogin ? 86400 * 7 : 0);
            if ($result === true) {
                // 登录成功后的扩展
                Hook::listen("admin_login_after", $this->request);
                // 成功返回后台地址
                return tJson(1, '登录成功', [
                    'state' => true,
                    'backstage' => $url
                ]);
            } else {
                // 错误回应，带上token
                return tJson(0, $this->auth->getError(), ['token' => $this->request->token()]);
            }
        }
        // 根据cookie,判断是否可以自动登录
        if ($this->auth->autoLogin()) {
            // $this->redirect($url);
        }
        //渲染
        return $this->view->fetch('login');
    }

    /**
     * 注销后台登录
     *
     * @return void
     */
    public function logout()
    {
        // 销毁鉴权信息
        $this->auth->logout();
        // 退出登录后的扩展
        Hook::listen("admin_logout_after", $this->request);
        $this->success('成功退出', 'index/index');
    }
}

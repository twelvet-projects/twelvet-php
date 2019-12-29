<?php

namespace app\admin\controller;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 后台权限控制器
 */

use app\admin\lib\Auth;
use think\Controller;
use think\facade\Config;
use think\facade\Session;
use think\facade\Hook;
use think\Loader;

class TwelveT extends Controller
{
    /**
     * 无需登录以及鉴权的方法
     *
     * @var array
     */
    protected $noLoginRequired = [];

    /**
     * 无需鉴权,但需要登录的方法
     *
     * @var array
     */
    protected $noRightRequired = [];

    /**
     * 权限控制
     *
     * @var [type]
     */
    protected $auth = null;

    /**
     * 应用初始化权限检查
     *
     * @return void
     */
    public function initialize()
    {
        // 获取模块名称
        $modulename = $this->request->module();
        // 获取控制器名称
        $controllername = Loader::parseName($this->request->controller());
        // 获取执行方法名称
        $actionname = strtolower($this->request->action());
        // 拼接路径
        $path = str_replace('.', '/', $controllername) . '/' . $actionname;

        // 定义是否Addtabs请求
        !defined('IS_ADDTABS') && define('IS_ADDTABS', input("addtabs") ? true : false);

        // 定义是否Dialog请求
        !defined('IS_DIALOG') && define('IS_DIALOG', input("dialog") ? true : false);

        // 定义是否AJAX请求
        !defined('IS_AJAX') && define('IS_AJAX', $this->request->isAjax());

        // 储存认证对象
        $this->auth = Auth::instance();

        // 检测是否需要验证登录
        if (!$this->auth->match($this->noLoginRequired)) {
            // 检测是否登录
            $isLogin = $this->auth->isLogin();
            if (!$isLogin['status']) {
                // 无登录访问页面执行
                Hook::listen('admin_nologin', $this);
                $url = $this->request->url();
                // 未登录警告跳转,带上来源地址
                $this->error($isLogin['msg'], url('index/index', ['referer' => $url]));
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noRightRequired)) {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path)) {
                    Hook::listen('admin_nopermission', $this);
                    // 给予错误警告
                    $this->error(__('You have no permission'), '');
                }
            }
        }

        // 严禁跳出iframe框架
        if (!$this->request->isPost() && !IS_AJAX && !IS_ADDTABS && !IS_DIALOG && input("ref") == 'addtabs') {
            // 匹配字符串?ref=addtabs，进行替换（删除）保留其余参数
            $url = preg_replace_callback("/([\?|&]+)ref=addtabs(&?)/i", function ($matches) {
                return $matches[2] == '&' ? $matches[1] : '';
            }, $this->request->url());
            // 生成跳转地址
            $this->redirect('index/backstage', [], 302, ['referer' => $url]);
            // 强行停止继续执行
            exit;
        }
        // 设置面包屑导航数据
        $this->auth->getBreadCrumb($path);
        // 语言检测
        $lang = strip_tags($this->request->langset());
        // 设置全局参数
        $config = [
            'twelvet'       => Config::get('twelvet.'),
            'language'      => $lang,
            'modulename'    => $modulename,
            'referer'       => Session::get("referer")
        ];
        // 配置信息后
        Hook::listen("config_init", $config);
        // 设置参数
        $this->assign('config', $config);
    }
}

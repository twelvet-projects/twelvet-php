<?php

namespace app\user\controller;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 前台权限控制器
 */

use app\common\lib\Token;
use think\Controller;
use think\facade\Config;
use think\facade\Session;
use think\facade\Cookie;
use app\user\model\User;
use think\facade\Hook;
use think\Db;

class TwelveT extends Controller
{
    // 无需登录以及鉴权的方法
    protected $noLoginRequired = [];
    // 无需鉴权,但需要登录的方法
    protected $noRightRequired = [];
    // Token默认有效时长
    protected $keeptime = 2592000;
    // 登录标识
    protected $_logined = false;
    // 全局登录用户信息
    protected $_user = null;
    // 全局登录用户信息
    protected $_token = '';
    // Token驱动类型
    protected $_tokenType = '';
    // 错误信息
    protected $_error = '';

    /**
     * 应用初始化权限检查
     *
     * @return void
     */
    public function initialize()
    {
        // 设置token驱动类型
        $this->_tokenType = ucwords(strtolower(Config::get('twelvet.token.type')));
        // 初始化登录属性
        $this->initLogin();
        // 检测是否需要验证登录
        if (!$this->match($this->noLoginRequired)) {
            // 检测是否登录
            if (!$this->isLogin()) {
                $url = $this->request->url();
                //未登录警告跳转,带上来源地址
                $this->error($this->getError(), url('index/index', ['url' => $url]));
            }
            // 判断是否需要验证权限
            if (!$this->match($this->noRightRequired)) {
                // 判断控制器和方法判断是否有对应权限

            }
        }
        // 设置全局参数
        $config = [
            'twelvet' => Config::get('twelvet.')
        ];
        // 设置参数
        $this->assign('config', $config);
    }

    /**
     * 登录信息初始化
     *
     * @return bool
     */
    private function initLogin()
    {
        // 预定义user
        $user = '';

        // 获取客户端token
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', Cookie::get('token')));
        if (!$token) return $this->setError('请先登录');

        // 根据Token获取数据
        if ($this->_tokenType == 'Mysql') {
            // 从默认Mysql数据库获取信息
            $user = User::get(['token' => getEncryptedToken($token)]);
            // 判断认证是否存在
            if (!$user) return $this->setError('您的账号已在别处登录！！！');
        } else {
            // 从扩展token获取相关ID
            $data = Token::get($token);
            // 判断认证是否存在
            if (!$data) return $this->setError('您的账号已在别处登录！！！');
            // 从数据库获取用户信息
            $user = User::get((int) $data['user_id']);
        }

        // 初始化值
        $this->_user = $user;
        $this->_logined = true;
        $this->_token = $token;
    }

    /**
     * 鉴权信息
     *
     * @param String $name
     * @param String $password
     * @param integer $keepTime
     * @return boolean
     */
    public function login(String $name, String $password, int $keepTime)
    {
        //获取账号数据
        $user = user::get(['name' => $name]);
        if (!$user) {
            return ['status' => false, 'msg' => '账号或密码错误'];
        }
        //判断账号是否被冻结
        if (Config::get('app.login_failure_retry') && $user['login_fail'] >= 10 && time() - $user['update_time'] < 86400) {
            return ['status' => false, 'msg' => '您的账号已被冻结1天'];
        }
        //开始判断账号是否启用
        if ($user['status'] != 'normal') {
            return ['status' => false, 'msg' => '此账号已被禁用'];
        }
        //检查密码是否匹配加密方式
        if ($user['password'] != md5(md5($password) . $user['password_key'])) {
            //增加错误次数
            $user->login_fail++;
            $user->save();
            return ['status' => false, 'msg' => '账号或密码错误'];
        }

        // 执行登录信息写入信息写入失败禁止登录
        if (!$this->direct($user)) {
            return ['status' => false, 'msg' => $this->getError()];
        }

        // 登录成功的事件
        Hook::listen("user_login_successed", $this->_user);
        return ['status' => true, 'msg' => '登录成功'];
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        $twelvet = Session::get('user');
        $admin = $this->get(intval($twelvet['id']));
        //寻找不到立即放回成功
        if (!$admin) {
            return true;
        }
        //清除在线令牌
        $admin['token'] = '';
        $admin->save();
        //清除登录session以及cookie
        Session::delete("twelvet");
        // 注销成功的事件
        Hook::listen("user_logout_successed", $this->_user);
        return true;
    }

    /**
     * 登录认证
     *
     * @param object $user
     * @return void
     */
    private function direct(object $user)
    {
        // 启用事务
        Db::startTrans();
        try {
            // 设置登录失败次数为0
            $user['login_fail'] = 0;
            $user['login_ip'] = request()->ip();
            $user['login_time'] = time();
            $this->_token = uuid();
            // 判断使用默认Msyql驱动或扩展驱动Token
            if ($this->_tokenType == 'Mysql') {
                // Token写入Mysql(二次加密token)
                $user['token'] = getEncryptedToken($this->_token);
            } else {
                // Token写入扩展驱动,默认30天保存时间
                Token::set($this->_token, $user['id'], $this->keeptime);
            }
            // 保存数据
            $user->save();
            
            // 设置全局属性
            $this->_user = $user;
            // 保持登录状态
            //$this->keepLogin($this->keepTime);
            // 提交SQL
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->setError('用户登录信息写入出错');
            return false;
        }

        return true;
    }

    /**
     * 检查是否已登录
     * 已登录返回true,反之false
     */
    protected function isLogin()
    {
        if ($this->_logined) {
            return true;
        }
        return false;
    }

    /**
     * 刷新Cookie保持永久的登录状态
     * 为了保证一定的安全性，超7天未使用系统将摧毁此状态
     *
     * @param integer $keepTime
     * @return boolean
     */
    protected function keepLogin(int $keepTime)
    {
        if ($keepTime) {
            // 生成销毁时间默认7天
            $expiretime = time() + $keepTime;
            // 生成签名
            $key = md5(md5($this->id) . md5($keepTime) . md5($expiretime) . $this->token);
            // 用户id、保存时间、摧毁时间、签名
            $data = [$this->id, $keepTime, $expiretime, $key];
            // 默认7天的登录状态
            Cookie::set('keepLogin', implode('|', $data), 86400 * 7);
            return true;
        }
        return false;
    }

    /**
     * 设置错误信息
     *
     * @param $error 错误信息
     * @return Auth
     */
    public function setError($error)
    {
        $this->_error = $error;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError()
    {
        return $this->_error ? $this->_error : '';
    }

    /**
     * 检查数组中的方法,是否需要权限
     * [$arr] : 检查数组
     * 寻找到存在放回true，反之false
     */
    private function match($arr = [])
    {
        //转换数组小写
        $arr = array_map('strtolower', $arr);
        // 寻找执行方法是否存在数组中
        if (in_array(strtolower($this->request->action()), $arr) || in_array('*', $arr)) {
            return true;
        }
        // 找不到返回
        return false;
    }
}

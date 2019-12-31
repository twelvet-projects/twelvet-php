<?php

namespace app\admin\lib;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 后台权限类
 */

use think\facade\Request;
use app\admin\model\Admin;
use think\facade\Config;
use think\facade\Session;
use think\facade\Cookie;
use think\Db;
use twelvet\utils\Random;
use app\common\controller\Tree;

class Auth
{
    /**
     * 对象实例
     *
     * @var [object]
     */
    protected static $instance;

    // 错误信息
    protected $_errpr = '';
    // 登录状态
    protected $logined = false;

    // 权限规则
    protected $breadcrumb = [];
    protected $rules = [];


    /**
     * 当前请求实例
     * @var Request
     */
    protected $request;
    //默认配置
    protected $config = [
        'auth_on'           => 1, // 权限开关
        'auth_type'         => 1, // 认证方式，1为实时认证；2为登录认证。
        'auth_group'        => 'auth_group', // 用户组数据表名
        'auth_group_access' => 'auth_group_access', // 用户-用户组关系表
        'auth_rule'         => 'auth_rule', // 权限规则表
        'auth_user'         => 'admin', // 用户信息表
    ];

    public function __construct()
    {
        if ($auth = Config::get('auth')) {
            $this->config = array_merge($this->config, $auth);
        }
        // 初始化request
        $this->request = Request::instance();
    }

    /**
     * 无特殊情况请使用此方法调用实例
     * @access public
     * @param array $options 参数
     * @return Auth
     */
    public static function instance(array $options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 返回Session信息提供使用
     *
     * @param [type] $name
     * @return String
     */
    public function __get(String $name)
    {
        return Session::get('admin.' . $name);
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
        $admin = Admin::get(['name' => $name]);
        if (!$admin) {
            $this->setError('账号或密码错误');
            return false;
        }
        //判断账号是否被冻结
        if (Config::get('twelvet.login_failure_retry') && $admin->login_fail >= 10 && time() - $admin->update_time < 86400) {
            $this->setError('您的账号已被冻结1天');
            return false;
        }
        //开始判断账号是否启用
        if ($admin->status != 'normal') {
            $this->setError('此账号已被禁用');
            return false;
        }
        //检查密码是否匹配加密方式
        if ($admin->password != md5(md5($password) . $admin->password_key)) {
            //增加错误次数
            $admin->login_fail++;
            $admin->save();
            $this->setError('账号或密码错误');
            return false;
        }
        //通过所有验证后
        $admin->login_fail = 0;
        $admin->login_time = time();
        //使用全球唯一标识作为令牌
        $admin->token = Random::uuid();
        $admin->save();
        // 设置登录状态
        Session::set("admin", $admin->toArray());
        // 保持登录状态
        $this->keepLogin($keepTime);
        return true;
    }

    /**
     * 清退鉴权信息
     *
     * @return boolean
     */
    public function logout()
    {
        // 获取私有属性id查询信息
        $admin = Admin::get(intval($this->id));
        if (!$admin) {
            return true;
        }
        //清除在线令牌
        $admin->token = '';
        $admin->save();
        // 清除登录session以及cookie
        Session::delete("admin");
        Cookie::delete("keeplogin");
        return true;
    }

    /**
     * 检查是否已鉴权
     *
     * @return boolean
     */
    public function isLogin()
    {
        // 判断存在登录状态
        if($this->logined) return true;
        // 尝试获取信息
        $sessionAdmin = Session::get('admin');
        // 空返回未登录标识
        if (empty($sessionAdmin)) return false;
        //是否需要进行账号在同一时间只能登录一个的判断
        if (Config::get('twelvet.login_unique')) {
            // 获取模型信息
            $admin = Admin::get($sessionAdmin['id']);
            // 判断token值是否一致,实现一账号一登录
            if ($admin->token != $sessionAdmin['token']) {
                // 自动执行清退
                $this->logout();
                return false;
            }
        }
        $this->logined = true;
        return true;
    }

    /**
     * 刷新Cookie保持永久的登录状态
     * 为了保证一定的安全性，超7天未使用系统将摧毁此状态
     *
     * @param integer $keepTime
     * @return boolean
     */
    public function keepLogin(int $keepTime)
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
     * 自动登录
     *
     * @return boolean
     */
    public function autoLogin()
    {
        // 获取客户端Cookie
        $keepLogin = Cookie::get('keepLogin');
        // 判断是否存在Cookie
        if (!$keepLogin) {
            return false;
        }
        // 打散数据赋值变量
        list($id, $keepTime, $expiretime, $key) = explode('|', $keepLogin);
        // 判断数据完整性以及允许的自动登录时间是否超时
        if ($id && $keepTime && $expiretime && $key && $expiretime > time()) {
            // 获取数据库信息
            $admin = Admin::get($id);
            // 判断是否存在数据以及token值
            if (!$admin || !$admin->token) return false;
            // 检查签名是否合法
            if ($key != md5(md5($id) . md5($keepTime) . md5($expiretime) . $admin->token)) {
                return false;
            }
            // 完成登录状态
            Session::set("admin", $admin->toArray());
            // 刷新自动登录的允许时效
            $this->keeplogin($keepTime);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查数组中的方法,是否需要权限
     *
     * @param array $arr
     * @return boolean
     */
    public function match(array $arr = [])
    {
        // 转换数组小写
        $arr = array_map('strtolower', $arr);
        // 寻找执行方法是否存在数组中
        if (in_array(strtolower($this->request->action()), $arr) || in_array('*', $arr)) {
            return true;
        }
        // 找不到返回
        return false;
    }

    /**
     * 检查权限
     * @param string|array $name     需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param int          $uid      认证用户的id
     * @param string       $relation 如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @param string       $mode     执行验证的模式,可分为url,normal
     * 
     * @return bool 通过验证返回true;失败返回false
     */
    public function check($name, $uid = '', String $relation = 'or', String $mode = 'url')
    {
        // 判断是否需要使用登ID
        $uid = $uid ? $uid : $this->id;
        // 权限开关
        if (!$this->config['auth_on'])  return true;

        // 获取用户需要验证的所有有效规则列表
        $rulelist = $this->getRuleList($uid);
        // 如果返回*代表全部权限
        if (in_array('*', $rulelist)) return true;

        // 判断是否为字符串
        if (is_string($name)) {
            // 将字符串中首字母转为大写
            $name = strtolower($name);
            // 判断，第一次出现位置
            if (strpos($name, ',') !== false) {
                // 分割为数组
                $name = explode(',', $name);
            } else {
                // 直接赋值为数组
                $name = [$name];
            }
        }
        //保存验证通过的规则名
        $list = [];
        // 判断是否为url模式
        if ('url' == $mode) {
            // 获取所有参数将其序列化,首字母大写转换后取消序列化
            $_request = unserialize(strtolower(serialize($this->request->param())));
        }
        // 遍历权限
        foreach ($rulelist as $rule) {
            // 获取地址后的参数（？后的字符串，U匹配一次）
            $query = preg_replace('/^.+\?/U', '', $rule);
            if ('url' == $mode && $query != $rule) {
                // 将参数解析到param
                parse_str($query, $param);
                // 返回交集
                $intersect = array_intersect_assoc($_request, $param);
                // 获取地址规则（移除参数）
                $rule = preg_replace('/\?.*$/U', '', $rule);
                // 判断是否存在规则中以及两个遍历是否相同
                if (in_array($rule, $name) && $intersect == $param) $list[] = $rule;
            } else {
                // 判断本次请求api是否在允许的规则中
                if (in_array($rule, $name)) $list[] = $rule;
            }
        }
        // 判断是否为or模式以及list不为空
        if ('or' == $relation && !empty($list))  return true;
        // 返回差集
        $diff = array_diff($name, $list);
        // and模式以及差集为空
        if ('and' == $relation && empty($diff))  return true;

        return false;
    }

    /**
     * 获取管理权限
     *
     * @param int $uid
     * @return array
     */
    public function getRuleList(int $uid = null)
    {
        // 判断是否为空,返回管理员id
        $uid = is_null($uid) ? $this->id : $uid;
        // 保存用户验证通过的权限列表
        static $_rulelist = [];
        // 判断是否设置了权限列表信息
        if (isset($_rulelist[$uid])) {
            return $_rulelist[$uid];
        }
        // 认证方式为登录认证和存在数据的立即返回信息
        if ($this->config['auth_type'] == 2 && Session::has('_rule_list_' . $uid)) {
            return Session::get('_rule_list_' . $uid);
        }

        // 读取用户规则节点
        $ids = $this->getRuleIds($uid);
        // 空值立即返回
        if (empty($ids)) {
            $_rulelist[$uid] = [];
            return [];
        }

        // 筛选条件
        $where = [
            'status' => 'normal'
        ];
        // 结果中无*标识使用in条件
        if (!in_array('*', $ids)) {
            $where['id'] = ['in', $ids];
        }
        // 使用数组或*读取用户所拥有的的权限
        $this->rules = Db::name($this->config['auth_rule'])->where($where)->field('id,pid,condition,icon,name,title,ismenu')->select();

        // 循环规则，判断结果
        $rulelist = [];
        if (in_array('*', $ids)) {
            $rulelist[] = "*";
        }
        // 遍历查询出的可操作权限
        foreach ($this->rules as $rule) {
            // 超级管理员无需验证condition
            if (!empty($rule['condition']) && !in_array('*', $ids)) {
                // 根据condition进行验证
                $user = $this->getUserInfo($uid);
                // 替换字符串
                $command = preg_replace('/\{(\w*?)\}/', '$user[\'\\1\']', $rule['condition']);
                // 字符串当php代码执行赋值
                @(eval('$condition=(' . $command . ');'));
                // 判断以上值是否存在
                if ($condition)  $rulelist[$rule['id']] = strtolower($rule['name']);
            } else {
                // 记录id对应的name
                $rulelist[$rule['id']] = strtolower($rule['name']);
            }
        }
        // 储存当前账号锁拥有的权限
        $_rulelist[$uid] = $rulelist;
        // 登录验证则需要保存规则列表
        if ($this->config['auth_type'] == 2) {
            // 规则列表结果保存到session，登录认证不能做到更改权限后立即同步到账号，相对来说也可以减轻数据库负担
            Session::set('_rule_list_' . $uid, array_unique($rulelist));
        }
        // 返回唯一数据
        return array_unique($rulelist);
    }

    /**
     * 获取可操作权限id号
     *
     * @param int $uid
     * @return array
     */
    public function getRuleIds(int $uid)
    {
        // 读取用户所属用户组
        $groups = $this->getGroups($uid);
        // 保存用户所属用户组设置的所有权限规则id
        $ids = [];
        foreach ($groups as $g) {
            // 合并数组（移除两侧,并以,分割数组合并）
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        // 移除重复值并保持返回(返回用户拥有的权限)
        $ids = array_unique($ids);
        return $ids;
    }

    /**
     * 返回管理员所属用户组
     *
     * @param int $uid
     * @return array
     */
    public function getGroups(int $uid)
    {
        // 定义用户组
        static $groups = [];
        // 根据信息返回
        if (isset($groups[$uid])) {
            return $groups[$uid];
        }
        // 执行查询所属（聚合查询：left 左表为主要）
        $user_groups = Db::name($this->config['auth_group_access'])
            ->alias('aga')
            ->join('__' . strtoupper($this->config['auth_group']) . '__ ag', 'aga.group_id = ag.id', 'LEFT')
            ->field('aga.uid,aga.group_id,ag.id,ag.pid,ag.name,ag.rules')
            ->where("aga.uid='{$uid}' and ag.status='normal'")
            ->select();
        // 设置信息并返回
        $groups[$uid] = $user_groups ?: [];
        return $groups[$uid];
    }

    /**
     * 获得用户资料
     *
     * @param int $uid
     * @return void
     */
    protected function getUserInfo(int $uid)
    {
        static $user_info = [];
        // 查询用户表
        $user = Db::name($this->config['auth_user']);
        // 获取用户表主键
        $_pk = is_string($user->getPk()) ? $user->getPk() : 'uid';
        // 是否设置了参数
        if (!isset($user_info[$uid])) {
            // 获取用户信息
            $user_info[$uid] = $user->where($_pk, $uid)->find();
        }

        return $user_info[$uid];
    }

    /**
     * 获取后台侧边栏导航
     *
     * @param array $params
     * @param string $fixedPage
     * @return void
     */
    public function getSidebar(array $params = [], String $fixedPage = 'dashboard')
    {
        // 设置彩虹七色
        $colorArr = ['red', 'green', 'yellow', 'blue', 'teal', 'orange', 'purple'];
        // 统计样式
        $colorNums = count($colorArr);
        // 预定义徽章
        $badgeList = [];
        // 获取当前模块
        $module = request()->module();
        // 生成菜单badge
        foreach ($params as $k => $v) {
            // 储存key
            $url = $k;
            // 处理数组\字符串
            if (is_array($v)) {
                $nums = isset($v[0]) ? $v[0] : 0;
                $color = isset($v[1]) ? $v[1] : $colorArr[(is_numeric($nums) ? $nums : strlen($nums)) % $colorNums];
                $class = isset($v[2]) ? $v[2] : 'label';
            } else {
                // 储存value
                $nums = $v;
                // 储存随机而固定的颜色
                $color = $colorArr[(is_numeric($nums) ? $nums : strlen($nums)) % $colorNums];
                // 设置class
                $class = 'label';
            }
            // 必须大于0
            if ($nums) {
                // 徽章列表
                $badgeList[$url] = "<small class='{$class} pull-right' style='background:{$color}'>{$nums}</small>";
            }
        }

        // 读取管理员当前拥有的权限节点
        $userRule = $this->getRuleList();
        $selected = $referer = [];
        $refererUrl = Session::get('referer');
        $pinyin = new \Overtrue\Pinyin\Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        // 必须将结果集转换为数组（缓存查询信息）
        $ruleList = collection(\app\admin\model\AuthRule::where('status', 'normal')
            ->where('ismenu', 1)
            ->order('weigh', 'desc')
            ->cache("__menu__")
            ->select())->toArray();
        // 模糊查询出index列表
        $indexRuleList = \app\admin\model\AuthRule::where('status', 'normal')
            ->where('ismenu', 0)
            ->where('name', 'like', '%/index')
            ->column('name,pid');
        // 储存唯一的数组（清除空值，false，0等值）
        $pidArr = array_filter(array_unique(array_map(function ($item) {
            return $item['pid'];
        }, $ruleList)));

        foreach ($ruleList as $k => &$v) {
            // 判断此操作是否在管理权限数组中,不存在立即清除此菜单并跳出循环
            if (!in_array($v['name'], $userRule)) {
                unset($ruleList[$k]);
                continue;
            }
            // 拼接地址
            $indexRuleName = $v['name'] . '/index';
            // 判断是否设置了此参数以及此菜单是否在管理员权限中
            if (isset($indexRuleList[$indexRuleName]) && !in_array($indexRuleName, $userRule)) {
                // 清除并跳出此次循环
                unset($ruleList[$k]);
                continue;
            }

            // 储存对应数据
            $v['icon'] = $v['icon'] . ' fa-fw';
            $v['url'] = '/' . $module . '/' . $v['name'];
            // 徽章
            $v['badge'] = isset($badgeList[$v['name']]) ? $badgeList[$v['name']] : '';
            // 拼音简写与全拼（使用拼音翻译）
            $v['py'] = $pinyin->abbr($v['title'], '');
            $v['pinyin'] = $pinyin->permalink($v['title'], '');
            // 最终显示的标题
            $v['title'] = $v['title'];
            // 是否为dashboard是直接$v否则selectd
            $selected = $v['name'] == $fixedPage ? $v : $selected;
            // 生成的地址是否相同
            $referer = url($v['url']) == $refererUrl ? $v : $referer;
        }
        // 返回差集：储存唯一的数组（清除空值，false，0等值）
        $lastArr = array_diff($pidArr, array_filter(array_unique(array_map(function ($item) {
            return $item['pid'];
        }, $ruleList))));

        foreach ($ruleList as $index => $item) {
            // 如果在差集数组中立即清除
            if (in_array($item['id'], $lastArr)) {
                unset($ruleList[$index]);
            }
        }
        // 相同清空数组
        if ($selected == $referer) {
            $referer = [];
        }
        // 存在数据执行赋值
        $selected && $selected['url'] = url($selected['url']);
        $referer && $referer['url'] = url($referer['url']);
        // 存在数据则id否则0
        $select_id = $selected ? $selected['id'] : 0;
        // 预定义
        $menu = $nav = '';

        // 开始构造菜单数据
        Tree::instance()->init($ruleList);
        // 调用数据处理
        $menu = Tree::instance()->getTreeMenu(
            0,
            '<li class="@class"><a href="javascript:;" data-url="@url" py="@py" addtabs="@id" pinyin="@pinyin"><i class="@icon"></i><span>@title</span>@caret</a> @childlist</li>',
            $select_id,
            '',
            'ul',
            'class="treeview-menu"'
        );
        // 判断是否存在数据进行操作
        if ($selected) {
            $nav .= '<li role="presentation" id="tab_' . $selected['id'] . '" class="' . ($referer ? '' : 'active') . '"><a href="#con_' . $selected['id'] . '" node-id="' . $selected['id'] . '" aria-controls="' . $selected['id'] . '" role="tab" data-toggle="tab"><i class="' . $selected['icon'] . ' fa-fw"></i> <span>' . $selected['title'] . '</span> </a></li>';
        }
        if ($referer) {
            $nav .= '<li role="presentation" id="tab_' . $referer['id'] . '" class="active"><a href="#con_' . $referer['id'] . '" node-id="' . $referer['id'] . '" aria-controls="' . $referer['id'] . '" role="tab" data-toggle="tab"><i class="' . $referer['icon'] . ' fa-fw"></i> <span>' . $referer['title'] . '</span> </a> <i class="close-tab fa fa-remove"></i></li>';
        }
        return [$menu, $nav, $selected, $referer];
    }

    /**
     * 获得面包屑导航
     * @param string $path
     * @return array
     */
    public function getBreadCrumb(String $path = '')
    {
        // 判断是否设置了信息以及是否拥有path路径
        if ($this->breadcrumb || !$path) return $this->breadcrumb;
        // 预定义id
        $path_rule_id = 0;
        // 遍历规则
        foreach ($this->rules as $rule) {
            // 判断是规则否匹配
            $path_rule_id = $rule['name'] == $path ? $rule['id'] : $path_rule_id;
        }
        //
        if ($path_rule_id) {
            $this->breadcrumb = Tree::instance()->init($this->rules)->getParents($path_rule_id, true);
            foreach ($this->breadcrumb as $k => &$v) {
                $v['url'] = url($v['name']);
                $v['title'] = __($v['title']);
            }
        }
        // 返回导航信息
        return $this->breadcrumb;
    }

    /**
     * 设置错误信息
     *
     * @param string $error 错误信息
     * @return Auth
     */
    public function setError(String $error)
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
        return $this->_error ? __($this->_error) : '';
    }
}

<?php

namespace think;

/**
 * 【后台入口文件】
 * 为了您的网站安全，请注意后台入口不泄露
 */

// PHP版本检查，可自行打开（默认通过composer安装的不需要打开此行）
// if (version_compare(PHP_VERSION, '7.3', '<')) die('PHP版本过低，最少需要PHP7.3，请升级PHP版本！');

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';
// bind admin 模块 执行应用并响应
Container::get('app')->bind('admin')->run()->send();

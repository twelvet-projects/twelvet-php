<?php
namespace think;
/**
 * 【后台入口文件】
 * 为了您的网站安全强烈建议更改入口名称
 */
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.6.0','<'))  die('PHP server requirements > 5.6.0');
// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';
// bind admin 模块 执行应用并响应
Container::get('app')->bind('admin')->run()->send();
<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]
namespace think;

// PHP版本检查，可自行打开（默认通过composer安装的不需要打开此行）
// if (version_compare(PHP_VERSION, '7.3', '<')) die('PHP版本过低，最少需要PHP7.3，请升级PHP版本！');

// 判断是否安装TwelveT
if (!is_file(__DIR__ . '/../application/admin/command/install/install.lock')) {
    header("location:./install.php");
    exit;
}

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 执行应用并响应
Container::get('app')->run()->send();

<?php

namespace app\user\model;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 12tla.com，并保留所有权利。
 * 官网地址:www.12tla.com
 * QQ:2471835953
 * ============================================================================
 * 后台基础视图控制器
 */

use think\Validate;
use think\facade\Config;
use think\facade\Request;
use think\facade\Session;

class User extends TwelveT
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}

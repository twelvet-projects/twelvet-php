<?php

namespace app\admin\controller;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 后台基础视图控制器
 */
class Admin extends TwelveT
{
    protected $rule = [
        'username|账号'  => 'require|length:3,30',
        'password|密码'  => 'require|length:3,30',
        '__token__' => 'token',
    ];
}

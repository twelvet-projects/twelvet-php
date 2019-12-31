<?php
return [
    // 是否开启前台会员中心
    'usercenter'          => true,
    // 全站验证码开关
    'login_captcha'       => false,
    // 登录次数错误10次禁登24小时
    'login_failure_retry' => true,
    // 是否同一账号同一时间只能在一个终端登录（管理员）
    'login_unique'        => true,
    // 自动检测更新
    'checkupdate'         => true,
    // 版本号
    'version'             => '1.0.0.20190510_beta',
    // API接口地址
    'api_url'             => 'https://api.twelvet.cn',
    // TwelveT核心数据表设置，请勿随意更改此处
    'db_tables'           => [
        '__PREFIX__admin',
        '__PREFIX__users'
    ],
    // +----------------------------------------------------------------------
    // | 用户Token驱动设置
    // +----------------------------------------------------------------------
    'token'                  => [
        // 驱动方式
        'type'          => 'Redis', // 默认采用Mysql配套,支持redis扩展或更多驱动(自行实现)
        // 是否同一账号同一时间只能在一个终端登录（用户）
        'login_unique'  => true, // 此配置不适配默认驱动Mysql
        // 缓存前缀
        'key'           => 'T1f4e57sl4fcva1dv2qw',
        // 加密方式
        'hashalgo'      => 'ripemd160',
        // 缓存有效期 0表示永久缓存
        'expire'        => 0,
    ],
];

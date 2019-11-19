<?php

namespace app\common\behavior;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 全局拦截器
 */

use think\facade\Config;
use think\facade\Env;

class Filter
{
    // 应用初始化时执行
    public function appInit()
    {
        // 设置mbstring字符编码
        mb_internal_encoding("UTF-8");

        // 获取地址
        $rootUrl = preg_replace("/\/(\w+)\.php$/i", '', request()->root());

        // 如果未设置__PUBLIC__则自动匹配得出
        if (!Config::get('template.tpl_replace_string.__STATIC__')) {
            Config::set('template.tpl_replace_string.__STATIC__', $rootUrl . '/static');
        }
        // 如果未设置__ROOT__则自动匹配得出
        if (!Config::get('template.tpl_replace_string.__ADMIN__')) {
            Config::set('template.tpl_replace_string.__ADMIN__', $rootUrl . '/static/twelvet');
        }
        if (Config::get('app.app_debug')) {
            // 开发模式将异常模板修改成官方
            Config::set('app.exception_tmpl', Env::get('think_path') . 'tpl/think_exception.tpl');
        }
        // 系统语言切换
        if (Config::get('app.lang_switch_on') && request()->get('lang')) {
            \think\facade\Cookie::set('think_var', request()->get('lang'));
        }
    }
}

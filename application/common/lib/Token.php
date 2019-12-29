<?php

namespace app\common\lib;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * Token操作类
 */

use app\common\lib\token\Driver;
use think\facade\Config;
use think\facade\Log;

class Token
{
    /**
     * @var array Token的实例
     */
    public static $instance = [];

    /**
     * @var object 操作句柄
     */
    public static $handler;

    /**
     * Token驱动
     * @access public
     * @param  array       $options 配置数组
     * @param  bool|String $name    Token连接标识 true 强制重新连接
     * @return Driver
     */
    public static function connect(array $options = [], $name = false)
    {
        // 判断参数是否为空
        $type = !empty($options['type']) ? ucwords( strtolower( $options['type'] ) ) : 'File';

        if (false === $name) {
            // 序列化并且加密
            $name = md5(serialize($options));
        }
        // 判断是否需要重新连接或实例是否为空
        if (true === $name || !isset(self::$instance[$name])) {
            // 判断参数是否有指定驱动路径
            $class = false === strpos($type, '\\') ?
                '\\app\\common\\lib\\token\\driver\\' . ucwords( strtolower( $type ) ) : $type;
            // 调试模式下把驱动初始化信息写入
            Config::get('app.app_debug') && Log::record('[ TOKEN ] INIT ' . $type, 'info');
            // 是否直接返回对象，不进行缓存
            if (true === $name) {
                return new $class($options);
            }
            // 设置全局Token对象
            self::$instance[$name] = new $class($options);
        }

        return self::$instance[$name];
    }

    /**
     * 自动初始化Token
     * @access public
     * @param  array $options 配置数组
     * @return Driver
     */
    public static function init(array $options = [])
    {
        // 判断操作句柄是否为空
        if (is_null(self::$handler)) {
            // 获取默认Token配置
            $options = Config::get('twelvet.token');
            // 连接Token驱动
            self::$handler = self::connect($options);
        }

        return self::$handler;
    }

    /**
     * 判断Token是否可用(check别名)
     * @access public
     * @param  string $token Token标识
     * @return bool
     */
    public static function has(String $token, int $user_id)
    {
        return self::check($token, $user_id);
    }

    /**
     * 判断Token是否可用
     * @param string $token Token标识
     * @return bool
     */
    public static function check(String $token, int $user_id)
    {
        return self::init()->check($token, $user_id);
    }

    /**
     * 读取Token
     * @access public
     * @param  string $token   Token标识
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public static function get(String $token, $default = false)
    {
        return self::init()->get($token, $default);
    }

    /**
     * 写入Token
     * @access public
     * @param  string   $token   Token标识
     * @param  mixed    $user_id 存储数据
     * @param  int|null $expire  有效时间 0为永久
     * @return boolean
     */
    public static function set(String $token, int $user_id, $expire = null)
    {
        return self::init()->set($token, $user_id, $expire);
    }

    /**
     * 删除Token(delete别名)
     * @access public
     * @param  string $token Token标识
     * @return boolean
     */
    public static function rm(String $token)
    {
        return self::delete($token);
    }

    /**
     * 删除Token
     * @param string $token 标签名
     * @return bool
     */
    public static function delete(String $token)
    {
        return self::init()->delete($token);
    }

    /**
     * 清除Token
     * @access public
     * @param  string $token Token标记
     * @return boolean
     */
    public static function clear(int $user_id = null)
    {
        return self::init()->clear($user_id);
    }
}

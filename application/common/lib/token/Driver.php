<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace app\common\lib\token;

/**
 * Token基础类
 */
abstract class Driver
{
    // 操作句柄
    protected $handler = null;
    // 配置参数
    protected $options = [];

    /**
     * 存储Token
     * @param   string $token   Token
     * @param   int    $user_id 会员ID
     * @param   int    $expire  过期时长,0表示无限,单位秒
     * @return bool
     */
    abstract function set(String $token, int $user_id, $expire = 0);

    /**
     * 进行唯一token限制
     * 根据不同驱动在set方法中调用实现
     *
     * @param [type] $user_id
     * @return void
     */
    abstract function unique(int $user_id);

    /**
     * 获取Token内的信息
     * @param   string $token
     * @return  array
     */
    abstract function get(String $token);

    /**
     * 判断Token是否可用
     * @param   string $token   Token
     * @param   int    $user_id 会员ID
     * @return  boolean
     */
    abstract function check(String $token, int $user_id);

    /**
     * 删除Token
     * @param   string $token
     * @return  boolean
     */
    abstract function delete(String $token);

    /**
     * 删除指定用户的所有Token
     * @param   int $user_id
     * @return  boolean
     */
    abstract function clear(int $user_id);

    /**
     * 返回句柄对象，可执行其它高级方法
     *
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }

    /**
     * 获取过期剩余时长
     * @param $expiretime
     * @return float|int|mixed
     */
    protected function getExpiredIn(int $expiretime)
    {
        return $expiretime ? max(0, $expiretime - time()) : 365 * 86400;
    }
}

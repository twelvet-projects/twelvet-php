<?php

namespace app\common\lib\token\driver;

use app\common\lib\token\Driver;
use think\facade\Config;

class Redis extends Driver
{
    // Redis基础配置
    protected $options = [
        'host'        => '127.0.0.1',
        'port'        => 6379,
        'password'    => '',
        'select'      => 0,
        'timeout'     => 0,
        'expire'      => 0,
        'persistent'  => false,
        'userprefix'  => 'up:',
        'tokenprefix' => 'tp:',
    ];

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @throws BadFunctionCallException|RedisException
     * @access public
     */
    public function __construct(array $options = [])
    {
        // 判断是否支持redis
        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('Not Support: Redis');
        }
        // 合并参数
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        try {
            $this->handler = new \Redis;
            // 是否启用redis持久化连接
            if ($this->options['persistent']) {
                $this->handler->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);
            } else {
                $this->handler->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
            }
            // 验证登录密码
            if ('' != $this->options['password']) {
                $this->handler->auth($this->options['password']);
            }
            // 是否切换redis库
            if (0 != $this->options['select']) {
                $this->handler->select($this->options['select']);
            }
        } catch (\RedisException $e) {
            throw new \RedisException('Redis连接失败');
        }
    }

    /**
     * 获取会员的key
     * @param $user_id
     * @return string
     */
    protected function getUserKey(int $user_id)
    {
        return $this->options['userprefix'] . $user_id;
    }

    /**
     * 存储Token
     * @param   string $token   Token
     * @param   int    $user_id 会员ID
     * @param   int    $expire  过期时长,0表示无限,单位秒
     * @return bool
     */
    public function set(String $token, int $user_id, $expire = 0)
    {
        // 判断是否
        if (is_null($expire)) $expire = $this->options['expire'];
        // 是否满足条件
        if ($expire instanceof \DateTime) {
            $expire = $expire->getTimestamp() - time();
        }
        // 获取加密token
        $key = getEncryptedToken($token);
        // 判断是否需要进行唯一检测
        if (Config::get('twelvet.token.login_unique')) {
            // 判断是否需要删除前一个客户端的登录信息
            $this->unique($user_id);
        }
        if ($expire) {
            $result = $this->handler->setex($key, $expire, $user_id);
        } else {
            $result = $this->handler->set($key, $user_id);
        }
        //写入会员关联的token
        $this->handler->sAdd($this->getUserKey($user_id), $key);
        return $result;
    }

    /**
     * 进行唯一token限制
     *
     * @param [type] $user_id
     * @return void
     */
    public function unique(int $user_id)
    {
        // 根据ID获取信息
        $value = $this->handler->sMembers($this->getUserKey($user_id));
        // 存在数据首先删除登录信息
        if ($value) {
            // 删除信息
            $this->clear($user_id);
        }
    }

    /**
     * 获取Token内的信息
     * @param   string $token
     * @return  array
     */
    public function get(String $token)
    {
        // 获取加密token信息
        $key = getEncryptedToken($token);
        // 从redis获取token信息
        $value = $this->handler->get($key);
        // 判断是否获取到信息
        if (is_null($value) || false === $value) {
            return [];
        }
        //获取有效期
        $expire = $this->handler->ttl($key);
        $expire = $expire < 0 ? 365 * 86400 : $expire;
        $expiretime = time() + $expire;
        $result = ['token' => $token, 'user_id' => $value, 'expiretime' => $expiretime, 'expires_in' => $expire];

        return $result;
    }

    /**
     * 删除Token
     * @param   string $token
     * @return  boolean
     */
    public function delete(String $token)
    {
        // 判断是否存在此token信息
        $data = $this->get($token);
        if ($data) {
            // 获取加密的token信息
            $key = getEncryptedToken($token);
            $user_id = $data['user_id'];
            // 删除指定token信息
            $this->handler->del($key);
            $this->handler->sRem($this->getUserKey($user_id), $key);
        }
        return true;
    }

    /**
     * 删除指定用户的所有Token
     * @param   int $user_id
     * @return  boolean
     */
    public function clear(int $user_id)
    {
        // 获取redis集合信息
        $keys = $this->handler->sMembers($this->getUserKey($user_id));
        // 全部删除
        $this->handler->del($this->getUserKey($user_id));
        $this->handler->del($keys);
        return true;
    }

    /**
     * 判断Token是否可用
     * @param   string $token   Token
     * @param   int    $user_id 会员ID
     * @return  boolean
     */
    public function check(String $token, int $user_id)
    {
        // 获取信息
        $data = self::get($token);
        // 判断是否存在数据以及是否匹配
        return $data && $data['user_id'] == $user_id ? true : false;
    }
}

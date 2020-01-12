<?php

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 */

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\facade\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('tJson')) {
    /**
     * 返回JSON数据
     *
     * @param [type] $msg
     * @param boolean $state
     * @param array $data
     * @return void
     */
    function tJson(int $code, String $msg, array $data = null, int $webCode = 200, $header = [], $options = [])
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'time' => time(),
            'data' => $data,
        ], $webCode, $header, $options);
    }
}


if (!function_exists('rmdirs')) {
    /**
     * 文件夹删除
     * @param string $dirname  目录
     * @param bool   $self 是否删除自身
     */
    function rmdirs($dirname, $self = true)
    {
        //判断是否为一个目录
        if (!is_dir($dirname))  return false;
        //使用递归计算
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        //遍历删除目录中的文件以及目录
        foreach ($files as $fileinfo) {
            //判断是否是目录
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        //是否需要删除自身
        if ($self) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('getEncryptedToken')) {
    /**
     * 获取加密后的Token
     * @param string $token Token标识
     * @return string
     */
    function getEncryptedToken($token)
    {
        $config = \think\facade\Config::get('twelvet.token');
        return hash_hmac($config['hashalgo'], $token, $config['key']);
    }
}

if (!function_exists('collection')) {
    /**
     * 数组转换为数据集对象
     * @param array $resultSet 数据集数组
     * @return \think\model\Collection|\think\Collection
     */
    function collection($resultSet)
    {
        $item = current($resultSet);
        if ($item instanceof Model) {
            return \think\model\Collection::make($resultSet);
        } else {
            return \think\Collection::make($resultSet);
        }
    }
}

if (!function_exists('dd')) {

    /**
     * 简易调试助手
     *
     * @param [type] ...$vars
     * @return void
     */
    function dd(...$vars)
    {
        foreach ($vars as $k => $v) {
            dump($v);
        }

        die(1);
    }
}

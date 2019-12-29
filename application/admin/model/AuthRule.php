<?php

namespace app\admin\model;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 权限模型
 */

use think\Cache;
use think\Model;

class AuthRule extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected static function init()
    {
        self::afterWrite(function ($row) {
            Cache::rm('__menu__');
        });
    }

    public function getTitleAttr($value, $data)
    {
        return __($value);
    }

}

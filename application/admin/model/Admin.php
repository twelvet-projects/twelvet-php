<?php

namespace app\admin\model;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 12tla.com，并保留所有权利。
 * 官网地址:www.12tla.com
 * QQ:2471835953
 * ============================================================================
 * 后台基础视图控制器
 */

use think\Model;

class Admin extends Model
{
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 重置用户密码
     *
     * @param [type] $uid
     * @param [type] $NewPassword
     * @return void
     */
    protected function resetPassword($id, $NewPassword)
    {
        $password = md5($NewPassword);
        $result = $this->where(['id' => $id])->update(['password' => $password]);
        return $result;
    }
}

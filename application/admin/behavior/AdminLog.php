<?php

namespace app\admin\behavior;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 日记控制器
 */

class AdminLog
{
    public function appEnd()
    {
        // 只记录post请求
        if (request()->isPost()) {
            \app\admin\model\AdminLog::record();
        }
    }
}

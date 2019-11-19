<?php

namespace app\admin\behavior;

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

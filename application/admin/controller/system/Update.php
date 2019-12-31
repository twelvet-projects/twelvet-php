<?php

namespace app\admin\controller\system;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * TwelveT在线更新
 */

use app\admin\controller\TwelveT;
use twelvet\utils\HTTP;
use think\facade\App;

class Update extends TwelveT
{
    public function initialize()
    {
        parent::initialize();
    }

    public function index()
    {
        // 只允许post的升级请求
        if ($this->request->isPost()) {
            // 获取用户认证信息
            $token = (string) $this->request->post('token');
            $uid = (string) $this->request->post('uid');
            if (!$token || !$uid) return tjson('缺少重要参数');
            $params = [
                'version' => '1.0',
                'token' => $token,
                'uid' => $uid
            ];
            // 发起HTTP请求更新信息
            $result = HTTP::get(config('twelvet.api_url') . '/update/index', $params);
            // 是否响应成功
            if (!$result['status']) return json([
                'code' => 0,
                'msg' => $result['msg']
            ]);
            // 解析json
            $json = (array) json_decode($result['msg'], true);
            // 判断请求信息是否成功
            if (!$json['code']) $this->result(null, 0, $json['msg']);

            return HTTP::download($json['data']['path'], App::getRuntimePath() . 'twelvet/', 'demo.zip', $params);
        }
        return tjson('请求成功');
    }
}

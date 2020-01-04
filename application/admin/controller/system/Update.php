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
use Exception;
use twelvet\utils\HTTP;
use twelvet\utils\File;
use think\facade\App;
use think\facade\Env;

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
            $version = (string) $this->request->post('version');
            if (!$token || !$uid) return tjson(0, '缺少重要参数');
            $params = [
                'version' => $version,
                'token' => $token,
                'uid' => $uid
            ];
            try {
                // 发起HTTP请求更新信息
                // $result = HTTP::get(config('twelvet.api_url') . '/update/index', $params);
                // // 解析json
                // $json = (array) json_decode($result, true);
                // // 判断请求信息是否成功
                // if ($json['code'] != 1) return tjson($json['code'], $json['msg']);
                // // 远程下载压缩包文件
                // $tempFile = HTTP::download($json['data']['path'], Env::get('RUNTIME_PATH') . 'twelvet/', 'demo.zip', $params);
                // // 解压压缩包
                // File::unzip($tempFile, Env::get('RUNTIME_PATH') . 'twelvet');
                File::rm('C:\wwwroot\www.12tla.com\runtime\twelvet\demo');
                return tjson(1, '成功执行');
            } catch (\twelvet\utils\exception\UtilsException $e) {
                return tjson($e->getCode(), $e->getMessage());
            }
        }
        return tjson(1, '请求成功');
    }
}

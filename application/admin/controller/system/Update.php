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
use twelvet\utils\File;
use think\facade\Env;
use think\facade\Config;
use twelvet\utils\exception\UtilsException;

class Update extends TwelveT
{
    private $tempPath = '';

    public function initialize()
    {
        parent::initialize();
        // 设置更新包目录
        $this->tempPath = Env::get('RUNTIME_PATH') . 'twelvet\\';
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
                $result = HTTP::get(
                    config('twelvet.api_url') . '/update/index',
                    $params
                );
                // 解析json
                $json = (array) json_decode($result, true);
                // 判断回应code是否成功
                if ($json['code'] != 1) return tjson($json['code'], $json['msg']);

                // 远程下载压缩包文件
                $tempZip = HTTP::download(
                    $json['data']['path'],
                    $this->tempPath,
                    $json['data']['version'] . '.zip',
                    $params
                );
                // 检查文件MD5信息
                //$this->checkMd5($tempFile, $json['data']['MD5']);
                // 解压更新包
                $tempDir = File::unzip((string) $tempZip, Env::get('RUNTIME_PATH') . 'twelvet');

                $demo = Config::parse($tempDir . '\\2.0\\config\\info.ini', '', "addon-info-twim");
                dd($demo);

                return tjson(1, '成功执行');
            } catch (UtilsException $e) {
                return tjson($e->getCode(), $e->getMessage());
            } finally {
                // 清空缓存
                //File::rm(Env::get('RUNTIME_PATH') . 'twelvet');
            }
        }
        return tjson(1, '请求成功');
    }

    public function checkMd5($tempFile, $MD5)
    {
        // 判断升级包md5是否正常
        if (md5_file($tempFile) != $MD5) {
            //echo md5_file($tempFile);
            throw new UtilsException('更新包MD5信息异常', 200);
        }
    }
}

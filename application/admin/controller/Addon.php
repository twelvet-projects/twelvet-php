<?php

namespace app\admin\controller;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 插件主控
 * 在线安装、卸载、禁用、启用插件，同时支持离线安装插件
 */

use twelvet\utils\Http;
use think\facade\Cache;
use think\facade\Env;
use think\addons\Service;
use think\addons\AddonException;
use think\Exception;
use think\facade\Config;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Addon extends TwelveT
{
    public function initialize()
    {
        parent::initialize();
    }
    /**
     * 配置文件以及图片上传
     */
    public function upload()
    {
        //获取参数以及文件信息
        (string) $name = $this->request->param('name');
        (object) $file = $this->request->file('image');
        //判断参数获取
        if (!$name || !$file) return tJson('上传参数错误');
        //定义插件配置目录
        $addonDir = Env::get('ROOT_PATH')
            . 'public'
            . DIRECTORY_SEPARATOR
            . 'addons'
            . DIRECTORY_SEPARATOR
            . $name
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR;
        //判断是否需要创建目录
        if (!is_dir($addonDir)) {
            @mkdir($addonDir, 0755, true);
        }
        //移动保存文件
        $info = $file
            ->rule('uniqid')
            ->validate([
                'size' => 10240000, 'ext' => 'jpg,png,gif,jpeg'
            ])
            ->move($addonDir);
        //判断是否上传成功
        if ($info) {
            $msg = ['state' => true, 'msg' => '\\addons\\qrcode\\config' . DIRECTORY_SEPARATOR . $info->getSaveName()];
        } else {
            $msg = ['state' => false, 'msg' => $file->getError()];
        }
        //返回信息
        return tJson('', '', $msg);
    }
    /**
     * 删除配置文件
     */
    public function drop()
    {
        //获取参数以及文件信息
        (string) $name = $this->request->param('name');
        //获取被删除的文件
        (array) $fileNames = $this->request->param('fileName');
        //定义插件静态配置文件
        $addonDir = Env::get('ROOT_PATH')
            . 'public'
            . DIRECTORY_SEPARATOR
            . 'addons'
            . DIRECTORY_SEPARATOR
            . $name
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR;
        //遍历删除
        foreach ($fileNames as $fileName) {
            //判断在对于目录下是否存在文件
            if (!is_file($addonDir . $fileName)) return tJson('找不到此文件：' . $fileName);
            //捕获删除异常
            try {
                unlink($addonDir . $fileName);
            } catch (Exception $e) {
                return tJson($fileName . '删除失败,权限不足');
            }
        }
        return tJson('删除成功', true);
    }
    /**
     * 管理上传配置文件
     */
    public function manage()
    {
        //获取参数以及文件信息
        (string) $name = $this->request->param('name');
        //判断参数获取时
        if (!$name) return tJson('参数获取错误');
        //判断是否数据请求
        if ($this->request->isAjax()) {
            $res = ['state' => true, 'code' => 0];
            //定义插件配置目录
            $dir = Env::get('ROOT_PATH')
                . 'public'
                . DIRECTORY_SEPARATOR
                . 'addons'
                . DIRECTORY_SEPARATOR
                . $name
                . DIRECTORY_SEPARATOR
                . 'config'
                . DIRECTORY_SEPARATOR;
            //递归计算文件
            $addonDirs = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            //定义前端路径
            $path = "/addons/{$name}/config/";
            //定义数据缓存
            $tempData = [];
            //遍历文件信息
            foreach ($addonDirs as $addonDir) {
                $pathinfo = pathinfo($addonDir);
                $tempData[] = [
                    'preview' => $path . $pathinfo['basename'],
                    'theme' => $pathinfo['basename'],
                    'type' => $pathinfo['extension'],
                    'time' => date("Y-h-d H:i:s", filectime($addonDir))
                ];
            }
            $res['data'] = $tempData ? $tempData : [];
            return tJson('', '', $res);
        }
        $this->assign('name', $name);
        //执行页面渲染
        return $this->fetch();
    }
    /**
     * 渲染页面
     */
    public function index()
    {
        return $this->fetch();
    }
    /**
     * 卸载插件
     */
    public function uninstall()
    {
        //获取需要卸载的插件名称参数
        (string) $name = $this->request->post("name");
        //获取需要卸载的插件名称参数
        (string) $title = $this->request->post("title");
        //获取是否需要强制卸载参数
        (int) $force = $this->request->post("force");
        //判断是否带有参数
        if (!$name) {
            $this->error('抱歉，缺少插件名称参数');
        }
        try {
            //执行卸载
            Service::uninstall($name, $force);
            return tjson('成功卸载插件：' . $title);
        } catch (AddonException $e) {
            $this->result($e->getData(), $e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 配置
     */
    public function config()
    {
        //获取参数
        (string) $name = $this->request->param("name");
        if (!$name) {
            return tjson('缺少主要参数');
        }
        //判断是否存在插件
        if (!is_dir(Env::get('ADDONS_PATH') . $name)) {
            return tjson('插件目录不存在');
        }
        //获取插件信息
        $info = get_addons_info($name);
        //判断是否成功获取
        if (!$info) {
            return tjson('获取插件信息失败');
        }
        //获取插件配置参数配置信息
        $config = get_addons_config($name);
        //判断是否是post请求,是执行请求回调
        if ($this->request->isAjax()) {
            $params = $this->request->post("twelvet");
            //判断是否获取参数成功
            if ($params) {
                //遍历配置信息
                foreach ($config as $k => &$v) {
                    //判断是否存在此参数,理论上不允许私自增加额外参数
                    if (isset($params[$v['name']])) {
                        //判断是否为数组类型
                        if ($v['type'] == 'array') {
                            //转换json数组true，储存数组
                            $value = (array) json_decode($params[$v['name']], true);
                        } else {
                            //不是数组类型而又是数组的使用,分割
                            $value = is_array($params[$v['name']]) ? implode(',', $params[$v['name']]) : $params[$v['name']];
                        }
                        //赋值最终参数
                        $v['value'] = $value;
                    }
                }
                try {
                    //更新配置文件
                    set_addons_fullconfig($name, $config);
                    return tjson('', true);
                } catch (Exception $e) {
                    return tjson($e->getMessage());
                }
            }
            return tjson('参数名称不能为空');
        }
        //定义数组
        $tips = [];
        //遍历插件配置信息是否存在提示信息
        foreach ($config as $key => &$item) {
            if ($item['name'] == '__tips__') {
                //保存提示信息
                $tips = $item;
                unset($config[$key]);
            }
        }
        //复制配置信息
        $this->view->assign("addon", ['info' => $info, 'config' => $config, 'tips' => $tips, 'theme' => $name]);
        //定义插件自定义配置信息模板路径
        $configView = Env::get('ADDONS_PATH') . $name . DIRECTORY_SEPARATOR . 'config.html';
        //判断是否为文件是返回路径否则空
        $viewPath = is_file($configView) ? $configView : '';
        //渲染模板路径
        return $this->view->fetch($viewPath);
    }
    /**
     * 系统插件启禁接口
     */
    public function state()
    {
        //获取参数
        (string) $name = $this->request->post("name");
        (string) $action = $this->request->post("action");
        (string) $force = (int) $this->request->post("force");
        //判断是否存在参数
        if (!$name) {
            $this->error('缺少插件名称参数');
        }
        //开始尝试执行
        try {
            //判断需要执行的行为
            if ($action == 'enable') {
                $res = '插件已启用';
            } else {
                $action = 'disable';
                $res = '插件已禁用';
            }
            //调用启用、禁用的方法
            Service::$action($name, $force);
            return tjson($res, true);
        } catch (AddonException $e) {
            $this->result($e->getData(), $e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }
    /**
     * 本地安装方法
     */
    public function localInstall()
    {
        //获取离线文件
        $file = $this->request->file('package');
        //定义全部插件临时缓存缓存目录
        $addonsTempDir = Env::get('runtime_path') . 'addons' . DIRECTORY_SEPARATOR;
        //判断是否需要创建目录
        if (!is_dir($addonsTempDir)) {
            @mkdir($addonsTempDir, 0755, true);
        }
        //开始上传并移动文件
        $info = $file->rule('uniqid')->validate(['size' => 10240000, 'ext' => 'zip'])->move($addonsTempDir);
        //判断是否上传成功
        if ($info) {
            //获取上传文件名称
            $tmpName = substr($info->getFilename(), 0, stripos($info->getFilename(), '.'));
            //定义缓存插件存在的目录位置
            $tmpAddonDir = Env::get('ADDONS_PATH') . $tmpName . DIRECTORY_SEPARATOR;
            //定义缓存插件全目录
            $tmpFileDir = $addonsTempDir . $info->getSaveName();
            try {
                //解压文件
                if ($info->getExtension() != 'zip') throw new Exception('对不起仅支持zip格式的插件包');
                Service::unPackage($tmpName);
                //释放资源占用,不释放无法删除
                unset($info);
                //删除源文件
                @unlink($tmpFileDir);
                //定义插件配置文件
                $infoFile = $tmpAddonDir . 'info.ini';
                //判断是否存在配置信息
                if (!is_file($infoFile)) throw new Exception('插件配置文件未找到!');
                //解析文件内容
                $config = Config::parse($infoFile, '', $tmpName);
                //判断是否存在重要配置信息
                $name = isset($config['name']) && isset($config['title']) ? $config['name'] : '';
                //抛出异常
                if (!$name) throw new Exception('插件配置信息不完整，请检查!');
                //定义新的插件目录名称
                $newAddonDir = Env::get('ADDONS_PATH') . $name . DIRECTORY_SEPARATOR;
                //判断是否存在相同的插件
                if (is_dir($newAddonDir)) throw new Exception('已存在相同插件,请检查您的插件!');
                //重命名插件文件夹
                rename($tmpAddonDir, $newAddonDir);
                try {
                    //执行插件启用
                    Service::enable($name);
                    //获取插件命名空间
                    $class = get_addons_class($name);
                    //判断是否存在类
                    if (class_exists($class)) {
                        //执行插件中的安装方法
                        $install = new $class();
                        $install->install();
                    }
                    //导入安装包的SQL
                    Service::importsql($config);
                    //返回安装成功信息
                    return tjson('成功安装插件：' . $config['title'], true);
                } catch (Exception $e) {
                    @rmdirs($newAddonDir);
                    throw new Exception($e->getMessage());
                }
            } catch (Exception $e) {
                //释放资源占用,不释放无法删除
                unset($info);
                //清除插件所有缓存
                @unlink($tmpFileDir);
                @rmdirs($tmpAddonDir);
                return tJson($e->getMessage());
            }
        } else {
            //返回错误信息
            return tJson($file->getError());
        }
    }
    /**
     * 系统已安装的插件
     */
    public function downloaded()
    {
        //获取分页页码
        $page = (int) $this->request->get("page");
        //获取分页每页的数量
        $limit = (int) $this->request->get("limit");
        $filter = $this->request->get("filter");
        //获取搜索参数,去除html代码
        $search = htmlspecialchars(strip_tags($this->request->get("search")));
        //读取缓存
        $cloudAddons = Cache::get("cloudAddons");
        //判断是否存在数据
        if (!is_array($cloudAddons)) {
            //读取api信息
            $result = Http::post(config('twelvet.api_url') . 'addon/index');
            //判断是否获取成功
            if ($result['status']) {
                //解码json数据,数组形式
                $json = (array) json_decode($result['msg'], true);
                //判断是否设置数据
                $data = isset($json['data']) ? $json['data'] : [];
                //定数组对象
                $cloudAddons = [];
                //遍历取出数据
                foreach ($data as $index => $row) {
                    $cloudAddons[$row['name']] = $row;
                }
                //设置缓存,30分钟失效
                Cache::set("cloudAddons", $cloudAddons, 1800);
            }
        }
        //获取插件基本信息 vendor\zzstudio\think-addons
        $addons = get_addons_list();
        //定义数组
        $list = [];
        foreach ($addons as $key => $value) {
            //检索是否符合搜索参数,仅允许对标题与简介的搜索
            if ($search && stripos($key, $search) === false && stripos($value['intro'], $search) === false) {
                continue;
            }
            //是否需要分栏目查询
            if ($filter && isset($filter['category_id']) && is_numeric($filter['category_id']) && $filter['category_id'] != $value['category_id']) {
                continue;
            }
            //判断是否设置了数组
            if (isset($cloudAddons[$key])) {
                //合并对应数组信息
                $value = array_merge($value, $cloudAddons[$key]);
            } else {
                $value['category_id'] = 0;
                $value['flag'] = '';
                $value['banner'] = '';
                $value['image'] = '';
                $value['donateimage'] = '';
                $value['demourl'] = '';
                $value['price'] = '未知价格';
                $value['screenshots'] = [];
                $value['releaselist'] = [];
            }
            //获取文件创建时间
            $value['createtime'] = filemtime(Env::get('ADDONS_PATH') . $key);
            //将最终结果添加进数组
            $list[] = $value;
        }
        //分页输出
        if ($limit) {
            //计算分页开始数值
            $page = ($page - 1) * $limit;
            //利用分页函数进行分页数组
            $list = array_slice($list, $page, $limit);
        }
        //统计最终数量
        $total = count($list);
        //组成最终信息
        $result = ['state' => 1, 'code' => 0, 'count' => $total, 'data' => $list];
        return tjson('', '', $result);
    }
}

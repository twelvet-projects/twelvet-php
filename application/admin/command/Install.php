<?php

namespace app\admin\command;

use PDO;
use think\facade\Config;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Env;
use think\Db;
use think\Exception;

/**
 * 系统安装命令（因安装参数过多，不推荐此种方式）
 */
class Install extends Command
{
    protected $model = null;

    protected function configure()
    {
        $config = Config::get('database.');
        // 设置命令名称并添加参数
        $this->setName('install')
            ->addOption('hostname', 'a', Option::VALUE_OPTIONAL, 'mysql hostname', $config['hostname'])
            ->addOption('hostport', 'o', Option::VALUE_OPTIONAL, 'mysql hostport', $config['hostport'])
            ->addOption('database', 'd', Option::VALUE_OPTIONAL, 'mysql database', $config['database'])
            ->addOption('prefix', 'r', Option::VALUE_OPTIONAL, 'table prefix', $config['prefix'])
            ->addOption('username', 'u', Option::VALUE_OPTIONAL, 'mysql username', $config['username'])
            ->addOption('password', 'p', Option::VALUE_OPTIONAL, 'mysql password', $config['password'])
            ->addOption('force', 'f', Option::VALUE_OPTIONAL, 'force override', false)
            ->setDescription('New installation of TwelveT');
    }

    protected function execute(Input $input, Output $output)
    {
        // 定义public目录
        $pub = Env::get('ROOT_PATH') . 'public/';
        // 覆盖安装
        $force = $input->getOption('force');
        $hostname = $input->getOption('hostname');
        $hostport = $input->getOption('hostport');
        $database = $input->getOption('database');
        $prefix   = $input->getOption('prefix');
        $username = $input->getOption('username');
        $password = $input->getOption('password');

        $installLockFile = $pub . "/install/install.lock";
        // 判断是否已安装
        if (is_file($installLockFile) && !$force) {
            throw new Exception("\nTwelveT already installed!\nIf you need to reinstall again, use the parameter --force=true ");
        }
        // 打开SQL文件
        $sql = file_get_contents($pub . '/install/twelvet.sql');

        $sql = str_replace("`tl_", "`{$prefix}", $sql);
        
        // 先尝试能否自动创建数据库
        $config = Config::get('database.');
        
        // 连接并尝试创建对应数据库
        $pdo = new \PDO("{$config['type']}:host={$hostname}" . ($hostport ? ";port={$hostport}" : ''), $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8 COLLATE utf8_general_ci;");
        $pdo->query("USE `{$database}`");

        // 调用原生PDO对象进行批量查询
        $pdo->exec($sql);
        // 写入安装锁
        file_put_contents($installLockFile, '此文件存在意义：防止恶意重复安装');

        // 后台入口文件
        $adminFile = Env::get('ROOT_PATH') . 'public/admin.php';
        
        // 数据库文件
        $dbConfigFile = Env::get('ROOT_PATH') . '/config/database.php';
        $config = @file_get_contents($dbConfigFile);
        $callback = function ($matches) use ($hostname, $hostport, $username, $password, $database, $prefix) {
            $field = $matches[1];
            $replace = $$field;
            if ($matches[1] == 'hostport' && $hostport == 3306) {
                $replace = '';
            }
            return "'{$matches[1]}'{$matches[2]}=>{$matches[3]}Env::get('database.{$matches[1]}', '{$replace}'),";
        };
        $config = preg_replace_callback("/'(hostname|database|username|password|hostport|prefix)'(\s+)=>(\s+)Env::get\((.*)\)\,/", $callback, $config);
        // 写入数据库配置
        file_put_contents($dbConfigFile, $config);

        // 修改后台入口
        if (is_file($adminFile)) {
            $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $adminName = substr(str_shuffle(str_repeat($x, ceil(10 / strlen($x)))), 1, 10) . '.php';
            rename($adminFile, Env::get('ROOT_PATH') . 'public/' . $adminName);
            $output->highlight("Admin url:{$adminName}");
        }
        $output->highlight("Admin username:admin");
        $output->highlight("Admin password:123456");

        $output->info("Install Successed!");
    }
}

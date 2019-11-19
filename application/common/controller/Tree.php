<?php

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 12tla.com，并保留所有权利。
 * 官网地址:www.12tla.com
 * QQ:2471835953
 * ============================================================================
 * 公用树形类
 */

namespace app\common\controller;

use think\facade\config;

class Tree
{
    protected static $instance;

    //默认配置
    protected $config = [];
    public $options = [];
    /**
     * 生成树型结构所需要的二维数组
     *
     * @var array
     */
    public $arr = [];

    /**
     * 生成树型结构所需修饰符号，可以换成图片
     * @var array
     */
    public $icon = ['│', '├', '└'];
    public $nbsp = "&nbsp;";
    public $pidname = 'pid';

    /**
     * 合并初始化参数
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        // 判断是否存在额外参数需要合并
        if ($config = Config::get('twelvet.tree')) {
            $this->options = array_merge($this->config, $config);
        }
        $this->options = array_merge($this->config, $options);
    }

    /**
     * 初始化对象
     *
     * @param array $options
     * @return object
     */
    public static function instance($options = [])
    {
        // 判断是否存在对象
        if (is_null(self::$instance)) {
            // 静态化参数
            self::$instance = new static($options);
        }
        // 返回对象
        return self::$instance;
    }

    /**
     * 初始化方法
     * @param array  $arr     
     *      二维数组，例如：
     *      array(
     *      1 => array('id'=>'1','pid'=>0,'name'=>'一级栏目一'),
     *      2 => array('id'=>'2','pid'=>0,'name'=>'一级栏目二'),
     *      3 => array('id'=>'3','pid'=>1,'name'=>'二级栏目一'),
     *      4 => array('id'=>'4','pid'=>1,'name'=>'二级栏目二'),
     *      5 => array('id'=>'5','pid'=>2,'name'=>'二级栏目三'),
     *      6 => array('id'=>'6','pid'=>3,'name'=>'三级栏目一'),
     *      7 => array('id'=>'7','pid'=>3,'name'=>'三级栏目二')
     *      )
     * @param string $pidname 父字段名称
     * @param string $nbsp    空格占位符
     * @return Tree
     */
    public function init(array $arr = [], String $pidname = null, String $nbsp = null)
    {
        // 设置数组
        $this->arr = $arr;
        // 判断是否存在参数，设置它
        if (!is_null($pidname)) {
            $this->pidname = $pidname;
        }
        if (!is_null($nbsp)) {
            $this->nbsp = $nbsp;
        }
        // 返回当前对象
        return $this;
    }

    /**
     * 获取子数组
     *
     * @param integer $pid
     * @return array
     */
    public function getChild(int $pid)
    {
        // 预定义
        $newarr = [];
        foreach ($this->arr as $value) {
            // 无id立即跳出此循环
            if (!isset($value['id']))  continue;
            // 判断数组父id是否与当前查询id相同
            if ($value[$this->pidname] == $pid) {
                // 储存id与值
                $newarr[$value['id']] = $value;
            }
        }
        // 返回数组
        return $newarr;
    }

    /**
     * 得到当前位置所有父辈数组
     * @param int
     * @param bool $withself 是否包含自己
     * @return array
     */
    public function getParents($myid, $withself = false)
    {
        $pid = 0;
        $newarr = [];
        foreach ($this->arr as $value) {
            if (!isset($value['id'])) {
                continue;
            }
            if ($value['id'] == $myid) {
                if ($withself) {
                    $newarr[] = $value;
                }
                $pid = $value[$this->pidname];
                break;
            }
        }
        if ($pid) {
            $arr = $this->getParents($pid, true);
            $newarr = array_merge($arr, $newarr);
        }
        return $newarr;
    }

    /**
     * 菜单数据
     * @param int    $pid 获得这个ID下的所有子级
     * @param string $itemtpl 条目模板 如："<li value=@id @selected @disabled>@name @childlist</li>"
     * @param mixed  $selectedids 选中的ID
     * @param mixed  $disabledids 禁用的ID
     * @param string $wraptag 子列表包裹标签
     * @param string $wrapattr 子列表包裹属性
     * @param int    $deeplevel 第几级分组
     * @return string
     */
    public function getTreeMenu(int $pid, String $itemtpl, $selectedids = '', $disabledids = '', String $wraptag = 'ul', String $wrapattr = '', int $deeplevel = 0)
    {
        // 预定义字符串
        $str = '';
        // 获取子数组
        $childs = $this->getChild($pid);
        // 判断是否存在子数组
        if ($childs) {
            foreach ($childs as $value) {
                // 缓存id参数
                $id = $value['id'];
                // 释放参数
                unset($value['child']);
                // 判断是否存在数组中（不为数组将其变为数组）
                $selected = in_array($id, (is_array($selectedids) ? $selectedids : explode(',', $selectedids))) ? 'selected' : '';
                $disabled = in_array($id, (is_array($disabledids) ? $disabledids : explode(',', $disabledids))) ? 'disabled' : '';
                // 合并参数（key => value）
                $value = array_merge($value, ['selected' => $selected, 'disabled' => $disabled]);
                // 合并参数（@key => value）
                $value = array_combine(
                    // 将函数运用在数组当中
                    array_map(
                        function ($k) {
                            return '@' . $k;
                        },
                        // 返回新的数组（数组的键名）
                        array_keys($value)
                    ),
                    $value
                );
                // 返回交集（array, 反转数组key\value）根据键名
                $bakvalue = array_intersect_key($value, array_flip(['@url', '@caret', '@class']));
                // 返回数组差集（根据键名）
                $value = array_diff_key($value, $bakvalue);
                // 替换模板中的数据（temp, array）
                $nstr = strtr($itemtpl, $value);
                // 再次合并数组
                $value = array_merge($value, $bakvalue);
                // 无极限分类寻找子数组
                $childdata = $this->getTreeMenu($id, $itemtpl, $selectedids, $disabledids, $wraptag, $wrapattr, $deeplevel + 1);
                // 判断是否存在子数组，编写html标签(<ul> <li></li> </ul>)
                $childlist = $childdata ? "<{$wraptag} {$wrapattr}>" . $childdata . "</{$wraptag}>" : "";
                // 替换生成html标签的class属性
                $childlist = strtr($childlist, ['@class' => $childdata ? 'last' : '']);
                // 设置替换属性
                $value = [
                    // 子分组
                    '@childlist' => $childlist,
                    // 连接地址
                    '@url'       => $childdata || !isset($value['@url']) ? "javascript:;" : url($value['@url']),
                    // 把地址标记为iframe框架跳转
                    '@addtabs'   => $childdata || !isset($value['@url']) ? "" : (stripos($value['@url'], "?") !== false ? "&" : "?") . "ref=addtabs",
                    // 插入左侧符号
                    '@caret'     => ($childdata && (!isset($value['@badge']) || !$value['@badge']) ? '<span class="fa fa-angle-left"></span>' : ''),
                    // 插入徽章
                    '@badge'     => isset($value['@badge']) ? $value['@badge'] : '',
                    // 插入class
                    '@class'     => ($selected ? ' active' : '') . ($disabled ? ' disabled' : '') . ($childdata ? ' treeview' : ''),
                ];
                // 替换并储存
                $str .= strtr($nstr, $value);
            }
        }
        return $str;
    }
}

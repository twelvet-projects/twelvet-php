<?php

namespace app\admin\model;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 twelvet.cn，并保留所有权利。
 * 官网地址:www.twelvet.cn
 * QQ:2471835953
 * ============================================================================
 * 后台栏目模型
 */
class Menutree extends TwelveT
{
	protected $pk = 'id';
	//取得后台导航
	public function getMenu()
	{
		return $this->all();
	}
}

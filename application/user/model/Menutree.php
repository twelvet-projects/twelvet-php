<?php

namespace app\user\model;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 12tla.com，并保留所有权利。
 * 官网地址:www.12tla.com
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

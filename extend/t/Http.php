<?php

namespace t;

/**
 * ============================================================================
 * TwelveT
 * 版权所有 2018-2019 12tla.com，并保留所有权利。
 * 官网地址:www.12tla.com
 * QQ:2471835953
 * ============================================================================
 * Http文件请求类
 */
class Http
{
	//预定义返回结果
	private static $result = ['state' => false, 'msg' => ''];

	/**
	 * 获取远程文件唯一入口
	 * @param  [type] $url      [需要处理的地址]
	 * @param  [type] $method   [get and post 默认使用post]
	 * @param  [type] $mode     [使用模式 默认curl]
	 * @param  [type] $charset  [设置编码 默认UTF-8]
	 * @param  [type] $dataArr  [发送参数]
	 * @return [type]           [处理网页抓取结果]
	 */
	public static function sendRequest($url, $method = 'post', $mode = 'curl', $charset = 'UTF-8', $dataArr = [])
	{
		switch ($mode) {
			case 'curl':
				self::$result = self::useCurl($url, $method, $charset, $dataArr);
				break;

			case 'fsocKopen':
				self::$result = self::useFsockopen($url, $method, $charset, $dataArr);
				break;

			case 'fopen':
				self::$result = self::useFopen($url, $method, $charset, $dataArr);
				break;

			default:
				//默认使用curl模式
				if (extension_loaded('curl')) {
					self::$result = self::UseCurl($url, $method, $charset, $dataArr);
				}
				//通过msg状态进行判断是否换模式
				if (self::$result['msg'] == '' && function_exists('fsockopen')) {
					self::$result = self::UseFsockopen($url, $method, $charset, $dataArr);
				}
				if (self::$result['msg'] == '' && (ini_get('allow_url_fopen') == 1 || strtolower(ini_get('allow_url_fopen')) == 'on')) {
					self::$result = self::UseFopen($url, $method, $charset, $dataArr);
				}
				break;
		}
		//根据状态返回所需信息
		if ($mode == 'status') {
			return self::$result['state'];
		} elseif ($mode == 'msg') {
			return self::$result['msg'];
		} else {
			return self::$result;
		}
	}

	/**
	 * 使用curl模式
	 * @param  [type] $method   [get and post]
	 * @param  [type] $url      [需要处理的地址]
	 * @param  [type] $charset  [设置编码]
	 * @param  [type] $dataArr  [发送参数]
	 * @return [type]           [处理网页抓取结果]
	 */
	private static function useCurl($url, $method, $charset = 'UTF-8', $dataArr = [])
	{
		if (empty($url)) return ['state' => false, 'msg' => 'Curl：网址为空'];

		$cloudServer = curl_init();
		//设置头部模拟
		curl_setopt($cloudServer, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:66.0) Gecko/20100101 Firefox/66.0');
		//处理地址
		curl_setopt($cloudServer, CURLOPT_URL, $url);  
		curl_setopt($cloudServer, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($cloudServer, CURLOPT_CONNECTTIMEOUT, 45);	// 响应时间
		curl_setopt($cloudServer, CURLOPT_TIMEOUT, 120);			// 设置超时
		// 使用的HTTP协议，CURL_HTTP_VERSION_NONE（让curl自己判断），CURL_HTTP_VERSION_1_0（HTTP/1.0），CURL_HTTP_VERSION_1_1（HTTP/1.1）
		curl_setopt($cloudServer, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
		//检查是否为https连接
		if (substr(strtolower($url), 0, 8) == 'https://') {
			curl_setopt($cloudServer, CURLOPT_SSL_VERIFYPEER, false);	// 跳过证书检查  
			curl_setopt($cloudServer, CURLOPT_SSL_VERIFYHOST, 2);		// 从证书中检查SSL加密算法是否存在
		}
		//请求方式判断
		if (strtoupper($method) == 'POST') {
			if (is_array($dataArr)) {
				$newData = http_build_query($dataArr);
			} else {
				$newData = $dataArr;
			}
			//设置post请求
			curl_setopt($cloudServer, CURLOPT_POST, 1);
			curl_setopt($cloudServer, CURLOPT_POSTFIELDS, $newData);
		}
		//开始接受数据
		$data = curl_exec($cloudServer);
		return ['state' => true, 'msg' => $data];
		// 检查是否有错误发生
		if (curl_errno($cloudServer)) {
			return ['state' => false, 'msg' => 'Error（' . curl_error($cloudServer) . '）'];
		}

		// 获取HTML返回状态
		$httpCode = curl_getinfo($cloudServer, CURLINFO_HTTP_CODE);
		//关闭连接
		curl_close($cloudServer);
		//检查状态码是否正常
		if ($httpCode != 200) {
			return ['state' => false, 'msg' => 'UseCurl：返回状态' . $httpCode];
		}
		//判断获取的内容是否为空
		if (strlen($data) == 0) {
			return ['state' => false, 'msg' => 'UseCurl：获取内容为空'];
		}
		//返回正常数据
		return ['state' => true, 'msg' => $data];
	}

	//获取页面源代码2 fsockopen模式
	private static function UseFsockopen($url, $method, $charset = 'UTF-8', $dataArr = [])
	{
		if (empty($url)) return ['state' => false, 'msg' => 'UseFsockopen：网址为空'];

		$urlMsg = parse_url($url); //解释url参数
		$host = $urlMsg['host'];
		$urlPath = $urlMsg['path'];
		$port = 80; //端口
		$errno = '';
		$errstr = '';
		$timeout = 30;
		if (strtolower(substr($url, 0, 8)) == 'https://') {
			$hStart = 'ssl://';
			$port = 443;
		} else {
			$hStart = '';
		} // tcp://

		if (!empty($urlMsg['query'])) {
			$urlPath .= '?' . $urlMsg['query'];
		}

		if ($method == 'POST') {

			if (is_array($dataArr)) {
				$newData = http_build_query($dataArr);	// 相反函数 parse_str()
			} else {
				$newData = $dataArr;
			}

			// 创建连接
			$fp = fsockopen($hStart . $host, $port, $errno, $errstr, $timeout);

			if (!$fp) {
				return array('status' => false, 'note' => 'UseFsockopen：POST发生错误');
			}

			// 发送请求
			$out = 'POST ' . $urlPath . ' HTTP/1.1\r\n';
			$out .= 'Host:' . $host . "\r\n";
			$out .= "Content-type:application/x-www-form-urlencoded\r\n";
			$out .= "Content-length:" . strlen($newData) . "\r\n";
			$out .= "Connection:close\r\n\r\n";
			$out .= $newData;
		} else {
			// 创建连接
			$fp = fsockopen($hStart . $host, $port, $errno, $errstr, $timeout);
			if (!$fp) {
				return ['state' => false, 'note' => 'UseFsockopen：GET发生错误'];
			}
			// 发送请求
			$out = "GET " . $urlPath . " HTTP/1.1\r\n";
			$out .= "Host: " . $host . "\r\n";
			$out .= "Connection:close\r\n\r\n";
		}

		fputs($fp, $out);

		$data = '';
		while ($row = fread($fp, 4096)) {
			$data .= $row;
		}

		fclose($fp);

		$pos = strpos($data, "\r\n\r\n");
		$data = substr($data, $pos + 4);
		if (strlen($data) == 0) return ['state' => false, 'msg' => 'UseFsockopen：获取内容为空'];
		return ['state' => true, 'msg' => $data];
	}


	//使用fopen模式
	private static function UseFopen($url, $method, $charset = 'UTF-8', $dataArr = [])
	{
		if (empty($url)) return ['state' => false, 'msg' => 'UseFopen：网址为空'];
		//设置php.ini文件配置请求信息，程序关闭立即失效
		ini_set('user_agent', 'Mozilla/5.0 (Windows NT 6.1; rv:66.0) Gecko/20100101 Firefox/66.0');
		//使用get或post方式
		if (strtoupper($method) == 'POST') {
			$context = array(
				'http' => array(
					'method'	=> (strtoupper($method) == 'POST' ? 'POST' : 'GET'),
					'header'	=> 'User-Agent:Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727;)' . PHP_EOL . 'Content-type: application/x-www-form-urlencoded' . PHP_EOL . 'Content-Length: ' . strlen($newData) . PHP_EOL,
					'content'	=> $newData,
					'timeout'	=> 60
				)
			);
			$stream_context = stream_context_create($context);
			$data = file_get_contents($url, false, $stream_context);
		} else {
			$data = file_get_contents($url);
		}
		//判断是否有数据
		if (strlen($data) == 0) return ['state' => false, 'msg' => 'UseFopen：获取内容为空'];

		return ['state' => true, 'msg' => $data];
	}
}

<?php
namespace twelvetED;
/**
 * ============================================================================
 * TwelveT CMS
 * 版权所有 2018-2019 12tla.com，并保留所有权利。
 * 官网地址:www.12tla.com
 * QQ:2471835953
 * ============================================================================
 * 远程文件保存公共类
 */
use twelvetED\ClassUrl as Url;
class ClassSaveFile{
    /**
	 * @param  [type] $localFile   [本地文件名]
     * @param  [type] $remoteFile      [远程文件URL]
     * @param  [type] $imgMaxSize [下载文件最大大小(KB)默认10M]
	 */
    public function SaveRemoteFile($localFile, $remoteFile, $imgMaxSize=10240){
        $msg = ['status'=>false, 'size'=>0, 'message'=>''];
        $getFile = $this->GetUrlContent($remoteFile);
        if(!empty($getFile) ){
             //获取文件长度
             $msgSize = strlen($getFile);
             $msg['size'] = $msgSize;
            if ($imgMaxSize > 0 && $msgSize > $imgMaxSize*1024){
                $msg['message'] = '图片/文件大小超出设定的最大值('. $msgSize .'|'. $imgMaxSize*1024 .')';
                return $msg;
            }elseif ($msgSize == 5 && $getFile == false){
                $msg['message']='图片/文件内容为false';
                return $msg;
            }
            //打开文件流
            $fp = fopen($localFile,'w');
            //开始写入并判断
            if(! fwrite($fp,$getFile)){
                $msg['message'] = '无法保存到本地';
                return $msg;
            }
            //关闭操作
            fclose($fp);
            //写入成功信息
            $msg['status'] = true;
		    $msg['message'] = '保存成功';
        }else{
            $msg['message']='获取不到图片/文件';
			return $msg;
        }
        return $msg;
    }
    /**
     * 调用TwewlvT扩展类执行文件信息获取
     * @param  [type] $url      [远程文件地址]
     * @param  [type] $timeout  [超时时间]
     */
    public function GetUrlContent($url){
        //获取内容
        $Url = new Url();
        //自动判断访问模式，get，地址
        $msg = $Url->urlAuto('auto', 'get', $url);
        //获取失败直接false
        if (!$msg['status']) return false;
        return $msg['message'];
	}
}
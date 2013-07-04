<?php
/**
*  微信 公众平台消息接口改进型 SDK DEMO
*  @author zuohome admin@zuohome.com/QQ:305612992
*  修改自  xhxu xh_xu@qq.com/QQ:7844577 1.0.20130103 版本
*  @version 2.0.20130305
*/
class Weixin
{
  public $token = '';
	public $debug =  false;
	public $pictures =  false;
	public $flag = false;
	public $msgtype = 'text';	//('text','image','location','voice')
	Public $msg = array();
	private $logPath = '';

	public function __construct($token,$debug,$log='./',$pictures)
	{
		$this->token = $token;
		$this->debug = $debug;
		$this->logPath = $log;
		$this->pictures = $pictures;
	}
	public function getMsg()
	{
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
		if ($this->debug) {
			if(!file_exists($this->logPath)){mkdir($this->logPath,0777);}
			file_put_contents($this->logPath.'log.txt', date('c')."\r\n".$postStr."\r\n",FILE_APPEND);
		}
		if (!empty($postStr)) {
			$this->msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
			$this->msgtype = strtolower($this->msg['MsgType']);
		}
		if ($this->msg['MsgType']==='image') {
			if ($this->pictures) {
				$this->msg['PicUrl']=self::getImg($this->msg['PicUrl']);
				}
		}
	}
	function getImg($url)
	{
	$header = file_get_contents ( $url , 0 , NULL , 0 , 5 );
	//return $header;
     if ( $header { 0 }. $header { 1 }== "\x89\x50" )
     {
         $ext = '.png' ;
		 $imgtype = 'png' ;
     }
     else if( $header { 0 }. $header { 1 } == "\xff\xd8" )
     {
         $ext = '.jpg' ;
		 $imgtype = 'jpeg' ;
     }
     else if( $header { 0 }. $header { 1 }. $header { 2 } == "\x47\x49\x46" )
     {
        
         if( $header { 4 } == "\x37" ){
             $ext = '.gif' ;
			 $imgtype = 'gif87';
		 }else if( $header { 4 } == "\x39" ){
             $ext = '.gif' ;
			 $imgtype = 'gif89';
		 }
     }else{
		return $url;//不是图片不处理
     }
	$category = 'user/';
	if(!file_exists($category)){mkdir($category,0777);}
	$category .= date("Ym");
	if(!file_exists($category)){mkdir($category,0777);}
	$filename = $category.'/'.date("dgis").$ext;
	$hander = curl_init();
	$fp = fopen($filename,'wb');
	curl_setopt($hander,CURLOPT_URL,$url);
	curl_setopt($hander,CURLOPT_FILE,$fp);
	curl_setopt($hander,CURLOPT_HEADER,0);
	curl_setopt($hander,CURLOPT_FOLLOWLOCATION,1);
	//curl_setopt($hander,CURLOPT_RETURNTRANSFER,false);//以数据流的方式返回数据,当为false是直接显示出来
	curl_setopt($hander,CURLOPT_TIMEOUT,60);
	curl_exec($hander);
	curl_close($hander);
	fclose($fp);
	$domain = 'http://'.$_SERVER['HTTP_HOST'];
	$filenames = (string)end(explode('/',$_SERVER['SCRIPT_NAME']));
	$strURL = $domain . str_replace($filenames,'',$_SERVER['SCRIPT_NAME']);
	return $strURL.$filename;
	}

	public function makeEter($paraEeter=array())
	{
		$CreateTime = time();
	if ($paraEeter['type']){
		$type = $paraEeter['type'];
		}else {
			$type = 'text';
	}
		$flag = $paraEeter['flag'] ? 1 : 0;
	if (is_array($paraEeter)) {
		if ($type==='text') {
		$Content = "<Content><![CDATA[{$paraEeter['Content']}]]></Content>";
	}else if ($type==='music') {
		$Content = "<Music>
			<Title><![CDATA[{$paraEeter['title']}]]></Title>
			<Description><![CDATA[{$paraEeter['description']}]]></Description>
			<MusicUrl><![CDATA[{$paraEeter['musicurl']}]]></MusicUrl>
			<HQMusicUrl><![CDATA[{$paraEeter['hqmusicurl']}]]></HQMusicUrl>
			</Music>";
	}else if ($type==='news') {
		$itemsCount = count($paraEeter['items']);
		$itemsCount = $itemsCount < 10 ? $itemsCount : 10;
		$newTplHeader = "<ArticleCount>{$itemsCount}</ArticleCount>
			<Articles>";
		$newTplItem = "<item>
			<Title><![CDATA[%s]]></Title>
			<Description><![CDATA[%s]]></Description>
			<PicUrl><![CDATA[%s]]></PicUrl>
			<Url><![CDATA[%s]]></Url>
			</item>";
		$newTplFoot = "</Articles>";
		$Content = '';
		if ($itemsCount) {
			foreach ($paraEeter['items'] as $key => $item) {
				if ($key<=9) {
					$Content .= sprintf($newTplItem,$item['title'],$item['description'],$item['picurl'],$item['url']);
				}
			}
		}
		$Content = $newTplHeader . $Content . $newTplFoot;
		}
	}else {
		$type = 'text';
		$Content = "<Content><![CDATA[$paraEeter]]></Content>";
		$flag = '0';
	}
		$header = "<xml>
			<ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
			<FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
			<CreateTime>{$CreateTime}</CreateTime>
			<MsgType><![CDATA[{$type}]]></MsgType>
			";
		$footer = "
			<FuncFlag>{$flag}</FuncFlag>
			</xml>";
		return $header . $Content . $footer;
	}

	public function reply($data)
	{
		if ($this->debug) {
			file_put_contents($this->logPath.'reply.txt',date('c')."\r\n". $data."\r\n",FILE_APPEND);
		}
		echo $data;
	}
	public function valid()
	{
		if ($this->checkSignature()) {
			if( $_SERVER['REQUEST_METHOD']=='GET' )
			{
				echo $_GET['echostr'];
				exit;
			}
		}else{
			echo '非法访问！';
			exit;
		}
	}
	private function checkSignature()
	{
		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$tmpArr = array($this->token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

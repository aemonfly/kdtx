<?php 
$mysql = new SaeMysql();
$mail = new SaeMail();

$data = $mysql->getData("SELECT * FROM task");

for($i=0;$i<count($data);$i++){
    
    $kuaid_num = $data[$i]["num"];
    $kuaidi_type = $data[$i]["kuaidi"];
    $kuaidi_cache = $data[$i]["last"];
  $user = $data[$i]["user"];

	$kuaidi_update = json_decode(file_get_contents('http://baidu.kuaidi100.com/query?type=' .$kuaidi_type. '&postid=' .$kuaid_num),1);

	if($kuaidi_update['status'] !== '200'){
        $ret = $mail->quickSend('1067099789qq.com','查询失败反馈','单号:'.$kuaid_num, 'kuaiditixing@163.com','kd285714285');
	}else{
        $last = $kuaidi_update['data']['0']['time'];
		if($kuaidi_cache !== $last){
   			$mysql->runSql("UPDATE task SET last = '$last' WHERE num='$kuaid_num'");
    
            $mail_data = $mysql->getData("SELECT mail FROM mail WHERE user='$user'");
            $mail_dress = $mail_data['0']["mail"];
            
  			$mail->quickSend($mail_dress,'（' .$kuaidi_type. '）快递更新',$kuaidi_update['data']['0']['time'] ."   ". $kuaidi_update['data']['0']['context'] ."\n\n单号：$kuaid_num\n取消此单提醒发送：qx$kuaid_num 到公众账号\n取消全部邮件提醒发送：@ 到公众账号\n\n如果觉得好用，欢迎推荐给你的好友哦~", 'kuaiditixing@163.com','kd285714285');
        }else{
            if(strstr($kuaidi_update['data']['0']['context'],'签收') or strstr($kuaidi_update['data']['0']['context'],'派件')){
                $mysql->runSql("DELETE FROM task WHERE num = '$kuaid_num'");
            }
        }
	}
}

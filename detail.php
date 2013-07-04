<html>
<head>
<meta charset='utf-8'>
<title>快递详情</title>
</head>
<body>

<?php  
ini_set("display_errors","Off");

$type = $_GET["type"];
$postid = $_GET["postid"];

$kuaidi_update = json_decode(file_get_contents('http://baidu.kuaidi100.com/query?type=' .$type. '&postid=' .$postid),1);


echo '单号：' .$postid. '<br>物流状态：<br>';

for($i=0;$i<count($kuaidi_update['data']);$i++){
    echo $kuaidi_update['data'][$i]['time'] .' '. $kuaidi_update['data'][$i]['context'] . '<br>';
}
?>

</body>

</html>

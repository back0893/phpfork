<?php
/**
 * Created by PhpStorm.
 * User: liu
 * Date: 2018/11/22
 * Time: 1:40
 */
$fp=stream_socket_client("tcp://127.0.0.1:8000");
if($fp===false){
    exit('链接失败!');
}
fwrite($fp,'login!');
while(!feof($fp)){
    echo fread($fp,1024);
}
fclose($fp);
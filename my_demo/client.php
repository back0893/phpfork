<?php
/**
 * Created by PhpStorm.
 * User: ASUS
 * Date: 2018/11/28
 * Time: 15:07
 */

$fp=stream_socket_client("tcp://127.0.0.1:5000");
if($fp){
    fwrite($fp,"test......");
    $read=fread($fp,1024);
    echo $read;
    fclose($fp);
}else{
    exit("!!!连接错误!!!");
}